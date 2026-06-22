<x-layouts::app :title="$vendor->name . ' — Vendor'">
    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('vendors.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-accent dark:bg-zinc-800">
                        {{ $vendor->code }}
                    </span>
                    @if ($vendor->status === 'active')
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 ring-1 ring-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700">Inactive</span>
                    @endif
                </div>
                <flux:heading size="xl" class="mt-1 font-extrabold">{{ $vendor->name }}</flux:heading>
            </div>
        </div>
        <flux:button href="{{ route('vendors.edit', $vendor) }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.pencil class="size-4" />
            Edit
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Info Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-1">
            <h3 class="mb-4 text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Vendor Details</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs text-zinc-400">Contact Person</dt>
                    <dd class="mt-0.5 font-medium text-zinc-800 dark:text-zinc-100">{{ $vendor->contact_person ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-zinc-400">Phone</dt>
                    <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ $vendor->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-zinc-400">Email</dt>
                    <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                        @if ($vendor->email)
                            <a href="mailto:{{ $vendor->email }}" class="text-accent hover:underline">{{ $vendor->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-zinc-400">Address</dt>
                    <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300 whitespace-pre-line">{{ $vendor->address ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-zinc-400">Service Types</dt>
                    <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ $vendor->serviceTypesLabel() }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <dt class="text-xs text-zinc-400">SLA Response</dt>
                        <dd class="mt-0.5 font-mono text-zinc-700 dark:text-zinc-300">
                            {{ $vendor->sla_response_hours !== null ? $vendor->sla_response_hours . ' hrs' : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-400">SLA Resolution</dt>
                        <dd class="mt-0.5 font-mono text-zinc-700 dark:text-zinc-300">
                            {{ $vendor->sla_resolution_days !== null ? $vendor->sla_resolution_days . ' days' : '—' }}
                        </dd>
                    </div>
                </div>
                @if ($vendor->notes)
                    <div>
                        <dt class="text-xs text-zinc-400">Notes</dt>
                        <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300 whitespace-pre-line">{{ $vendor->notes }}</dd>
                    </div>
                @endif
            </dl>

            {{-- Stat cards --}}
            <div class="mt-5 grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-zinc-50 px-3 py-2 text-center dark:bg-zinc-800">
                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $vendor->warranties->count() }}</p>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wide">Warranties</p>
                </div>
                <div class="rounded-lg bg-zinc-50 px-3 py-2 text-center dark:bg-zinc-800">
                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $activeAmcCount }}</p>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wide">Active AMC</p>
                </div>
                <div class="rounded-lg bg-zinc-50 px-3 py-2 text-center dark:bg-zinc-800">
                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $vendor->services->count() }}</p>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wide">Services</p>
                </div>
            </div>
            @if ($totalServiceCost > 0)
                <div class="mt-2 rounded-lg bg-accent/5 px-3 py-2 text-center">
                    <p class="text-sm font-bold text-accent">₹ {{ number_format($totalServiceCost, 2) }}</p>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wide">Total Service Cost</p>
                </div>
            @endif
        </div>

        {{-- Linked Records Tabs --}}
        <div class="lg:col-span-2" x-data="{ tab: 'warranties' }">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">

                {{-- Tab nav --}}
                <div class="flex border-b border-zinc-200 dark:border-zinc-800">
                    <button type="button" @click="tab = 'warranties'"
                            :class="tab === 'warranties' ? 'border-b-2 border-accent text-accent' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                            class="px-5 py-3 text-sm font-medium transition">
                        Warranties ({{ $vendor->warranties->count() }})
                    </button>
                    <button type="button" @click="tab = 'amc'"
                            :class="tab === 'amc' ? 'border-b-2 border-accent text-accent' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                            class="px-5 py-3 text-sm font-medium transition">
                        AMC Contracts ({{ $vendor->amcContracts->count() }})
                    </button>
                    <button type="button" @click="tab = 'services'"
                            :class="tab === 'services' ? 'border-b-2 border-accent text-accent' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
                            class="px-5 py-3 text-sm font-medium transition">
                        Services ({{ $vendor->services->count() }})
                    </button>
                </div>

                {{-- Warranties tab --}}
                <div x-show="tab === 'warranties'" class="overflow-x-auto">
                    @if ($vendor->warranties->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-zinc-400">No linked warranties.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 text-left text-xs text-zinc-400 dark:border-zinc-800">
                                    <th class="px-4 py-2.5 font-semibold">Asset</th>
                                    <th class="px-4 py-2.5 font-semibold">Type</th>
                                    <th class="px-4 py-2.5 font-semibold">Scope</th>
                                    <th class="px-4 py-2.5 font-semibold">Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($vendor->warranties as $w)
                                    <tr class="hover:bg-accent/5">
                                        <td class="px-4 py-2.5">
                                            @if ($w->asset)
                                                <a href="{{ route('assets.show', $w->asset) }}" wire:navigate class="font-mono text-xs text-accent hover:underline">
                                                    {{ $w->asset->asset_code }}
                                                </a>
                                                <span class="ml-1.5 text-xs text-zinc-500">{{ $w->asset->asset_name }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $w->warrantyTypeLabel() }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $w->scopeLabel() }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $w->expiry_date?->format('d M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- AMC tab --}}
                <div x-show="tab === 'amc'" class="overflow-x-auto" x-cloak>
                    @if ($vendor->amcContracts->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-zinc-400">No linked AMC contracts.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 text-left text-xs text-zinc-400 dark:border-zinc-800">
                                    <th class="px-4 py-2.5 font-semibold">Asset</th>
                                    <th class="px-4 py-2.5 font-semibold">Contract No.</th>
                                    <th class="px-4 py-2.5 font-semibold">Coverage</th>
                                    <th class="px-4 py-2.5 font-semibold">Period</th>
                                    <th class="px-4 py-2.5 font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($vendor->amcContracts as $amc)
                                    <tr class="hover:bg-accent/5">
                                        <td class="px-4 py-2.5">
                                            @if ($amc->asset)
                                                <a href="{{ route('assets.show', $amc->asset) }}" wire:navigate class="font-mono text-xs text-accent hover:underline">
                                                    {{ $amc->asset->asset_code }}
                                                </a>
                                                <span class="ml-1.5 text-xs text-zinc-500">{{ $amc->asset->asset_name }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $amc->contract_number ?: '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $amc->coverage_type_label }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $amc->amc_date_from?->format('d M Y') }} – {{ $amc->amc_date_to?->format('d M Y') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $amc->amc_amount ? '₹ ' . number_format($amc->amc_amount, 2) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Services tab --}}
                <div x-show="tab === 'services'" class="overflow-x-auto" x-cloak>
                    @if ($vendor->services->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-zinc-400">No linked service records.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 text-left text-xs text-zinc-400 dark:border-zinc-800">
                                    <th class="px-4 py-2.5 font-semibold">Asset</th>
                                    <th class="px-4 py-2.5 font-semibold">Service Type</th>
                                    <th class="px-4 py-2.5 font-semibold">Date</th>
                                    <th class="px-4 py-2.5 font-semibold">Cost</th>
                                    <th class="px-4 py-2.5 font-semibold">Condition</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($vendor->services as $svc)
                                    <tr class="hover:bg-accent/5">
                                        <td class="px-4 py-2.5">
                                            @if ($svc->asset)
                                                <a href="{{ route('assets.show', $svc->asset) }}" wire:navigate class="font-mono text-xs text-accent hover:underline">
                                                    {{ $svc->asset->asset_code }}
                                                </a>
                                                <span class="ml-1.5 text-xs text-zinc-500">{{ $svc->asset->asset_name }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $svc->service_type_label }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $svc->service_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $svc->service_cost ? '₹ ' . number_format($svc->service_cost, 2) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-xs {{ $svc->condition_rating_color }}">{{ $svc->condition_rating_label }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </div>
        </div>

    </div>
</x-layouts::app>
