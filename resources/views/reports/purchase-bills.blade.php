<x-layouts::app title="Purchase / Bill Details Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Purchase / <span class="text-accent">Bill Details</span></flux:heading>
            <flux:text class="text-zinc-400 mt-1">Assets with purchase bill information.</flux:text>
        </div>
        <div class="text-xs text-zinc-500">
            {{ $assets->total() }} {{ Str::plural('record', $assets->total()) }}
            @if ($totalAmount > 0)
                &nbsp;·&nbsp; Total: <span class="text-zinc-200 font-semibold">₹ {{ number_format($totalAmount, 2) }}</span>
            @endif
        </div>
    </div>

    @include('reports._filters', ['showDates' => true, 'showStatus' => true,
        'exportUrl' => route('reports.purchase-bills.export', request()->query())])

    <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Vendor / Supplier</th>
                    <th class="px-4 py-3">Bill No.</th>
                    <th class="px-4 py-3">Bill Date</th>
                    <th class="px-4 py-3 text-right">Bill Amount</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('assets.show', $asset) }}" class="font-mono text-xs font-semibold text-accent">{{ $asset->asset_code }}</a>
                        </td>
                        <td class="px-4 py-2.5 font-medium text-zinc-100">{{ $asset->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $asset->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $asset->vendor_supplier ?: '—' }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-zinc-300">{{ $asset->bill_no ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $asset->bill_date?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-zinc-200">
                            {{ $asset->bill_amount ? '₹ ' . number_format($asset->bill_amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
            @if ($assets->isNotEmpty() && $totalAmount > 0)
                <tfoot>
                    <tr class="border-t border-zinc-700 bg-zinc-800/50">
                        <td colspan="7" class="px-4 py-3 text-right text-xs font-semibold text-zinc-400 uppercase">Total</td>
                        <td class="px-4 py-3 text-right font-extrabold text-accent">₹ {{ number_format($totalAmount, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="mt-4 print:hidden">{{ $assets->links() }}</div>
    @endif
</x-layouts::app>
