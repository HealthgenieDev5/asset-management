<x-layouts::app title="Service History Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Service <span class="text-accent">History</span></flux:heading>
            <flux:text class="text-zinc-400 mt-1">Complete service, maintenance, and inspection history.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</span>
    </div>

    @include('reports._filters', ['showDates' => true, 'showServiceType' => true,
        'exportUrl' => route('reports.service-history.export', request()->query())])

    <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th><th class="px-4 py-3">Service Type</th>
                    <th class="px-4 py-3">Service Date</th><th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3">Condition</th>
                    <th class="px-4 py-3 text-right">Service Cost</th>
                    <th class="px-4 py-3 text-right">Parts Cost</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($records as $svc)
                    @php
                        $partsCost = $svc->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
                        $total     = ($svc->service_cost ?? 0) + $partsCost;
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5"><a href="{{ route('assets.show', [$svc->asset, 'tab' => 'services']) }}" class="font-mono text-xs font-semibold text-accent">{{ $svc->asset?->asset_code }}</a></td>
                        <td class="px-4 py-2.5 font-medium text-zinc-100">{{ $svc->asset?->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->asset?->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">{{ $svc->service_type_label }}</span></td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->service_date->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->service_agency ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs font-medium {{ $svc->condition_rating_color }}">{{ $svc->condition_rating_label }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-300">{{ $svc->service_cost ? '₹ ' . number_format($svc->service_cost, 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-300">{{ $partsCost > 0 ? '₹ ' . number_format($partsCost, 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-zinc-100">{{ $total > 0 ? '₹ ' . number_format($total, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="mt-4 print:hidden">{{ $records->links() }}</div>@endif
</x-layouts::app>
