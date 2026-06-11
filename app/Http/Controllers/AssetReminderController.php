<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
use Illuminate\Http\Request;

class AssetReminderController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'upcoming');

        $reminders = collect();

        // Original warranty
        Asset::whereNotNull('warranty_lapse_date')
            ->with('category')
            ->get()
            ->each(function ($asset) use (&$reminders) {
                $reminders->push([
                    'asset'         => $asset,
                    'type'          => 'Original Warranty',
                    'expiry'        => $asset->warranty_lapse_date,
                    'reminder_days' => $asset->warranty_reminder_before_days,
                    'detail'        => $asset->warranty_details,
                    'tab'           => 'warranty',
                ]);
            });

        // Extended warranties
        AssetExtendedWarranty::whereNotNull('extended_warranty_date_to')
            ->whereHas('asset')
            ->with('asset.category')
            ->get()
            ->each(function ($ew) use (&$reminders) {
                $reminders->push([
                    'asset'         => $ew->asset,
                    'type'          => 'Extended Warranty',
                    'expiry'        => $ew->extended_warranty_date_to,
                    'reminder_days' => $ew->reminder_before_days,
                    'detail'        => $ew->extended_warranty_vendor,
                    'tab'           => 'ext-warranty',
                ]);
            });

        // AMC contracts
        AssetAmcContract::whereNotNull('amc_date_to')
            ->whereHas('asset')
            ->with('asset.category')
            ->get()
            ->each(function ($amc) use (&$reminders) {
                $reminders->push([
                    'asset'         => $amc->asset,
                    'type'          => 'AMC Contract',
                    'expiry'        => $amc->amc_date_to,
                    'reminder_days' => $amc->reminder_before_days,
                    'detail'        => $amc->vendor_name ?: $amc->contract_number,
                    'tab'           => 'amc',
                ]);
            });

        // Insurance policies
        AssetInsurancePolicy::whereNotNull('policy_date_to')
            ->whereHas('asset')
            ->with('asset.category')
            ->get()
            ->each(function ($policy) use (&$reminders) {
                $reminders->push([
                    'asset'         => $policy->asset,
                    'type'          => 'Insurance',
                    'expiry'        => $policy->policy_date_to,
                    'reminder_days' => $policy->reminder_before_days,
                    'detail'        => $policy->insurer_name ?: $policy->policy_number,
                    'tab'           => 'insurance',
                ]);
            });

        // PUC expiry
        Asset::whereNotNull('puc_expiry_date')
            ->with('category')
            ->get()
            ->each(function ($asset) use (&$reminders) {
                $reminders->push([
                    'asset'         => $asset,
                    'type'          => 'PUC Expiry',
                    'expiry'        => $asset->puc_expiry_date,
                    'reminder_days' => $asset->puc_reminder_before_days,
                    'detail'        => 'Pollution Under Control Certificate',
                    'tab'           => 'overview',
                ]);
            });

        // Fitness certificate expiry
        Asset::whereNotNull('fitness_expiry_date')
            ->with('category')
            ->get()
            ->each(function ($asset) use (&$reminders) {
                $reminders->push([
                    'asset'         => $asset,
                    'type'          => 'Fitness Certificate',
                    'expiry'        => $asset->fitness_expiry_date,
                    'reminder_days' => $asset->fitness_reminder_before_days,
                    'detail'        => 'Vehicle Fitness Certificate',
                    'tab'           => 'overview',
                ]);
            });

        // Road tax expiry
        Asset::whereNotNull('road_tax_expiry_date')
            ->with('category')
            ->get()
            ->each(function ($asset) use (&$reminders) {
                $reminders->push([
                    'asset'         => $asset,
                    'type'          => 'Road Tax',
                    'expiry'        => $asset->road_tax_expiry_date,
                    'reminder_days' => $asset->road_tax_reminder_before_days,
                    'detail'        => 'Road Tax Renewal',
                    'tab'           => 'overview',
                ]);
            });

        // Next service dates (most recent service record per asset that has a next_service_date)
        AssetService::whereNotNull('next_service_date')
            ->whereHas('asset')
            ->with('asset.category')
            ->get()
            ->each(function ($svc) use (&$reminders) {
                $reminders->push([
                    'asset'         => $svc->asset,
                    'type'          => 'Next Service Due',
                    'expiry'        => $svc->next_service_date,
                    'reminder_days' => $svc->next_service_reminder_before_days,
                    'detail'        => $svc->service_type_label . ($svc->service_agency ? ' — ' . $svc->service_agency : ''),
                    'tab'           => 'services',
                ]);
            });

        // Certification expiry from service records
        AssetService::whereNotNull('certification_expiry')
            ->whereHas('asset')
            ->with('asset.category')
            ->get()
            ->each(function ($svc) use (&$reminders) {
                $reminders->push([
                    'asset'         => $svc->asset,
                    'type'          => 'Certification Expiry',
                    'expiry'        => $svc->certification_expiry,
                    'reminder_days' => $svc->certification_reminder_before_days,
                    'detail'        => $svc->service_type_label . ($svc->service_agency ? ' — ' . $svc->service_agency : ''),
                    'tab'           => 'services',
                ]);
            });

        // Count buckets before filtering
        $counts = [
            'upcoming' => $reminders->filter(fn($r) => ! $r['expiry']->isPast() && $r['expiry']->lte(now()->addDays(90)))->count(),
            'expired'  => $reminders->filter(fn($r) => $r['expiry']->isPast())->count(),
            'all'      => $reminders->count(),
        ];

        // Apply filter
        $reminders = match ($filter) {
            'expired'  => $reminders->filter(fn($r) => $r['expiry']->isPast()),
            'upcoming' => $reminders->filter(fn($r) => ! $r['expiry']->isPast() && $r['expiry']->lte(now()->addDays(90))),
            default    => $reminders,
        };

        // Sort: expired most recently first, then upcoming soonest first
        $reminders = $reminders->sortBy(fn($r) => $r['expiry']->timestamp)->values();

        return view('asset-reminders.index', compact('reminders', 'counts', 'filter'));
    }
}
