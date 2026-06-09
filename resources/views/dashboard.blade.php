<x-layouts::app :title="__('Dashboard')">

    {{-- Page Header --}}
    <div class="mb-6">
        <flux:heading size="xl" class="font-extrabold">
            Asset Management <span class="text-accent">Dashboard</span>
        </flux:heading>
        <flux:text class="mt-1 text-zinc-400">Overview of your company's fixed assets and upcoming reminders.</flux:text>
    </div>

    {{-- ── Row 1: Asset Status Cards ── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
        @php
            $statusCards = [
                ['label' => 'Total Assets',          'value' => $assetStats['total'],             'status' => null,                'icon' => 'clipboard-document-list', 'color' => 'text-accent'],
                ['label' => 'Active',                'value' => $assetStats['active'],            'status' => 'active',            'icon' => 'check-circle',            'color' => 'text-green-400'],
                ['label' => 'Under Repair',          'value' => $assetStats['under_repair'],      'status' => 'under_repair',      'icon' => 'wrench-screwdriver',      'color' => 'text-yellow-400'],
                ['label' => 'Under Maintenance',     'value' => $assetStats['under_maintenance'], 'status' => 'under_maintenance', 'icon' => 'cog-6-tooth',             'color' => 'text-blue-400'],
                ['label' => 'Disposed / Written Off','value' => $assetStats['disposed'],          'status' => 'disposed',          'icon' => 'archive-box-x-mark',      'color' => 'text-zinc-500'],
                ['label' => 'Inactive',              'value' => $assetStats['inactive'],          'status' => 'inactive',          'icon' => 'pause-circle',            'color' => 'text-zinc-500'],
            ];
        @endphp

        @foreach ($statusCards as $card)
            <a href="{{ route('assets.index', $card['status'] ? ['status' => $card['status']] : []) }}"
               class="rounded-xl border border-zinc-800 bg-zinc-900 p-5 transition hover:border-zinc-600">
                <div class="flex items-center justify-between">
                    <flux:text class="text-xs font-medium text-zinc-400">{{ $card['label'] }}</flux:text>
                    <flux:icon :icon="$card['icon']" class="size-4 {{ $card['color'] }}" />
                </div>
                <div class="mt-3 text-3xl font-extrabold text-zinc-100">{{ number_format($card['value']) }}</div>
            </a>
        @endforeach
    </div>

    {{-- ── Row 2: Global Reminder Totals ── --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('asset-reminders.index', ['filter' => 'expired']) }}"
           class="rounded-xl border border-red-800/50 bg-red-950/20 p-5 transition hover:border-red-700/60">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm font-medium text-red-400">Expired Reminders</flux:text>
                <flux:icon.exclamation-circle class="size-5 text-red-500" />
            </div>
            <div class="mt-3 text-3xl font-extrabold text-red-400">{{ number_format($reminderStats['expired']) }}</div>
            <flux:text class="mt-1 text-xs text-red-500">Immediate attention required</flux:text>
        </a>

        <a href="{{ route('asset-reminders.index', ['filter' => 'upcoming']) }}"
           class="rounded-xl border border-orange-800/50 bg-orange-950/20 p-5 transition hover:border-orange-700/60">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm font-medium text-orange-400">Expiring in 7 Days</flux:text>
                <flux:icon.clock class="size-5 text-orange-500" />
            </div>
            <div class="mt-3 text-3xl font-extrabold text-orange-400">{{ number_format($reminderStats['expiring_7']) }}</div>
            <flux:text class="mt-1 text-xs text-orange-500">Action needed this week</flux:text>
        </a>

        <a href="{{ route('asset-reminders.index', ['filter' => 'upcoming']) }}"
           class="rounded-xl border border-yellow-800/50 bg-yellow-950/20 p-5 transition hover:border-yellow-700/60">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm font-medium text-yellow-400">Expiring in 30 Days</flux:text>
                <flux:icon.calendar-days class="size-5 text-yellow-500" />
            </div>
            <div class="mt-3 text-3xl font-extrabold text-yellow-400">{{ number_format($reminderStats['expiring_30']) }}</div>
            <flux:text class="mt-1 text-xs text-yellow-500">Plan renewal this month</flux:text>
        </a>
    </div>

    {{-- ── Row 3: Per-Type Expiry Breakdown ── --}}
    <div class="mt-6">
        <flux:heading class="mb-3 text-sm font-semibold text-zinc-400 uppercase tracking-wider">Expiry Breakdown by Type</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $expiryTypes = [
                    ['label' => 'Original Warranty',  'data' => $warranty,    'icon' => 'shield-check',   'accent' => 'text-violet-400'],
                    ['label' => 'Extended Warranty',  'data' => $extWarranty, 'icon' => 'shield-exclamation','accent' => 'text-indigo-400'],
                    ['label' => 'AMC Contract',       'data' => $amc,         'icon' => 'document-text',  'accent' => 'text-blue-400'],
                    ['label' => 'Insurance Policy',   'data' => $insurance,   'icon' => 'banknotes',       'accent' => 'text-teal-400'],
                ];
            @endphp

            @foreach ($expiryTypes as $et)
                <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <flux:icon :icon="$et['icon']" class="size-4 {{ $et['accent'] }}" />
                        <flux:text class="text-sm font-semibold text-zinc-300">{{ $et['label'] }}</flux:text>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-red-400">Expired</span>
                            <span class="font-bold text-red-400">{{ $et['data']['expired'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-orange-400">Within 7 days</span>
                            <span class="font-bold text-orange-400">{{ $et['data']['in7'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-yellow-400">Within 30 days</span>
                            <span class="font-bold text-yellow-400">{{ $et['data']['in30'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Row 4: Vehicle Compliance ── --}}
    @if ($puc['expired'] + $puc['in7'] + $puc['in30'] + $fitness['expired'] + $fitness['in7'] + $fitness['in30'] + $roadTax['expired'] + $roadTax['in7'] + $roadTax['in30'] > 0)
    <div class="mt-6">
        <flux:heading class="mb-3 text-sm font-semibold text-zinc-400 uppercase tracking-wider">Vehicle Compliance</flux:heading>
        <div class="grid gap-4 sm:grid-cols-3">
            @php
                $vehicleTypes = [
                    ['label' => 'PUC Certificate',  'data' => $puc,     'icon' => 'cloud',           'accent' => 'text-sky-400'],
                    ['label' => 'Fitness Certificate','data' => $fitness, 'icon' => 'check-badge',     'accent' => 'text-green-400'],
                    ['label' => 'Road Tax',          'data' => $roadTax, 'icon' => 'currency-rupee',  'accent' => 'text-amber-400'],
                ];
            @endphp

            @foreach ($vehicleTypes as $vt)
                <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <flux:icon :icon="$vt['icon']" class="size-4 {{ $vt['accent'] }}" />
                        <flux:text class="text-sm font-semibold text-zinc-300">{{ $vt['label'] }}</flux:text>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-red-400">Expired</span>
                            <span class="font-bold text-red-400">{{ $vt['data']['expired'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-orange-400">Within 7 days</span>
                            <span class="font-bold text-orange-400">{{ $vt['data']['in7'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-yellow-400">Within 30 days</span>
                            <span class="font-bold text-yellow-400">{{ $vt['data']['in30'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Row 4b: Service Due & Certification Expiry ── --}}
    @if ($serviceDue['expired'] + $serviceDue['in7'] + $serviceDue['in30'] + $certExpiry['expired'] + $certExpiry['in7'] + $certExpiry['in30'] > 0)
    <div class="mt-6">
        <flux:heading class="mb-3 text-sm font-semibold text-zinc-400 uppercase tracking-wider">Service &amp; Inspection</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2">
            @php
                $serviceTypes = [
                    ['label' => 'Next Service Due',     'data' => $serviceDue, 'icon' => 'cog-6-tooth',   'accent' => 'text-cyan-400'],
                    ['label' => 'Certification Expiry', 'data' => $certExpiry, 'icon' => 'document-check','accent' => 'text-purple-400'],
                ];
            @endphp

            @foreach ($serviceTypes as $st)
                <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <flux:icon :icon="$st['icon']" class="size-4 {{ $st['accent'] }}" />
                        <flux:text class="text-sm font-semibold text-zinc-300">{{ $st['label'] }}</flux:text>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-red-400">Overdue / Expired</span>
                            <span class="font-bold text-red-400">{{ $st['data']['expired'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-orange-400">Within 7 days</span>
                            <span class="font-bold text-orange-400">{{ $st['data']['in7'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-yellow-400">Within 30 days</span>
                            <span class="font-bold text-yellow-400">{{ $st['data']['in30'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Row 5: Upcoming Expiries Table + Recent Assets ── --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">

        {{-- Upcoming Expiries --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="flex items-center justify-between mb-4">
                <flux:heading class="font-semibold text-zinc-300">Upcoming Expiries (30 days)</flux:heading>
                <flux:button href="{{ route('asset-reminders.index') }}" wire:navigate variant="ghost" size="sm">
                    View All
                </flux:button>
            </div>

            @if ($upcomingExpiries->isEmpty())
                <div class="py-8 text-center">
                    <flux:icon.check-circle class="mx-auto mb-2 size-8 text-green-500" />
                    <flux:text class="text-sm text-zinc-500">No expiries in the next 30 days.</flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800">
                                <th class="pb-2 text-left text-xs font-medium text-zinc-500">Asset</th>
                                <th class="pb-2 text-left text-xs font-medium text-zinc-500">Type</th>
                                <th class="pb-2 text-right text-xs font-medium text-zinc-500">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/60">
                            @foreach ($upcomingExpiries as $item)
                                @php
                                    $daysLeft = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($item['expiry_date'])->startOfDay(), false);
                                    $color = $daysLeft <= 7 ? 'text-orange-400' : 'text-yellow-400';
                                @endphp
                                <tr class="group">
                                    <td class="py-2 pr-3">
                                        @if ($item['asset_id'])
                                            <a href="{{ route('assets.show', [$item['asset_id'], 'tab' => $item['tab']]) }}"
                                               class="font-medium text-zinc-200 hover:text-accent transition">
                                                {{ $item['asset_code'] }}
                                            </a>
                                            <div class="text-xs text-zinc-500 truncate max-w-35">{{ $item['asset_name'] }}</div>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-zinc-400 text-xs">{{ $item['type'] }}</td>
                                    <td class="py-2 text-right">
                                        <span class="{{ $color }} font-semibold">
                                            {{ \Carbon\Carbon::parse($item['expiry_date'])->format('d M Y') }}
                                        </span>
                                        <div class="text-xs {{ $color }} opacity-75">{{ $daysLeft }}d left</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Recent Assets --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="flex items-center justify-between mb-4">
                <flux:heading class="font-semibold text-zinc-300">Recently Added Assets</flux:heading>
                <flux:button href="{{ route('assets.create') }}" wire:navigate variant="ghost" size="sm">
                    <flux:icon.plus class="size-3" />
                    Add Asset
                </flux:button>
            </div>

            @if ($recentAssets->isEmpty())
                <div class="py-8 text-center">
                    <flux:icon.clipboard-document-list class="mx-auto mb-2 size-8 text-zinc-600" />
                    <flux:text class="text-sm text-zinc-500">No assets added yet.</flux:text>
                    <flux:button href="{{ route('assets.create') }}" wire:navigate variant="primary" size="sm" class="mt-3">
                        Add your first asset
                    </flux:button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800">
                                <th class="pb-2 text-left text-xs font-medium text-zinc-500">Asset</th>
                                <th class="pb-2 text-left text-xs font-medium text-zinc-500">Category</th>
                                <th class="pb-2 text-right text-xs font-medium text-zinc-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/60">
                            @foreach ($recentAssets as $asset)
                                @php
                                    $statusColor = match($asset->status) {
                                        'active'            => 'bg-green-900/40 text-green-400',
                                        'under_repair'      => 'bg-yellow-900/40 text-yellow-400',
                                        'under_maintenance' => 'bg-blue-900/40 text-blue-400',
                                        'disposed','written_off' => 'bg-zinc-800 text-zinc-500',
                                        'inactive'          => 'bg-zinc-800 text-zinc-500',
                                        default             => 'bg-zinc-800 text-zinc-400',
                                    };
                                @endphp
                                <tr>
                                    <td class="py-2 pr-3">
                                        <a href="{{ route('assets.show', $asset) }}"
                                           class="font-medium text-zinc-200 hover:text-accent transition">
                                            {{ $asset->asset_code }}
                                        </a>
                                        <div class="text-xs text-zinc-500 truncate max-w-35">{{ $asset->asset_name }}</div>
                                    </td>
                                    <td class="py-2 pr-3 text-xs text-zinc-400">
                                        {{ $asset->category?->name ?? '—' }}
                                    </td>
                                    <td class="py-2 text-right">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                            {{ ucwords(str_replace('_', ' ', $asset->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($assetStats['total'] > 8)
                    <div class="mt-3 text-center">
                        <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" size="sm">
                            View all {{ number_format($assetStats['total']) }} assets →
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- ── Row 6: Quick Actions ── --}}
    <div class="mt-6 rounded-xl border border-zinc-800 bg-zinc-900 p-5">
        <flux:heading class="mb-4 font-semibold text-zinc-300">Quick Actions</flux:heading>
        <div class="flex flex-wrap gap-3">
            <flux:button href="{{ route('assets.create') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.plus class="size-4" />
                New Asset
            </flux:button>
            <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.clipboard-document-list class="size-4" />
                Asset Register
            </flux:button>
            <flux:button href="{{ route('asset-reminders.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.bell-alert class="size-4" />
                All Reminders
            </flux:button>
            <flux:button href="{{ route('asset-categories.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.tag class="size-4" />
                Categories
            </flux:button>
            <flux:button href="{{ route('asset-subcategories.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.queue-list class="size-4" />
                Subcategories
            </flux:button>
            <flux:button href="{{ route('reports.asset-register') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.document-chart-bar class="size-4" />
                Reports
            </flux:button>
        </div>
    </div>

</x-layouts::app>
