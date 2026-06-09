<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\AssetSubcategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'code' => 'VE',
                'name' => 'Vehicle',
                'subcategories' => ['Car', 'Bike', 'Scooter', 'Truck', 'Auto'],
            ],
            [
                'code' => 'AC',
                'name' => 'Air Conditioner',
                'subcategories' => ['Window AC', 'Split AC', 'HVAC', 'Cassette AC'],
            ],
            [
                'code' => 'IT',
                'name' => 'IT Equipment',
                'subcategories' => ['Laptop', 'Desktop', 'Printer', 'Scanner', 'Server', 'Network Switch', 'UPS'],
            ],
            [
                'code' => 'OX',
                'name' => 'Other Office Equipment',
                'subcategories' => ['RO Water Purifier', 'Geyser', 'Kitchen Hob', 'Exhaust Fan', 'Microwave'],
            ],
            [
                'code' => 'UE',
                'name' => 'Utility Equipment',
                'subcategories' => ['Water Cooler', 'Air Purifier', 'Vacuum Cleaner'],
            ],
            [
                'code' => 'GE',
                'name' => 'Generator',
                'subcategories' => ['Diesel Generator', 'Solar Generator', 'Portable Generator'],
            ],
            [
                'code' => 'MA',
                'name' => 'Machine',
                'subcategories' => ['Industrial Machine', 'CNC Machine', 'Lathe Machine', 'Drilling Machine'],
            ],
            [
                'code' => 'OE',
                'name' => 'Office Equipment',
                'subcategories' => ['Photocopier', 'Fax Machine', 'Shredder', 'Paper Binding Machine'],
            ],
            [
                'code' => 'FN',
                'name' => 'Furniture',
                'subcategories' => ['Chair', 'Table', 'Cabinet', 'Shelf', 'Sofa'],
            ],
            [
                'code' => 'MO',
                'name' => 'Mobile / Tablet',
                'subcategories' => ['Mobile Phone', 'Tablet', 'iPad'],
            ],
        ];

        foreach ($data as $item) {
            $category = AssetCategory::firstOrCreate(
                ['code' => $item['code']],
                ['name' => $item['name'], 'status' => 'active']
            );

            foreach ($item['subcategories'] as $subName) {
                AssetSubcategory::firstOrCreate(
                    ['asset_category_id' => $category->id, 'name' => $subName],
                    ['status' => 'active']
                );
            }
        }
    }
}
