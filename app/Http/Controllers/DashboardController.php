<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetComplaint;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
use App\Models\AssetWarranty;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now  = now()->toDateString();
        $in7  = now()->addDays(7)->toDateString();
        $in30 = now()->addDays(30)->toDateString();

        // ── Asset status counts ──────────────────────────────────────────────
        $statusCounts = Asset::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $assetStats = [
            'total'        => Asset::count(),
            'active'       => $statusCounts->get('active', 0),
            'under_repair' => $statusCounts->get('under_repair', 0),
            'disposed'     => $statusCounts->get('disposed', 0) + $statusCounts->get('written_off', 0),
            'inactive'     => $statusCounts->get('inactive', 0),
            'scrapped'     => $statusCounts->get('scrapped', 0),
        ];

        // ── Total asset value (purchase cost) ────────────────────────────────
        $totalAssetValue = Asset::whereNotNull('bill_amount')->sum('bill_amount');

        // ── Expiry counts per source ─────────────────────────────────────────
        // Active statuses — inactive/disposed/scrapped assets excluded from all expiry counts
        $activeStatuses = "('active', 'under_repair')";

        $warranty    = $this->expiryBuckets('assets', 'warranty_lapse_date', $now, $in7, $in30, "deleted_at IS NULL AND status IN $activeStatuses");
        $extWarranty = $this->expiryBucketsJoined('asset_warranties', 'expiry_date', $now, $in7, $in30, "warranty_type = 'extended'", $activeStatuses);
        $amc         = $this->expiryBucketsJoined('asset_amc_contracts', 'amc_date_to', $now, $in7, $in30, '1=1', $activeStatuses);
        $insurance   = $this->expiryBucketsJoined('asset_insurance_policies', 'policy_date_to', $now, $in7, $in30, '1=1', $activeStatuses);

        // Vehicle compliance — only active vehicles
        $puc     = $this->expiryBuckets('assets', 'puc_expiry_date',      $now, $in7, $in30, "deleted_at IS NULL AND puc_expiry_date IS NOT NULL      AND status IN $activeStatuses");
        $fitness = $this->expiryBuckets('assets', 'fitness_expiry_date',  $now, $in7, $in30, "deleted_at IS NULL AND fitness_expiry_date IS NOT NULL  AND status IN $activeStatuses");
        $roadTax = $this->expiryBuckets('assets', 'road_tax_expiry_date', $now, $in7, $in30, "deleted_at IS NULL AND road_tax_expiry_date IS NOT NULL AND status IN $activeStatuses");

        // All warranties from asset_warranties table (active assets only)
        $allWarranties = $this->expiryBucketsJoined('asset_warranties', 'expiry_date', $now, $in7, $in30, '1=1', $activeStatuses);

        // Part warranties — via asset_services join
        $partWarranties = $this->expiryBucketsPartsJoined($now, $in7, $in30, $activeStatuses);

        // Date-based maintenance schedules (active assets only)
        $schedules = $this->expiryBucketsSchedulesJoined($now, $in7, $in30, $activeStatuses);

        // Aggregated expiry totals — active/under_repair assets only
        $reminderStats = [
            'expired'     => $warranty['expired'] + $allWarranties['expired'] + $amc['expired'] + $insurance['expired']
                           + $puc['expired'] + $fitness['expired'] + $roadTax['expired']
                           + $partWarranties['expired'] + $schedules['expired'],
            'expiring_7'  => $warranty['in7']  + $allWarranties['in7']  + $amc['in7']  + $insurance['in7']
                           + $puc['in7'] + $fitness['in7'] + $roadTax['in7']
                           + $partWarranties['in7'] + $schedules['in7'],
            'expiring_30' => $warranty['in30'] + $allWarranties['in30'] + $amc['in30'] + $insurance['in30']
                           + $puc['in30'] + $fitness['in30'] + $roadTax['in30']
                           + $partWarranties['in30'] + $schedules['in30'],
        ];

        $serviceDue = $this->expiryBuckets('asset_services', 'next_service_date', $now, $in7, $in30, 'deleted_at IS NULL AND next_service_date IS NOT NULL');
        $certExpiry = $this->expiryBuckets('asset_services', 'certification_expiry', $now, $in7, $in30, 'deleted_at IS NULL AND certification_expiry IS NOT NULL');

        // ── Assets by category (for donut chart) ────────────────────────────
        $assetsByCategory = Asset::select('asset_categories.name', DB::raw('count(*) as count'))
            ->join('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
            ->whereNull('assets.deleted_at')
            ->groupBy('asset_categories.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => (int) $r->count]);

        // ── Assets added per month (last 6 months, for bar chart) ────────────
        $assetsByMonth = Asset::selectRaw('DATE_FORMAT(created_at, "%b %Y") as month, DATE_FORMAT(created_at, "%Y-%m") as sort_key, count(*) as count')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupByRaw('DATE_FORMAT(created_at, "%b %Y"), DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('sort_key')
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'count' => (int) $r->count]);

        // ── Service cost last 6 months (for area chart) ──────────────────────
        $serviceCostByMonth = AssetService::selectRaw('DATE_FORMAT(service_date, "%b %Y") as month, DATE_FORMAT(service_date, "%Y-%m") as sort_key, SUM(service_cost) as total')
            ->whereNull('deleted_at')
            ->whereNotNull('service_date')
            ->where('service_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupByRaw('DATE_FORMAT(service_date, "%b %Y"), DATE_FORMAT(service_date, "%Y-%m")')
            ->orderBy('sort_key')
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'total' => (float) ($r->total ?? 0)]);

        // ── Complaint stats ──────────────────────────────────────────────────
        $complaintStats = AssetComplaint::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $complaints = [
            'open'        => (int) ($complaintStats->get('open', 0) + $complaintStats->get('acknowledged', 0) + $complaintStats->get('in_progress', 0)),
            'resolved'    => (int) ($complaintStats->get('resolved', 0) + $complaintStats->get('closed', 0)),
            'total'       => (int) $complaintStats->sum(),
        ];

        // ── Recent assets (last 8) ───────────────────────────────────────────
        $recentAssets = Asset::with('category')
            ->latest()
            ->limit(8)
            ->get();

        // ── Upcoming expiries (next 30 days, all sources) ────────────────────
        $upcomingExpiries = $this->upcomingExpiries($now, $in30);

        // ── Overdue maintenance schedules ────────────────────────────────────
        $overdueSchedules = DB::table('asset_maintenance_schedules as s')
            ->join('assets as a', 'a.id', '=', 's.asset_id')
            ->whereNull('a.deleted_at')
            ->where('s.is_active', 1)
            ->whereNotNull('s.next_due_date')
            ->where('s.next_due_date', '<', $now)
            ->select('a.id as asset_id', 'a.asset_code', 'a.asset_name', 's.schedule_name', 's.next_due_date')
            ->orderBy('s.next_due_date')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'assetStats',
            'totalAssetValue',
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
            'assetsByCategory',
            'assetsByMonth',
            'serviceCostByMonth',
            'complaints',
            'recentAssets',
            'upcomingExpiries',
            'overdueSchedules',
        ));
    }

    private function expiryBuckets(string $table, string $col, string $now, string $in7, string $in30, string $where = '1=1'): array
    {
        $rows = DB::select("
            SELECT
                SUM(CASE WHEN `{$col}` < :now1  THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN `{$col}` >= :now2 AND `{$col}` <= :in7  THEN 1 ELSE 0 END) AS in7,
                SUM(CASE WHEN `{$col}` > :in7b  AND `{$col}` <= :in30 THEN 1 ELSE 0 END) AS in30
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

    // For related tables (warranties, AMC, insurance) — joins assets to filter by status
    private function expiryBucketsJoined(string $table, string $col, string $now, string $in7, string $in30, string $where, string $activeStatuses): array
    {
        $rows = DB::select("
            SELECT
                SUM(CASE WHEN t.`{$col}` < :now1  THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN t.`{$col}` >= :now2 AND t.`{$col}` <= :in7  THEN 1 ELSE 0 END) AS in7,
                SUM(CASE WHEN t.`{$col}` > :in7b  AND t.`{$col}` <= :in30 THEN 1 ELSE 0 END) AS in30
            FROM `{$table}` t
            INNER JOIN assets a ON a.id = t.asset_id
            WHERE a.deleted_at IS NULL
              AND a.status IN {$activeStatuses}
              AND t.`{$col}` IS NOT NULL
              AND {$where}
        ", ['now1' => $now, 'now2' => $now, 'in7' => $in7, 'in7b' => $in7, 'in30' => $in30]);

        $r = $rows[0] ?? null;
        return [
            'expired' => (int) ($r->expired ?? 0),
            'in7'     => (int) ($r->in7     ?? 0),
            'in30'    => (int) ($r->in30    ?? 0),
        ];
    }

    // Part warranties — asset_service_parts → asset_services → assets
    private function expiryBucketsPartsJoined(string $now, string $in7, string $in30, string $activeStatuses): array
    {
        $rows = DB::select("
            SELECT
                SUM(CASE WHEN p.warranty_till < :now1  THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN p.warranty_till >= :now2 AND p.warranty_till <= :in7  THEN 1 ELSE 0 END) AS in7,
                SUM(CASE WHEN p.warranty_till > :in7b  AND p.warranty_till <= :in30 THEN 1 ELSE 0 END) AS in30
            FROM asset_service_parts p
            INNER JOIN asset_services s ON s.id = p.asset_service_id
            INNER JOIN assets a ON a.id = s.asset_id
            WHERE a.deleted_at IS NULL
              AND a.status IN {$activeStatuses}
              AND p.warranty_till IS NOT NULL
        ", ['now1' => $now, 'now2' => $now, 'in7' => $in7, 'in7b' => $in7, 'in30' => $in30]);

        $r = $rows[0] ?? null;
        return [
            'expired' => (int) ($r->expired ?? 0),
            'in7'     => (int) ($r->in7     ?? 0),
            'in30'    => (int) ($r->in30    ?? 0),
        ];
    }

    // Maintenance schedules — joins assets to filter by status
    private function expiryBucketsSchedulesJoined(string $now, string $in7, string $in30, string $activeStatuses): array
    {
        $rows = DB::select("
            SELECT
                SUM(CASE WHEN sch.next_due_date < :now1  THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN sch.next_due_date >= :now2 AND sch.next_due_date <= :in7  THEN 1 ELSE 0 END) AS in7,
                SUM(CASE WHEN sch.next_due_date > :in7b  AND sch.next_due_date <= :in30 THEN 1 ELSE 0 END) AS in30
            FROM asset_maintenance_schedules sch
            INNER JOIN assets a ON a.id = sch.asset_id
            WHERE a.deleted_at IS NULL
              AND a.status IN {$activeStatuses}
              AND sch.schedule_type = 'date'
              AND sch.is_active = 1
              AND sch.next_due_date IS NOT NULL
        ", ['now1' => $now, 'now2' => $now, 'in7' => $in7, 'in7b' => $in7, 'in30' => $in30]);

        $r = $rows[0] ?? null;
        return [
            'expired' => (int) ($r->expired ?? 0),
            'in7'     => (int) ($r->in7     ?? 0),
            'in30'    => (int) ($r->in30    ?? 0),
        ];
    }

    private function upcomingExpiries(string $now, string $in30): \Illuminate\Support\Collection
    {
        $active = ['active', 'under_repair'];
        $items  = collect();

        Asset::whereNotNull('warranty_lapse_date')
            ->whereBetween('warranty_lapse_date', [$now, $in30])
            ->whereNull('deleted_at')
            ->whereIn('status', $active)
            ->select('id', 'asset_code', 'asset_name', 'warranty_lapse_date as expiry_date')
            ->get()
            ->each(fn($a) => $items->push(['asset_id' => $a->id, 'asset_code' => $a->asset_code, 'asset_name' => $a->asset_name, 'type' => 'Warranty', 'expiry_date' => $a->expiry_date, 'tab' => 'warranty']));

        AssetWarranty::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$now, $in30])
            ->whereHas('asset', fn($q) => $q->whereIn('status', $active))
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($w) => $items->push(['asset_id' => $w->asset_id, 'asset_code' => $w->asset?->asset_code, 'asset_name' => $w->asset?->asset_name, 'type' => 'Ext. Warranty', 'expiry_date' => $w->expiry_date, 'tab' => 'warranty']));

        AssetAmcContract::whereNotNull('amc_date_to')
            ->whereBetween('amc_date_to', [$now, $in30])
            ->whereHas('asset', fn($q) => $q->whereIn('status', $active))
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($c) => $items->push(['asset_id' => $c->asset_id, 'asset_code' => $c->asset?->asset_code, 'asset_name' => $c->asset?->asset_name, 'type' => 'AMC', 'expiry_date' => $c->amc_date_to, 'tab' => 'amc']));

        AssetInsurancePolicy::whereNotNull('policy_date_to')
            ->whereBetween('policy_date_to', [$now, $in30])
            ->whereHas('asset', fn($q) => $q->whereIn('status', $active))
            ->with('asset:id,asset_code,asset_name')
            ->get()
            ->each(fn($p) => $items->push(['asset_id' => $p->asset_id, 'asset_code' => $p->asset?->asset_code, 'asset_name' => $p->asset?->asset_name, 'type' => 'Insurance', 'expiry_date' => $p->policy_date_to, 'tab' => 'insurance']));

        return $items->sortBy('expiry_date')->values()->take(20);
    }
}
