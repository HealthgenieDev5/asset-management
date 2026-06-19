<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Seed from existing asset location strings
        Asset::whereNotNull('location')->where('location', '!=', '')
            ->distinct()->pluck('location')
            ->each(fn($name) => Location::firstOrCreate(['name' => $name], ['is_active' => true]));

        // Common defaults
        $defaults = ['Head Office', 'Server Room', 'Conference Room', 'Warehouse', 'Reception', 'Basement', 'IT Department'];
        foreach ($defaults as $name) {
            Location::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
