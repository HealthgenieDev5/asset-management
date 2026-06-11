@php
    $expiryFilterOptions = ['all' => 'All', 'expired' => 'Expired', 'in30' => 'Within 30 Days', 'in90' => 'Within 90 Days'];
@endphp
<x-layouts::app title="Certification Expiry Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Certification <span class="text-accent">Expiry</span></flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Inspection & compliance certification expiry dates from service records.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</span>
    </div>

    @include('reports._filters', ['showExpiry' => true, 'showServiceType' => true, 'expiryOptions' => $expiryFilterOptions,
        'exportUrl' => route('reports.certification-expiry.export', request()->query())])

    <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th><th class="px-4 py-3">Service Type</th>
                    <th class="px-4 py-3">Service Date</th><th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3">Cert. Expiry</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($records as $svc)
                    @php
                        $days = (int) now()->startOfDay()->diffInDays($svc->certification_expiry->startOfDay(), false);
                        $expired = $days < 0; $soon = !$expired && $days <= 30;
                        $cls = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-orange-400' : 'text-zinc-700 dark:text-zinc-200');
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">@if($svc->asset)<a href="{{ route('assets.show', [$svc->asset, 'tab' => 'services']) }}" class="font-mono text-xs font-semibold text-accent">{{ $svc->asset->asset_code }}</a>@else<span class="font-mono text-xs text-zinc-400">—</span>@endif</td>
                        <td class="px-4 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $svc->asset?->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->asset?->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">{{ $svc->service_type_label }}</span></td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->service_date->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">{{ $svc->service_agency ?: '—' }}</td>
                        <td class="px-4 py-2.5 {{ $cls }}">
                            {{ $svc->certification_expiry->format('d M Y') }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="mt-4 print:hidden">{{ $records->links() }}</div>@endif
</x-layouts::app>
