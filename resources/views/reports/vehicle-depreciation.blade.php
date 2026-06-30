<x-layouts::app title="Vehicle Depreciation Report">
    @include('partials.flash')

    @php
        $totalObv = $assets->getCollection()->sum('vehicle_obv');
        $totalBook = $assets->getCollection()->sum('vehicle_depreciation_book_value');
    @endphp

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Vehicle <span class="text-accent">Depreciation</span></flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Original book value and current book value for vehicles.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $assets->total() }} {{ Str::plural('vehicle', $assets->total()) }}</span>
    </div>

    @include('reports._filters', [
        'showSearch' => true,
        'showStatus' => true,
        'exportUrl'  => route('reports.vehicle-depreciation.export', request()->query()),
    ])

    <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Reg. No.</th>
                    <th class="px-4 py-3">Department</th><th class="px-4 py-3">Custodian</th>
                    <th class="px-4 py-3">Purchase Date</th>
                    <th class="px-4 py-3 text-right">Age (Yrs)</th>
                    <th class="px-4 py-3 text-right">OBV (₹)</th>
                    <th class="px-4 py-3 text-right">Dep. %</th>
                    <th class="px-4 py-3 text-right">Book Value (₹)</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5"><a href="{{ route('assets.show', $asset) }}" class="font-mono text-xs font-semibold text-accent">{{ $asset->asset_code }}</a></td>
                        <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $asset->asset_name }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-zinc-500 dark:text-zinc-400 uppercase">{{ $asset->registration_number ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $asset->department ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $asset->custodian ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $asset->purchase_date?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-500 dark:text-zinc-400">
                            {{ $asset->purchase_date ? $asset->purchase_date->diffInYears(now()) . ' yr' : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-zinc-700 dark:text-zinc-200">{{ $asset->vehicle_obv ? number_format($asset->vehicle_obv, 2) : '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-500 dark:text-zinc-400">{{ $asset->vehicle_depreciation_percent ? $asset->vehicle_depreciation_percent . '%' : '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-zinc-800 dark:text-zinc-100">{{ $asset->vehicle_depreciation_book_value ? number_format($asset->vehicle_depreciation_book_value, 2) : '—' }}</td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">{{ $asset->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-12 text-center text-zinc-500">No vehicle depreciation records found.</td></tr>
                @endforelse
            </tbody>
            @if ($assets->isNotEmpty() && ($totalObv > 0 || $totalBook > 0))
                <tfoot>
                    <tr class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <td colspan="8" class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Page Total</td>
                        <td class="px-4 py-3 text-right font-bold text-zinc-700 dark:text-zinc-200">{{ number_format($totalObv, 2) }}</td>
                        <td></td>
                        <td class="px-4 py-3 text-right font-extrabold text-accent">{{ number_format($totalBook, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if ($assets->hasPages())<div class="mt-4 print:hidden">{{ $assets->links() }}</div>@endif
</x-layouts::app>
