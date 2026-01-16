<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SeedReferralSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:seed-referral {--force : Force overwrite existing settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed referral and promotion settings';

    /**
     * Settings data to seed.
     */
    protected array $settings = [
        [
            'type' => 'first_deposit_bonus',
            'content' => [
                'name' => 'Cash Bonus',
                'description' => '被推荐人首次充值后，推荐人得到固定金额或充值%金额返现。',
                'value' => [],
                'type' => 0,
                'min_deposit' => 100,
                'enabled' => false,
            ],
            'showable' => false,
            'group' => 'referral',
        ],
        [
            'type' => 'deposit_bonus',
            'content' => [
                'name' => 'Deposit Bonus',
                'description' => '被推荐人充值后，推荐人得到固定金额或充值%金额返现。',
                'value' => [],
                'type' => 1,
                'enabled' => false,
            ],
            'showable' => false,
            'group' => 'referral',
        ],
        [
            'type' => 'first_deposit',
            'content' => [
                'enabled' => false,
                'value' => [0, 0, 0],
                'value_type' => 1,
                'condition' => [0, 0, 0],
                'unlock_wager' => 2,
                'cap_bonus' => 2,
                'max_bonus' => 10,
            ],
            'showable' => true,
            'group' => 'promotion',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Seeding referral and promotion settings...');
        $this->newLine();

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($this->settings as $item) {
            $key = $item['type'];
            $group = $item['group'];
            
            // Build the value object
            $value = [
                'content' => $item['content'],
                'showable' => $item['showable'],
            ];

            $existing = Setting::where('key', $key)->first();

            if ($existing) {
                if ($force) {
                    $existing->value = $value;
                    $existing->type = 'json';
                    $existing->group = $group;
                    $existing->save();
                    
                    $this->line("  ✏️  Updated: <comment>{$key}</comment>");
                    $updated++;
                } else {
                    $this->line("  ⏭️  Skipped: <comment>{$key}</comment> (already exists)");
                    $skipped++;
                }
            } else {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'type' => 'json',
                    'group' => $group,
                    'description' => $item['content']['description'] ?? null,
                ]);
                
                $this->line("  ✅ Created: <info>{$key}</info>");
                $created++;
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
            ]
        );

        $this->newLine();
        $this->info('✅ Done!');
    }
}
