<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'name'      => 'Acme Tech Services',
                'type'      => 'company',
                'phone'     => '9876543210',
                'alt_phone' => null,
                'email'     => 'rajesh@acmetech.in',
                'alt_email' => null,
                'address'   => 'Mumbai, Maharashtra',
                'status'    => 'active',
            ],
            [
                'name'      => 'SwiftFix Solutions',
                'type'      => 'company',
                'phone'     => '9871234567',
                'alt_phone' => null,
                'email'     => 'priya@swiftfix.in',
                'alt_email' => null,
                'address'   => 'Pune, Maharashtra',
                'status'    => 'active',
            ],
            [
                'name'      => 'AutoCare Vendors',
                'type'      => 'company',
                'phone'     => '9823456789',
                'alt_phone' => '9811223344',
                'email'     => 'ali@autocare.in',
                'alt_email' => null,
                'address'   => 'Delhi, India',
                'status'    => 'active',
            ],
            [
                'name'      => 'BuildSafe Equipment',
                'type'      => 'company',
                'phone'     => '9812345678',
                'alt_phone' => null,
                'email'     => 'sunita@buildsafe.in',
                'alt_email' => 'support@buildsafe.in',
                'address'   => 'Bangalore, Karnataka',
                'status'    => 'active',
            ],
            [
                'name'      => 'Vikram Nair',
                'type'      => 'individual',
                'phone'     => '9800011122',
                'alt_phone' => null,
                'email'     => 'vikram@electropro.in',
                'alt_email' => null,
                'address'   => 'Chennai, Tamil Nadu',
                'status'    => 'inactive',
            ],
        ];

        foreach ($vendors as $data) {
            Vendor::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
