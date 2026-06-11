@php
    $expiryFilterOptions = ['all' => 'All', 'expired' => 'Expired', 'in30' => 'Within 30 Days', 'in90' => 'Within 90 Days'];
@endphp
<x-layouts::app title="Insurance Expiry Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Insurance <span class="text-accent">Expiry</span></flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Insurance policy expiry dates.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</span>
    </div>

    @include('reports._filters', ['showExpiry' => true, 'expiryOptions' => $expiryFilterOptions,
        'exportUrl' => route('reports.insurance-expiry.export', request()->query())])

    <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Insurer</th>
                    <th class="px-4 py-3">Policy No.</th>
                    <th class="px-4 py-3">Policy Type</th>
                    <th class="px-4 py-3">From</th>
                    <th class="px-4 py-3">Expiry Date</th>
                    <th class="px-4 py-3 text-right">Premium</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($records as $policy)
                    @php
                        $days    = $policy->policy_date_to ? (int) now()->startOfDay()->diffInDays($policy->policy_date_to->startOfDay(), false) : null;
                        $expired = $days !== null && $days < 0;
                        $soon    = $days !== null && ! $expired && $days <= 30;
                        $dateClass = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-700 dark:text-zinc-200');
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">@if($policy->asset)<a href="{{ route('assets.show', [$policy->asset, 'tab' => 'insurance']) }}" class="font-mono text-xs font-semibold text-accent">{{ $policy->asset->asset_code }}</a>@else<span class="font-mono text-xs text-zinc-400">—</span>@endif</td>
                        <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $policy->asset?->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $policy->asset?->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $policy->insurer_name ?: '—' }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-zinc-600 dark:text-zinc-300">{{ $policy->policy_number ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $policy->policy_type ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $policy->policy_date_from?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-2.5 {{ $dateClass }}">
                            {{ $policy->policy_date_to?->format('d M Y') ?: '—' }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d)</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right text-zinc-600 dark:text-zinc-300">{{ $policy->premium_amount ? '₹ ' . number_format($policy->premium_amount, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="mt-4 print:hidden">{{ $records->links() }}</div>@endif
</x-layouts::app>
