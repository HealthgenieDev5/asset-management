@php
    $expiryFilterOptions = ['all' => 'All', 'overdue' => 'Overdue', 'in30' => 'Due in 30 Days', 'in90' => 'Due in 90 Days'];
@endphp
<x-layouts::app title="Service Due Report">
    @include('partials.flash')

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Service <span class="text-accent">Due</span></flux:heading>
            <flux:text class="text-zinc-400 mt-1">Assets with an upcoming or overdue next service date.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</span>
    </div>

    @include('reports._filters', ['showExpiry' => true, 'showServiceType' => true, 'expiryOptions' => $expiryFilterOptions,
        'exportUrl' => route('reports.service-due.export', request()->query())])

    <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category</th><th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Service Type</th><th class="px-4 py-3">Last Service</th>
                    <th class="px-4 py-3">Agency</th><th class="px-4 py-3">Next Service Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($records as $svc)
                    @php
                        $days = (int) now()->startOfDay()->diffInDays($svc->next_service_date->startOfDay(), false);
                        $overdue = $days < 0; $soon = !$overdue && $days <= 30;
                        $cls = $overdue ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
                    @endphp
                    <tr class="hover:bg-accent/5">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5"><a href="{{ route('assets.show', [$svc->asset, 'tab' => 'services']) }}" class="font-mono text-xs font-semibold text-accent">{{ $svc->asset?->asset_code }}</a></td>
                        <td class="px-4 py-2.5 font-medium text-zinc-100">{{ $svc->asset?->asset_name }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->asset?->category?->name ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->asset?->department ?: '—' }}</td>
                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">{{ $svc->service_type_label }}</span></td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->service_date->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-zinc-400">{{ $svc->service_agency ?: '—' }}</td>
                        <td class="px-4 py-2.5 {{ $cls }}">
                            {{ $svc->next_service_date->format('d M Y') }}
                            @if ($overdue) <span class="text-xs font-normal">(Overdue)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-zinc-500">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="mt-4 print:hidden">{{ $records->links() }}</div>@endif
</x-layouts::app>
