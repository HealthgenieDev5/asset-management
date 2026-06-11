<x-layouts::app title="Asset Register Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Asset <span class="text-accent">Register</span></flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Complete list of all company assets.</flux:text>
        </div>
        <div class="flex gap-2 text-xs text-zinc-500">
            {{ $assets->total() }} {{ Str::plural('asset', $assets->total()) }}
        </div>
    </div>

    @include('reports._filters', [
        'showSearch' => true,
        'showStatus' => true,
        'exportUrl'  => route('assets.export', array_filter(request()->only(['category_id', 'subcategory_id', 'department', 'status', 'search']))),
    ])

    <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-800 dark:bg-zinc-900 print:border-0 print:bg-white">
        <table class="w-full text-sm print:text-xs">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 print:border-gray-300 print:text-gray-600">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Serial No.</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Custodian</th>
                    <th class="px-4 py-3">Purchase Date</th>
                    <th class="px-4 py-3 text-right">Bill Amount</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 print:divide-gray-200">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-accent/5 print:hover:bg-transparent">
                        <td class="px-4 py-2.5 text-zinc-500 print:text-gray-400">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('assets.show', $asset) }}" class="font-mono text-xs font-semibold text-accent print:text-gray-800">{{ $asset->asset_code }}</a>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100 print:text-gray-900">{{ $asset->asset_name }}</div>
                            @if ($asset->manufacturer || $asset->model)
                                <div class="text-xs text-zinc-500 print:text-gray-500">{{ implode(' · ', array_filter([$asset->manufacturer, $asset->model])) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-300 print:text-gray-700">
                            {{ $asset->category?->name }}
                            @if ($asset->subcategory)
                                <span class="text-zinc-400 dark:text-zinc-500"> / {{ $asset->subcategory->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-zinc-500 dark:text-zinc-400 print:text-gray-600">{{ $asset->serial_number ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 print:text-gray-600">{{ $asset->location ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 print:text-gray-600">{{ $asset->department ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 print:text-gray-600">{{ $asset->custodian ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 print:text-gray-600">{{ $asset->purchase_date?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-zinc-700 dark:text-zinc-300 print:text-gray-700">
                            {{ $asset->bill_amount ? '₹ ' . number_format($asset->bill_amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }} print:border-gray-300 print:text-gray-700">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-12 text-center text-zinc-500">No assets found matching the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="mt-4 print:hidden">{{ $assets->links() }}</div>
    @endif
</x-layouts::app>
