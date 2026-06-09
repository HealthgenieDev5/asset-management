<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Asset status counts ──────────────────────────────────────────────
        $statusCounts = Asset::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $assetStats = [
            'total'            => Asset::count(),
            'active'           => $statusCounts->get('active', 0),
            'under_repair'     => $statusCounts->get('under_repair', 0),
            'under_maintenance'=> $statusCounts->get('under_maintenance', 0),
            'disposed'         => $statusCounts->get('disposed', 0) + $statusCounts->get('written_off', 0),
            'inactive'         => $statusCounts->get('inactive', 0),
        ];

        $now  = now()->toDateString();
        $in7  = now()->addDays(7)->toDateString();
        $in30 = now()->addDays(30)->toDateString();

        // ── Expiry counts per source ─────────────────────────────────────────
        // Original warranty (asset.warranty_lapse_date)
        $warranty = $this->expiryBuckets('assets', 'warranty_lapse_date', $now, $in7, $in30, 'deleted_at IS NULL');

        // Extended warranty
        $extWarranty = $this->expiryBuckets('asset_extended_warranties', 'extended_warranty_date_to', $now, $in7, $in30);

        // AMC contracts
        $amc = $this->expiryBuckets('asset_amc_contracts', 'amc_date_to', $now, $in7, $in30);

        // Insurance policies
        $insurance = $this->expiryBuckets('asset_insurance_policies', 'policy_date_to', $now, $in7, $in30);

        // Vehicle compliance — three date columns on assets table
        $puc     = $this->expiryBuckets('assets', 'puc_expiry_date', $now, $in7, $in30, 'deleted_at IS NULL AND puc_expiry_date IS NOT NULL');
        $fitness = $this->expiryBuckets('assets', 'fitness_expiry_date', $now, $in7, $in30, 'deleted_at IS NULL AND fitness_expiry_date IS NOT NULL');
        $roadTax = $this->expiryBuckets('assets', 'road_tax_expiry_date', $now, $in7, $in30, 'deleted_at IS NULL AND road_tax_expiry_date IS NOT NULL');

        // ── Aggregated reminder totals ───────────────────────────────────────
        $reminderStats = [
            'expired'    => $warranty['expired'] + $extWarranty['expired'] + $amc['expired'] + $insurance['expired'],
            'expiring_7' => $warranty['in7'] + $extWarranty['in7'] + $amc['in7'] + $insurance['in7'],
            'expiring_30'=> $warranty['in30'] + $extWarranty['in30'] + $amc['in30'] + $insurance['in30'],
        ];

        // ── Service-due counts ──────────────────────────────────────────────
        $serviceDue = $this->expiryBuckets('asset_services', 'next_service_date', $now, $in7, $in30, 'deleted_at IS NULL AND next_service_date IS NOT NULL');
        $certExpiry = $this->expiryBuckets('asset_services', 'certification_expiry', $now, $in7, $in30, 'deleted_at IS NULL AND certification_expiry IS NOT NULL');

        // ── Recent assets (last 8) ───────────────────────────────────────────
        $recentAssets = Asset::with('category')
            ->latest()
            ->limit(8)
            ->get();

        // ── Assets expiring in next 30 days (all types, flattened) ───────────
        $upcomingExpiries = $this->upcomingExpiries($now, $in30);

        return view('dashboard', compact(
            'assetStats',
            'warranty',
            'extWarranty',
            'amc',
            'insurance',
            'puc',
            'fitness',
            'roadTax',
            'serviceDue',
            'certExpiry',
            'reminderStats',
            'recentAssets',
            'upcomingExpiries',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return [expired, in7, in30] counts for a single date column in a table.
     * All buckets are mutually exclusive: expired < today, in7 = today..+7, in30 = +8..+30.
     */
    private function expiryBuckets(string $table, string $col, string $now, string $in7, string $in30, string $where = '1=1'): array
    {
        $rows = DB::select("
            SELECT
                SUM(CASE WHEN `{$col}` < :now1   THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN `{$col}` >= :now2
                          AND `{$col}` <= :in7   THEN 1 ELSE 0 END) AS in7,
                SUM(CASE WHEN `{$col}` > :in7b
                          AND `{$col}` <= :in30  THEN 1 ELSE 0 END) AS in30
            FROM `{$table}`
            WHERE {$where}
              AND `{$col}` IS NOT NULL
        ", ['now1' => $now, 'now2' => $now, 'in7' => $in7, 'in7b' => $in7, 'in30' => $in30]);

        $r = $rows[0] ?? null;
        return [
            'expired' => (int) ($r->expired ?? 0),
            'in7'     => (int) ($r->in7     ?? 0),
            'in30'    => (int) ($r->in30    ?? 0),
        ];
    }

    /**
     * Collect up to 20 soonest upcoming expiries (all sources) within the next 30 days.
     */
    private function upcomingExpiries(string $now, string $in30): \Illuminate\Support\Collection
    {
        $items = collect();

        // Original warranty
        Asset::whereNotNull('warranty_lapse_date')
            ->whereBetween('warranty_lapse_date', [$now, $in30])
            ->whereNull('deleted_at')
            ->select('id', 'asset_code', 'asset_name', 'warranty_lapse_date as expiry_date')
            ->get()
            ->each(fn($a) => $items->push([
                'asset_id'    => $a->id,
                'asset_code'  => $a->asset_code,
                'asset_name'  => $a->asset_name,
                'type'        => 'Original Warranty',
                'expiry_date' => $a->expiry_date,
                'tab'         => 'warranty',
            ]));

        // Extended warranty
        AssetExtendedWarranty::whereNotNull('extended_warranty_date_to')
            ->whereBetween('extended_warranty_date_to', [$now, $in30])
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($ew) => $items->push([
                'asset_id'    => $ew->asset_id,
                'asset_code'  => $ew->asset?->asset_code,
                'asset_name'  => $ew->asset?->asset_name,
                'type'        => 'Extended Warranty',
                'expiry_date' => $ew->extended_warranty_date_to,
                'tab'         => 'ext-warranty',
            ]));

        // AMC
        AssetAmcContract::whereNotNull('amc_date_to')
            ->whereBetween('amc_date_to', [$now, $in30])
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($c) => $items->push([
                'asset_id'    => $c->asset_id,
                'asset_code'  => $c->asset?->asset_code,
                'asset_name'  => $c->asset?->asset_name,
                'type'        => 'AMC Contract',
                'expiry_date' => $c->amc_date_to,
                'tab'         => 'amc',
            ]));

        // Insurance
        AssetInsurancePolicy::whereNotNull('policy_date_to')
            ->whereBetween('policy_date_to', [$now, $in30])
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($p) => $items->push([
                'asset_id'    => $p->asset_id,
                'asset_code'  => $p->asset?->asset_code,
                'asset_name'  => $p->asset?->asset_name,
                'type'        => 'Insurance',
                'expiry_date' => $p->policy_date_to,
                'tab'         => 'insurance',
            ]));

        return $items->sortBy('expiry_date')->values()->take(20);
    }
}
