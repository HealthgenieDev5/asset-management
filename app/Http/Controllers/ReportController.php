<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetCategory;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetService;
use App\Models\AssetSubcategory;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    // ── Shared filter options ─────────────────────────────────────────────────

    private function filterOptions(): array
    {
        return [
            'categories'    => AssetCategory::orderBy('name')->get(['id', 'name']),
            'subcategories' => AssetSubcategory::orderBy('name')->get(['id', 'name', 'asset_category_id']),
            'departments'   => Asset::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'locations'     => Asset::whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
        ];
    }

    // ── Asset base query with common filters ──────────────────────────────────

    private function baseAssetQuery(Request $request)
    {
        return Asset::with(['category', 'subcategory'])
            ->when($request->category_id,    fn($q, $v) => $q->where('asset_category_id', $v))
            ->when($request->subcategory_id, fn($q, $v) => $q->where('asset_subcategory_id', $v))
            ->when($request->department,     fn($q, $v) => $q->where('department', $v))
            ->when($request->location,       fn($q, $v) => $q->where('location', $v))
            ->when($request->custodian,      fn($q, $v) => $q->where('custodian', 'like', "%{$v}%"))
            ->when($request->vendor,         fn($q, $v) => $q->where('vendor_supplier', 'like', "%{$v}%"))
            ->when($request->status,         fn($q, $v) => $q->where('status', $v));
    }

    // ── CSV helper ────────────────────────────────────────────────────────────

    private function csvResponse(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function expiryLabel(mixed $date): string
    {
        if (! $date) return '';
        return $date->format('d/m/Y');
    }

    private function statusLabel(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    // ── 1. Asset Register ─────────────────────────────────────────────────────

    public function assetRegister(Request $request)
    {
        $assets = $this->baseAssetQuery($request)
            ->when($request->search, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('asset_code', 'like', "%{$v}%")
                    ->orWhere('asset_name', 'like', "%{$v}%")
                    ->orWhere('serial_number', 'like', "%{$v}%")
                    ->orWhere('manufacturer', 'like', "%{$v}%")
                    ->orWhere('location', 'like', "%{$v}%")
                    ->orWhere('registration_number', 'like', "%{$v}%")
                )
            )
            ->orderBy('asset_code')
            ->paginate(50)->withQueryString();

        return view('reports.asset-register', array_merge(
            $this->filterOptions(),
            compact('assets')
        ));
    }

    public function exportAssetRegister(Request $request): StreamedResponse
    {
        $rows = $this->baseAssetQuery($request)
            ->when($request->search, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('asset_code', 'like', "%{$v}%")
                    ->orWhere('asset_name', 'like', "%{$v}%")
                    ->orWhere('serial_number', 'like', "%{$v}%")
                    ->orWhere('manufacturer', 'like', "%{$v}%")
                    ->orWhere('location', 'like', "%{$v}%")
                    ->orWhere('registration_number', 'like', "%{$v}%")
                )
            )
            ->orderBy('asset_code')->get()
            ->map(fn($a) => [
                $a->asset_code,
                $a->asset_name,
                $a->category?->name,
                $a->subcategory?->name,
                $a->serial_number,
                $a->registration_number,
                $a->manufacturer,
                $a->model,
                $a->location,
                $a->department,
                $a->custodian,
                $a->purchase_date?->format('d/m/Y'),
                $a->bill_amount ? number_format($a->bill_amount, 2) : '',
                $this->statusLabel($a->status),
            ]);

        return $this->csvResponse('asset-register-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Sub-Category', 'Serial No.',
            'Reg. No.', 'Manufacturer', 'Model', 'Location', 'Department', 'Custodian',
            'Purchase Date', 'Bill Amount (₹)', 'Status',
        ], $rows);
    }

    // ── 2. Purchase / Bill Details ────────────────────────────────────────────

    public function purchaseBills(Request $request)
    {
        $query = $this->baseAssetQuery($request)
            ->whereNotNull('bill_no')
            ->when($request->date_from, fn($q, $v) => $q->whereDate('bill_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('bill_date', '<=', $v));

        $assets      = (clone $query)->orderByDesc('bill_date')->paginate(50)->withQueryString();
        $totalAmount = $query->sum('bill_amount');

        return view('reports.purchase-bills', array_merge(
            $this->filterOptions(),
            compact('assets', 'totalAmount')
        ));
    }

    public function exportPurchaseBills(Request $request): StreamedResponse
    {
        $rows = $this->baseAssetQuery($request)
            ->whereNotNull('bill_no')
            ->when($request->date_from, fn($q, $v) => $q->whereDate('bill_date', '>=', $v))
            ->when($request->date_to,   fn($q, $v) => $q->whereDate('bill_date', '<=', $v))
            ->orderByDesc('bill_date')->get()
            ->map(fn($a) => [
                $a->asset_code, $a->asset_name,
                $a->category?->name,
                $a->vendor_supplier,
                $a->bill_no,
                $a->bill_date?->format('d/m/Y'),
                $a->bill_amount ? number_format($a->bill_amount, 2) : '',
                $a->purchase_date?->format('d/m/Y'),
                $a->warranty_lapse_date?->format('d/m/Y'),
            ]);

        return $this->csvResponse('purchase-bills-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Vendor / Supplier',
            'Bill No.', 'Bill Date', 'Bill Amount (₹)', 'Purchase Date', 'Warranty Lapse Date',
        ], $rows);
    }

    // ── 3. Warranty Expiry ────────────────────────────────────────────────────

    public function warrantyExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('warranty_lapse_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('warranty_lapse_date', '<', today()),
            'in30'    => $query->whereDate('warranty_lapse_date', '>=', today())->whereDate('warranty_lapse_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('warranty_lapse_date', '>=', today())->whereDate('warranty_lapse_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $assets = $query->orderBy('warranty_lapse_date')->paginate(50)->withQueryString();
        return view('reports.warranty-expiry', array_merge($this->filterOptions(), compact('assets', 'filter')));
    }

    public function exportWarrantyExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('warranty_lapse_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('warranty_lapse_date', '<', today()),
            'in30'    => $query->whereDate('warranty_lapse_date', '>=', today())->whereDate('warranty_lapse_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('warranty_lapse_date', '>=', today())->whereDate('warranty_lapse_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('warranty_lapse_date')->get()->map(fn($a) => [
            $a->asset_code, $a->asset_name, $a->category?->name, $a->department,
            $a->warranty_details, $a->warranty_lapse_date?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($a->warranty_lapse_date->startOfDay(), false),
        ]);
        return $this->csvResponse('warranty-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department',
            'Warranty Details', 'Warranty Lapse Date', 'Days Remaining',
        ], $rows);
    }

    // ── 4. AMC Expiry ─────────────────────────────────────────────────────────

    public function amcExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetAmcContract::with(['asset.category', 'asset.subcategory'])
            ->whereHas('asset')
            ->whereNotNull('amc_date_to')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('amc_date_to', '<', today()),
            'in30'    => $query->whereDate('amc_date_to', '>=', today())->whereDate('amc_date_to', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('amc_date_to', '>=', today())->whereDate('amc_date_to', '<=', today()->addDays(90)),
            default   => $query,
        };
        $records = $query->orderBy('amc_date_to')->paginate(50)->withQueryString();
        return view('reports.amc-expiry', array_merge($this->filterOptions(), compact('records', 'filter')));
    }

    public function exportAmcExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetAmcContract::with(['asset.category'])
            ->whereHas('asset')
            ->whereNotNull('amc_date_to')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('amc_date_to', '<', today()),
            'in30'    => $query->whereDate('amc_date_to', '>=', today())->whereDate('amc_date_to', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('amc_date_to', '>=', today())->whereDate('amc_date_to', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('amc_date_to')->get()->map(fn($r) => [
            $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
            $r->vendor_name, $r->contract_number, $r->coverage_type,
            $r->amc_date_from?->format('d/m/Y'), $r->amc_date_to?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($r->amc_date_to->startOfDay(), false),
            $r->amc_amount ? number_format($r->amc_amount, 2) : '',
        ]);
        return $this->csvResponse('amc-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'AMC Vendor', 'Contract No.',
            'Coverage Type', 'Date From', 'Date To', 'Days Remaining', 'Amount (₹)',
        ], $rows);
    }

    // ── 6. Insurance Expiry ───────────────────────────────────────────────────

    public function insuranceExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetInsurancePolicy::with(['asset.category', 'asset.subcategory'])
            ->whereHas('asset')
            ->whereNotNull('policy_date_to')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('policy_date_to', '<', today()),
            'in30'    => $query->whereDate('policy_date_to', '>=', today())->whereDate('policy_date_to', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('policy_date_to', '>=', today())->whereDate('policy_date_to', '<=', today()->addDays(90)),
            default   => $query,
        };
        $records = $query->orderBy('policy_date_to')->paginate(50)->withQueryString();
        return view('reports.insurance-expiry', array_merge($this->filterOptions(), compact('records', 'filter')));
    }

    public function exportInsuranceExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetInsurancePolicy::with(['asset.category'])
            ->whereHas('asset')
            ->whereNotNull('policy_date_to')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('policy_date_to', '<', today()),
            'in30'    => $query->whereDate('policy_date_to', '>=', today())->whereDate('policy_date_to', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('policy_date_to', '>=', today())->whereDate('policy_date_to', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('policy_date_to')->get()->map(fn($r) => [
            $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
            $r->policy_number, $r->insurer_name, $r->policy_type,
            $r->policy_date_from?->format('d/m/Y'), $r->policy_date_to?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($r->policy_date_to->startOfDay(), false),
            $r->premium_amount ? number_format($r->premium_amount, 2) : '',
        ]);
        return $this->csvResponse('insurance-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Policy Number', 'Insurer',
            'Policy Type', 'Date From', 'Date To', 'Days Remaining', 'Premium (₹)',
        ], $rows);
    }

    // ── 7. PUC Expiry ─────────────────────────────────────────────────────────

    public function pucExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('puc_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('puc_expiry_date', '<', today()),
            'in30'    => $query->whereDate('puc_expiry_date', '>=', today())->whereDate('puc_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('puc_expiry_date', '>=', today())->whereDate('puc_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $assets = $query->orderBy('puc_expiry_date')->paginate(50)->withQueryString();
        return view('reports.puc-expiry', array_merge($this->filterOptions(), compact('assets', 'filter')));
    }

    public function exportPucExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('puc_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('puc_expiry_date', '<', today()),
            'in30'    => $query->whereDate('puc_expiry_date', '>=', today())->whereDate('puc_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('puc_expiry_date', '>=', today())->whereDate('puc_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('puc_expiry_date')->get()->map(fn($a) => [
            $a->asset_code, $a->asset_name, $a->category?->name,
            $a->department, $a->custodian, $a->puc_expiry_date?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($a->puc_expiry_date->startOfDay(), false),
        ]);
        return $this->csvResponse('puc-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Custodian',
            'PUC Expiry Date', 'Days Remaining',
        ], $rows);
    }

    // ── 8. Fitness Expiry ─────────────────────────────────────────────────────

    public function fitnessExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('fitness_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('fitness_expiry_date', '<', today()),
            'in30'    => $query->whereDate('fitness_expiry_date', '>=', today())->whereDate('fitness_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('fitness_expiry_date', '>=', today())->whereDate('fitness_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $assets = $query->orderBy('fitness_expiry_date')->paginate(50)->withQueryString();
        return view('reports.fitness-expiry', array_merge($this->filterOptions(), compact('assets', 'filter')));
    }

    public function exportFitnessExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('fitness_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('fitness_expiry_date', '<', today()),
            'in30'    => $query->whereDate('fitness_expiry_date', '>=', today())->whereDate('fitness_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('fitness_expiry_date', '>=', today())->whereDate('fitness_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('fitness_expiry_date')->get()->map(fn($a) => [
            $a->asset_code, $a->asset_name, $a->category?->name,
            $a->department, $a->custodian, $a->fitness_expiry_date?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($a->fitness_expiry_date->startOfDay(), false),
        ]);
        return $this->csvResponse('fitness-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Custodian',
            'Fitness Expiry Date', 'Days Remaining',
        ], $rows);
    }

    // ── 9. Road Tax Expiry ────────────────────────────────────────────────────

    public function roadTaxExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('road_tax_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('road_tax_expiry_date', '<', today()),
            'in30'    => $query->whereDate('road_tax_expiry_date', '>=', today())->whereDate('road_tax_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('road_tax_expiry_date', '>=', today())->whereDate('road_tax_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $assets = $query->orderBy('road_tax_expiry_date')->paginate(50)->withQueryString();
        return view('reports.road-tax-expiry', array_merge($this->filterOptions(), compact('assets', 'filter')));
    }

    public function exportRoadTaxExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = $this->baseAssetQuery($request)->whereNotNull('road_tax_expiry_date');
        $query  = match ($filter) {
            'expired' => $query->whereDate('road_tax_expiry_date', '<', today()),
            'in30'    => $query->whereDate('road_tax_expiry_date', '>=', today())->whereDate('road_tax_expiry_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('road_tax_expiry_date', '>=', today())->whereDate('road_tax_expiry_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('road_tax_expiry_date')->get()->map(fn($a) => [
            $a->asset_code, $a->asset_name, $a->category?->name,
            $a->department, $a->custodian, $a->road_tax_expiry_date?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($a->road_tax_expiry_date->startOfDay(), false),
        ]);
        return $this->csvResponse('road-tax-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Custodian',
            'Road Tax Expiry Date', 'Days Remaining',
        ], $rows);
    }

    // ── 10. Inspection Due ────────────────────────────────────────────────────

    public function inspectionDue(Request $request)
    {
        $assets = $this->baseAssetQuery($request)
            ->where('inspection_required', true)
            ->orderBy('asset_code')
            ->paginate(50)->withQueryString();
        return view('reports.inspection-due', array_merge($this->filterOptions(), compact('assets')));
    }

    public function exportInspectionDue(Request $request): StreamedResponse
    {
        $rows = $this->baseAssetQuery($request)
            ->where('inspection_required', true)
            ->orderBy('asset_code')->get()
            ->map(fn($a) => [
                $a->asset_code, $a->asset_name, $a->category?->name,
                $a->department, $a->custodian, $a->location,
                $a->inspection_frequency_value
                    ? $a->inspection_frequency_value . ' ' . $a->inspection_frequency_unit
                    : '',
            ]);
        return $this->csvResponse('inspection-due-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Custodian',
            'Location', 'Inspection Frequency',
        ], $rows);
    }

    // ── 11. Certification Expiry ──────────────────────────────────────────────

    public function certificationExpiry(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetService::with(['asset.category', 'asset.subcategory'])
            ->whereHas('asset')
            ->whereNotNull('certification_expiry')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('certification_expiry', '<', today()),
            'in30'    => $query->whereDate('certification_expiry', '>=', today())->whereDate('certification_expiry', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('certification_expiry', '>=', today())->whereDate('certification_expiry', '<=', today()->addDays(90)),
            default   => $query,
        };
        $records = $query->orderBy('certification_expiry')->paginate(50)->withQueryString();
        return view('reports.certification-expiry', array_merge($this->filterOptions(), compact('records', 'filter')));
    }

    public function exportCertificationExpiry(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetService::with(['asset.category'])
            ->whereHas('asset')
            ->whereNotNull('certification_expiry')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)));
        $query  = match ($filter) {
            'expired' => $query->whereDate('certification_expiry', '<', today()),
            'in30'    => $query->whereDate('certification_expiry', '>=', today())->whereDate('certification_expiry', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('certification_expiry', '>=', today())->whereDate('certification_expiry', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('certification_expiry')->get()->map(fn($r) => [
            $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
            $r->service_type_label, $r->service_date?->format('d/m/Y'),
            $r->service_agency, $r->certification_expiry?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($r->certification_expiry->startOfDay(), false),
        ]);
        return $this->csvResponse('certification-expiry-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Service Type', 'Service Date',
            'Agency', 'Certification Expiry', 'Days Remaining',
        ], $rows);
    }

    // ── 12. Service Due ───────────────────────────────────────────────────────

    public function serviceDue(Request $request)
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetService::with(['asset.category', 'asset.subcategory'])
            ->whereHas('asset')
            ->whereNotNull('next_service_date')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v));
        $query  = match ($filter) {
            'overdue' => $query->whereDate('next_service_date', '<', today()),
            'in30'    => $query->whereDate('next_service_date', '>=', today())->whereDate('next_service_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('next_service_date', '>=', today())->whereDate('next_service_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $records = $query->orderBy('next_service_date')->paginate(50)->withQueryString();
        return view('reports.service-due', array_merge($this->filterOptions(), compact('records', 'filter')));
    }

    public function exportServiceDue(Request $request): StreamedResponse
    {
        $filter = $request->get('expiry_filter', 'all');
        $query  = AssetService::with(['asset.category'])
            ->whereHas('asset')
            ->whereNotNull('next_service_date')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v));
        $query  = match ($filter) {
            'overdue' => $query->whereDate('next_service_date', '<', today()),
            'in30'    => $query->whereDate('next_service_date', '>=', today())->whereDate('next_service_date', '<=', today()->addDays(30)),
            'in90'    => $query->whereDate('next_service_date', '>=', today())->whereDate('next_service_date', '<=', today()->addDays(90)),
            default   => $query,
        };
        $rows = $query->orderBy('next_service_date')->get()->map(fn($r) => [
            $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
            $r->asset?->department, $r->service_type_label,
            $r->service_date?->format('d/m/Y'), $r->service_agency,
            $r->next_service_date?->format('d/m/Y'),
            (int) now()->startOfDay()->diffInDays($r->next_service_date->startOfDay(), false),
        ]);
        return $this->csvResponse('service-due-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Service Type',
            'Last Service Date', 'Agency', 'Next Service Due', 'Days',
        ], $rows);
    }

    // ── 13. Service History ───────────────────────────────────────────────────

    public function serviceHistory(Request $request)
    {
        $records = AssetService::with(['asset.category', 'asset.subcategory', 'parts'])
            ->whereHas('asset')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v))
            ->when($request->date_from,      fn($q, $v) => $q->whereDate('service_date', '>=', $v))
            ->when($request->date_to,        fn($q, $v) => $q->whereDate('service_date', '<=', $v))
            ->orderByDesc('service_date')
            ->paginate(50)->withQueryString();
        return view('reports.service-history', array_merge($this->filterOptions(), compact('records')));
    }

    public function exportServiceHistory(Request $request): StreamedResponse
    {
        $rows = AssetService::with(['asset.category', 'parts'])
            ->whereHas('asset')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v))
            ->when($request->date_from,      fn($q, $v) => $q->whereDate('service_date', '>=', $v))
            ->when($request->date_to,        fn($q, $v) => $q->whereDate('service_date', '<=', $v))
            ->orderByDesc('service_date')->get()
            ->map(fn($r) => [
                $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
                $r->service_type_label, $r->service_date?->format('d/m/Y'),
                $r->service_agency, $r->technician_name, $r->condition_rating_label,
                $r->service_cost ? number_format($r->service_cost, 2) : '',
                number_format($r->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity), 2),
                number_format(($r->service_cost ?? 0) + $r->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity), 2),
            ]);
        return $this->csvResponse('service-history-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Service Type', 'Service Date',
            'Agency', 'Technician', 'Condition', 'Labour Cost (₹)', 'Parts Cost (₹)', 'Total (₹)',
        ], $rows);
    }

    // ── 14. Maintenance Cost ──────────────────────────────────────────────────

    public function maintenanceCost(Request $request)
    {
        $records = AssetService::with(['asset.category', 'asset.subcategory', 'parts'])
            ->whereHas('asset')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v))
            ->when($request->date_from,      fn($q, $v) => $q->whereDate('service_date', '>=', $v))
            ->when($request->date_to,        fn($q, $v) => $q->whereDate('service_date', '<=', $v))
            ->orderByDesc('service_date')
            ->paginate(50)->withQueryString();
        return view('reports.maintenance-cost', array_merge($this->filterOptions(), compact('records')));
    }

    public function exportMaintenanceCost(Request $request): StreamedResponse
    {
        $rows = AssetService::with(['asset.category', 'parts'])
            ->whereHas('asset')
            ->when($request->category_id,    fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_category_id', $v)))
            ->when($request->subcategory_id, fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('asset_subcategory_id', $v)))
            ->when($request->department,     fn($q, $v) => $q->whereHas('asset', fn($a) => $a->where('department', $v)))
            ->when($request->service_type,   fn($q, $v) => $q->where('service_type', $v))
            ->when($request->date_from,      fn($q, $v) => $q->whereDate('service_date', '>=', $v))
            ->when($request->date_to,        fn($q, $v) => $q->whereDate('service_date', '<=', $v))
            ->orderByDesc('service_date')->get()
            ->map(fn($r) => [
                $r->asset?->asset_code, $r->asset?->asset_name, $r->asset?->category?->name,
                $r->asset?->department, $r->service_type_label, $r->service_date?->format('d/m/Y'),
                $r->service_cost ? number_format($r->service_cost, 2) : '',
                number_format($r->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity), 2),
                number_format(($r->service_cost ?? 0) + $r->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity), 2),
            ]);
        return $this->csvResponse('maintenance-cost-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Category', 'Department', 'Service Type',
            'Service Date', 'Labour Cost (₹)', 'Parts Cost (₹)', 'Total (₹)',
        ], $rows);
    }

    // ── 15. Vehicle Depreciation ──────────────────────────────────────────────

    public function vehicleDepreciation(Request $request)
    {
        $assets = Asset::with(['category', 'subcategory'])
            ->whereNotNull('vehicle_obv')
            ->when($request->department, fn($q, $v) => $q->where('department', $v))
            ->when($request->custodian,  fn($q, $v) => $q->where('custodian', 'like', "%{$v}%"))
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->search,     fn($q, $v) => $q->where(fn($q2) => $q2
                ->where('asset_code', 'like', "%{$v}%")
                ->orWhere('asset_name', 'like', "%{$v}%")
                ->orWhere('registration_number', 'like', "%{$v}%")
            ))
            ->orderBy('asset_code')
            ->paginate(50)->withQueryString();
        return view('reports.vehicle-depreciation', array_merge($this->filterOptions(), compact('assets')));
    }

    public function exportVehicleDepreciation(Request $request): StreamedResponse
    {
        $rows = Asset::with(['category'])
            ->whereNotNull('vehicle_obv')
            ->when($request->department, fn($q, $v) => $q->where('department', $v))
            ->when($request->custodian,  fn($q, $v) => $q->where('custodian', 'like', "%{$v}%"))
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->search,     fn($q, $v) => $q->where(fn($q2) => $q2
                ->where('asset_code', 'like', "%{$v}%")
                ->orWhere('asset_name', 'like', "%{$v}%")
                ->orWhere('registration_number', 'like', "%{$v}%")
            ))
            ->orderBy('asset_code')->get()
            ->map(fn($a) => [
                $a->asset_code, $a->asset_name, $a->registration_number ?: '',
                $a->category?->name, $a->department, $a->custodian,
                $a->purchase_date?->format('d/m/Y'),
                $a->vehicle_obv ? number_format($a->vehicle_obv, 2) : '',
                $a->vehicle_depreciation_percent,
                $a->vehicle_depreciation_book_value ? number_format($a->vehicle_depreciation_book_value, 2) : '',
                $this->statusLabel($a->status),
            ]);
        return $this->csvResponse('vehicle-depreciation-' . today()->format('Y-m-d') . '.csv', [
            'Asset Code', 'Asset Name', 'Reg. No.', 'Category', 'Department', 'Custodian',
            'Purchase Date', 'OBV (₹)', 'Dep %', 'Book Value (₹)', 'Status',
        ], $rows);
    }

    // ── 16. Vendor Performance ────────────────────────────────────────────────

    public function vendorPerformance(Request $request)
    {
        $vendors = Vendor::withCount([
                'warranties',
                'amcContracts',
                'amcContracts as active_amc_count' => fn ($q) =>
                    $q->whereDate('amc_date_to', '>=', today()),
                'services',
            ])
            ->withSum('services', 'service_cost')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('reports.vendor-performance', compact('vendors'));
    }

    public function exportVendorPerformance(Request $request): StreamedResponse
    {
        $vendors = Vendor::withCount([
                'warranties',
                'amcContracts',
                'amcContracts as active_amc_count' => fn ($q) =>
                    $q->whereDate('amc_date_to', '>=', today()),
                'services',
            ])
            ->withSum('services', 'service_cost')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->get();

        $rows = $vendors->map(fn ($v) => [
            $v->name,
            $v->typeLabel(),
            $v->phone ?? '',
            $v->warranties_count,
            $v->amc_contracts_count,
            $v->active_amc_count,
            $v->services_count,
            $v->services_sum_service_cost ? number_format($v->services_sum_service_cost, 2) : '—',
            ucfirst($v->status),
        ]);

        return $this->csvResponse('vendor-performance-' . today()->format('Y-m-d') . '.csv', [
            'Name', 'Type', 'Phone',
            'Warranties', 'Total AMC', 'Active AMC', 'Service Incidents',
            'Total Service Cost (₹)', 'Status',
        ], $rows);
    }

    // ── Legacy / unused stubs kept for route compatibility ────────────────────

    public function expiry(Request $request)
    {
        return redirect()->route('reports.warranty-expiry');
    }
}
