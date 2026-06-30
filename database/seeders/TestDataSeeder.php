<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetCategory;
use App\Models\AssetComplaint;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetService;
use App\Models\AssetServicePart;
use App\Models\AssetSubcategory;
use App\Models\AssetWarranty;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    private User $user;

    // Category handles
    private AssetCategory $catVE;
    private AssetCategory $catIT;
    private AssetCategory $catAC;
    private AssetCategory $catGE;
    private AssetCategory $catOE;
    private AssetCategory $catFN;
    private AssetCategory $catMO;

    // Vendor handles
    private Vendor $vendorIT;
    private Vendor $vendorAuto;
    private Vendor $vendorAC;
    private Vendor $vendorGen;

    public function run(): void
    {
        $this->user = User::first();

        $this->loadCategories();
        $this->ensureVendors();

        $this->seedVehicles();
        $this->seedITEquipment();
        $this->seedAirConditioners();
        $this->seedGenerators();
        $this->seedOfficeEquipment();
        $this->seedFurniture();
        $this->seedMobileTablets();

        $this->command->info('✓ TestDataSeeder complete — all report scenarios covered.');
    }

    // ── Categories ────────────────────────────────────────────────────────────

    private function loadCategories(): void
    {
        $this->catVE = AssetCategory::where('code', 'VE')->firstOrFail();
        $this->catIT = AssetCategory::where('code', 'IT')->firstOrFail();
        $this->catAC = AssetCategory::where('code', 'AC')->firstOrFail();
        $this->catGE = AssetCategory::where('code', 'GE')->firstOrFail();
        $this->catOE = AssetCategory::where('code', 'OE')->firstOrFail();
        $this->catFN = AssetCategory::where('code', 'FN')->firstOrFail();
        $this->catMO = AssetCategory::where('code', 'MO')->firstOrFail();
    }

    // ── Vendors ───────────────────────────────────────────────────────────────

    private function ensureVendors(): void
    {
        $this->vendorIT   = Vendor::firstOrCreate(['name' => 'Acme Tech Services'],  ['type' => 'company', 'phone' => '9876543210', 'email' => 'rajesh@acmetech.in',  'address' => 'Mumbai, Maharashtra', 'status' => 'active']);
        $this->vendorAuto = Vendor::firstOrCreate(['name' => 'AutoCare Vendors'],    ['type' => 'company', 'phone' => '9823456789', 'email' => 'ali@autocare.in',      'address' => 'Delhi, India',        'status' => 'active']);
        $this->vendorAC   = Vendor::firstOrCreate(['name' => 'SwiftFix Solutions'],  ['type' => 'company', 'phone' => '9871234567', 'email' => 'priya@swiftfix.in',    'address' => 'Pune, Maharashtra',   'status' => 'active']);
        $this->vendorGen  = Vendor::firstOrCreate(['name' => 'BuildSafe Equipment'], ['type' => 'company', 'phone' => '9812345678', 'email' => 'sunita@buildsafe.in',  'address' => 'Bangalore, Karnataka','status' => 'active']);
        Vendor::firstOrCreate(['name' => 'ElectroPro Services'], ['type' => 'individual', 'phone' => '9800011122', 'email' => 'vikram@electropro.in', 'address' => 'Chennai, Tamil Nadu', 'status' => 'inactive']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function asset(string $code, array $data): Asset
    {
        return Asset::firstOrCreate(['asset_code' => $code], array_merge($data, ['created_by' => $this->user->id]));
    }

    private function amc(Asset $asset, array $data): void
    {
        if (! AssetAmcContract::where('contract_number', $data['contract_number'])->exists()) {
            AssetAmcContract::create(array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id], $data));
        }
    }

    private function insurance(Asset $asset, array $data): void
    {
        if (! AssetInsurancePolicy::where('policy_number', $data['policy_number'])->exists()) {
            AssetInsurancePolicy::create(array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id], $data));
        }
    }

    private function warranty(Asset $asset, array $data): void
    {
        AssetWarranty::firstOrCreate(
            ['asset_id' => $asset->id, 'bill_no' => $data['bill_no'] ?? null, 'warranty_type' => $data['warranty_type'] ?? 'original'],
            array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id], $data)
        );
    }

    private function service(Asset $asset, array $data): AssetService
    {
        return AssetService::firstOrCreate(
            ['asset_id' => $asset->id, 'bill_no' => $data['bill_no'] ?? null, 'service_date' => $data['service_date']],
            array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id], $data)
        );
    }

    private function part(AssetService $svc, Asset $asset, string $name, float $cost, string $from = ''): void
    {
        AssetServicePart::firstOrCreate(
            ['asset_service_id' => $svc->id, 'part_name' => $name],
            ['asset_id' => $asset->id, 'part_cost' => $cost, 'purchased_from' => $from, 'created_by' => $this->user->id]
        );
    }

    private function complaint(Asset $asset, array $data): void
    {
        AssetComplaint::firstOrCreate(
            ['asset_id' => $asset->id, 'title' => $data['title']],
            array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id], $data)
        );
    }

    private function schedule(Asset $asset, array $data): void
    {
        AssetMaintenanceSchedule::firstOrCreate(
            ['asset_id' => $asset->id, 'schedule_name' => $data['schedule_name']],
            array_merge(['asset_id' => $asset->id, 'created_by' => $this->user->id, 'reminder_thresholds' => [['value' => 7, 'unit' => 'days']]], $data)
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // VEHICLES  (PUC · Fitness · Road Tax · Depreciation reports)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedVehicles(): void
    {
        $subCar  = AssetSubcategory::where('name', 'Car')->first();
        $subBike = AssetSubcategory::where('name', 'Bike')->first();
        $subTruck = AssetSubcategory::where('name', 'Truck')->first();

        // ── VE-1: Toyota Innova — mixed compliance (PUC overdue, fitness soon, road tax fine) ──
        $car1 = $this->asset('VE-1', [
            'asset_name'                      => 'Toyota Innova Crysta',
            'asset_category_id'               => $this->catVE->id,
            'asset_subcategory_id'            => $subCar?->id,
            'registration_number'             => 'MH12AB1234',
            'serial_number'                   => 'MAIJP8JX4L7123456',
            'manufacturer'                    => 'Toyota',
            'model'                           => 'Innova Crysta 2.4 GX',
            'model_year'                      => 2021,
            'location'                        => 'Head Office',
            'department'                      => 'Administration',
            'custodian'                       => 'Ramesh Kumar',
            'vendor_supplier'                 => 'City Toyota',
            'bill_no'                         => 'TOY-2021-001',
            'bill_amount'                     => 1850000,
            'bill_date'                       => '2021-03-15',
            'purchase_date'                   => '2021-03-15',
            'warranty_details'                => '3-year / 100,000 km manufacturer warranty',
            'warranty_lapse_date'             => today()->addDays(20),
            'warranty_reminder_before_days'   => 30,
            'puc_expiry_date'                 => today()->subDays(5),   // OVERDUE
            'puc_reminder_before_days'        => 15,
            'fitness_expiry_date'             => today()->addDays(15),  // Due in 30
            'fitness_reminder_before_days'    => 30,
            'road_tax_expiry_date'            => today()->addDays(60),  // Due in 90
            'road_tax_reminder_before_days'   => 30,
            'vehicle_obv'                     => 1850000,
            'vehicle_depreciation_percent'    => 15.00,
            'vehicle_depreciation_book_value' => 1347813,
            'inspection_required'             => true,
            'inspection_frequency_value'      => 6,
            'inspection_frequency_unit'       => 'months',
            'status'                          => 'active',
        ]);

        $this->insurance($car1, [
            'policy_number'        => 'INS-CAR-001',
            'insurer_name'         => 'HDFC ERGO',
            'policy_type'          => 'comprehensive',
            'policy_date_from'     => today()->subYear()->addDays(5),
            'policy_date_to'       => today()->addDays(25), // expiring soon
            'premium_amount'       => 42000,
            'sum_insured'          => 1500000,
            'reminder_before_days' => 30,
        ]);

        $svc = $this->service($car1, [
            'service_type'                    => 'preventive_maintenance',
            'service_date'                    => today()->subDays(120),
            'service_agency'                  => 'Toyota Authorized Service',
            'vendor_id'                       => $this->vendorAuto->id,
            'technician_name'                 => 'Mahesh Auto Workshop',
            'work_done'                       => 'Oil change, filter replacement, general checkup',
            'service_cost'                    => 8500,
            'bill_no'                         => 'SVC-CAR-001',
            'next_service_date'               => today()->subDays(30), // OVERDUE
            'service_interval_value'          => 3,
            'service_interval_unit'           => 'months',
            'meter_reading'                   => 45000,
            'mileage_reading'                 => 45000,
            'condition_rating'                => 'good',
            'next_service_reminder_before_days' => 15,
        ]);
        $this->part($svc, $car1, 'Engine Oil 5W-30 (4L)', 2400, 'Toyota Parts');
        $this->part($svc, $car1, 'Oil Filter', 350, 'Toyota Parts');

        $this->service($car1, [
            'service_type'                       => 'inspection',
            'service_date'                       => today()->subDays(5),
            'service_agency'                     => 'Toyota Authorized Service',
            'vendor_id'                          => $this->vendorAuto->id,
            'work_done'                          => 'Annual safety inspection, brake check, alignment',
            'service_cost'                       => 3500,
            'bill_no'                            => 'SVC-CAR-002',
            'next_service_date'                  => today()->addDays(25),
            'certification_expiry'               => today()->addDays(25), // cert due soon
            'certification_reminder_before_days' => 15,
            'condition_rating'                   => 'excellent',
        ]);

        $this->schedule($car1, [
            'schedule_name'     => 'Quarterly Oil Change',
            'schedule_category' => 'servicing',
            'service_type'      => 'preventive_maintenance',
            'schedule_type'     => 'date',
            'interval_value'    => 3,
            'interval_unit'     => 'months',
            'last_done_date'    => today()->subDays(120),
            'next_due_date'     => today()->subDays(30),
            'is_active'         => true,
        ]);

        $this->complaint($car1, [
            'title'             => 'AC not cooling properly',
            'description'       => 'The cabin air conditioning is not providing adequate cooling. Temperature does not drop below 26°C even on max settings.',
            'reported_by_name'  => 'Ramesh Kumar',
            'reported_by_email' => 'ramesh@company.com',
            'priority'          => 'medium',
            'status'            => 'in_progress',
            'department'        => 'Administration',
            'location'          => 'Head Office',
        ]);

        // ── VE-2: Honda Activa — PUC & fitness OVERDUE, warranty expired ──────
        $bike1 = $this->asset('VE-2', [
            'asset_name'                      => 'Honda Activa 6G',
            'asset_category_id'               => $this->catVE->id,
            'asset_subcategory_id'            => $subBike?->id,
            'registration_number'             => 'MH12CD5678',
            'manufacturer'                    => 'Honda',
            'model'                           => 'Activa 6G DLX',
            'model_year'                      => 2022,
            'location'                        => 'Warehouse',
            'department'                      => 'Delivery',
            'custodian'                       => 'Suresh Patel',
            'vendor_supplier'                 => 'Shree Honda',
            'bill_no'                         => 'HON-2022-040',
            'bill_amount'                     => 75000,
            'bill_date'                       => '2022-01-10',
            'purchase_date'                   => '2022-01-10',
            'warranty_details'                => '2-year standard warranty',
            'warranty_lapse_date'             => today()->subDays(200), // EXPIRED
            'warranty_reminder_before_days'   => 15,
            'puc_expiry_date'                 => today()->subDays(30),  // OVERDUE
            'puc_reminder_before_days'        => 15,
            'fitness_expiry_date'             => today()->subDays(10),  // OVERDUE
            'fitness_reminder_before_days'    => 15,
            'road_tax_expiry_date'            => today()->addDays(200), // fine
            'road_tax_reminder_before_days'   => 30,
            'vehicle_obv'                     => 75000,
            'vehicle_depreciation_percent'    => 10.00,
            'vehicle_depreciation_book_value' => 60750,
            'status'                          => 'active',
        ]);

        $this->insurance($bike1, [
            'policy_number'        => 'INS-BIKE-001',
            'insurer_name'         => 'New India Assurance',
            'policy_type'          => 'comprehensive',
            'policy_date_from'     => today()->subYear()->subDays(10),
            'policy_date_to'       => today()->subDays(10), // EXPIRED
            'premium_amount'       => 8500,
            'sum_insured'          => 65000,
            'reminder_before_days' => 30,
        ]);

        $this->service($bike1, [
            'service_type'                    => 'preventive_maintenance',
            'service_date'                    => today()->subDays(30),
            'service_agency'                  => 'Honda Service Center',
            'technician_name'                 => 'Ravi Mechanic',
            'work_done'                       => 'Engine oil change, chain lubrication, tyre pressure check',
            'service_cost'                    => 1200,
            'next_service_date'               => today()->addDays(60),
            'service_interval_value'          => 3,
            'service_interval_unit'           => 'months',
            'meter_reading'                   => 12000,
            'condition_rating'                => 'good',
        ]);

        $this->complaint($bike1, [
            'title'            => 'Engine noise on startup',
            'description'      => 'Unusual knocking noise observed from engine compartment during cold start.',
            'reported_by_name' => 'Suresh Patel',
            'priority'         => 'high',
            'status'           => 'open',
            'department'       => 'Delivery',
        ]);

        // ── VE-3: Mahindra XUV 700 — all compliance DUE IN 30 DAYS ───────────
        $car2 = $this->asset('VE-3', [
            'asset_name'                      => 'Mahindra XUV 700 AX7',
            'asset_category_id'               => $this->catVE->id,
            'asset_subcategory_id'            => $subCar?->id,
            'registration_number'             => 'DL8CAF9901',
            'manufacturer'                    => 'Mahindra',
            'model'                           => 'XUV 700 AX7 Diesel',
            'model_year'                      => 2023,
            'location'                        => 'Head Office',
            'department'                      => 'Management',
            'custodian'                       => 'Anjali Mehta',
            'vendor_supplier'                 => 'Mahindra First Choice',
            'bill_no'                         => 'MAH-2023-007',
            'bill_amount'                     => 2250000,
            'bill_date'                       => '2023-01-20',
            'purchase_date'                   => '2023-01-20',
            'warranty_details'                => '3-year / 1,00,000 km warranty',
            'warranty_lapse_date'             => today()->addDays(80),
            'puc_expiry_date'                 => today()->addDays(12),  // Due in 30
            'puc_reminder_before_days'        => 30,
            'fitness_expiry_date'             => today()->addDays(18),  // Due in 30
            'fitness_reminder_before_days'    => 30,
            'road_tax_expiry_date'            => today()->addDays(85),  // Due in 90
            'road_tax_reminder_before_days'   => 30,
            'vehicle_obv'                     => 2250000,
            'vehicle_depreciation_percent'    => 15.00,
            'vehicle_depreciation_book_value' => 2006250,
            'inspection_required'             => true,
            'inspection_frequency_value'      => 12,
            'inspection_frequency_unit'       => 'months',
            'status'                          => 'active',
        ]);

        $this->insurance($car2, [
            'policy_number'        => 'INS-XUV-001',
            'insurer_name'         => 'Bajaj Allianz',
            'policy_type'          => 'comprehensive',
            'policy_date_from'     => today()->subYear()->addDays(20),
            'policy_date_to'       => today()->addDays(80),
            'premium_amount'       => 68000,
            'sum_insured'          => 2000000,
            'reminder_before_days' => 30,
        ]);

        $this->amc($car2, [
            'contract_number'      => 'AMC-XUV-001',
            'vendor_name'          => 'Mahindra Extended Care',
            'vendor_id'            => $this->vendorAuto->id,
            'amc_date_from'        => today()->subYear(),
            'amc_date_to'          => today()->addDays(18), // Due in 30
            'amc_amount'           => 45000,
            'coverage_type'        => 'comprehensive',
            'reminder_before_days' => 30,
        ]);

        $svc3 = $this->service($car2, [
            'service_type'                       => 'inspection',
            'service_date'                       => today()->subDays(15),
            'service_agency'                     => 'Mahindra Service',
            'vendor_id'                          => $this->vendorAuto->id,
            'technician_name'                    => 'Mahindra Certified Tech',
            'work_done'                          => 'Full vehicle inspection, wheel balancing',
            'service_cost'                       => 5500,
            'bill_no'                            => 'SVC-XUV-001',
            'next_service_date'                  => today()->addDays(75),
            'certification_expiry'               => today()->addDays(75),
            'certification_reminder_before_days' => 30,
            'condition_rating'                   => 'excellent',
        ]);

        // ── VE-4: Tata Ace Truck — road tax OVERDUE ───────────────────────────
        $truck1 = $this->asset('VE-4', [
            'asset_name'                      => 'Tata Ace Gold CNG',
            'asset_category_id'               => $this->catVE->id,
            'asset_subcategory_id'            => $subTruck?->id,
            'registration_number'             => 'GJ01ZZ1001',
            'manufacturer'                    => 'Tata Motors',
            'model'                           => 'Ace Gold CNG BS6',
            'model_year'                      => 2020,
            'location'                        => 'Warehouse',
            'department'                      => 'Operations',
            'custodian'                       => 'Vikram Singh',
            'vendor_supplier'                 => 'Tata Commercial',
            'bill_no'                         => 'TAT-2020-015',
            'bill_amount'                     => 640000,
            'bill_date'                       => '2020-06-05',
            'purchase_date'                   => '2020-06-05',
            'warranty_lapse_date'             => today()->subDays(800), // EXPIRED long ago
            'puc_expiry_date'                 => today()->addDays(45),
            'puc_reminder_before_days'        => 30,
            'fitness_expiry_date'             => today()->addDays(120),
            'fitness_reminder_before_days'    => 30,
            'road_tax_expiry_date'            => today()->subDays(15), // OVERDUE
            'road_tax_reminder_before_days'   => 30,
            'vehicle_obv'                     => 640000,
            'vehicle_depreciation_percent'    => 20.00,
            'vehicle_depreciation_book_value' => 262144,
            'inspection_required'             => true,
            'inspection_frequency_value'      => 6,
            'inspection_frequency_unit'       => 'months',
            'status'                          => 'active',
        ]);

        $this->service($truck1, [
            'service_type'                    => 'preventive_maintenance',
            'service_date'                    => today()->subDays(60),
            'service_agency'                  => 'Tata Authorized Workshop',
            'vendor_id'                       => $this->vendorAuto->id,
            'work_done'                       => 'Full service — engine oil, coolant, filters',
            'service_cost'                    => 9800,
            'bill_no'                         => 'SVC-ACE-001',
            'next_service_date'               => today()->addDays(30),
            'mileage_reading'                 => 78000,
            'condition_rating'                => 'fair',
        ]);

        $this->complaint($truck1, [
            'title'            => 'Brake pads worn out',
            'description'      => 'Front brake pads require immediate replacement. Squeaking noise observed.',
            'reported_by_name' => 'Vikram Singh',
            'priority'         => 'critical',
            'status'           => 'acknowledged',
            'department'       => 'Operations',
        ]);

        // ── VE-5: Maruti Swift (disposed) ─────────────────────────────────────
        $this->asset('VE-5', [
            'asset_name'                      => 'Maruti Swift VXI (Old)',
            'asset_category_id'               => $this->catVE->id,
            'asset_subcategory_id'            => $subCar?->id,
            'registration_number'             => 'MH04GH7756',
            'manufacturer'                    => 'Maruti Suzuki',
            'model'                           => 'Swift VXI',
            'model_year'                      => 2015,
            'department'                      => 'Administration',
            'custodian'                       => 'HR Department',
            'bill_amount'                     => 480000,
            'purchase_date'                   => '2015-09-01',
            'vehicle_obv'                     => 480000,
            'vehicle_depreciation_percent'    => 15.00,
            'vehicle_depreciation_book_value' => 106900,
            'status'                          => 'disposed',
            'remarks'                         => 'Disposed on 2024-06-30. Sold for scrap value ₹45,000.',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // IT EQUIPMENT  (Warranty · AMC · Service History · Cert Expiry)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedITEquipment(): void
    {
        $subLaptop  = AssetSubcategory::where('name', 'Laptop')->first();
        $subDesktop = AssetSubcategory::where('name', 'Desktop')->first();
        $subServer  = AssetSubcategory::where('name', 'Server')->first();
        $subPrinter = AssetSubcategory::where('name', 'Printer')->first();
        $subSwitch  = AssetSubcategory::where('name', 'Network Switch')->first();
        $subUPS     = AssetSubcategory::where('name', 'UPS')->first();

        // ── IT-1: Dell Laptop — warranty due in 10 days ───────────────────────
        $laptop1 = $this->asset('IT-1', [
            'asset_name'                    => 'Dell Latitude 5540',
            'asset_category_id'             => $this->catIT->id,
            'asset_subcategory_id'          => $subLaptop?->id,
            'serial_number'                 => 'DLL-5540-TEST-001',
            'manufacturer'                  => 'Dell',
            'model'                         => 'Latitude 5540 i5-1345U',
            'model_year'                    => 2023,
            'location'                      => 'IT Department',
            'department'                    => 'IT',
            'custodian'                     => 'Priya Sharma',
            'vendor_supplier'               => 'Acme Tech Services',
            'bill_no'                       => 'ATS-2023-099',
            'bill_amount'                   => 95000,
            'bill_date'                     => '2023-06-01',
            'purchase_date'                 => '2023-06-01',
            'warranty_details'              => '2-year onsite warranty with next-business-day response',
            'warranty_lapse_date'           => today()->addDays(10), // Due in 30
            'warranty_reminder_before_days' => 30,
            'status'                        => 'active',
        ]);

        $this->warranty($laptop1, [
            'warranty_type'        => 'original',
            'scope'                => 'overall',
            'vendor'               => 'Dell Technologies',
            'vendor_id'            => $this->vendorIT->id,
            'bill_no'              => 'ATS-2023-099',
            'bill_amount'          => 95000,
            'details'              => '2-year onsite NBD warranty',
            'tracking_mode'        => 'time',
            'date_from'            => '2023-06-01',
            'expiry_date'          => today()->addDays(10),
            'reminder_before_days' => 30,
            'status'               => 'active',
        ]);

        $this->amc($laptop1, [
            'contract_number'      => 'AMC-IT-001',
            'vendor_name'          => 'Acme Tech Services',
            'vendor_id'            => $this->vendorIT->id,
            'amc_date_from'        => today()->subYear(),
            'amc_date_to'          => today()->addDays(20), // Due in 30
            'amc_amount'           => 12000,
            'coverage_type'        => 'non_comprehensive',
            'reminder_before_days' => 30,
        ]);

        $this->insurance($laptop1, [
            'policy_number'        => 'INS-IT-001',
            'insurer_name'         => 'Bajaj Allianz',
            'policy_type'          => 'comprehensive',
            'policy_date_from'     => today()->subMonths(3),
            'policy_date_to'       => today()->addMonths(9),
            'premium_amount'       => 3500,
            'sum_insured'          => 95000,
            'reminder_before_days' => 30,
        ]);

        $svc = $this->service($laptop1, [
            'service_type'                       => 'calibration',
            'service_date'                       => today()->subDays(45),
            'service_agency'                     => 'Dell Certified Service',
            'vendor_id'                          => $this->vendorIT->id,
            'technician_name'                    => 'Deepak IT Support',
            'work_done'                          => 'Screen calibration, battery diagnostic, thermal paste replacement',
            'service_cost'                       => 2500,
            'bill_no'                            => 'SVC-IT-001',
            'next_service_date'                  => today()->addDays(80),
            'certification_expiry'               => today()->subDays(10), // CERT EXPIRED
            'certification_reminder_before_days' => 30,
            'condition_rating'                   => 'excellent',
        ]);

        $this->complaint($laptop1, [
            'title'            => 'Battery drains in 2 hours',
            'description'      => 'Battery health degraded significantly. Device requires battery replacement under warranty.',
            'reported_by_name' => 'Priya Sharma',
            'priority'         => 'medium',
            'status'           => 'resolved',
            'resolution_summary' => 'Battery replaced under warranty. New battery tested, health at 100%.',
            'resolved_at'      => today()->subDays(5)->toDateString(),
            'department'       => 'IT',
        ]);

        // ── IT-2: HP Desktop — warranty EXPIRED ───────────────────────────────
        $desktop1 = $this->asset('IT-2', [
            'asset_name'                    => 'HP EliteDesk 800 G9',
            'asset_category_id'             => $this->catIT->id,
            'asset_subcategory_id'          => $subDesktop?->id,
            'serial_number'                 => 'HP-ED800-TEST-002',
            'manufacturer'                  => 'HP',
            'model'                         => 'EliteDesk 800 G9 MT',
            'model_year'                    => 2021,
            'location'                      => 'IT Department',
            'department'                    => 'Accounts',
            'custodian'                     => 'Mohan Iyer',
            'vendor_supplier'               => 'Acme Tech Services',
            'bill_no'                       => 'ATS-2021-055',
            'bill_amount'                   => 65000,
            'bill_date'                     => '2021-07-15',
            'purchase_date'                 => '2021-07-15',
            'warranty_lapse_date'           => today()->subDays(180), // EXPIRED
            'warranty_reminder_before_days' => 30,
            'status'                        => 'active',
        ]);

        $this->warranty($desktop1, [
            'warranty_type'        => 'original',
            'scope'                => 'overall',
            'vendor'               => 'HP Inc',
            'vendor_id'            => $this->vendorIT->id,
            'bill_no'              => 'ATS-2021-055',
            'bill_amount'          => 65000,
            'details'              => '3-year onsite warranty',
            'tracking_mode'        => 'time',
            'date_from'            => '2021-07-15',
            'expiry_date'          => today()->subDays(180),
            'reminder_before_days' => 30,
            'status'               => 'expired',
        ]);

        $this->service($desktop1, [
            'service_type'        => 'preventive_maintenance',
            'service_date'        => today()->subDays(90),
            'service_agency'      => 'Acme Tech Services',
            'vendor_id'           => $this->vendorIT->id,
            'technician_name'     => 'Rajesh Technician',
            'work_done'           => 'Dust cleaning, HDD health check, OS updates, RAM test',
            'service_cost'        => 1800,
            'bill_no'             => 'SVC-IT-002',
            'next_service_date'   => today()->addDays(275),
            'condition_rating'    => 'good',
        ]);

        // ── IT-3: Server — warranty fine, AMC active, cert expiring soon ──────
        $server1 = $this->asset('IT-3', [
            'asset_name'                    => 'Dell PowerEdge R750',
            'asset_category_id'             => $this->catIT->id,
            'asset_subcategory_id'          => $subServer?->id,
            'serial_number'                 => 'PE-R750-TEST-003',
            'manufacturer'                  => 'Dell',
            'model'                         => 'PowerEdge R750 2U Rack',
            'model_year'                    => 2022,
            'location'                      => 'Server Room',
            'department'                    => 'IT',
            'custodian'                     => 'IT Infrastructure Team',
            'vendor_supplier'               => 'Acme Tech Services',
            'bill_no'                       => 'ATS-2022-008',
            'bill_amount'                   => 480000,
            'bill_date'                     => '2022-03-10',
            'purchase_date'                 => '2022-03-10',
            'warranty_lapse_date'           => today()->addDays(240), // fine
            'warranty_reminder_before_days' => 30,
            'inspection_required'           => true,
            'inspection_frequency_value'    => 6,
            'inspection_frequency_unit'     => 'months',
            'status'                        => 'active',
        ]);

        $this->amc($server1, [
            'contract_number'      => 'AMC-SERVER-001',
            'vendor_name'          => 'Acme Tech Services',
            'vendor_id'            => $this->vendorIT->id,
            'amc_date_from'        => today()->subMonths(2),
            'amc_date_to'          => today()->addMonths(10), // fine
            'amc_amount'           => 85000,
            'coverage_type'        => 'comprehensive',
            'reminder_before_days' => 30,
        ]);

        $this->service($server1, [
            'service_type'                       => 'inspection',
            'service_date'                       => today()->subDays(10),
            'service_agency'                     => 'Acme Tech Services',
            'vendor_id'                          => $this->vendorIT->id,
            'technician_name'                    => 'Server Team Lead',
            'work_done'                          => 'Hardware diagnostic, RAID health, firmware update, cooling check',
            'service_cost'                       => 6000,
            'bill_no'                            => 'SVC-SERVER-001',
            'next_service_date'                  => today()->addDays(80),
            'certification_expiry'               => today()->addDays(20), // CERT DUE SOON
            'certification_reminder_before_days' => 30,
            'condition_rating'                   => 'good',
        ]);

        // ── IT-4: Printer — under repair ─────────────────────────────────────
        $printer1 = $this->asset('IT-4', [
            'asset_name'                    => 'HP LaserJet Pro M428fdn',
            'asset_category_id'             => $this->catIT->id,
            'asset_subcategory_id'          => $subPrinter?->id,
            'serial_number'                 => 'PRNTR-M428-TEST-004',
            'manufacturer'                  => 'HP',
            'model'                         => 'LaserJet Pro M428fdn',
            'model_year'                    => 2020,
            'location'                      => 'IT Department',
            'department'                    => 'Accounts',
            'custodian'                     => 'Accounts Team',
            'vendor_supplier'               => 'Acme Tech Services',
            'bill_no'                       => 'ATS-2020-033',
            'bill_amount'                   => 32000,
            'bill_date'                     => '2020-11-20',
            'purchase_date'                 => '2020-11-20',
            'warranty_lapse_date'           => today()->subDays(600),
            'status'                        => 'under_repair',
        ]);

        $svcPrinter = $this->service($printer1, [
            'service_type'      => 'repair',
            'service_date'      => today()->subDays(7),
            'service_agency'    => 'Acme Tech Services',
            'vendor_id'         => $this->vendorIT->id,
            'work_done'         => 'Fuser unit replacement in progress',
            'service_cost'      => 4200,
            'bill_no'           => 'SVC-PRINT-001',
            'downtime_hours'    => 72.00,
            'condition_rating'  => 'fair',
            'next_service_date' => today()->addDays(180),
        ]);
        $this->part($svcPrinter, $printer1, 'Fuser Unit RM2-5583', 3200, 'HP Parts India');

        $this->complaint($printer1, [
            'title'            => 'Paper jam and fuser error',
            'description'      => 'Frequent paper jams and E1 fuser error code appearing. Printer offline since 3 days.',
            'reported_by_name' => 'Accounts Manager',
            'priority'         => 'high',
            'status'           => 'in_progress',
            'department'       => 'Accounts',
        ]);

        // ── IT-5: Network Switch — active, no warranty issues ─────────────────
        $this->asset('IT-5', [
            'asset_name'          => 'Cisco Catalyst 2960-X 48-Port',
            'asset_category_id'   => $this->catIT->id,
            'asset_subcategory_id'=> $subSwitch?->id,
            'serial_number'       => 'CAT2960X-TEST-005',
            'manufacturer'        => 'Cisco',
            'model'               => 'WS-C2960X-48FPD-L',
            'model_year'          => 2022,
            'location'            => 'Server Room',
            'department'          => 'IT',
            'custodian'           => 'Network Team',
            'vendor_supplier'     => 'Acme Tech Services',
            'bill_no'             => 'ATS-2022-019',
            'bill_amount'         => 185000,
            'bill_date'           => '2022-05-01',
            'purchase_date'       => '2022-05-01',
            'warranty_lapse_date' => today()->addDays(400),
            'status'              => 'active',
        ]);

        // ── IT-6: UPS — inactive ──────────────────────────────────────────────
        $this->asset('IT-6', [
            'asset_name'          => 'APC Smart-UPS 3000VA',
            'asset_category_id'   => $this->catIT->id,
            'asset_subcategory_id'=> $subUPS?->id,
            'serial_number'       => 'APC-3000-TEST-006',
            'manufacturer'        => 'APC',
            'model'               => 'SMT3000I',
            'model_year'          => 2019,
            'location'            => 'Server Room',
            'department'          => 'IT',
            'custodian'           => 'IT Infrastructure Team',
            'bill_amount'         => 78000,
            'purchase_date'       => '2019-03-15',
            'warranty_lapse_date' => today()->subDays(900),
            'status'              => 'inactive',
            'remarks'             => 'Battery bank failed. Awaiting budget approval for battery replacement.',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // AIR CONDITIONERS  (AMC · Service History · Warranty)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedAirConditioners(): void
    {
        $subSplit   = AssetSubcategory::where('name', 'Split AC')->first();
        $subWindow  = AssetSubcategory::where('name', 'Window AC')->first();
        $subCassette= AssetSubcategory::where('name', 'Cassette AC')->first();

        // ── AC-1: Voltas Split AC — under repair, AMC expired ─────────────────
        $ac1 = $this->asset('AC-1', [
            'asset_name'                    => 'Voltas 1.5 Ton Split AC Vertis Gold',
            'asset_category_id'             => $this->catAC->id,
            'asset_subcategory_id'          => $subSplit?->id,
            'manufacturer'                  => 'Voltas',
            'model'                         => 'Vertis Gold 185V DZY',
            'model_year'                    => 2020,
            'location'                      => 'Conference Room',
            'department'                    => 'Admin',
            'custodian'                     => 'Admin Department',
            'vendor_supplier'               => 'Voltas Dealer',
            'bill_no'                       => 'VOL-2020-055',
            'bill_amount'                   => 42000,
            'bill_date'                     => '2020-04-01',
            'purchase_date'                 => '2020-04-01',
            'warranty_lapse_date'           => today()->subDays(1200),
            'status'                        => 'under_repair',
        ]);

        $this->amc($ac1, [
            'contract_number'      => 'AMC-AC-001',
            'vendor_name'          => 'Voltas Service Center',
            'vendor_id'            => $this->vendorAC->id,
            'amc_date_from'        => '2023-01-01',
            'amc_date_to'          => today()->subDays(30), // EXPIRED
            'amc_amount'           => 5000,
            'coverage_type'        => 'comprehensive',
            'reminder_before_days' => 30,
        ]);

        $svcAC = $this->service($ac1, [
            'service_type'      => 'repair',
            'service_date'      => today()->subDays(90),
            'service_agency'    => 'SwiftFix Solutions',
            'vendor_id'         => $this->vendorAC->id,
            'work_done'         => 'Compressor replacement, gas refill',
            'service_cost'      => 18000,
            'bill_no'           => 'SVC-AC-001',
            'next_service_date' => today()->subDays(20), // OVERDUE
            'downtime_hours'    => 48.00,
            'condition_rating'  => 'fair',
        ]);
        $this->part($svcAC, $ac1, 'AC Compressor R-410A', 14000, 'Voltas Parts');

        $this->complaint($ac1, [
            'title'            => 'Compressor replaced but AC still not cold',
            'description'      => 'After compressor replacement, cooling is inconsistent. AC struggles to reach below 24°C.',
            'reported_by_name' => 'Office Manager',
            'priority'         => 'high',
            'status'           => 'in_progress',
            'department'       => 'Admin',
        ]);

        // ── AC-2: Daikin 2 Ton — AMC expiring in 25 days ─────────────────────
        $ac2 = $this->asset('AC-2', [
            'asset_name'                    => 'Daikin 2 Ton Inverter Split AC',
            'asset_category_id'             => $this->catAC->id,
            'asset_subcategory_id'          => $subSplit?->id,
            'manufacturer'                  => 'Daikin',
            'model'                         => 'FTKF60TV 5-Star Inverter',
            'model_year'                    => 2022,
            'location'                      => 'MD Office',
            'department'                    => 'Management',
            'custodian'                     => 'Admin Department',
            'vendor_supplier'               => 'SwiftFix Solutions',
            'bill_no'                       => 'SWF-2022-088',
            'bill_amount'                   => 68000,
            'bill_date'                     => '2022-05-10',
            'purchase_date'                 => '2022-05-10',
            'warranty_lapse_date'           => today()->addDays(60),
            'status'                        => 'active',
        ]);

        $this->amc($ac2, [
            'contract_number'      => 'AMC-AC-002',
            'vendor_name'          => 'SwiftFix Solutions',
            'vendor_id'            => $this->vendorAC->id,
            'amc_date_from'        => today()->subYear()->addDays(15),
            'amc_date_to'          => today()->addDays(25), // Due in 30
            'amc_amount'           => 8000,
            'coverage_type'        => 'comprehensive',
            'reminder_before_days' => 30,
        ]);

        $this->service($ac2, [
            'service_type'      => 'preventive_maintenance',
            'service_date'      => today()->subMonths(6),
            'service_agency'    => 'SwiftFix Solutions',
            'vendor_id'         => $this->vendorAC->id,
            'technician_name'   => 'AC Technician',
            'work_done'         => 'Filter cleaning, coil washing, gas check',
            'service_cost'      => 1500,
            'bill_no'           => 'SVC-AC-002',
            'next_service_date' => today()->addDays(60),
            'condition_rating'  => 'excellent',
        ]);

        // ── AC-3: Blue Star Window AC — warranty fine, AMC fine ───────────────
        $this->asset('AC-3', [
            'asset_name'                    => 'Blue Star 1.5 Ton Window AC',
            'asset_category_id'             => $this->catAC->id,
            'asset_subcategory_id'          => $subWindow?->id,
            'manufacturer'                  => 'Blue Star',
            'model'                         => 'BWI518AAFU 5-Star',
            'model_year'                    => 2023,
            'location'                      => 'Reception',
            'department'                    => 'Admin',
            'vendor_supplier'               => 'SwiftFix Solutions',
            'bill_no'                       => 'SWF-2023-102',
            'bill_amount'                   => 38000,
            'purchase_date'                 => '2023-11-01',
            'warranty_lapse_date'           => today()->addDays(300),
            'status'                        => 'active',
        ]);

        // ── AC-4: Carrier Cassette AC — scrapped ──────────────────────────────
        $this->asset('AC-4', [
            'asset_name'          => 'Carrier 4 Ton Cassette AC (Old)',
            'asset_category_id'   => $this->catAC->id,
            'asset_subcategory_id'=> $subCassette?->id,
            'manufacturer'        => 'Carrier',
            'model'               => '42QHG048D8',
            'model_year'          => 2014,
            'location'            => 'Warehouse',
            'department'          => 'Operations',
            'bill_amount'         => 120000,
            'purchase_date'       => '2014-06-01',
            'status'              => 'scrapped',
            'remarks'             => 'Compressor beyond repair. Scrapped June 2024.',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GENERATORS  (Inspection · Service Due · Certification)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedGenerators(): void
    {
        $subDiesel   = AssetSubcategory::where('name', 'Diesel Generator')->first();
        $subPortable = AssetSubcategory::where('name', 'Portable Generator')->first();

        // ── GE-1: Kirloskar 25KVA — inactive, inspection required ─────────────
        $gen1 = $this->asset('GE-1', [
            'asset_name'                    => 'Kirloskar 25 KVA Diesel Generator',
            'asset_category_id'             => $this->catGE->id,
            'asset_subcategory_id'          => $subDiesel?->id,
            'manufacturer'                  => 'Kirloskar',
            'model'                         => 'KG1-25AS',
            'model_year'                    => 2019,
            'location'                      => 'Basement',
            'department'                    => 'Facilities',
            'custodian'                     => 'Facilities Manager',
            'vendor_supplier'               => 'BuildSafe Equipment',
            'bill_no'                       => 'BSE-2019-012',
            'bill_amount'                   => 350000,
            'bill_date'                     => '2019-08-20',
            'purchase_date'                 => '2019-08-20',
            'inspection_required'           => true,
            'inspection_frequency_value'    => 3,
            'inspection_frequency_unit'     => 'months',
            'status'                        => 'inactive',
        ]);

        $this->amc($gen1, [
            'contract_number'      => 'AMC-GEN-001',
            'vendor_name'          => 'BuildSafe Equipment',
            'vendor_id'            => $this->vendorGen->id,
            'amc_date_from'        => today()->subMonths(2),
            'amc_date_to'          => today()->addMonths(10), // fine
            'amc_amount'           => 25000,
            'coverage_type'        => 'comprehensive',
            'reminder_before_days' => 30,
        ]);

        $svcGen = $this->service($gen1, [
            'service_type'                       => 'inspection',
            'service_date'                       => today()->subDays(10),
            'service_agency'                     => 'Kirloskar Service Center',
            'vendor_id'                          => $this->vendorGen->id,
            'work_done'                          => 'Load testing, oil level check, battery test, exhaust check',
            'service_cost'                       => 6000,
            'bill_no'                            => 'SVC-GEN-001',
            'next_service_date'                  => today()->addDays(80),
            'certification_expiry'               => today()->addDays(20), // Cert due soon
            'certification_reminder_before_days' => 30,
            'condition_rating'                   => 'good',
        ]);

        $this->schedule($gen1, [
            'schedule_name'     => 'Quarterly Load Test',
            'schedule_category' => 'servicing',
            'service_type'      => 'inspection',
            'schedule_type'     => 'date',
            'interval_value'    => 3,
            'interval_unit'     => 'months',
            'last_done_date'    => today()->subDays(10),
            'next_due_date'     => today()->addDays(80),
            'is_active'         => true,
        ]);

        // ── GE-2: Cummins 62.5 KVA — active, cert expired ────────────────────
        $gen2 = $this->asset('GE-2', [
            'asset_name'                    => 'Cummins 62.5 KVA Generator',
            'asset_category_id'             => $this->catGE->id,
            'asset_subcategory_id'          => $subDiesel?->id,
            'manufacturer'                  => 'Cummins',
            'model'                         => 'C62.5D5 62.5 kVA',
            'model_year'                    => 2021,
            'location'                      => 'Warehouse',
            'department'                    => 'Operations',
            'custodian'                     => 'Maintenance Team',
            'vendor_supplier'               => 'BuildSafe Equipment',
            'bill_no'                       => 'BSE-2021-034',
            'bill_amount'                   => 750000,
            'bill_date'                     => '2021-02-28',
            'purchase_date'                 => '2021-02-28',
            'inspection_required'           => true,
            'inspection_frequency_value'    => 6,
            'inspection_frequency_unit'     => 'months',
            'status'                        => 'active',
        ]);

        $this->service($gen2, [
            'service_type'                       => 'inspection',
            'service_date'                       => today()->subDays(200),
            'service_agency'                     => 'Cummins India',
            'vendor_id'                          => $this->vendorGen->id,
            'work_done'                          => 'Annual statutory inspection and noise compliance',
            'service_cost'                       => 12000,
            'bill_no'                            => 'SVC-GEN-002',
            'next_service_date'                  => today()->subDays(20), // OVERDUE
            'certification_expiry'               => today()->subDays(20), // CERT EXPIRED
            'certification_reminder_before_days' => 30,
            'condition_rating'                   => 'good',
        ]);

        $this->schedule($gen2, [
            'schedule_name'     => 'Half-Yearly Statutory Inspection',
            'schedule_category' => 'servicing',
            'service_type'      => 'inspection',
            'schedule_type'     => 'date',
            'interval_value'    => 6,
            'interval_unit'     => 'months',
            'last_done_date'    => today()->subDays(200),
            'next_due_date'     => today()->subDays(20),
            'is_active'         => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // OFFICE EQUIPMENT  (Purchase Bills · Asset Register)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedOfficeEquipment(): void
    {
        $subUPS = AssetSubcategory::where('name', 'UPS')->first();

        $this->asset('OE-1', [
            'asset_name'          => 'Godrej Shredmaster Pro 12',
            'asset_category_id'   => $this->catOE->id,
            'manufacturer'        => 'Godrej',
            'model'               => 'Shredmaster Pro 12',
            'model_year'          => 2022,
            'location'            => 'Head Office',
            'department'          => 'Finance',
            'custodian'           => 'Finance Team',
            'vendor_supplier'     => 'Acme Tech Services',
            'bill_no'             => 'ATS-2022-077',
            'bill_amount'         => 18500,
            'bill_date'           => '2022-09-01',
            'purchase_date'       => '2022-09-01',
            'warranty_lapse_date' => today()->addDays(50),
            'status'              => 'active',
        ]);

        $this->asset('OE-2', [
            'asset_name'          => 'Konica Minolta Bizhub C300i MFD',
            'asset_category_id'   => $this->catOE->id,
            'manufacturer'        => 'Konica Minolta',
            'model'               => 'Bizhub C300i',
            'model_year'          => 2021,
            'location'            => 'IT Department',
            'department'          => 'Administration',
            'custodian'           => 'Admin Team',
            'vendor_supplier'     => 'SwiftFix Solutions',
            'bill_no'             => 'SWF-2021-045',
            'bill_amount'         => 145000,
            'bill_date'           => '2021-04-10',
            'purchase_date'       => '2021-04-10',
            'warranty_lapse_date' => today()->subDays(300),
            'status'              => 'active',
        ]);

        $this->asset('OE-3', [
            'asset_name'          => 'Wipro 600VA UPS',
            'asset_category_id'   => $this->catOE->id,
            'asset_subcategory_id'=> $subUPS?->id,
            'manufacturer'        => 'Wipro',
            'model'               => 'WiproValue 600VA',
            'model_year'          => 2023,
            'location'            => 'Reception',
            'department'          => 'Administration',
            'custodian'           => 'Reception Desk',
            'vendor_supplier'     => 'Acme Tech Services',
            'bill_no'             => 'ATS-2023-111',
            'bill_amount'         => 6500,
            'purchase_date'       => '2023-12-01',
            'warranty_lapse_date' => today()->addDays(400),
            'status'              => 'active',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // FURNITURE  (Asset Register · Purchase Bills)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedFurniture(): void
    {
        foreach ([
            ['FN-1', 'Executive Desk (MD Cabin)',       'HR', 'Head Office',  'Board Room', 'BSE-2022-FN-01', 85000,  '2022-01-15'],
            ['FN-2', 'Ergonomic Chair Set (10 Nos)',    'IT', 'IT Department','IT Department','BSE-2022-FN-02', 65000, '2022-03-10'],
            ['FN-3', 'Conference Table 12-Seater',      'Administration', 'Conference Room', null, 'BSE-2023-FN-01', 95000, '2023-06-01'],
            ['FN-4', 'Steel Almirah 4-Door (5 Nos)',    'HR',  'Warehouse',   null, 'BSE-2021-FN-01', 45000, '2021-09-20'],
        ] as [$code, $name, $dept, $loc, $cust, $bill, $amt, $date]) {
            $this->asset($code, [
                'asset_name'        => $name,
                'asset_category_id' => $this->catFN->id,
                'department'        => $dept,
                'location'          => $loc,
                'custodian'         => $cust,
                'vendor_supplier'   => 'BuildSafe Equipment',
                'bill_no'           => $bill,
                'bill_amount'       => $amt,
                'bill_date'         => $date,
                'purchase_date'     => $date,
                'status'            => 'active',
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MOBILES / TABLETS  (Asset Register · Purchase Bills)
    // ═══════════════════════════════════════════════════════════════════════════

    private function seedMobileTablets(): void
    {
        foreach ([
            ['MO-1', 'Apple iPhone 15',          'IT',       'IT Department', 'IT Manager',     'ATS-2023-MO-01', 89999, '2023-10-01', today()->addDays(280)],
            ['MO-2', 'Samsung Galaxy Tab S9',    'Marketing','Head Office',   'Marketing Team', 'ATS-2023-MO-02', 72000, '2023-08-15', today()->addDays(200)],
            ['MO-3', 'OnePlus 12 (Field Use)',   'Operations','Warehouse',    'Field Executive','ATS-2024-MO-01', 64999, '2024-01-10', today()->addDays(350)],
        ] as [$code, $name, $dept, $loc, $cust, $bill, $amt, $date, $warranty]) {
            $this->asset($code, [
                'asset_name'                    => $name,
                'asset_category_id'             => $this->catMO->id,
                'department'                    => $dept,
                'location'                      => $loc,
                'custodian'                     => $cust,
                'vendor_supplier'               => 'Acme Tech Services',
                'bill_no'                       => $bill,
                'bill_amount'                   => $amt,
                'purchase_date'                 => $date,
                'warranty_lapse_date'           => $warranty,
                'warranty_reminder_before_days' => 30,
                'status'                        => 'active',
            ]);
        }
    }
}
