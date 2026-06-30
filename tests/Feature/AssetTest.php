<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
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

    // --- Auth guard ---

    public function test_guests_are_redirected_from_asset_index(): void
    {
        $this->get(route('assets.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_asset_create(): void
    {
        $this->get(route('assets.create'))->assertRedirect(route('login'));
    }

    // --- Index ---

    public function test_authenticated_user_can_list_assets(): void
    {
        Asset::factory()->count(3)->create(['asset_category_id' => $this->category->id]);

        $this->actingAs($this->user)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertViewIs('assets.index')
            ->assertViewHas('assets');
    }

    public function test_asset_list_is_empty_when_no_assets_exist(): void
    {
        $response = $this->actingAs($this->user)->get(route('assets.index'));
        $response->assertOk();
        $this->assertEquals(0, $response->viewData('assets')->total());
    }

    // --- Search / Filter ---

    public function test_search_filter_narrows_results_by_name(): void
    {
        Asset::factory()->create([
            'asset_name'       => 'Dell Laptop Pro',
            'asset_category_id' => $this->category->id,
        ]);
        Asset::factory()->create([
            'asset_name'       => 'HP Printer',
            'asset_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('assets.index', ['search' => 'Dell']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('assets')->total());
    }

    public function test_status_filter_returns_only_matching_assets(): void
    {
        Asset::factory()->create(['asset_category_id' => $this->category->id, 'status' => 'active']);
        Asset::factory()->create(['asset_category_id' => $this->category->id, 'status' => 'disposed']);

        $response = $this->actingAs($this->user)
            ->get(route('assets.index', ['status' => 'disposed']));

        $this->assertEquals(1, $response->viewData('assets')->total());
    }

    public function test_category_filter_returns_only_matching_category(): void
    {
        $other = AssetCategory::factory()->create(['code' => 'VH']);
        Asset::factory()->create(['asset_category_id' => $this->category->id]);
        Asset::factory()->create(['asset_category_id' => $other->id]);

        $response = $this->actingAs($this->user)
            ->get(route('assets.index', ['category_id' => $this->category->id]));

        $this->assertEquals(1, $response->viewData('assets')->total());
    }

    // --- Create / Store ---

    public function test_authenticated_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('assets.create'))
            ->assertOk()
            ->assertViewIs('assets.create');
    }

    public function test_storing_valid_asset_creates_record_and_redirects(): void
    {
        $response = $this->actingAs($this->user)->post(route('assets.store'), [
            'asset_name'       => 'Test Laptop',
            'asset_category_id' => $this->category->id,
            'status'           => 'active',
        ]);

        $this->assertDatabaseHas('assets', [
            'asset_name'       => 'Test Laptop',
            'asset_category_id' => $this->category->id,
        ]);

        $asset = Asset::firstWhere('asset_name', 'Test Laptop');
        $response->assertRedirect(route('assets.show', $asset));
    }

    public function test_asset_code_is_auto_generated_on_store(): void
    {
        $this->actingAs($this->user)->post(route('assets.store'), [
            'asset_name'       => 'Auto Code Asset',
            'asset_category_id' => $this->category->id,
            'status'           => 'active',
        ]);

        $asset = Asset::firstWhere('asset_name', 'Auto Code Asset');
        $this->assertStringStartsWith($this->category->code . '-', $asset->asset_code);
    }

    public function test_store_requires_asset_name(): void
    {
        $this->actingAs($this->user)
            ->post(route('assets.store'), ['asset_category_id' => $this->category->id])
            ->assertSessionHasErrors('asset_name');
    }

    public function test_store_requires_category(): void
    {
        $this->actingAs($this->user)
            ->post(route('assets.store'), ['asset_name' => 'No Category Asset'])
            ->assertSessionHasErrors('asset_category_id');
    }

    // --- Show ---

    public function test_authenticated_user_can_view_asset_detail(): void
    {
        $asset = Asset::factory()->create(['asset_category_id' => $this->category->id]);

        $this->actingAs($this->user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertViewIs('assets.show');
    }

    public function test_viewing_nonexistent_asset_returns_404(): void
    {
        $this->actingAs($this->user)
            ->get(route('assets.show', 99999))
            ->assertNotFound();
    }

    // --- Update ---

    public function test_authenticated_user_can_update_an_asset(): void
    {
        $asset = Asset::factory()->create(['asset_category_id' => $this->category->id]);

        $this->actingAs($this->user)->put(route('assets.update', $asset), [
            'asset_name'       => 'Updated Name',
            'asset_category_id' => $this->category->id,
            'status'           => 'inactive',
        ]);

        $this->assertDatabaseHas('assets', [
            'id'         => $asset->id,
            'asset_name' => 'Updated Name',
            'status'     => 'inactive',
        ]);
    }

    // --- Delete (soft) ---

    public function test_authenticated_user_can_soft_delete_an_asset(): void
    {
        $asset = Asset::factory()->create(['asset_category_id' => $this->category->id]);

        $this->actingAs($this->user)
            ->delete(route('assets.destroy', $asset))
            ->assertRedirect();

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_soft_deleted_asset_is_not_visible_in_index(): void
    {
        $asset = Asset::factory()->create(['asset_category_id' => $this->category->id]);
        $asset->delete();

        $response = $this->actingAs($this->user)->get(route('assets.index'));
        $this->assertEquals(0, $response->viewData('assets')->total());
    }

    // --- CSV Export ---

    public function test_asset_export_returns_csv_file(): void
    {
        Asset::factory()->create(['asset_category_id' => $this->category->id]);

        $this->actingAs($this->user)
            ->get(route('reports.asset-register.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_asset_export_csv_has_correct_headers(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.asset-register.export'));

        $content = $response->streamedContent();
        $lines   = explode("\n", ltrim($content, "\xEF\xBB\xBF"));
        $headers = str_getcsv($lines[0]);

        $this->assertContains('Code', $headers);
        $this->assertContains('Asset Name', $headers);
        $this->assertContains('Vendor / Supplier', $headers);
        $this->assertContains('Age', $headers);
        $this->assertContains('Status', $headers);
    }
}
