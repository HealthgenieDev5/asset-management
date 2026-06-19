<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Backfill from existing asset data
        Asset::whereNotNull('department')->where('department', '!=', '')
            ->distinct()->pluck('department')
            ->each(fn($name) => Department::firstOrCreate(['name' => $name], ['is_active' => true]));

        // Common defaults
        $defaults = ['IT', 'HR', 'Finance', 'Operations', 'Admin', 'Management', 'Accounts', 'Marketing'];
        foreach ($defaults as $name) {
            Department::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
