@php use Illuminate\Support\Facades\Storage; @endphp

{{-- ── Doc Lightbox ── --}}
<div x-data="docLightbox()"
     x-on:keydown.escape.window="close()"
     x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
     x-show="open" style="display:none"
     class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-2.5">
        <p class="truncate text-sm font-medium text-white" x-text="title"></p>
        <button type="button" @click="close()"
                class="shrink-0 rounded-md p-1 text-white/60 hover:bg-white/10 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        </button>
    </div>
    <div class="flex flex-1 items-center justify-center overflow-hidden p-4">
        <template x-if="isPdf">
            <iframe :src="src" class="h-full w-full max-w-4xl rounded-lg border-0 bg-white" style="min-height:70vh"></iframe>
        </template>
        <template x-if="!isPdf">
            <img :src="src" :alt="title" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
        </template>
    </div>
</div>

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Servicing History</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->services->count() }} {{ Str::plural('record', $asset->services->count()) }}
                @if ($asset->services->sum('service_cost') > 0)
                    &nbsp;·&nbsp; Total cost: ₹ {{ number_format($asset->services->sum('service_cost'), 2) }}
                @endif
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-service')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Servicing
        </button>
    </div>

    {{-- Add Modal --}}
    <x-modal name="add-service" title="New Servicing Record" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'service' && !old('_service_id')">
        <form method="POST" action="{{ route('assets.services.store', $asset) }}"
              enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="service">

            @include('assets.tabs._service-form', ['service' => null])

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                    Save Record
                </button>
                <button type="button" x-on:click="$dispatch('close-modal-add-service')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Modals (one per service record) --}}
    @foreach ($asset->services->sortByDesc('service_date') as $svc)
        <x-modal name="edit-service-{{ $svc->id }}" title="Edit Servicing Record" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'service' && (int) old('_service_id') === $svc->id">
            <form method="POST" action="{{ route('assets.services.update', [$asset, $svc]) }}"
                  enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="service">
                <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                @include('assets.tabs._service-form', ['service' => $svc])

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Save Changes
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-service-{{ $svc->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- View Modals (one per service record) --}}
    @foreach ($asset->services->sortByDesc('service_date') as $svc)
        @php
            $viewNextDays    = $svc->daysUntilNextService();
            $viewNextOverdue = $svc->isNextServiceOverdue();
            $viewCertDays    = $svc->daysUntilCertificationExpiry();
            $viewCertExpired = $svc->isCertificationExpired();
            $viewPartsCost   = $svc->totalPartsCost();
        @endphp
        <x-modal name="view-service-{{ $svc->id }}" title="Servicing Record Details">
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                                {{ $svc->service_type_label }}
                            </span>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $svc->service_date->format('d M Y') }}</h3>
                        </div>
                        @if ($svc->vendor || $svc->service_agency)
                            <p class="mt-1 text-xs text-zinc-500">
                                @if ($svc->vendor)
                                    <a href="{{ route('vendors.show', $svc->vendor) }}" wire:navigate class="text-accent hover:underline">{{ $svc->vendor->name }}</a>
                                @else
                                    {{ $svc->service_agency }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-wrap justify-end gap-1.5">
                        @if ($viewNextOverdue)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Service Overdue</span>
                        @elseif ($viewNextDays !== null && $viewNextDays <= 30)
                            <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Due in {{ $viewNextDays }}d</span>
                        @endif
                        @if ($viewCertExpired)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Cert Expired</span>
                        @elseif ($viewCertDays !== null && $viewCertDays <= 30)
                            <span class="rounded-full bg-orange-400/10 px-2 py-0.5 text-xs font-medium text-orange-400">Cert in {{ $viewCertDays }}d</span>
                        @endif
                    </div>
                </div>

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Technician</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->technician_name ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Condition</dt>
                        <dd class="mt-0.5 text-sm font-medium {{ $svc->condition_rating ? $svc->condition_rating_color : 'text-zinc-800 dark:text-zinc-100' }}">{{ $svc->condition_rating ? $svc->condition_rating_label : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Service Cost</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->service_cost ? 'Rs. ' . number_format($svc->service_cost, 2) : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Parts Cost</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $viewPartsCost > 0 ? 'Rs. ' . number_format($viewPartsCost, 2) : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Grand Total</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $svc->grandTotalCost() > 0 ? 'Rs. ' . number_format($svc->grandTotalCost(), 2) : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->bill_no ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Bill Date</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->bill_date?->format('d M Y') ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Next Service Due</dt>
                        <dd class="mt-0.5 text-sm {{ $viewNextOverdue ? 'text-red-400 font-semibold' : ($viewNextDays !== null && $viewNextDays <= 30 ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $svc->next_service_date?->format('d M Y') ?: '--' }}
                            @if ($viewNextOverdue) <span class="text-xs font-normal">(Overdue)</span>
                            @elseif ($viewNextDays !== null && $viewNextDays <= 30) <span class="text-xs">({{ $viewNextDays }}d)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Interval</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $svc->service_interval_value && $svc->service_interval_unit ? 'Every ' . $svc->service_interval_value . ' ' . $svc->service_interval_unit : '--' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Meter / Op. Hours</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->meter_reading ? number_format($svc->meter_reading) : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Odometer (km)</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->mileage_reading ? number_format($svc->mileage_reading) : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Downtime</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->downtime_hours ? $svc->downtime_hours . ' hrs' : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Certification Expiry</dt>
                        <dd class="mt-0.5 text-sm {{ $viewCertExpired ? 'text-red-400 font-semibold' : ($viewCertDays !== null && $viewCertDays <= 30 ? 'text-orange-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $svc->certification_expiry?->format('d M Y') ?: '--' }}
                            @if ($viewCertExpired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($viewCertDays !== null && $viewCertDays <= 30) <span class="text-xs">({{ $viewCertDays }}d left)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Certification Reminder</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->certification_reminder_before_days ? $svc->certification_reminder_before_days . ' days' : '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Next Service Reminder</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->next_service_reminder_before_days ? $svc->next_service_reminder_before_days . ' days' : '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Work Done</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->work_done ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Safety Notes</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->safety_notes ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->remarks ?: '--' }}</dd>
                    </div>
                </dl>

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Parts</p>
                    @if ($svc->parts->isNotEmpty())
                        <div class="divide-y divide-zinc-200/60 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                            @foreach ($svc->parts as $part)
                                @php $lineTotal = $part->part_cost !== null ? (float) $part->part_cost * $part->quantity : null; @endphp
                                <div class="px-3 py-2">
                                    <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-100">{{ $part->part_name }}</p>
                                    <p class="mt-0.5 text-[11px] text-zinc-500">
                                        Qty: {{ $part->quantity }}
                                        @if ($part->part_cost !== null)
                                            &middot; Rs. {{ number_format($part->part_cost, 2) }}
                                            @if ($part->quantity > 1 && $lineTotal !== null) = Rs. {{ number_format($lineTotal, 2) }} @endif
                                        @endif
                                        @if ($part->purchased_from) &middot; {{ $part->purchased_from }} @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No parts recorded.</p>
                    @endif
                </div>

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                    @if ($svc->documents->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($svc->documents as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                    <button type="button"
                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                        title="View"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.eye class="size-3" />
                                    </button>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                        title="Download"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No documents attached.</p>
                    @endif
                </div>
            </div>
        </x-modal>
    @endforeach

    {{-- Add Part Modals (one per service record) --}}
    @foreach ($asset->services as $svc)
        <x-modal name="add-part-{{ $svc->id }}" title="Add Part" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'part' && (int) old('_service_id') === $svc->id">
            <form method="POST" action="{{ route('assets.services.parts.store', [$asset, $svc]) }}"
                  class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="part">
                <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                @include('assets.tabs._part-form', ['part' => null])

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Save Part
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-add-part-{{ $svc->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- Records List --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            @php
                $nextDays    = $svc->daysUntilNextService();
                $nextOverdue = $svc->isNextServiceOverdue();
                $certDays    = $svc->daysUntilCertificationExpiry();
                $certExpired = $svc->isCertificationExpired();
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Card Header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-3 min-w-0 flex-wrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                            {{ $svc->service_type_label }}
                        </span>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $svc->service_date->format('d M Y') }}
                        </span>
                        @if ($svc->vendor || $svc->service_agency)
                            <span class="text-xs text-zinc-500 dark:text-zinc-500">
                                @if ($svc->vendor)
                                    <a href="{{ route('vendors.show', $svc->vendor) }}" wire:navigate class="text-accent hover:underline">{{ $svc->vendor->name }}</a>
                                @else
                                    {{ $svc->service_agency }}
                                @endif
                            </span>
                        @endif
                        @if ($svc->service_cost)
                            <span class="text-xs font-mono text-zinc-400">₹ {{ number_format($svc->service_cost, 2) }}</span>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5 flex-wrap justify-end">
                        @if ($nextOverdue)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Service Overdue</span>
                        @elseif ($nextDays !== null && $nextDays <= 30)
                            <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Due in {{ $nextDays }}d</span>
                        @endif
                        @if ($certExpired)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Cert Expired</span>
                        @elseif ($certDays !== null && $certDays <= 30)
                            <span class="rounded-full bg-orange-400/10 px-2 py-0.5 text-xs font-medium text-orange-400">Cert in {{ $certDays }}d</span>
                        @endif
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'serviceid' => $svc->id]) }}"
                           title="{{ $svc->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border border-accent text-accent hover:bg-accent/10 transition-colors">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-view-service-{{ $svc->id }}')"
                                aria-label="View service record"
                                title="View service record"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-edit-service-{{ $svc->id }}')"
                                aria-label="Edit service record"
                                title="Edit service record"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.services.destroy', [$asset, $svc]) }}"
                              onsubmit="return confirm('Delete this service record?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete service record"
                                    title="Delete service record"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Detail Grid --}}
                <div class="px-5 py-4">
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($svc->technician_name)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Technician</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->technician_name }}</dd>
                            </div>
                        @endif

                        @if ($svc->condition_rating)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Condition</dt>
                                <dd class="mt-0.5 text-sm font-medium {{ $svc->condition_rating_color }}">{{ $svc->condition_rating_label }}</dd>
                            </div>
                        @endif

                        @if ($svc->next_service_date)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Next Service Due</dt>
                                <dd class="mt-0.5 text-sm {{ $nextOverdue ? 'text-red-400 font-semibold' : ($nextDays !== null && $nextDays <= 30 ? 'text-yellow-400' : 'text-zinc-200') }}">
                                    {{ $svc->next_service_date->format('d M Y') }}
                                    @if ($nextOverdue)
                                        <span class="text-xs font-normal">(Overdue)</span>
                                    @elseif ($nextDays !== null && $nextDays <= 30)
                                        <span class="text-xs">({{ $nextDays }}d)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif

                        @if ($svc->service_interval_value && $svc->service_interval_unit)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Interval</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">Every {{ $svc->service_interval_value }} {{ $svc->service_interval_unit }}</dd>
                            </div>
                        @endif

                        @if ($svc->meter_reading)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Meter / Op. Hours</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ number_format($svc->meter_reading) }}</dd>
                            </div>
                        @endif

                        @if ($svc->mileage_reading)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Odometer (km)</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ number_format($svc->mileage_reading) }}</dd>
                            </div>
                        @endif

                        @if ($svc->downtime_hours)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Downtime</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->downtime_hours }} hrs</dd>
                            </div>
                        @endif

                        @if ($svc->bill_no)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->bill_no }}</dd>
                            </div>
                        @endif

                        @if ($svc->certification_expiry)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Certification Expiry</dt>
                                <dd class="mt-0.5 text-sm {{ $certExpired ? 'text-red-400 font-semibold' : ($certDays !== null && $certDays <= 30 ? 'text-orange-400' : 'text-zinc-200') }}">
                                    {{ $svc->certification_expiry->format('d M Y') }}
                                    @if ($certExpired)
                                        <span class="text-xs font-normal">(Expired)</span>
                                    @elseif ($certDays !== null && $certDays <= 30)
                                        <span class="text-xs">({{ $certDays }}d left)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Parts cost summary --}}
                    @if ($svc->parts->isNotEmpty())
                        @php $partsCost = $svc->totalPartsCost(); @endphp
                        <div class="mt-3 flex flex-wrap gap-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/30 dark:text-zinc-400">
                            <span>
                                <flux:icon.puzzle-piece class="inline size-3 mr-1" />
                                {{ $svc->parts->count() }} {{ Str::plural('part', $svc->parts->count()) }}
                                @if ($partsCost > 0) — ₹ {{ number_format($partsCost, 2) }} @endif
                            </span>
                            @if ($svc->service_cost && $partsCost > 0)
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                    Total: ₹ {{ number_format($svc->grandTotalCost(), 2) }}
                                </span>
                            @endif
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'parts']) }}"
                               class="text-accent hover:underline">View parts →</a>
                        </div>
                    @endif

                    @if ($svc->work_done)
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Work Done</p>
                            <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $svc->work_done }}</p>
                        </div>
                    @endif

                    @if ($svc->safety_notes)
                        <div class="mt-3">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Safety Notes</p>
                            <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $svc->safety_notes }}</p>
                        </div>
                    @endif

                    @if ($svc->remarks)
                        <div class="mt-3">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Remarks</p>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $svc->remarks }}</p>
                        </div>
                    @endif

                    {{-- Documents --}}
                    @if ($svc->documents->isNotEmpty())
                        <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($svc->documents as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                    <span class="text-xs text-zinc-600">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                    <button type="button"
                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                        title="View"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.eye class="size-3" />
                                    </button>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                        title="Download"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add Part --}}
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-zinc-500">Parts ({{ $svc->parts->count() }})</p>
                            <button type="button" x-on:click="$dispatch('open-modal-add-part-{{ $svc->id }}')"
                                    class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add Part
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Always-visible placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
            <flux:icon.cog-6-tooth class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->services->isEmpty() ? 'No Servicing Records' : 'Add Another Record' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Log preventive maintenance, repairs, inspections, and compliance checks here.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-service')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $asset->services->isEmpty() ? 'Add First Record' : 'Add Servicing Record' }}
                </button>
            </div>
        </div>
    </div>
</div>
