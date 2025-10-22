<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandDetail;
use Illuminate\Database\Seeder;

class BrandDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::all();
        
        $currencies = ['USD', 'EUR', 'GBP', 'CNY', 'JPY', 'KRW', 'THB', 'MYR', 'SGD', 'HKD'];
        
        foreach ($brands as $brand) {
            // 为每个品牌创建多个货币支持
            $currencyCount = rand(2, 6); // 每个品牌支持2-6种货币
            $selectedCurrencies = collect($currencies)->random($currencyCount);
            
            foreach ($selectedCurrencies as $currency) {
                $isSupported = rand(0, 1) == 1; // 随机是否支持
                $isConfigured = $isSupported ? (rand(0, 1) == 1) : false; // 如果支持，随机是否已配置
                $gameCount = $isSupported ? rand(10, 500) : 0; // 如果支持，随机游戏数量
                $isEnabled = $isSupported && $isConfigured; // 只有支持且配置的才启用
                
                BrandDetail::create([
                    'brand_id' => $brand->id,
                    'coin' => $currency,
                    'support' => $isSupported,
                    'configured' => $isConfigured,
                    'game_count' => $gameCount,
                    'enabled' => $isEnabled,
                    'rate' => $isSupported ? round(rand(80, 120) / 100, 2) : null, // 0.8-1.2 的随机费率
                ]);
            }
            
            // 为某些品牌添加无货币的详情（通用支持）
            if (rand(0, 1) == 1) {
                BrandDetail::create([
                    'brand_id' => $brand->id,
                    'coin' => null,
                    'support' => true,
                    'configured' => rand(0, 1) == 1,
                    'game_count' => rand(50, 200),
                    'enabled' => true,
                    'rate' => round(rand(90, 110) / 100, 2), // 0.9-1.1 的随机费率
                ]);
            }
        }

        // 为特定品牌创建一些特殊配置
        $this->createSpecialConfigurations();

        $this->command->info('Brand details seeded successfully!');
    }

    /**
     * Create special configurations for specific brands
     */
    private function createSpecialConfigurations(): void
    {
        // Evolution Gaming - 主要支持USD和EUR
        $evolution = Brand::where('provider', 'evolution')->first();
        if ($evolution) {
            // 删除现有的details
            $evolution->details()->delete();
            
            // 创建新的details
            BrandDetail::create([
                'brand_id' => $evolution->id,
                'coin' => 'USD',
                'support' => true,
                'configured' => true,
                'game_count' => 150,
                'enabled' => true,
                'rate' => 1.0, // 标准费率
            ]);
            
            BrandDetail::create([
                'brand_id' => $evolution->id,
                'coin' => 'EUR',
                'support' => true,
                'configured' => true,
                'game_count' => 120,
                'enabled' => true,
                'rate' => 0.95, // 略低的费率
            ]);
            
            BrandDetail::create([
                'brand_id' => $evolution->id,
                'coin' => 'GBP',
                'support' => true,
                'configured' => false,
                'game_count' => 0,
                'enabled' => false,
                'rate' => null, // 未配置时无费率
            ]);
        }

        // Pragmatic Play - 支持多种货币
        $pragmatic = Brand::where('provider', 'pragmatic')->first();
        if ($pragmatic) {
            $pragmatic->details()->delete();
            
            $pragmaticCurrencies = ['USD', 'EUR', 'GBP', 'CNY', 'JPY'];
            foreach ($pragmaticCurrencies as $currency) {
                BrandDetail::create([
                    'brand_id' => $pragmatic->id,
                    'coin' => $currency,
                    'support' => true,
                    'configured' => true,
                    'game_count' => rand(80, 200),
                    'enabled' => true,
                    'rate' => round(rand(85, 115) / 100, 2), // 0.85-1.15 的随机费率
                ]);
            }
        }

        // NetEnt - 主要支持USD
        $netent = Brand::where('provider', 'netent')->first();
        if ($netent) {
            $netent->details()->delete();
            
            BrandDetail::create([
                'brand_id' => $netent->id,
                'coin' => 'USD',
                'support' => true,
                'configured' => true,
                'game_count' => 300,
                'enabled' => true,
                'rate' => 1.05, // 略高的费率
            ]);
            
            BrandDetail::create([
                'brand_id' => $netent->id,
                'coin' => 'EUR',
                'support' => true,
                'configured' => false,
                'game_count' => 0,
                'enabled' => false,
                'rate' => null, // 未配置时无费率
            ]);
        }

        // 维护中的品牌 - 禁用所有details
        $maintenanceBrand = Brand::where('provider', 'maintenance')->first();
        if ($maintenanceBrand) {
            $maintenanceBrand->details()->update(['enabled' => false]);
        }

        // 禁用的品牌 - 禁用所有details
        $disabledBrand = Brand::where('provider', 'disabled')->first();
        if ($disabledBrand) {
            $disabledBrand->details()->update(['enabled' => false]);
        }
    }
}