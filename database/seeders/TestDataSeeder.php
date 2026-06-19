<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetCategory;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
use App\Models\AssetServicePart;
use App\Models\AssetSubcategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $hasAssets   = Asset::withTrashed()->where('asset_code', 'LIKE', '%-TEST%')->exists();
        $hasServices = AssetService::count() > 0;
        if ($hasAssets && $hasServices) {
            $this->command->warn('Test data already exists — skipping. Run migrate:fresh --seed to reset.');
            return;
        }

        $user = User::first();

        $catVehicle = AssetCategory::where('code', 'VE')->first();
        $catIT      = AssetCategory::where('code', 'IT')->first();
        $catAC      = AssetCategory::where('code', 'AC')->first();
        $catGen     = AssetCategory::where('code', 'GE')->first();

        $subCar     = AssetSubcategory::where('name', 'Car')->first();
        $subBike    = AssetSubcategory::where('name', 'Bike')->first();
        $subLaptop  = AssetSubcategory::where('name', 'Laptop')->first();
        $subSplitAC = AssetSubcategory::where('name', 'Split AC')->first();
        $subDiesel  = AssetSubcategory::where('name', 'Diesel Generator')->first();

        // ── SCENARIO 1: Active vehicle – all compliance dates set ──────────────
        $car1 = Asset::firstOrCreate(['asset_code' => $catVehicle->code . '-TEST1'], [
            'asset_code'                   => $catVehicle->code . '-TEST1',
            'asset_name'                   => 'Toyota Innova (Test)',
            'asset_category_id'            => $catVehicle->id,
            'asset_subcategory_id'         => $subCar->id,
            'serial_number'                => 'TIN-TEST-001',
            'manufacturer'                 => 'Toyota',
            'model'                        => 'Innova Crysta',
            'model_year'                   => 2021,
            'location'                     => 'Head Office',
            'department'                   => 'Administration',
            'custodian'                    => 'Ramesh Kumar',
            'vendor_supplier'              => 'City Toyota',
            'bill_no'                      => 'INV-2021-001',
            'bill_amount'                  => 1850000,
            'bill_date'                    => '2021-03-15',
            'purchase_date'                => '2021-03-15',
            'warranty_details'             => '3-year manufacturer warranty',
            'warranty_lapse_date'          => today()->addDays(20),  // due in 30 — soon
            'warranty_reminder_before_days'=> 30,
            'puc_expiry_date'              => today()->subDays(5),   // overdue
            'puc_reminder_before_days'     => 15,
            'fitness_expiry_date'          => today()->addDays(15),  // due in 30 — soon
            'fitness_reminder_before_days' => 30,
            'road_tax_expiry_date'         => today()->addDays(60),  // due in 90
            'road_tax_reminder_before_days'=> 30,
            'vehicle_obv'                  => 1200000,
            'vehicle_depreciation_percent' => 15.00,
            'vehicle_depreciation_book_value' => 1020000,
            'inspection_required'          => true,
            'inspection_frequency_value'   => 6,
            'inspection_frequency_unit'    => 'months',
            'status'                       => 'active',
            'created_by'                   => $user->id,
        ]);

        // ── SCENARIO 2: Active bike – PUC/fitness overdue ─────────────────────
        $bike1 = Asset::firstOrCreate(['asset_code' => $catVehicle->code . '-TEST2'], [
            'asset_code'                   => $catVehicle->code . '-TEST2',
            'asset_name'                   => 'Honda Activa (Test)',
            'asset_category_id'            => $catVehicle->id,
            'asset_subcategory_id'         => $subBike->id,
            'manufacturer'                 => 'Honda',
            'model'                        => 'Activa 6G',
            'model_year'                   => 2022,
            'department'                   => 'Delivery',
            'custodian'                    => 'Suresh Patel',
            'bill_no'                      => 'INV-2022-040',
            'bill_amount'                  => 75000,
            'bill_date'                    => '2022-01-10',
            'purchase_date'                => '2022-01-10',
            'warranty_lapse_date'          => today()->subDays(200), // expired
            'puc_expiry_date'              => today()->subDays(30),  // overdue
            'fitness_expiry_date'          => today()->subDays(10),  // overdue
            'road_tax_expiry_date'         => today()->addDays(200), // fine
            'vehicle_obv'                  => 75000,
            'vehicle_depreciation_percent' => 10.00,
            'vehicle_depreciation_book_value' => 67500,
            'status'                       => 'active',
            'created_by'                   => $user->id,
        ]);

        // ── SCENARIO 3: Laptop – active, warranty expiring soon ───────────────
        $laptop1 = Asset::firstOrCreate(['asset_code' => $catIT->code . '-TEST1'], [
            'asset_code'          => $catIT->code . '-TEST1',
            'asset_name'          => 'Dell Latitude 5540 (Test)',
            'asset_category_id'   => $catIT->id,
            'asset_subcategory_id'=> $subLaptop->id,
            'serial_number'       => 'DL-TEST-5540',
            'manufacturer'        => 'Dell',
            'model'               => 'Latitude 5540',
            'model_year'          => 2023,
            'department'          => 'IT',
            'location'            => 'Server Room',
            'custodian'           => 'Priya Sharma',
            'vendor_supplier'     => 'Tech Solutions Pvt Ltd',
            'bill_no'             => 'TS-2023-099',
            'bill_amount'         => 95000,
            'bill_date'           => '2023-06-01',
            'purchase_date'       => '2023-06-01',
            'warranty_details'    => '2-year onsite warranty',
            'warranty_lapse_date' => today()->addDays(10),  // due in 30 — soon
            'status'              => 'active',
            'created_by'          => $user->id,
        ]);

        // ── SCENARIO 4: Split AC – under repair ───────────────────────────────
        $ac1 = Asset::firstOrCreate(['asset_code' => $catAC->code . '-TEST1'], [
            'asset_code'          => $catAC->code . '-TEST1',
            'asset_name'          => 'Voltas 1.5 Ton Split AC (Test)',
            'asset_category_id'   => $catAC->id,
            'asset_subcategory_id'=> $subSplitAC->id,
            'manufacturer'        => 'Voltas',
            'model'               => 'Vertis Gold',
            'model_year'          => 2020,
            'department'          => 'Admin',
            'location'            => 'Conference Room',
            'bill_no'             => 'VOL-2020-055',
            'bill_amount'         => 42000,
            'bill_date'           => '2020-04-01',
            'purchase_date'       => '2020-04-01',
            'warranty_lapse_date' => today()->subDays(500), // expired long ago
            'status'              => 'under_repair',
            'created_by'          => $user->id,
        ]);

        // ── SCENARIO 5: Generator – inactive ─────────────────────────────────
        $gen1 = Asset::firstOrCreate(['asset_code' => $catGen->code . '-TEST1'], [
            'asset_code'          => $catGen->code . '-TEST1',
            'asset_name'          => 'Kirloskar 25 KVA Generator (Test)',
            'asset_category_id'   => $catGen->id,
            'asset_subcategory_id'=> $subDiesel->id,
            'manufacturer'        => 'Kirloskar',
            'model'               => 'KG1-25AS',
            'model_year'          => 2019,
            'department'          => 'Facilities',
            'location'            => 'Basement',
            'bill_no'             => 'KIR-2019-012',
            'bill_amount'         => 350000,
            'bill_date'           => '2019-08-20',
            'purchase_date'       => '2019-08-20',
            'inspection_required' => true,
            'inspection_frequency_value' => 3,
            'inspection_frequency_unit'  => 'months',
            'status'              => 'inactive',
            'created_by'          => $user->id,
        ]);

        // ── SCENARIO 6: Asset that will be soft-deleted (orphan test) ─────────
        $orphanAsset = Asset::withTrashed()->firstOrCreate(['asset_code' => $catIT->code . '-TEST99'], [
            'asset_code'        => $catIT->code . '-TEST99',
            'asset_name'        => 'Orphan Test Laptop (DELETE ME)',
            'asset_category_id' => $catIT->id,
            'status'            => 'active',
            'created_by'        => $user->id,
        ]);

        // ── AMC Contracts ──────────────────────────────────────────────────────

        // AMC expired
        AssetAmcContract::firstOrCreate(['contract_number' => 'AMC-AC-TEST-001'], [
            'asset_id'         => $ac1->id,
            'contract_number'  => 'AMC-AC-TEST-001',
            'vendor_name'      => 'Voltas Service Center',
            'amc_date_from'    => '2023-01-01',
            'amc_date_to'      => today()->subDays(30), // expired
            'amc_amount'       => 5000,
            'coverage_type'    => 'comprehensive',
            'reminder_before_days' => 30,
            'created_by'       => $user->id,
        ]);

        // AMC expiring in 20 days
        AssetAmcContract::firstOrCreate(['contract_number' => 'AMC-IT-TEST-001'], [
            'asset_id'         => $laptop1->id,
            'contract_number'  => 'AMC-IT-TEST-001',
            'vendor_name'      => 'Dell Support',
            'amc_date_from'    => today()->subYear(),
            'amc_date_to'      => today()->addDays(20),
            'amc_amount'       => 12000,
            'coverage_type'    => 'non_comprehensive',
            'reminder_before_days' => 30,
            'created_by'       => $user->id,
        ]);

        // AMC active (90+ days)
        AssetAmcContract::firstOrCreate(['contract_number' => 'AMC-GEN-TEST-001'], [
            'asset_id'         => $gen1->id,
            'contract_number'  => 'AMC-GEN-TEST-001',
            'vendor_name'      => 'Kirloskar Service',
            'amc_date_from'    => today()->subMonths(2),
            'amc_date_to'      => today()->addMonths(10),
            'amc_amount'       => 25000,
            'coverage_type'    => 'comprehensive',
            'reminder_before_days' => 30,
            'created_by'       => $user->id,
        ]);

        // AMC for orphan asset (will be cascade-deleted)
        AssetAmcContract::firstOrCreate(['contract_number' => 'AMC-ORPHAN-TEST-001'], [
            'asset_id'         => $orphanAsset->id,
            'contract_number'  => 'AMC-ORPHAN-TEST-001',
            'vendor_name'      => 'Test Vendor',
            'amc_date_from'    => today()->subMonths(1),
            'amc_date_to'      => today()->addMonths(6),
            'amc_amount'       => 5000,
            'coverage_type'    => 'comprehensive',
            'created_by'       => $user->id,
        ]);

        // ── Insurance Policies ─────────────────────────────────────────────────

        // Expired policy
        AssetInsurancePolicy::firstOrCreate(['policy_number' => 'INS-BIKE-TEST-001'], [
            'asset_id'            => $bike1->id,
            'policy_number'       => 'INS-BIKE-TEST-001',
            'insurer_name'        => 'New India Assurance',
            'policy_type'         => 'comprehensive',
            'policy_date_from'    => today()->subYear()->subDays(10),
            'policy_date_to'      => today()->subDays(10), // expired
            'premium_amount'      => 8500,
            'sum_insured'         => 65000,
            'reminder_before_days'=> 30,
            'created_by'          => $user->id,
        ]);

        // Policy expiring in 25 days
        AssetInsurancePolicy::firstOrCreate(['policy_number' => 'INS-CAR-TEST-001'], [
            'asset_id'            => $car1->id,
            'policy_number'       => 'INS-CAR-TEST-001',
            'insurer_name'        => 'HDFC ERGO',
            'policy_type'         => 'comprehensive',
            'policy_date_from'    => today()->subYear()->addDays(5),
            'policy_date_to'      => today()->addDays(25),
            'premium_amount'      => 42000,
            'sum_insured'         => 1500000,
            'reminder_before_days'=> 30,
            'created_by'          => $user->id,
        ]);

        // Policy active (fine)
        AssetInsurancePolicy::firstOrCreate(['policy_number' => 'INS-IT-TEST-001'], [
            'asset_id'            => $laptop1->id,
            'policy_number'       => 'INS-IT-TEST-001',
            'insurer_name'        => 'Bajaj Allianz',
            'policy_type'         => 'comprehensive',
            'policy_date_from'    => today()->subMonths(3),
            'policy_date_to'      => today()->addMonths(9),
            'premium_amount'      => 3500,
            'sum_insured'         => 95000,
            'reminder_before_days'=> 30,
            'created_by'          => $user->id,
        ]);

        // Insurance for orphan asset (will be cascade-deleted)
        AssetInsurancePolicy::firstOrCreate(['policy_number' => 'INS-ORPHAN-TEST-001'], [
            'asset_id'            => $orphanAsset->id,
            'policy_number'       => 'INS-ORPHAN-TEST-001',
            'insurer_name'        => 'Test Insurer',
            'policy_type'         => 'comprehensive',
            'policy_date_from'    => today()->subMonth(),
            'policy_date_to'      => today()->addMonths(11),
            'premium_amount'      => 2000,
            'sum_insured'         => 50000,
            'created_by'          => $user->id,
        ]);

        // ── Extended Warranties ────────────────────────────────────────────────

        if (! AssetExtendedWarranty::where('asset_id', $ac1->id)->exists()) {
            AssetExtendedWarranty::create([
                'asset_id'                    => $ac1->id,
                'extended_warranty_vendor'    => 'Voltas Care',
                'extended_warranty_date_from' => '2022-04-01',
                'extended_warranty_date_to'   => today()->subDays(60),
                'extended_warranty_amount'    => 8000,
                'reminder_before_days'        => 30,
            ]);
        }

        if (! AssetExtendedWarranty::where('asset_id', $laptop1->id)->exists()) {
            AssetExtendedWarranty::create([
                'asset_id'                    => $laptop1->id,
                'extended_warranty_vendor'    => 'Dell Extended Care',
                'extended_warranty_date_from' => today()->subYear()->addDays(10),
                'extended_warranty_date_to'   => today()->addDays(15),
                'extended_warranty_amount'    => 15000,
                'reminder_before_days'        => 30,
            ]);
        }

        if (! AssetExtendedWarranty::where('asset_id', $car1->id)->exists()) {
            AssetExtendedWarranty::create([
                'asset_id'                    => $car1->id,
                'extended_warranty_vendor'    => 'Toyota Warranty Plus',
                'extended_warranty_date_from' => today()->subMonths(6),
                'extended_warranty_date_to'   => today()->addMonths(18),
                'extended_warranty_amount'    => 35000,
                'reminder_before_days'        => 30,
            ]);
        }

        // ── Services ───────────────────────────────────────────────────────────
        $skipServices = AssetService::where('asset_id', $car1->id)->exists();

        // Past service for car — next service overdue
        $svc1 = $skipServices ? AssetService::where('bill_no', 'SVC-CAR-001')->first() : AssetService::create([
            'asset_id'                        => $car1->id,
            'service_type'                    => 'preventive_maintenance',
            'service_date'                    => today()->subDays(120),
            'service_agency'                  => 'Toyota Authorized Service',
            'technician_name'                 => 'Mahesh Auto',
            'work_done'                       => 'Oil change, filter replacement, general checkup',
            'service_cost'                    => 8500,
            'bill_no'                         => 'SVC-CAR-001',
            'next_service_date'               => today()->subDays(30), // overdue
            'service_interval_value'          => 3,
            'service_interval_unit'           => 'months',
            'meter_reading'                   => 45000,
            'mileage_reading'                 => 45000,
            'condition_rating'                => 'good',
            'next_service_reminder_before_days' => 15,
            'created_by'                      => $user->id,
        ]);

        if (! $skipServices) {
            // Recent service for car — next due in 25 days
            AssetService::create([
                'asset_id'                        => $car1->id,
                'service_type'                    => 'inspection',
                'service_date'                    => today()->subDays(5),
                'service_agency'                  => 'Toyota Authorized Service',
                'work_done'                       => 'Annual inspection, brake check',
                'service_cost'                    => 3500,
                'bill_no'                         => 'SVC-CAR-002',
                'next_service_date'               => today()->addDays(25),
                'certification_expiry'            => today()->addDays(25),
                'certification_reminder_before_days' => 15,
                'condition_rating'                => 'excellent',
                'created_by'                      => $user->id,
            ]);

            // Bike service — next due in 60 days
            AssetService::create([
                'asset_id'                        => $bike1->id,
                'service_type'                    => 'preventive_maintenance',
                'service_date'                    => today()->subDays(30),
                'service_agency'                  => 'Honda Service Center',
                'work_done'                       => 'Engine oil change, chain lubrication',
                'service_cost'                    => 1200,
                'next_service_date'               => today()->addDays(60),
                'service_interval_value'          => 3,
                'service_interval_unit'           => 'months',
                'meter_reading'                   => 12000,
                'condition_rating'                => 'good',
                'created_by'                      => $user->id,
            ]);
        }

        // AC service — repair type with downtime, next overdue
        $svc4 = AssetService::where('bill_no', 'SVC-AC-001')->first()
            ?? AssetService::create([
                'asset_id'                        => $ac1->id,
                'service_type'                    => 'repair',
                'service_date'                    => today()->subDays(90),
                'service_agency'                  => 'Voltas Service',
                'work_done'                       => 'Compressor replacement',
                'service_cost'                    => 18000,
                'bill_no'                         => 'SVC-AC-001',
                'next_service_date'               => today()->subDays(20),
                'downtime_hours'                  => 48.00,
                'condition_rating'                => 'fair',
                'created_by'                      => $user->id,
            ]);

        if (! AssetService::where('asset_id', $laptop1->id)->exists()) {
            AssetService::create([
                'asset_id'                        => $laptop1->id,
                'service_type'                    => 'calibration',
                'service_date'                    => today()->subDays(45),
                'service_agency'                  => 'Dell Certified Service',
                'technician_name'                 => 'IT Support Team',
                'work_done'                       => 'Screen calibration, battery diagnostic',
                'service_cost'                    => 2500,
                'next_service_date'               => today()->addDays(80),
                'certification_expiry'            => today()->subDays(10),
                'certification_reminder_before_days' => 30,
                'condition_rating'                => 'excellent',
                'created_by'                      => $user->id,
            ]);
        }

        if (! AssetService::where('asset_id', $gen1->id)->exists()) {
            AssetService::create([
                'asset_id'                        => $gen1->id,
                'service_type'                    => 'inspection',
                'service_date'                    => today()->subDays(10),
                'service_agency'                  => 'Kirloskar Service Center',
                'work_done'                       => 'Load testing, oil level check',
                'service_cost'                    => 6000,
                'next_service_date'               => today()->addDays(80),
                'certification_expiry'            => today()->addDays(20),
                'certification_reminder_before_days' => 30,
                'condition_rating'                => 'good',
                'created_by'                      => $user->id,
            ]);
        }

        if (! AssetService::where('asset_id', $orphanAsset->id)->exists()) {
            AssetService::create([
                'asset_id'         => $orphanAsset->id,
                'service_type'     => 'preventive_maintenance',
                'service_date'     => today()->subDays(15),
                'work_done'        => 'Orphan service — will be soft-deleted with asset',
                'service_cost'     => 1000,
                'next_service_date'=> today()->addDays(75),
                'created_by'       => $user->id,
            ]);
        }

        // ── Service Parts ──────────────────────────────────────────────────────

        if ($svc1 && ! AssetServicePart::where('asset_service_id', $svc1->id)->exists()) {
            AssetServicePart::create([
                'asset_service_id' => $svc1->id,
                'asset_id'         => $car1->id,
                'part_name'        => 'Engine Oil (5W-30)',
                'part_cost'        => 2400,
                'purchased_from'   => 'Toyota Parts',
                'created_by'       => $user->id,
            ]);

            AssetServicePart::create([
                'asset_service_id' => $svc1->id,
                'asset_id'         => $car1->id,
                'part_name'        => 'Oil Filter',
                'part_cost'        => 350,
                'purchased_from'   => 'Toyota Parts',
                'created_by'       => $user->id,
            ]);
        }

        if ($svc4 && ! AssetServicePart::where('asset_service_id', $svc4->id)->exists()) {
            AssetServicePart::create([
                'asset_service_id' => $svc4->id,
                'asset_id'         => $ac1->id,
                'part_name'        => 'AC Compressor',
                'part_cost'        => 14000,
                'purchased_from'   => 'Voltas Parts',
                'created_by'       => $user->id,
            ]);
        }

        // ── NOW: soft-delete the orphan asset to test cascade ─────────────────
        $orphanAsset->delete();

        $this->command->info('Test data seeded successfully.');
        $this->command->info('Scenarios covered:');
        $this->command->info('  [Asset] Active vehicle (car) with compliance dates — mixed overdue/soon/fine');
        $this->command->info('  [Asset] Active bike — PUC & fitness overdue, warranty expired');
        $this->command->info('  [Asset] Active laptop — warranty expiring soon');
        $this->command->info('  [Asset] Split AC — under repair, warranty expired');
        $this->command->info('  [Asset] Generator — inactive, inspection required');
        $this->command->info('  [Asset] Orphan test asset — SOFT DELETED (cascade test)');
        $this->command->info('  [AMC]   Expired / expiring in 20d / active / orphan (deleted)');
        $this->command->info('  [Ins]   Expired / expiring in 25d / active / orphan (deleted)');
        $this->command->info('  [ExtW]  Expired / expiring in 15d / active');
        $this->command->info('  [Svc]   Overdue / due in 25d / due in 60d / overdue / due in 80d');
        $this->command->info('  [Svc]   Cert expired / cert expiring in 20d');
        $this->command->info('  [Parts] 3 part records across 2 services');
    }
}
