<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;

class AssetReminderController extends Controller
{
    public function index(Request $request)
    {
        $filter      = $request->query('filter', 'upcoming');
        $type        = $request->query('type', '');
        $search      = $request->query('search', '');
        $categoryId  = $request->query('asset_category_id', '');
        $subcatId    = $request->query('asset_subcategory_id', '');
        $sort        = in_array($request->query('sort'), ['asset', 'category', 'name', 'expiry', 'days_left', 'status']) ? $request->query('sort') : 'days_left';
        $direction   = $request->query('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $categories    = AssetCategory::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $subcategories = AssetSubcategory::when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'asset_category_id']);

        $assets = Asset::with([
            'category',
            'warranties',
            'extendedWarranties',
            'amcContracts',
            'insurancePolicies',
            'services.parts',
            'maintenanceSchedules',
        ])
        ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
        ->when($subcatId,   fn($q) => $q->where('asset_subcategory_id', $subcatId))
        ->get();

        $items = collect();

        foreach ($assets as $asset) {
            $assetMatches = ! $search
                || str_contains(strtolower($asset->asset_name ?? ''), strtolower($search))
                || str_contains(strtolower($asset->asset_code ?? ''), strtolower($search));

            if (! $assetMatches) {
                continue;
            }

            // 1. Legacy warranty on asset record
            if ($asset->warranty_lapse_date) {
                $items->push($this->item(
                    $asset,
                    'warranty',
                    'Warranty',
                    'bg-blue-400/10 text-blue-400',
                    'shield-check',
                    $asset->warranty_details ?: 'Asset Warranty',
                    'From asset record',
                    $asset->warranty_lapse_date,
                    'overview'
                ));
            }

            // 2. Unified warranties (asset_warranties table)
            foreach ($asset->warranties as $w) {
                if (! $w->expiry_date) continue;
                $isPart = $w->scope === 'part';
                $cat    = $isPart ? 'part_warranty' : 'warranty';
                $label  = $isPart ? 'Part Warranty' : ($w->warrantyTypeLabel() . ' Warranty');
                $color  = $isPart ? 'bg-violet-400/10 text-violet-400' : 'bg-blue-400/10 text-blue-400';
                $icon   = $isPart ? 'puzzle-piece' : 'shield-exclamation';
                $name   = $isPart
                    ? ($w->part_name ?: 'Unnamed Part')
                    : ($w->vendor ?: ($w->details ?: ($w->warrantyTypeLabel() . ' Warranty')));
                $source = $isPart
                    ? ($w->vendor ?: null)
                    : ($w->vendor && $w->details ? $w->details : null);
                $items->push($this->item($asset, $cat, $label, $color, $icon, $name, $source, $w->expiry_date, 'warranty'));
            }

            // 3. Extended warranties (legacy)
            foreach ($asset->extendedWarranties as $ew) {
                if (! $ew->extended_warranty_date_to) continue;
                $items->push($this->item(
                    $asset,
                    'extended_warranty',
                    'Extended Warranty',
                    'bg-indigo-400/10 text-indigo-400',
                    'shield-exclamation',
                    $ew->extended_warranty_vendor ?: 'No vendor',
                    null,
                    $ew->extended_warranty_date_to,
                    'warranty'
                ));
            }

            // 4. AMC contracts
            foreach ($asset->amcContracts as $amc) {
                if (! $amc->amc_date_to) continue;
                $name   = $amc->vendor_name ?: ($amc->contract_number ?: 'No vendor');
                $source = $amc->contract_number && $amc->vendor_name ? 'Contract #' . $amc->contract_number : null;
                $items->push($this->item(
                    $asset,
                    'amc',
                    'AMC',
                    'bg-amber-400/10 text-amber-400',
                    'wrench-screwdriver',
                    $name,
                    $source,
                    $amc->amc_date_to,
                    'amc'
                ));
            }

            // 5. Insurance policies
            foreach ($asset->insurancePolicies as $policy) {
                if (! $policy->policy_date_to) continue;
                $name   = $policy->insurer_name ?: ($policy->policy_number ?: 'No insurer');
                $source = $policy->policy_number ? 'Policy #' . $policy->policy_number : null;
                $items->push($this->item(
                    $asset,
                    'insurance',
                    'Insurance',
                    'bg-green-400/10 text-green-400',
                    'building-library',
                    $name,
                    $source,
                    $policy->policy_date_to,
                    'insurance'
                ));
            }

            // 6. Part warranties (from service parts)
            foreach ($asset->services->flatMap->parts as $part) {
                if (! $part->warranty_till) continue;
                $items->push($this->item(
                    $asset,
                    'part_warranty',
                    'Part Warranty',
                    'bg-violet-400/10 text-violet-400',
                    'puzzle-piece',
                    $part->part_name,
                    $part->purchased_from ?: null,
                    $part->warranty_till,
                    'servicing'
                ));
            }

            // 7. Maintenance schedules (date-based with next_due_date)
            foreach ($asset->maintenanceSchedules as $sch) {
                if ($sch->schedule_type !== 'date' || ! $sch->next_due_date) continue;
                $items->push($this->item(
                    $asset,
                    'schedule',
                    'Schedule',
                    'bg-cyan-400/10 text-cyan-400',
                    'calendar-days',
                    $sch->schedule_name,
                    $sch->serviceTypeLabel(),
                    $sch->next_due_date,
                    'schedules'
                ));
            }

            // 8. Vehicle-specific fields
            if ($asset->isVehicle()) {
                if ($asset->puc_expiry_date) {
                    $items->push($this->item(
                        $asset,
                        'puc',
                        'PUC',
                        'bg-orange-400/10 text-orange-400',
                        'document-check',
                        'PUC Certificate',
                        null,
                        $asset->puc_expiry_date,
                        'overview'
                    ));
                }
                if ($asset->fitness_expiry_date) {
                    $items->push($this->item(
                        $asset,
                        'fitness',
                        'Fitness',
                        'bg-orange-400/10 text-orange-400',
                        'document-check',
                        'Fitness Certificate',
                        null,
                        $asset->fitness_expiry_date,
                        'overview'
                    ));
                }
                if ($asset->road_tax_expiry_date) {
                    $items->push($this->item(
                        $asset,
                        'road_tax',
                        'Road Tax',
                        'bg-orange-400/10 text-orange-400',
                        'document-check',
                        'Road Tax',
                        null,
                        $asset->road_tax_expiry_date,
                        'overview'
                    ));
                }
            }
        }

        // Apply type filter
        if ($type) {
            $items = $items->filter(fn($i) => $i['type_slug'] === $type);
        }

        // Compute days_left and status
        $items = $items->map(function ($item) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($item['expiry']->copy()->startOfDay(), false);
            $status   = $daysLeft < 0 ? 'expired' : ($daysLeft <= 30 ? 'soon' : 'active');
            return array_merge($item, ['days_left' => $daysLeft, 'status' => $status]);
        });

        // Count buckets
        $counts = [
            'upcoming' => $items->where('status', '!=', 'expired')->count(),
            'expired'  => $items->where('status', 'expired')->count(),
            'all'      => $items->count(),
        ];

        // Apply tab filter
        $items = match ($filter) {
            'expired'  => $items->filter(fn($i) => $i['status'] === 'expired'),
            'upcoming' => $items->filter(fn($i) => $i['status'] !== 'expired'),
            default    => $items,
        };

        $items = $items->sortBy(function ($i) use ($sort) {
            return match ($sort) {
                'asset'    => strtolower($i['asset']->asset_name ?? ''),
                'category' => $i['category'],
                'name'     => strtolower($i['name']),
                'expiry'   => $i['expiry']->timestamp,
                'status'   => $i['status'],
                default    => $i['days_left'],
            };
        }, SORT_REGULAR, $direction === 'desc')->values();

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

        return view('asset-reminders.index', compact('items', 'counts', 'filter', 'type', 'search', 'typeOptions', 'categories', 'subcategories', 'categoryId', 'subcatId', 'sort', 'direction'));
    }

    private function item(
        Asset $asset,
        string $typeSlug,
        string $category,
        string $categoryColor,
        string $icon,
        string $name,
        ?string $source,
        $expiry,
        string $tab,
    ): array {
        return [
            'asset'          => $asset,
            'type_slug'      => $typeSlug,
            'category'       => $category,
            'category_color' => $categoryColor,
            'icon'           => $icon,
            'name'           => $name,
            'source'         => $source,
            'expiry'         => $expiry,
            'tab'            => $tab,
        ];
    }
}
