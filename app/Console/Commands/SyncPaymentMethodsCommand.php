<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use App\Services\SopayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaymentMethodsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-methods:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync payment methods from Sopay service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing payment methods from Sopay...');
        $this->newLine();

        try {
            $sopayService = new SopayService();
            
            // Sync fiat payments
            $this->info('📱 Syncing fiat payments...');
            $fiatStats = $this->syncFiatPayments($sopayService);
            
            $this->newLine();
            
            // Sync crypto coins
            $this->info('🪙 Syncing crypto coins...');
            $cryptoStats = $this->syncCryptoCoins($sopayService);

            $this->newLine();
            $this->info("✅ Sync completed!");
            $this->table(
                ['Type', 'Action', 'Count'],
                [
                    ['Fiat', 'Created', $fiatStats['created']],
                    ['Fiat', 'Updated', $fiatStats['updated']],
                    ['Fiat', 'Errors', $fiatStats['errors']],
                    ['Crypto', 'Created', $cryptoStats['created']],
                    ['Crypto', 'Updated', $cryptoStats['updated']],
                    ['Crypto', 'Errors', $cryptoStats['errors']],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync payment methods: ' . $e->getMessage());
            Log::error('Sync payment methods command error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Sync fiat payments
     */
    protected function syncFiatPayments(SopayService $sopayService): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;

        try {
            $response = $sopayService->getPayments();

            if ($response['code'] !== 0) {
                $this->error('❌ Failed to fetch fiat payments: ' . ($response['errmsg'] ?? 'Unknown error'));
                return compact('created', 'updated', 'errors');
            }

            $items = $response['data']['items'] ?? [];
            
            if (empty($items)) {
                $this->warn('⚠️  No fiat payment methods found');
                return compact('created', 'updated', 'errors');
            }

            // Iterate through currencies
            foreach ($items as $currency => $paymentMethods) {
                $this->info("  Processing currency: {$currency}");
                
                foreach ($paymentMethods as $paymentData) {
                    try {
                        $enableDeposit = $paymentData['enable_deposit'] ?? 0;
                        $enableWithdraw = $paymentData['enable_withdraw'] ?? 0;
                        
                        // Create or update deposit record if enabled
                        if ($enableDeposit) {
                            $this->syncFiatPaymentRecord(
                                $paymentData,
                                $currency,
                                'deposit',
                                $created,
                                $updated,
                                $errors
                            );
                        }
                        
                        // Create or update withdraw record if enabled
                        if ($enableWithdraw) {
                            $this->syncFiatPaymentRecord(
                                $paymentData,
                                $currency,
                                'withdraw',
                                $created,
                                $updated,
                                $errors
                            );
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("    ✗ Error processing {$paymentData['name']} ({$currency}): " . $e->getMessage());
                        Log::error('Sync fiat payment method error', [
                            'payment' => $paymentData,
                            'currency' => $currency,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync fiat payments: ' . $e->getMessage());
            Log::error('Sync fiat payments error', [
                'error' => $e->getMessage(),
            ]);
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * Sync a single fiat payment record
     */
    protected function syncFiatPaymentRecord(array $paymentData, string $currency, string $type, int &$created, int &$updated, int &$errors): void
    {
        try {
            // Find existing payment method by key and type
            $paymentMethod = PaymentMethod::where('key', $paymentData['id'])
                ->where('type', $type)
                ->where('is_fiat', true)
                ->first();

            // Prepare data
            $data = [
                'key' => $paymentData['id'],
                'name' => $paymentData['name'],
                'currency' => $currency,
                'currency_type' => $currency,
                'type' => $type,
                'is_fiat' => true,
                'enabled' => true,
                'synced_at' => now(),
            ];

            // Store payment_info in notes as JSON
            if (!empty($paymentData['payment_info'])) {
                $data['notes'] = json_encode($paymentData['payment_info'], JSON_UNESCAPED_UNICODE);
            }

            // Extract fields based on type (deposit_fields or withdraw_fields)
            $fieldsKey = $type === 'deposit' ? 'deposit_fields' : 'withdraw_fields';
            $newFields = null;
            if (!empty($paymentData['payment_info'][$fieldsKey])) {
                $fields = $paymentData['payment_info'][$fieldsKey];
                $newFields = $this->processFields($fields, $paymentData['payment_info'], $currency);
            }

            if ($paymentMethod) {
                // Update existing - merge fields (only update intersection)
                if ($newFields !== null) {
                    $existingFields = $paymentMethod->fields ?? [];
                    $data['fields'] = $this->mergeFieldsIntersection($existingFields, $newFields);
                }
                $paymentMethod->update($data);
                $updated++;
                $this->line("    ✓ Updated: {$paymentData['name']} ({$currency}) [{$type}] [ID: {$paymentData['id']}]");
            } else {
                // Create new - use all new fields
                if ($newFields !== null) {
                    $data['fields'] = $newFields;
                }
                $data['display_name'] = $paymentData['name'];
                PaymentMethod::create($data);
                $created++;
                $this->line("    + Created: {$paymentData['name']} ({$currency}) [{$type}] [ID: {$paymentData['id']}]");
            }
        } catch (\Exception $e) {
            $errors++;
            $this->error("    ✗ Error processing {$paymentData['name']} ({$currency}) [{$type}]: " . $e->getMessage());
            Log::error('Sync fiat payment record error', [
                'payment' => $paymentData,
                'currency' => $currency,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Merge fields - only update intersection fields (existing fields that also exist in new fields)
     * Preserves existing fields' custom properties while updating sync-related properties from new fields
     */
    protected function mergeFieldsIntersection(array $existingFields, array $newFields): array
    {
        // Build a map of new fields by field name
        $newFieldsMap = [];
        foreach ($newFields as $field) {
            if (isset($field['field'])) {
                $newFieldsMap[$field['field']] = $field;
            }
        }

        // Update existing fields with new data for intersection only
        $mergedFields = [];
        foreach ($existingFields as $existingField) {
            $fieldName = $existingField['field'] ?? null;
            if ($fieldName && isset($newFieldsMap[$fieldName])) {
                // Field exists in both - merge: keep existing as base, update with new sync data
                $merged = $existingField;
                $newField = $newFieldsMap[$fieldName];
                
                // Update sync-related properties from new field
                if (isset($newField['require'])) $merged['require'] = $newField['require'];
                if (isset($newField['type'])) $merged['type'] = $newField['type'];
                if (isset($newField['list'])) $merged['list'] = $newField['list'];
                
                $mergedFields[] = $merged;
            } else {
                // Field only exists in existing - keep as is
                $mergedFields[] = $existingField;
            }
        }

        return $mergedFields;
    }

    /**
     * Process fields to add title, placeholder and list options
     */
    protected function processFields(array $fields, array $paymentInfo, string $currency): array
    {
        $extra = $paymentInfo['extra'] ?? [];
        
        // Process bank_code list
        $bankCodes = [];
        if (isset($extra['bank_code'])) {
            foreach ($extra['bank_code'] as $key => $t) {
                if ($currency === 'IDR' && isset($t['bank_id'])) {
                    $bankCodes[$key]['name'] = $t['bank_code'] ?? '';
                    $bankCodes[$key]['value'] = $t['bank_code'] ?? '';
                    $bankCodes[$key]['bank_info'] = $t;
                    $bankCodes[$key]['value_type'] = '1';
                } else {
                    if (isset($t['bank_name'])) $bankCodes[$key]['name'] = $t['bank_name'];
                    if (isset($t['bank_code'])) $bankCodes[$key]['value'] = $t['bank_code'];
                    if (isset($t['bank_icon'])) $bankCodes[$key]['icon'] = $t['bank_icon'];
                }
            }
        }

        // Process each field
        $processedFields = [];
        foreach ($fields as $field) {
            // For IDR currency, skip bank_id and bank_name fields
            if ($currency === 'IDR') {
                if ($field['field'] === 'bank_id' || $field['field'] === 'bank_name') {
                    continue;
                }
            }

            // Add list options for specific fields
            if ($field['field'] === 'bank_code' && !empty($bankCodes)) {
                $field['list'] = array_values($bankCodes);
            }
            if ($field['field'] === 'bank_type' && isset($extra['bank_type'])) {
                $field['list'] = $extra['bank_type'];
            }
            if ($field['field'] === 'pix_type' && isset($extra['pix_type'])) {
                $field['list'] = $extra['pix_type'];
            }
            if ($field['field'] === 'wallet_type' && isset($extra['wallet_type'])) {
                $field['list'] = $extra['wallet_type'];
            }
            if ($field['field'] === 'account_type' && isset($extra['account_type'])) {
                $field['list'] = $extra['account_type'];
            }

            $processedFields[] = $field;
        }

        return $processedFields;
    }

    /**
     * Sync crypto coins
     */
    protected function syncCryptoCoins(SopayService $sopayService): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;

        try {
            $response = $sopayService->getCoins();

            if ($response['code'] !== 0) {
                $this->error('❌ Failed to fetch crypto coins: ' . ($response['errmsg'] ?? 'Unknown error'));
                return compact('created', 'updated', 'errors');
            }

            $items = $response['data']['items'] ?? [];
            
            if (empty($items)) {
                $this->warn('⚠️  No crypto coins found');
                return compact('created', 'updated', 'errors');
            }

            foreach ($items as $coinData) {
                try {
                    $enableDeposit = $coinData['enable_deposit'] ?? 0;
                    $enableWithdraw = $coinData['enable_withdraw'] ?? 0;
                    
                    // Create or update deposit record if enabled
                    if ($enableDeposit) {
                        $this->syncCryptoCoinRecord(
                            $coinData,
                            'deposit',
                            $created,
                            $updated,
                            $errors
                        );
                    }
                    
                    // Create or update withdraw record if enabled
                    if ($enableWithdraw) {
                        $this->syncCryptoCoinRecord(
                            $coinData,
                            'withdraw',
                            $created,
                            $updated,
                            $errors
                        );
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("    ✗ Error processing {$coinData['symbol']}: " . $e->getMessage());
                    Log::error('Sync crypto coin error', [
                        'coin' => $coinData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync crypto coins: ' . $e->getMessage());
            Log::error('Sync crypto coins error', [
                'error' => $e->getMessage(),
            ]);
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * Sync a single crypto coin record
     */
    protected function syncCryptoCoinRecord(array $coinData, string $type, int &$created, int &$updated, int &$errors): void
    {
        try {
            // Find existing payment method by key and type
            $paymentMethod = PaymentMethod::where('key', $coinData['id'])
                ->where('type', $type)
                ->where('is_fiat', false)
                ->first();

            // Prepare data
            $data = [
                'key' => $coinData['id'],
                'name' => $coinData['symbol'],
                'currency' => $coinData['symbol'],
                'currency_type' => $coinData['coin_type'] ?? null,
                'type' => $type,
                'is_fiat' => false,
                'enabled' => true,
                'synced_at' => now(),
            ];

            // Store crypto info in crypto_info as JSON
            $cryptoInfo = [
                'token_name' => $coinData['token_name'] ?? null,
                'coin_type' => $coinData['coin_type'] ?? null,
                'contract_address' => $coinData['contract_address'] ?? null,
                'token_decimal' => $coinData['token_decimal'] ?? null,
                'min_withdraw' => $coinData['min_withdraw'] ?? null,
                'withdraw_fee' => $coinData['withdraw_fee'] ?? null,
                'arrive_time' => $coinData['arrive_time'] ?? null,
                'display_precision' => $coinData['display_precision'] ?? null,
                'type_alias' => $coinData['type_alias'] ?? null,
                'multi_chain' => $coinData['multi_chain'] ?? false,
                'memoable' => $coinData['memoable'] ?? false,
            ];
            $data['crypto_info'] = $cryptoInfo;

            // Set min_amount if available
            if (!empty($coinData['min_withdraw'])) {
                $data['min_amount'] = $coinData['min_withdraw'];
            }

            if ($paymentMethod) {
                // Update existing
                $paymentMethod->update($data);
                $updated++;
                $this->line("    ✓ Updated: {$coinData['symbol']} [{$type}] [ID: {$coinData['id']}]");
            } else {
                // Create new
                $data['display_name'] = $coinData['token_name'] ?? $coinData['symbol'];
                if (!empty($coinData['icon'])) {
                    $data['icon'] = $coinData['icon'];
                }
                if (isset($coinData['sort_id'])) {
                    $data['sort_id'] = $coinData['sort_id'];
                }
                PaymentMethod::create($data);
                $created++;
                $this->line("    + Created: {$coinData['symbol']} [{$type}] [ID: {$coinData['id']}]");
            }
        } catch (\Exception $e) {
            $errors++;
            $this->error("    ✗ Error processing {$coinData['symbol']} [{$type}]: " . $e->getMessage());
            Log::error('Sync crypto coin record error', [
                'coin' => $coinData,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

