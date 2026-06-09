@php
    $expiryFilterOptions = ['all' => 'All', 'expired' => 'Expired', 'in30' => 'Within 30 Days', 'in90' => 'Within 90 Days'];
@endphp
<x-layouts::app title="Fitness Certificate Expiry Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Fitness Certificate <span class="text-accent">Expiry</span></flux:heading>
            <flux:text class="text-zinc-400 mt-1">Vehicle fitness certificate expiry dates.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $assets->total() }} {{ Str::plural('vehicle', $assets->total()) }}</span>
    </div>

    @include('reports._filters', ['showExpiry' => true, 'expiryOptions' => $expiryFilterOptions,
        'exportUrl' => route('reports.fitness-expiry.export', request()->query())])

    <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Department</th><th class="px-4 py-3">Custodian</th>
                    <th class="px-4 py-3">Fitness Expiry</th><th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($assets as $asset)
                    @php
                        $days = (int) now()->startOfDay()->diffInDays($asset->fitness_expiry_date->startOfDay(), false);
                        $expired = $days < 0; $soon = !$expired && $days <= 30;
                        $cls = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5"><a href="{{ route('assets.show', $asset) }}" class="font-mono text-xs font-semibold text-accent">{{ $asset->asset_code }}</a></td>
                        <td class="px-4 py-2.5 font-medium text-zinc-100">{{ $asset->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $asset->department ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $asset->custodian ?: '—' }}</td>
                        <td class="px-4 py-2.5 {{ $cls }}">
                            {{ $asset->fitness_expiry_date->format('d M Y') }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d)</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">{{ $asset->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($assets->hasPages())<div class="mt-4 print:hidden">{{ $assets->links() }}</div>@endif
</x-layouts::app>
