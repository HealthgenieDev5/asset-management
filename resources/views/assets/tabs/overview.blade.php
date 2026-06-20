@php
    $row = fn(string $label, $value, string $extra = '') => [$label, $value, $extra];
@endphp

<div class="space-y-6">

    {{-- Core Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Core Details</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
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
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
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

        @php
            use Illuminate\Support\Facades\Storage;
            $purchaseBills = $asset->documents->where('document_type', 'purchase_bill')->values();
        @endphp
        @if ($purchaseBills->isNotEmpty())
            @php
                $previewBills = $purchaseBills->filter(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'))->values();
            @endphp
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <p class="mb-2 text-xs font-medium text-zinc-500">Purchase Bill</p>

                @if ($previewBills->isNotEmpty())
                    <div id="pond-wrap-purchase-bill" class="max-w-md"
                         x-data
                         x-init="
                             const wrap     = document.getElementById('pond-wrap-purchase-bill');
                             const mount    = document.getElementById('pond-mount-purchase-bill');
                             const files    = {{ Js::from($previewBills->map(fn($d) => ['source' => Storage::url($d->file_path), 'options' => ['type' => 'local']])) }};
                             const fileMeta = {{ Js::from($previewBills->map(fn($d) => ['src' => Storage::url($d->file_path), 'title' => $d->file_original_name, 'isPdf' => str_contains($d->file_mime_type ?? '', 'pdf')])) }};
                             let pond = null;
                             const mountPond = () => {
                                 if (pond) { try { destroyDocImageViewer(pond); } catch(e) {} pond = null; }
                                 if (!mount.isConnected) return;
                                 mount.innerHTML = '';
                                 const input = document.createElement('input');
                                 input.type = 'file';
                                 mount.appendChild(input);
                                 pond = initDocImageViewer(input, files);
                             };
                             wrap.addEventListener('click', (e) => {
                                 if (wrap.offsetParent === null) return;
                                 const item = e.target.closest('.filepond--item');
                                 if (!item) return;
                                 const idx = Array.from(wrap.querySelectorAll('.filepond--item')).indexOf(item);
                                 if (fileMeta[idx]) $dispatch('bill-lightbox-open', fileMeta[idx]);
                             });
                             window.addEventListener('tab-visible', (e) => {
                                 if (e.detail === 'overview') setTimeout(mountPond, 50);
                             });
                             if (document.readyState === 'complete') { setTimeout(mountPond, 50); }
                             else { window.addEventListener('load', () => setTimeout(mountPond, 50), { once: true }); }
                         ">
                        <div id="pond-mount-purchase-bill"></div>
                    </div>

                    <div class="mt-2 max-w-md space-y-1">
                        @foreach ($previewBills as $doc)
                            <div class="flex items-center gap-2 px-1">
                                <span class="min-w-0 flex-1 truncate text-xs text-zinc-500">{{ $doc->file_original_name }}</span>
                                <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                   class="shrink-0 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php $otherBills = $purchaseBills->diff($previewBills); @endphp
                @if ($otherBills->isNotEmpty())
                    <div class="mt-2 space-y-1.5">
                        @foreach ($otherBills as $doc)
                            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                <span class="text-xs text-zinc-500">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                   class="text-xs text-accent hover:underline">View</a>
                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                   class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Purchase bill image/PDF lightbox --}}
            <div x-data="docLightbox()"
                 x-on:bill-lightbox-open.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
                 x-show="open"
                 x-cloak
                 x-on:keydown.escape.window="if (open) close()"
                 class="fixed inset-0 z-60 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/85" x-on:click="close()"></div>
                <div class="relative z-10 flex max-w-5xl w-full flex-col rounded-xl overflow-hidden shadow-2xl" x-on:click.stop>
                    <div class="flex items-center justify-between bg-zinc-900 px-4 py-2 shrink-0">
                        <span x-text="title" class="truncate text-sm text-zinc-300"></span>
                        <button type="button" x-on:click="close()"
                            class="ml-4 flex shrink-0 items-center gap-1 text-sm text-zinc-400 hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                            </svg>
                            Close
                        </button>
                    </div>
                    <template x-if="!isPdf">
                        <div class="flex items-center justify-center bg-zinc-950 w-full" style="height:82vh;">
                            <img :src="src" :alt="title"
                                 class="max-h-full max-w-full object-contain rounded-lg shadow-xl">
                        </div>
                    </template>
                    <template x-if="isPdf">
                        <object :data="src" type="application/pdf"
                                class="w-full bg-white" style="height:82vh;">
                            <p class="text-center p-4">
                                <a :href="src" target="_blank" class="underline text-accent">Open PDF in new tab</a>
                            </p>
                        </object>
                    </template>
                </div>
            </div>
        @endif
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
                                @php $expired = $date->isPast(); $daysLeft = (int) now()->diffInDays($date, false); $soon = !$expired && $daysLeft <= ($reminderDays ?? 30); @endphp
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
