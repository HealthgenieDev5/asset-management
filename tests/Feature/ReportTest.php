<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers all 16 report view pages + 14 CSV export endpoints.
 * Checks HTTP status, Content-Type header, and column header presence in CSV output.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AssetCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = AssetCategory::factory()->create(['code' => 'EQ', 'name' => 'Equipment']);
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_reports_index(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_from_csv_export(): void
    {
        $this->get(route('reports.asset-register.export'))->assertRedirect(route('login'));
    }

    // ─── Report hub page ─────────────────────────────────────────────────────

    public function test_reports_index_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk();
    }

    // ─── All view pages return 200 ────────────────────────────────────────────

    #[DataProvider('reportRouteProvider')]
    public function test_report_page_loads(string $routeName): void
    {
        $this->actingAs($this->user)
            ->get(route($routeName))
            ->assertOk();
    }

    public static function reportRouteProvider(): array
    {
        return [
            'asset-register'      => ['reports.asset-register'],
            'purchase-bills'      => ['reports.purchase-bills'],
            'warranty-expiry'     => ['reports.warranty-expiry'],
            'amc-expiry'          => ['reports.amc-expiry'],
            'insurance-expiry'    => ['reports.insurance-expiry'],
            'puc-expiry'          => ['reports.puc-expiry'],
            'fitness-expiry'      => ['reports.fitness-expiry'],
            'road-tax-expiry'     => ['reports.road-tax-expiry'],
            'inspection-due'      => ['reports.inspection-due'],
            'certification-expiry' => ['reports.certification-expiry'],
            'service-due'         => ['reports.service-due'],
            'service-history'     => ['reports.service-history'],
            'maintenance-cost'    => ['reports.maintenance-cost'],
            'vehicle-depreciation' => ['reports.vehicle-depreciation'],
            'vendor-performance'  => ['reports.vendor-performance'],
        ];
    }

    // ─── All CSV exports return 200 with correct Content-Type ────────────────

    #[DataProvider('exportRouteProvider')]
    public function test_csv_export_returns_ok_with_csv_content_type(string $routeName): void
    {
        $this->actingAs($this->user)
            ->get(route($routeName))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public static function exportRouteProvider(): array
    {
        return [
            'asset-register export'      => ['reports.asset-register.export'],
            'purchase-bills export'      => ['reports.purchase-bills.export'],
            'warranty-expiry export'     => ['reports.warranty-expiry.export'],
            'amc-expiry export'          => ['reports.amc-expiry.export'],
            'insurance-expiry export'    => ['reports.insurance-expiry.export'],
            'puc-expiry export'          => ['reports.puc-expiry.export'],
            'fitness-expiry export'      => ['reports.fitness-expiry.export'],
            'road-tax-expiry export'     => ['reports.road-tax-expiry.export'],
            'inspection-due export'      => ['reports.inspection-due.export'],
            'certification-expiry export' => ['reports.certification-expiry.export'],
            'service-due export'         => ['reports.service-due.export'],
            'service-history export'     => ['reports.service-history.export'],
            'maintenance-cost export'    => ['reports.maintenance-cost.export'],
            'vehicle-depreciation export' => ['reports.vehicle-depreciation.export'],
            'vendor-performance export'  => ['reports.vendor-performance.export'],
        ];
    }

    // ─── CSV column header validation ─────────────────────────────────────────

    private function parseCsvHeaders(string $routeName, array $params = []): array
    {
        $content = $this->actingAs($this->user)
            ->get(route($routeName, $params))
            ->streamedContent();

        // Strip UTF-8 BOM prepended for Excel compatibility
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines   = explode("\n", $content);
        return str_getcsv(trim($lines[0]));
    }

    public function test_asset_register_export_has_vendor_and_age_columns(): void
    {
        $headers = $this->parseCsvHeaders('reports.asset-register.export');

        $this->assertContains('Vendor / Supplier', $headers);
        $this->assertContains('Age', $headers);
        $this->assertContains('Status', $headers);
    }

    public function test_purchase_bills_export_has_department_column(): void
    {
        $headers = $this->parseCsvHeaders('reports.purchase-bills.export');

        $this->assertContains('Department', $headers);
        $this->assertContains('Vendor / Supplier', $headers);
        $this->assertContains('Bill Amount (₹)', $headers);
    }

    public function test_warranty_expiry_export_has_custodian_and_status(): void
    {
        $headers = $this->parseCsvHeaders('reports.warranty-expiry.export');

        $this->assertContains('Custodian', $headers);
        $this->assertContains('Location', $headers);
        $this->assertContains('Vendor / Supplier', $headers);
        $this->assertContains('Status', $headers);
        $this->assertContains('Days Remaining', $headers);
    }

    public function test_amc_expiry_export_has_department_and_location(): void
    {
        $headers = $this->parseCsvHeaders('reports.amc-expiry.export');

        $this->assertContains('Department', $headers);
        $this->assertContains('Location', $headers);
        $this->assertContains('Days Remaining', $headers);
    }

    public function test_insurance_expiry_export_has_department(): void
    {
        $headers = $this->parseCsvHeaders('reports.insurance-expiry.export');

        $this->assertContains('Department', $headers);
        $this->assertContains('Days Remaining', $headers);
    }

    public function test_puc_expiry_export_has_reg_no_and_status(): void
    {
        $headers = $this->parseCsvHeaders('reports.puc-expiry.export');

        $this->assertContains('Reg. No.', $headers);
        $this->assertContains('Status', $headers);
        $this->assertContains('Days Remaining', $headers);
    }

    public function test_fitness_expiry_export_has_reg_no_and_status(): void
    {
        $headers = $this->parseCsvHeaders('reports.fitness-expiry.export');

        $this->assertContains('Reg. No.', $headers);
        $this->assertContains('Status', $headers);
    }

    public function test_road_tax_expiry_export_has_reg_no_and_status(): void
    {
        $headers = $this->parseCsvHeaders('reports.road-tax-expiry.export');

        $this->assertContains('Reg. No.', $headers);
        $this->assertContains('Status', $headers);
    }

    public function test_service_history_export_has_department_and_technician(): void
    {
        $headers = $this->parseCsvHeaders('reports.service-history.export');

        $this->assertContains('Department', $headers);
        $this->assertContains('Technician', $headers);
    }

    public function test_maintenance_cost_export_has_agency_column(): void
    {
        $headers = $this->parseCsvHeaders('reports.maintenance-cost.export');

        $this->assertContains('Agency', $headers);
    }

    public function test_vehicle_depreciation_export_has_age_column(): void
    {
        $headers = $this->parseCsvHeaders('reports.vehicle-depreciation.export');

        $this->assertContains('Age', $headers);
        $this->assertContains('OBV (₹)', $headers);
        $this->assertContains('Book Value (₹)', $headers);
    }

    public function test_vendor_performance_export_has_email_column(): void
    {
        $headers = $this->parseCsvHeaders('reports.vendor-performance.export');

        $this->assertContains('Email', $headers);
        $this->assertContains('Vendor Name', $headers);
        $this->assertContains('Total Cost (₹)', $headers);
    }

    // ─── CSV data row validation ───────────────────────────────────────────────

    public function test_asset_register_export_includes_asset_data_rows(): void
    {
        Asset::factory()->create([
            'asset_name'       => 'Test Server',
            'asset_category_id' => $this->category->id,
            'vendor_supplier'  => 'Dell Inc',
            'status'           => 'active',
        ]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.asset-register.export'))
            ->streamedContent();

        $this->assertStringContainsString('Test Server', $content);
        $this->assertStringContainsString('Dell Inc', $content);
    }

    public function test_vendor_performance_export_includes_vendor_rows(): void
    {
        Vendor::factory()->create(['name' => 'Apex Vendor', 'email' => 'apex@vendor.com']);

        $content = $this->actingAs($this->user)
            ->get(route('reports.vendor-performance.export'))
            ->streamedContent();

        $this->assertStringContainsString('Apex Vendor', $content);
        $this->assertStringContainsString('apex@vendor.com', $content);
    }

    // ─── Expiry stats banner (controller passes correct view data) ────────────

    public function test_warranty_expiry_view_receives_stat_variables(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.warranty-expiry'));

        $response->assertViewHasAll(['statExpired', 'stat30', 'stat90']);
    }

    public function test_puc_expiry_view_receives_stat_variables(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.puc-expiry'));

        $response->assertViewHasAll(['statExpired', 'stat30', 'stat90']);
    }

    public function test_amc_expiry_view_receives_stat_variables(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.amc-expiry'));

        $response->assertViewHasAll(['statExpired', 'stat30', 'stat90']);
    }

    // ─── Filter regression: filter params forwarded to export ─────────────────

    public function test_asset_register_export_accepts_status_filter(): void
    {
        Asset::factory()->create([
            'asset_category_id' => $this->category->id,
            'status'            => 'active',
        ]);
        Asset::factory()->create([
            'asset_category_id' => $this->category->id,
            'status'            => 'disposed',
        ]);

        $content = $this->actingAs($this->user)
            ->get(route('reports.asset-register.export', ['status' => 'disposed']))
            ->streamedContent();

        $this->assertStringContainsString('Disposed', $content);
        $this->assertStringNotContainsString('Active', $content);
    }
}
