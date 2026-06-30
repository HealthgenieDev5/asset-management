<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // --- Auth guard ---

    public function test_guests_are_redirected_from_vendors_index(): void
    {
        $this->get(route('vendors.index'))->assertRedirect(route('login'));
    }

    // --- Index ---

    public function test_authenticated_user_can_list_vendors(): void
    {
        Vendor::factory()->count(3)->create();

        $this->actingAs($this->user)
            ->get(route('vendors.index'))
            ->assertOk()
            ->assertViewIs('vendors.index');
    }

    // --- Search filter ---

    public function test_search_filter_narrows_vendor_results(): void
    {
        Vendor::factory()->create(['name' => 'Acme Corp']);
        Vendor::factory()->create(['name' => 'Beta Solutions']);

        $response = $this->actingAs($this->user)
            ->get(route('vendors.index', ['search' => 'Acme']));

        $response->assertOk()->assertSee('Acme Corp')->assertDontSee('Beta Solutions');
    }

    public function test_status_filter_shows_only_active_vendors(): void
    {
        Vendor::factory()->create(['name' => 'Active Co', 'status' => 'active']);
        Vendor::factory()->create(['name' => 'Inactive Co', 'status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->get(route('vendors.index', ['status' => 'active']));

        $response->assertOk()->assertSee('Active Co')->assertDontSee('Inactive Co');
    }

    // --- Store ---

    public function test_can_create_a_vendor(): void
    {
        $response = $this->actingAs($this->user)->post(route('vendors.store'), [
            'name'   => 'New Vendor Ltd',
            'type'   => 'company',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('vendors', ['name' => 'New Vendor Ltd', 'type' => 'company']);
        $response->assertRedirect(route('vendors.index'));
    }

    public function test_vendor_store_requires_name(): void
    {
        $this->actingAs($this->user)
            ->post(route('vendors.store'), ['type' => 'company', 'status' => 'active'])
            ->assertSessionHasErrors('name');
    }

    public function test_vendor_store_requires_unique_name(): void
    {
        Vendor::factory()->create(['name' => 'Existing Vendor']);

        $this->actingAs($this->user)
            ->post(route('vendors.store'), [
                'name'   => 'Existing Vendor',
                'type'   => 'company',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');
    }

    // --- Update ---

    public function test_can_update_a_vendor(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->user)->put(route('vendors.update', $vendor), [
            'name'   => 'New Name',
            'type'   => $vendor->type,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'New Name', 'status' => 'inactive']);
    }

    // --- Delete ---

    public function test_can_soft_delete_a_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('vendors.destroy', $vendor))
            ->assertRedirect();

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    // --- Show ---

    public function test_can_view_vendor_detail_page(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->user)
            ->get(route('vendors.show', $vendor))
            ->assertOk();
    }

    // --- Export ---

    public function test_vendor_export_returns_csv(): void
    {
        Vendor::factory()->count(2)->create();

        $this->actingAs($this->user)
            ->get(route('vendors.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
