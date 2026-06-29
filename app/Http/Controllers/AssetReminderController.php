<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssetReminderController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        $filter     = $request->query('filter', 'upcoming');
        $type       = $request->query('type', '');
        $search     = $request->query('search', '');
        $categoryId = $request->query('asset_category_id', '');
        $subcatId   = $request->query('asset_subcategory_id', '');
        $sort       = in_array($request->query('sort'), ['asset', 'category', 'name', 'expiry', 'days_left', 'status'])
            ? $request->query('sort') : 'days_left';
        $direction  = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $categories    = AssetCategory::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $subcategories = AssetSubcategory::when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'asset_category_id']);

        // Outer query wraps the full UNION so we can filter/sort/paginate uniformly
        $base = DB::query()->fromSub($this->buildUnion($search, $categoryId, $subcatId), 'r');

        if ($type) {
            $base->where('type_slug', $type);
        }

        // Count all three tab buckets before applying the tab filter
        $counts = $this->computeCounts(clone $base);

        if ($filter === 'upcoming') {
            $base->whereDate('expiry_date', '>=', today());
        } elseif ($filter === 'expired') {
            $base->whereDate('expiry_date', '<', today());
        }

        $sortCol = match ($sort) {
            'asset'    => 'asset_name',
            'category' => 'type_label',
            'name'     => 'name',
            default    => 'expiry_date',   // days_left, expiry, status all sort by date
        };
        $base->orderBy($sortCol, $direction);

        $items = $base->paginate(self::PER_PAGE)->withQueryString()
            ->through(fn($row) => $this->rowToItem($row));

        $typeOptions = [
            'warranty'          => 'Warranty',
            'extended_warranty' => 'Extended Warranty',
            'amc'               => 'AMC',
            'insurance'         => 'Insurance',
            'part_warranty'     => 'Part Warranty',
            'schedule'          => 'Schedule',
            'puc'               => 'PUC',
            'fitness'           => 'Fitness',
            'road_tax'          => 'Road Tax',
        ];

        return view('asset-reminders.index', compact(
            'items', 'counts', 'filter', 'type', 'search',
            'typeOptions', 'categories', 'subcategories',
            'categoryId', 'subcatId', 'sort', 'direction'
        ));
    }

    // ── UNION ALL builder ─────────────────────────────────────────────────────

    private function buildUnion(string $search, string $categoryId, string $subcatId)
    {
        $queries = [];

        // 1. Asset-level warranty_lapse_date
        $queries[] = $this->applyAssetFilters(
            DB::table('assets as a')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')->whereNotNull('a.warranty_lapse_date')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("'warranty' as type_slug, 'Warranty' as type_label"),
                    DB::raw("'bg-blue-400/10 text-blue-400' as category_color, 'shield-check' as icon"),
                    DB::raw("COALESCE(NULLIF(a.warranty_details,''),'Asset Warranty') as name"),
                    DB::raw("'From asset record' as source"),
                    'a.warranty_lapse_date as expiry_date',
                    DB::raw("'overview' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 2. asset_warranties (original / extended / part scope — all use expiry_date)
        $queries[] = $this->applyAssetFilters(
            DB::table('asset_warranties as aw')
                ->join('assets as a', 'a.id', '=', 'aw.asset_id')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')->whereNotNull('aw.expiry_date')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("CASE WHEN aw.scope='part' THEN 'part_warranty' ELSE 'warranty' END as type_slug"),
                    DB::raw("CASE WHEN aw.scope='part' THEN 'Part Warranty' ELSE 'Warranty' END as type_label"),
                    DB::raw("CASE WHEN aw.scope='part' THEN 'bg-violet-400/10 text-violet-400'
                                  ELSE 'bg-blue-400/10 text-blue-400' END as category_color"),
                    DB::raw("CASE WHEN aw.scope='part' THEN 'puzzle-piece' ELSE 'shield-exclamation' END as icon"),
                    DB::raw("CASE WHEN aw.scope='part'
                                  THEN COALESCE(NULLIF(aw.part_name,''),'Unnamed Part')
                                  ELSE COALESCE(
                                      NULLIF(aw.vendor,''),
                                      NULLIF(aw.details,''),
                                      CONCAT(CASE WHEN aw.warranty_type='original' THEN 'Original'
                                                  WHEN aw.warranty_type='extended' THEN 'Extended'
                                                  ELSE COALESCE(aw.warranty_type,'Unknown') END,' Warranty')
                                  )
                             END as name"),
                    DB::raw("CASE WHEN aw.scope='part'
                                  THEN NULLIF(aw.vendor,'')
                                  WHEN NULLIF(aw.vendor,'') IS NOT NULL AND NULLIF(aw.details,'') IS NOT NULL
                                  THEN aw.details
                                  ELSE NULL END as source"),
                    'aw.expiry_date',
                    DB::raw("'warranty' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 3. AMC contracts
        $queries[] = $this->applyAssetFilters(
            DB::table('asset_amc_contracts as amc')
                ->join('assets as a', 'a.id', '=', 'amc.asset_id')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')->whereNotNull('amc.amc_date_to')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("'amc' as type_slug, 'AMC' as type_label"),
                    DB::raw("'bg-amber-400/10 text-amber-400' as category_color, 'wrench-screwdriver' as icon"),
                    DB::raw("COALESCE(NULLIF(amc.vendor_name,''),NULLIF(amc.contract_number,''),'No vendor') as name"),
                    DB::raw("CASE WHEN NULLIF(amc.contract_number,'') IS NOT NULL AND NULLIF(amc.vendor_name,'') IS NOT NULL
                                  THEN CONCAT('Contract #',amc.contract_number) ELSE NULL END as source"),
                    'amc.amc_date_to as expiry_date',
                    DB::raw("'amc' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 4. Insurance policies
        $queries[] = $this->applyAssetFilters(
            DB::table('asset_insurance_policies as ip')
                ->join('assets as a', 'a.id', '=', 'ip.asset_id')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')->whereNotNull('ip.policy_date_to')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("'insurance' as type_slug, 'Insurance' as type_label"),
                    DB::raw("'bg-green-400/10 text-green-400' as category_color, 'building-library' as icon"),
                    DB::raw("COALESCE(NULLIF(ip.insurer_name,''),NULLIF(ip.policy_number,''),'No insurer') as name"),
                    DB::raw("CASE WHEN NULLIF(ip.policy_number,'') IS NOT NULL
                                  THEN CONCAT('Policy #',ip.policy_number) ELSE NULL END as source"),
                    'ip.policy_date_to as expiry_date',
                    DB::raw("'insurance' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 5. Part warranties via service parts
        $queries[] = $this->applyAssetFilters(
            DB::table('asset_service_parts as p')
                ->join('asset_services as svc', 'svc.id', '=', 'p.asset_service_id')
                ->join('assets as a', 'a.id', '=', 'svc.asset_id')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')->whereNotNull('p.warranty_till')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("'part_warranty' as type_slug, 'Part Warranty' as type_label"),
                    DB::raw("'bg-violet-400/10 text-violet-400' as category_color, 'puzzle-piece' as icon"),
                    'p.part_name as name',
                    DB::raw("NULLIF(p.purchased_from,'') as source"),
                    'p.warranty_till as expiry_date',
                    DB::raw("'servicing' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 6. Date-based maintenance schedules
        $queries[] = $this->applyAssetFilters(
            DB::table('asset_maintenance_schedules as sch')
                ->join('assets as a', 'a.id', '=', 'sch.asset_id')
                ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                ->whereNull('a.deleted_at')
                ->where('sch.schedule_type', 'date')->where('sch.is_active', 1)->whereNotNull('sch.next_due_date')
                ->select([
                    'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                    DB::raw("'schedule' as type_slug, 'Schedule' as type_label"),
                    DB::raw("'bg-cyan-400/10 text-cyan-400' as category_color, 'calendar-days' as icon"),
                    'sch.schedule_name as name',
                    DB::raw("NULLIF(sch.service_type,'') as source"),
                    'sch.next_due_date as expiry_date',
                    DB::raw("'schedules' as tab"),
                ]),
            $search, $categoryId, $subcatId
        );

        // 7–9. Vehicle compliance (PUC / Fitness / Road Tax) — category code = VE
        foreach ([
            ['puc_expiry_date',      'puc',      'PUC',      'PUC Certificate',     'bg-orange-400/10 text-orange-400'],
            ['fitness_expiry_date',  'fitness',  'Fitness',  'Fitness Certificate',  'bg-orange-400/10 text-orange-400'],
            ['road_tax_expiry_date', 'road_tax', 'Road Tax', 'Road Tax',            'bg-orange-400/10 text-orange-400'],
        ] as [$col, $slug, $label, $name, $color]) {
            $queries[] = $this->applyAssetFilters(
                DB::table('assets as a')
                    ->join('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
                    ->whereNull('a.deleted_at')->where('cat.code', 'VE')->whereNotNull("a.{$col}")
                    ->select([
                        'a.id as asset_id', 'a.asset_code', 'a.asset_name', 'cat.name as category_name',
                        DB::raw("'{$slug}' as type_slug, '{$label}' as type_label"),
                        DB::raw("'{$color}' as category_color, 'document-check' as icon"),
                        DB::raw("'{$name}' as name"),
                        DB::raw("NULL as source"),
                        "a.{$col} as expiry_date",
                        DB::raw("'overview' as tab"),
                    ]),
                $search, $categoryId, $subcatId
            );
        }

        $union = array_shift($queries);
        foreach ($queries as $q) {
            $union->unionAll($q);
        }
        return $union;
    }

    private function applyAssetFilters($query, string $search, string $categoryId, string $subcatId)
    {
        $query->whereIn('a.status', ['active', 'under_repair']);

        if ($search) {
            $query->where(fn($q) => $q
                ->where('a.asset_name', 'like', "%{$search}%")
                ->orWhere('a.asset_code', 'like', "%{$search}%"));
        }
        if ($categoryId) {
            $query->where('a.asset_category_id', $categoryId);
        }
        if ($subcatId) {
            $query->where('a.asset_subcategory_id', $subcatId);
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function computeCounts($base): array
    {
        $row = $base->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN expiry_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming,
            SUM(CASE WHEN expiry_date <  CURDATE() THEN 1 ELSE 0 END) as expired
        ")->first();

        return [
            'all'      => (int) ($row->total    ?? 0),
            'upcoming' => (int) ($row->upcoming  ?? 0),
            'expired'  => (int) ($row->expired   ?? 0),
        ];
    }

    private function rowToItem(object $row): array
    {
        $expiry   = Carbon::parse((string) $row->expiry_date);
        $daysLeft = (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false);
        $status   = $daysLeft < 0 ? 'expired' : ($daysLeft <= 30 ? 'soon' : 'active');

        return [
            'asset_id'       => $row->asset_id,
            'asset_code'     => $row->asset_code,
            'asset_name'     => $row->asset_name,
            'category_name'  => $row->category_name,
            'type_slug'      => $row->type_slug,
            'category'       => $row->type_label,
            'category_color' => $row->category_color,
            'icon'           => $row->icon,
            'name'           => $row->name ?? '',
            'source'         => $row->source ?: null,
            'expiry'         => $expiry,
            'tab'            => $row->tab,
            'days_left'      => $daysLeft,
            'status'         => $status,
        ];
    }
}
