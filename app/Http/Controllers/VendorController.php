<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::query()
            ->withCount(['warranties', 'amcContracts', 'services'])
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) =>
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%")
                      ->orWhere('contact_person', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                )
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->service_type, fn ($q, $s) =>
                $q->whereJsonContains('service_types', $s)
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load([
            'warranties.asset',
            'amcContracts.asset',
            'services.asset',
        ]);

        $totalServiceCost = $vendor->services->sum(fn ($s) => (float) ($s->service_cost ?? 0));
        $activeAmcCount   = $vendor->amcContracts->filter(fn ($a) => ! $a->isExpired())->count();

        return view('vendors.show', compact('vendor', 'totalServiceCost', 'activeAmcCount'));
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate($this->rules($vendor->id));
        $validated['updated_by'] = auth()->id();

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $hasLinked = $vendor->warranties()->exists()
            || $vendor->amcContracts()->exists()
            || $vendor->services()->exists();

        if ($hasLinked) {
            return back()->with('error', 'Cannot delete this vendor — it has linked warranties, AMC contracts, or service records. Reassign or remove those records first.');
        }

        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $vendors = Vendor::query()
            ->withCount(['warranties', 'amcContracts', 'services'])
            ->withSum('services', 'service_cost')
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) =>
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%")
                )
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->service_type, fn ($q, $s) =>
                $q->whereJsonContains('service_types', $s)
            )
            ->orderBy('name')
            ->get();

        $headers = [
            'Code', 'Name', 'Contact Person', 'Phone', 'Email',
            'Service Types', 'SLA Response (hrs)', 'SLA Resolution (days)',
            'Status', 'Warranties', 'AMC Contracts', 'Services', 'Total Service Cost (₹)',
        ];

        $rows = $vendors->map(fn ($v) => [
            $v->code,
            $v->name,
            $v->contact_person ?? '',
            $v->phone ?? '',
            $v->email ?? '',
            $v->serviceTypesLabel(),
            $v->sla_response_hours ?? '',
            $v->sla_resolution_days ?? '',
            ucfirst($v->status),
            $v->warranties_count,
            $v->amc_contracts_count,
            $v->services_count,
            $v->services_sum_service_cost ? number_format($v->services_sum_service_cost, 2) : '',
        ]);

        return $this->csvResponse('vendors-' . today()->format('Y-m-d') . '.csv', $headers, $rows);
    }

    private function rules(int $ignoreId = null): array
    {
        return [
            'name'               => ['required', 'string', 'max:255',
                Rule::unique('vendors', 'name')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'code'               => ['nullable', 'string', 'max:50',
                Rule::unique('vendors', 'code')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'contact_person'     => ['nullable', 'string', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'email'              => ['nullable', 'email', 'max:255'],
            'address'            => ['nullable', 'string'],
            'service_types'      => ['nullable', 'array'],
            'service_types.*'    => ['in:warranty,amc,service,all'],
            'sla_response_hours' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'sla_resolution_days'=> ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes'              => ['nullable', 'string'],
            'status'             => ['required', 'in:active,inactive'],
        ];
    }

    private function csvResponse(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
