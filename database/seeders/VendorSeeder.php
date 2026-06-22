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
                'code'                => 'VEN-001',
                'name'                => 'Acme Tech Services',
                'contact_person'      => 'Rajesh Kumar',
                'phone'               => '9876543210',
                'email'               => 'rajesh@acmetech.in',
                'service_types'       => ['amc', 'service'],
                'sla_response_hours'  => 4,
                'sla_resolution_days' => 2,
                'status'              => 'active',
            ],
            [
                'code'                => 'VEN-002',
                'name'                => 'SwiftFix Solutions',
                'contact_person'      => 'Priya Sharma',
                'phone'               => '9871234567',
                'email'               => 'priya@swiftfix.in',
                'service_types'       => ['service'],
                'sla_response_hours'  => 8,
                'sla_resolution_days' => 3,
                'status'              => 'active',
            ],
            [
                'code'                => 'VEN-003',
                'name'                => 'AutoCare Vendors',
                'contact_person'      => 'Mohammed Ali',
                'phone'               => '9823456789',
                'email'               => 'ali@autocare.in',
                'service_types'       => ['warranty', 'service'],
                'sla_response_hours'  => 2,
                'sla_resolution_days' => 1,
                'status'              => 'active',
            ],
            [
                'code'                => 'VEN-004',
                'name'                => 'BuildSafe Equipment',
                'contact_person'      => 'Sunita Patel',
                'phone'               => '9812345678',
                'email'               => 'sunita@buildsafe.in',
                'service_types'       => ['all'],
                'sla_response_hours'  => 12,
                'sla_resolution_days' => 5,
                'status'              => 'active',
            ],
            [
                'code'                => 'VEN-005',
                'name'                => 'ElectroPro Services',
                'contact_person'      => 'Vikram Nair',
                'phone'               => '9800011122',
                'email'               => 'vikram@electropro.in',
                'service_types'       => ['amc'],
                'sla_response_hours'  => 6,
                'sla_resolution_days' => 3,
                'status'              => 'inactive',
            ],
        ];

        foreach ($vendors as $data) {
            Vendor::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
