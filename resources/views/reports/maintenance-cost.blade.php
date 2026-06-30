<x-layouts::app title="Maintenance Cost Report">
    @include('partials.flash')

    @php
        $allRecords   = $records->getCollection();
        $totalService = $allRecords->sum('service_cost');
        $totalParts   = $allRecords->sum(fn($s) => $s->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity));
        $grandTotal   = $totalService + $totalParts;
    @endphp

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Maintenance <span class="text-accent">Cost</span></flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Total cost analysis across all service and parts records.</flux:text>
        </div>
        @if ($grandTotal > 0)
            <div class="text-right">
                <div class="text-xs text-zinc-500">Labour: ₹ {{ number_format($totalService, 2) }} &nbsp;·&nbsp; Parts: ₹ {{ number_format($totalParts, 2) }}</div>
                <div class="text-lg font-extrabold text-accent">Total: ₹ {{ number_format($grandTotal, 2) }}</div>
            </div>
        @endif
    </div>

    @include('reports._filters', ['showDates' => true, 'showServiceType' => true,
        'exportUrl' => route('reports.maintenance-cost.export', request()->query())])

    <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th><th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Service Type</th><th class="px-4 py-3">Service Date</th>
                    <th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3 text-right">Labour</th>
                    <th class="px-4 py-3 text-right">Parts</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($records as $svc)
                    @php
                        $pCost = $svc->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
                        $tot   = ($svc->service_cost ?? 0) + $pCost;
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">@if($svc->asset)<a href="{{ route('assets.show', [$svc->asset, 'tab' => 'services']) }}" class="font-mono text-xs font-semibold text-accent">{{ $svc->asset->asset_code }}</a>@else<span class="font-mono text-xs text-zinc-400">—</span>@endif</td>
                        <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $svc->asset?->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->asset?->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->asset?->department ?: '—' }}</td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">{{ $svc->service_type_label }}</span></td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->service_date->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->service_agency ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-600 dark:text-zinc-300">{{ $svc->service_cost ? '₹ ' . number_format($svc->service_cost, 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-600 dark:text-zinc-300">{{ $pCost > 0 ? '₹ ' . number_format($pCost, 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-zinc-800 dark:text-zinc-100">{{ $tot > 0 ? '₹ ' . number_format($tot, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
            @if ($grandTotal > 0 && $records->isNotEmpty())
                <tfoot>
                    <tr class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <td colspan="8" class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Page Total</td>
                        <td class="px-4 py-3 text-right font-bold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($totalService, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($totalParts, 2) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold text-accent">₹ {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if ($records->hasPages())<div class="mt-4 print:hidden">{{ $records->links() }}</div>@endif
</x-layouts::app>
