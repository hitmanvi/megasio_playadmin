<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Evolution Gaming',
                'provider' => 'evolution',
                'restricted_region' => ['CN', 'US'],
                'sort_id' => 1,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Pragmatic Play',
                'provider' => 'pragmatic',
                'restricted_region' => ['CN'],
                'sort_id' => 2,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'NetEnt',
                'provider' => 'netent',
                'restricted_region' => null,
                'sort_id' => 3,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Microgaming',
                'provider' => 'microgaming',
                'restricted_region' => ['CN', 'US', 'UK'],
                'sort_id' => 4,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Play\'n GO',
                'provider' => 'playngo',
                'restricted_region' => ['CN'],
                'sort_id' => 5,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Red Tiger Gaming',
                'provider' => 'redtiger',
                'restricted_region' => null,
                'sort_id' => 6,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Yggdrasil Gaming',
                'provider' => 'yggdrasil',
                'restricted_region' => ['CN', 'US'],
                'sort_id' => 7,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Thunderkick',
                'provider' => 'thunderkick',
                'restricted_region' => null,
                'sort_id' => 8,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Big Time Gaming',
                'provider' => 'btg',
                'restricted_region' => ['CN'],
                'sort_id' => 9,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            [
                'name' => 'Nolimit City',
                'provider' => 'nolimit',
                'restricted_region' => ['CN', 'US'],
                'sort_id' => 10,
                'enabled' => true,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
            // 维护中的品牌示例
            [
                'name' => 'Maintenance Brand',
                'provider' => 'maintenance',
                'restricted_region' => null,
                'sort_id' => 11,
                'enabled' => true,
                'maintain_start' => now()->subHours(2),
                'maintain_end' => now()->addHours(2),
                'maintain_auto' => true,
            ],
            // 禁用的品牌示例
            [
                'name' => 'Disabled Brand',
                'provider' => 'disabled',
                'restricted_region' => null,
                'sort_id' => 12,
                'enabled' => false,
                'maintain_start' => null,
                'maintain_end' => null,
                'maintain_auto' => false,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::create($brandData);
        }

        $this->command->info('Brands seeded successfully!');
    }
}