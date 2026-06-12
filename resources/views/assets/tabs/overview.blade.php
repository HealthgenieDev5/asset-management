@php
    $row = fn(string $label, $value, string $extra = '') => [$label, $value, $extra];
@endphp

<div class="space-y-6">

    {{-- Core Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Core Details</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Asset Code',    $asset->asset_code],
                ['Category',      $asset->category?->name],
                ['Subcategory',   $asset->subcategory?->name ?: '—'],
                ['Manufacturer',  $asset->manufacturer ?: '—'],
                ['Model',         $asset->model ?: '—'],
                ['Model Year',    $asset->model_year ?: '—'],
                ['Serial Number', $asset->serial_number ?: '—'],
                ['Status',        $asset->status_label],
            ] as [$label, $value])
                <div>
                    <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                    <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                        @if ($label === 'Asset Code')
                            <span class="font-mono text-accent">{{ $value }}</span>
                        @elseif ($label === 'Status')
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                                {{ $value }}
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Location & Ownership --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Location & Ownership</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Location',        $asset->location ?: '—'],
                ['Department',      $asset->department ?: '—'],
                ['Custodian',       $asset->custodian ?: '—'],
                ['Vendor/Supplier', $asset->vendor_supplier ?: '—'],
            ] as [$label, $value])
                <div>
                    <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                    <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Purchase Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Purchase Details</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Bill Number',   $asset->bill_no ?: '—'],
                ['Bill Amount',   $asset->bill_amount ? '₹ ' . number_format($asset->bill_amount, 2) : '—'],
                ['Bill Date',     $asset->bill_date?->format('d M Y') ?: '—'],
                ['Purchase Date', $asset->purchase_date?->format('d M Y') ?: '—'],
            ] as [$label, $value])
                <div>
                    <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                    <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Original Warranty --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Original Warranty</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium text-zinc-500">Warranty Details</dt>
                <dd class="mt-0.5 text-sm text-zinc-200">{{ $asset->warranty_details ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                <dd class="mt-0.5 text-sm text-zinc-200">
                    @if ($asset->warranty_lapse_date)
                        @php $expired = $asset->warranty_lapse_date->isPast(); @endphp
                        <span class="{{ $expired ? 'text-red-400' : 'text-zinc-200' }}">
                            {{ $asset->warranty_lapse_date->format('d M Y') }}
                            @if ($expired) <span class="text-xs">(expired)</span> @endif
                        </span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                <dd class="mt-0.5 text-sm text-zinc-200">
                    {{ $asset->warranty_reminder_before_days ? $asset->warranty_reminder_before_days . ' days' : '—' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Maintenance Schedule --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Maintenance Schedule</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-medium text-zinc-500">Schedule Type</dt>
                <dd class="mt-0.5 text-sm text-zinc-800 capitalize dark:text-zinc-200">{{ str_replace('_', ' ', $asset->maintenance_schedule_type) }}</dd>
            </div>
            @if ($asset->maintenance_schedule_type !== 'none')
                <div>
                    <dt class="text-xs font-medium text-zinc-500">Interval</dt>
                    <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                        {{ $asset->maintenance_interval_value }} {{ str_replace('_', ' ', $asset->maintenance_interval_unit) }}
                    </dd>
                </div>
            @endif
            <div>
                <dt class="text-xs font-medium text-zinc-500">Inspection Required</dt>
                <dd class="mt-0.5 text-sm">
                    <span class="{{ $asset->inspection_required ? 'text-green-400' : 'text-zinc-500' }}">
                        {{ $asset->inspection_required ? 'Yes' : 'No' }}
                    </span>
                </dd>
            </div>
            @if ($asset->inspection_required)
                <div>
                    <dt class="text-xs font-medium text-zinc-500">Inspection Frequency</dt>
                    <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                        {{ $asset->inspection_frequency_value }} {{ $asset->inspection_frequency_unit }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Vehicle Compliance (only for VE category) --}}
    @if ($asset->isVehicle())
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Vehicle Compliance</flux:heading>
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                @if ($asset->registration_number)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Registration Number</dt>
                        <dd class="mt-0.5 font-mono text-sm font-semibold uppercase text-zinc-800 dark:text-zinc-200">{{ $asset->registration_number }}</dd>
                    </div>
                @endif

                @foreach ([
                    ['PUC Expiry',      $asset->puc_expiry_date,      $asset->puc_reminder_before_days],
                    ['Fitness Expiry',  $asset->fitness_expiry_date,  $asset->fitness_reminder_before_days],
                    ['Road Tax Expiry', $asset->road_tax_expiry_date, $asset->road_tax_reminder_before_days],
                ] as [$label, $date, $reminderDays])
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm">
                            @if ($date)
                                @php $expired = $date->isPast(); $soon = !$expired && $date->diffInDays(now()) <= 30; @endphp
                                <span class="{{ $expired ? 'text-red-400' : ($soon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }}">
                                    {{ $date->format('d M Y') }}
                                    @if ($expired) <span class="text-xs">(expired)</span>
                                    @elseif ($soon) <span class="text-xs">(expiring soon)</span>
                                    @endif
                                </span>
                                @if ($reminderDays)
                                    <span class="ml-1 text-xs text-zinc-600">· remind {{ $reminderDays }}d before</span>
                                @endif
                            @else
                                <span class="text-zinc-500">—</span>
                            @endif
                        </dd>
                    </div>
                @endforeach

                @foreach ([
                    ['OBV',               $asset->vehicle_obv ? '₹ ' . number_format($asset->vehicle_obv, 2) : '—'],
                    ['Depreciation %',    $asset->vehicle_depreciation_percent ? $asset->vehicle_depreciation_percent . '%' : '—'],
                    ['Book Value',        $asset->vehicle_depreciation_book_value ? '₹ ' . number_format($asset->vehicle_depreciation_book_value, 2) : '—'],
                ] as [$label, $value])
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    {{-- Remarks --}}
    @if ($asset->remarks)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading class="mb-2 font-semibold text-zinc-700 dark:text-zinc-300">Remarks</flux:heading>
            <p class="text-sm text-zinc-700 whitespace-pre-line dark:text-zinc-300">{{ $asset->remarks }}</p>
        </div>
    @endif

    {{-- Meta --}}
    <div class="text-xs text-zinc-600 space-y-0.5">
        <p>Created: {{ $asset->created_at->format('d M Y, h:i A') }}</p>
        <p>Last updated: {{ $asset->updated_at->format('d M Y, h:i A') }}</p>
    </div>
</div>
