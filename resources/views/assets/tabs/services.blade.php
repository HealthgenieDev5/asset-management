@php use Illuminate\Support\Facades\Storage; @endphp
<style>
.sd-upload .filepond--panel-root { border: 2px dashed #3f3f46; border-radius: 12px; background: rgba(39,39,42,0.3); }
.sd-upload .filepond--root:hover .filepond--panel-root { border-color: var(--color-accent, #6366f1); background: rgba(39,39,42,0.5); }
.sd-upload .filepond--drop-label { min-height: 130px; }
.sd-upload .filepond--drop-label label { cursor: pointer; }
</style>

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
    <div class="flex flex-1 cursor-zoom-out items-center justify-center overflow-hidden p-4" @click.self="close()">
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
            <flux:text class="mt-0.5 text-xs text-zinc-500">
                {{ $asset->services->count() }} {{ Str::plural('record', $asset->services->count()) }}
                @if ($asset->services->sum('service_cost') > 0)
                    &nbsp;·&nbsp; Total cost: ₹ {{ number_format($asset->services->sum('service_cost'), 2) }}
                @endif
            </flux:text>
        </div>
        {{-- <button type="button" x-on:click="$dispatch('open-modal-add-service')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Servicing
        </button> --}}
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

    {{-- Per-record modals + cards --}}
    @foreach ($asset->services->sortByDesc('service_date') as $svc)
        @php
            $nextDays    = $svc->daysUntilNextService();
            $nextOverdue = $svc->isNextServiceOverdue();
            $certDays    = $svc->daysUntilCertificationExpiry();
            $certExpired = $svc->isCertificationExpired();
            $partsCost   = $svc->totalPartsCost();
            $hasMeta     = $svc->vendor || $svc->service_agency || $svc->service_cost || $nextOverdue || ($nextDays !== null && $nextDays <= 30) || $certExpired || ($certDays !== null && $certDays <= 30);
        @endphp

        {{-- Edit Modal --}}
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

        {{-- View Modal (inline edit) --}}
        @php
            $sPatchUrl  = route('assets.services.patch-field', [$asset, $svc]);
            $sInp       = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
            $sBtnOk     = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
            $sBtnX      = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
            $sPencilSvg = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
            $sCheckSvg  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
            $sXSvg      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
            $sDt        = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
            $sDd        = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
            $vendorJsonSvc = ($vendors ?? collect())->mapWithKeys(fn($v) => [$v->id => $v->name])->toJson();
        @endphp
        <x-modal name="view-service-{{ $svc->id }}" title="Servicing Record">
            <x-slot:footer>
                <div class="flex items-center gap-2">
                    <flux:icon.cog-6-tooth class="size-4 shrink-0 text-zinc-400" />
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $svc->service_type_color }}">{{ $svc->service_type_label }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal-view-service-{{ $svc->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                        <flux:icon.x-mark class="size-3.5" />
                        Close
                    </button>
                    <form method="POST" action="{{ route('assets.services.destroy', [$asset, $svc]) }}" onsubmit="confirmDelete(this, 'Delete this service record?'); return false;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300/60 px-3 py-1.5 text-xs font-medium text-red-500 transition-colors hover:border-red-500/60 hover:bg-red-500/5 dark:border-red-700/50 dark:text-red-400 dark:hover:border-red-500/60">
                            <flux:icon.trash class="size-3.5" />
                            Delete
                        </button>
                    </form>
                </div>
            </x-slot:footer>

            <div x-data="{
                svcType:      '{{ $svc->service_type }}',
                svcDate:      '{{ $svc->service_date->format('d M Y') }}',
                vendorLabel:  '{{ addslashes($svc->vendor?->name ?? '') }}',
                techName:     '{{ addslashes($svc->technician_name ?? '') }}',
                condition:    '{{ $svc->condition_rating ?? '' }}',
                svcCost:      '{{ $svc->service_cost ?? '' }}',
                billNo:       '{{ addslashes($svc->bill_no ?? '') }}',
                billDate:     '{{ $svc->bill_date?->format('d M Y') ?? '' }}',
                nextSvcDate:  '{{ $svc->next_service_date?->format('d M Y') ?? '' }}',
                intervalVal:  '{{ $svc->service_interval_value ?? '' }}',
                intervalUnit: '{{ $svc->service_interval_unit ?? '' }}',
                meterReading: '{{ $svc->meter_reading ?? '' }}',
                mileage:      '{{ $svc->mileage_reading ?? '' }}',
                downtime:     '{{ $svc->downtime_hours ?? '' }}',
                certExpiry:   '{{ $svc->certification_expiry?->format('d M Y') ?? '' }}',
                certRemind:   '{{ $svc->certification_reminder_before_days ?? '' }}',
                svcRemind:    '{{ $svc->next_service_reminder_before_days ?? '' }}',
                workDone:     {{ json_encode($svc->work_done ?? '') }},
                safetyNotes:  {{ json_encode($svc->safety_notes ?? '') }},
                remarks:      {{ json_encode($svc->remarks ?? '') }},
                vendors:      {{ $vendorJsonSvc }},
                async sp(field, value) {
                    const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                    const r = await fetch('{{ $sPatchUrl }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: fd
                    });
                    if (!r.ok) { toastr.error('Save failed.'); return false; }
                    toastr.success('Updated.');
                    if (field === 'vendor_id') { const d = await r.json(); this.vendorLabel = d.label ?? ''; }
                    return true;
                }
            }" class="flex min-h-0 gap-5 mt-1">

                {{-- ── Left: fields ── --}}
                <div class="flex-1 min-w-0 space-y-5">

                {{-- ── Service Info ── --}}
                <div>
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Service Info</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Service Type --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Service Type</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $svc->service_type_color }}" x-text="{ preventive_maintenance:'Preventive Maintenance', corrective_maintenance:'Corrective Maintenance', inspection:'Inspection', repair:'Repair', calibration:'Calibration', cleaning:'Cleaning', other:'Other' }[svcType]"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selSvcType" class="{{ $sInp }}" :value="svcType">
                                            <option value="preventive_maintenance">Preventive Maintenance</option>
                                            <option value="corrective_maintenance">Corrective Maintenance</option>
                                            <option value="inspection">Inspection</option>
                                            <option value="repair">Repair</option>
                                            <option value="calibration">Calibration</option>
                                            <option value="cleaning">Cleaning</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('service_type',$refs.selSvcType.value)){svcType=$refs.selSvcType.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Service Date --}}
                        <div x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpSvcDate, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $sDt }}">Service Date</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="svcDate || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpSvcDate" class="{{ $sInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('service_date',$refs.fpSvcDate._flatpickr?.input.value||$refs.fpSvcDate.value)){svcDate=$refs.fpSvcDate._flatpickr?.altInput?.value||$refs.fpSvcDate.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Vendor --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Service Agency / Vendor</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="vendorLabel || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selVendor" class="{{ $sInp }}">
                                            <option value="">— None —</option>
                                            @foreach ($vendors ?? [] as $vnd)
                                                <option value="{{ $vnd->id }}" {{ $svc->vendor_id == $vnd->id ? 'selected' : '' }}>{{ $vnd->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('vendor_id',$refs.selVendor.value)){editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Technician --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Technician</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="techName || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="inpTech" class="{{ $sInp }} w-36" :value="techName" maxlength="255" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('technician_name',$refs.inpTech.value)){techName=$refs.inpTech.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Condition --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Condition Rating</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="text-sm font-medium" :class="{ 'text-green-400': condition==='excellent', 'text-blue-400': condition==='good', 'text-yellow-400': condition==='fair', 'text-orange-400': condition==='poor', 'text-red-400': condition==='critical', 'text-zinc-500': !condition }" x-text="{ excellent:'Excellent', good:'Good', fair:'Fair', poor:'Poor', critical:'Critical' }[condition] || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selCond" class="{{ $sInp }}" :value="condition">
                                            <option value="">— None —</option>
                                            <option value="excellent">Excellent</option>
                                            <option value="good">Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="poor">Poor</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('condition_rating',$refs.selCond.value)){condition=$refs.selCond.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Billing ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Billing</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Service Cost --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Service Cost</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="svcCost ? '₹ ' + parseFloat(svcCost).toLocaleString('en-IN',{minimumFractionDigits:2}) : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpCost" class="{{ $sInp }} w-28" :value="svcCost" min="0" step="0.01" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('service_cost',$refs.inpCost.value)){svcCost=$refs.inpCost.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Parts Cost (read-only) --}}
                        <div>
                            <dt class="{{ $sDt }}">Parts Cost</dt>
                            <dd class="{{ $sDd }}">{{ $partsCost > 0 ? '₹ ' . number_format($partsCost, 2) : '--' }}</dd>
                        </div>

                        {{-- Grand Total (read-only) --}}
                        <div>
                            <dt class="{{ $sDt }}">Grand Total</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $svc->grandTotalCost() > 0 ? '₹ ' . number_format($svc->grandTotalCost(), 2) : '--' }}</dd>
                        </div>

                        {{-- Bill No --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Bill Number</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="billNo || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="inpBillNo" class="{{ $sInp }} w-32" :value="billNo" maxlength="255" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('bill_no',$refs.inpBillNo.value)){billNo=$refs.inpBillNo.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Bill Date --}}
                        <div x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpBillDate, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $sDt }}">Bill Date</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="billDate || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpBillDate" class="{{ $sInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('bill_date',$refs.fpBillDate._flatpickr?.input.value||$refs.fpBillDate.value)){billDate=$refs.fpBillDate._flatpickr?.altInput?.value||$refs.fpBillDate.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Readings ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Readings</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Meter / Op Hours --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Meter / Op. Hours</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="meterReading ? Number(meterReading).toLocaleString() : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpMeter" class="{{ $sInp }} w-28" :value="meterReading" min="0" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('meter_reading',$refs.inpMeter.value)){meterReading=$refs.inpMeter.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Odometer --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Odometer (km)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="mileage ? Number(mileage).toLocaleString() : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpMileage" class="{{ $sInp }} w-28" :value="mileage" min="0" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('mileage_reading',$refs.inpMileage.value)){mileage=$refs.inpMileage.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Downtime --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Downtime (hrs)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="downtime ? downtime + ' hrs' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpDowntime" class="{{ $sInp }} w-24" :value="downtime" min="0" step="0.5" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('downtime_hours',$refs.inpDowntime.value)){downtime=$refs.inpDowntime.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Schedule & Certification ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Schedule & Certification</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Next Service Date --}}
                        <div x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpNextSvc, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $sDt }}">Next Service Date</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="text-sm {{ $nextOverdue ? 'font-semibold text-red-400' : ($nextDays !== null && $nextDays <= 30 ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }}" x-text="nextSvcDate || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpNextSvc" class="{{ $sInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('next_service_date',$refs.fpNextSvc._flatpickr?.input.value||$refs.fpNextSvc.value)){nextSvcDate=$refs.fpNextSvc._flatpickr?.altInput?.value||$refs.fpNextSvc.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Interval --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Service Interval</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="intervalVal && intervalUnit ? 'Every ' + intervalVal + ' ' + intervalUnit : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex flex-wrap items-center gap-1">
                                        <input type="number" x-ref="inpIntVal" class="{{ $sInp }} w-16" :value="intervalVal" min="1" placeholder="N" />
                                        <select x-ref="selIntUnit" class="{{ $sInp }}" :value="intervalUnit">
                                            <option value="">— Unit —</option>
                                            <option value="days">Days</option>
                                            <option value="weeks">Weeks</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                            <option value="operating_hours">Op. Hours</option>
                                            <option value="kilometers">Km</option>
                                        </select>
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('service_interval_value',$refs.inpIntVal.value) && await sp('service_interval_unit',$refs.selIntUnit.value)){intervalVal=$refs.inpIntVal.value;intervalUnit=$refs.selIntUnit.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Next Service Reminder --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Next Service Reminder</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="svcRemind ? svcRemind + ' days before' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpSvcRemind" class="{{ $sInp }} w-20" :value="svcRemind" min="1" max="365" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('next_service_reminder_before_days',$refs.inpSvcRemind.value)){svcRemind=$refs.inpSvcRemind.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Certification Expiry --}}
                        <div x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpCertExp, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $sDt }}">Certification Expiry</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="text-sm {{ $certExpired ? 'font-semibold text-red-400' : ($certDays !== null && $certDays <= 30 ? 'text-orange-400' : 'text-zinc-800 dark:text-zinc-200') }}" x-text="certExpiry || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpCertExp" class="{{ $sInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('certification_expiry',$refs.fpCertExp._flatpickr?.input.value||$refs.fpCertExp.value)){certExpiry=$refs.fpCertExp._flatpickr?.altInput?.value||$refs.fpCertExp.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Cert Reminder --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Cert. Reminder</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $sDd }}" x-text="certRemind ? certRemind + ' days before' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }}">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpCertRemind" class="{{ $sInp }} w-20" :value="certRemind" min="1" max="365" />
                                        <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('certification_reminder_before_days',$refs.inpCertRemind.value)){certRemind=$refs.inpCertRemind.value;editing=false}">{!! $sCheckSvg !!}</button>
                                        <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Notes ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Notes</p>
                    <dl class="space-y-4">

                        {{-- Work Done --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Work Done</dt>
                            <dd class="mt-0.5 flex items-start gap-1.5">
                                <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="workDone || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }} mt-0.5">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex w-full flex-col gap-1">
                                        <textarea x-ref="taWork" rows="3" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="workDone"></textarea>
                                        <span class="flex gap-1">
                                            <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('work_done',$refs.taWork.value)){workDone=$refs.taWork.value;editing=false}">{!! $sCheckSvg !!}</button>
                                            <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                        </span>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Safety Notes --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Safety Notes</dt>
                            <dd class="mt-0.5 flex items-start gap-1.5">
                                <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="safetyNotes || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }} mt-0.5">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex w-full flex-col gap-1">
                                        <textarea x-ref="taSafety" rows="3" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="safetyNotes"></textarea>
                                        <span class="flex gap-1">
                                            <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('safety_notes',$refs.taSafety.value)){safetyNotes=$refs.taSafety.value;editing=false}">{!! $sCheckSvg !!}</button>
                                            <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                        </span>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Remarks --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $sDt }}">Remarks</dt>
                            <dd class="mt-0.5 flex items-start gap-1.5">
                                <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="remarks || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $sBtnX }} mt-0.5">{!! $sPencilSvg !!}</button>
                                <template x-if="editing">
                                    <span class="flex w-full flex-col gap-1">
                                        <textarea x-ref="taRemarks" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="remarks"></textarea>
                                        <span class="flex gap-1">
                                            <button type="button" class="{{ $sBtnOk }}" @click="if(await sp('remarks',$refs.taRemarks.value)){remarks=$refs.taRemarks.value;editing=false}">{!! $sCheckSvg !!}</button>
                                            <button type="button" class="{{ $sBtnX }}" @click="editing=false">{!! $sXSvg !!}</button>
                                        </span>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Parts ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Parts ({{ $svc->parts->count() }})</p>
                        <button type="button" x-on:click="$dispatch('close-modal-view-service-{{ $svc->id }}'); $nextTick(() => $dispatch('open-modal-add-part-{{ $svc->id }}'))"
                                class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-0.5 text-xs font-medium text-zinc-600 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Add Part
                        </button>
                    </div>
                    @if ($svc->parts->isNotEmpty())
                        <div class="divide-y divide-zinc-200/60 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                            @foreach ($svc->parts as $part)
                                @php $lineTotal = $part->part_cost !== null ? (float) $part->part_cost * ($part->quantity ?? 1) : null; @endphp
                                <div class="px-3 py-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-100">{{ $part->part_name }}</p>
                                            @if ($part->part_serial_number)
                                                <p class="text-[11px] text-zinc-400">S/N: {{ $part->part_serial_number }}</p>
                                            @endif
                                            <p class="mt-0.5 text-[11px] text-zinc-500">
                                                @if ($part->part_cost !== null) ₹ {{ number_format($part->part_cost, 2) }} @endif
                                                @if ($part->purchased_from) &middot; {{ $part->purchased_from }} @endif
                                                @if ($part->bill_no) &middot; Bill: {{ $part->bill_no }} @endif
                                            </p>
                                        </div>
                                        @if ($part->warranty_till || $part->warranty_counter_limit)
                                            <span class="shrink-0 rounded-full bg-blue-400/10 px-1.5 py-0.5 text-[10px] font-medium text-blue-400">Warranty</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No parts recorded.</p>
                    @endif
                </div>

                </div>{{-- end left column --}}

                {{-- ── Right: Documents panel ── --}}
                <aside class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Documents</p>

                    {{-- FilePond upload (server mode — no page reload) --}}
                    @php $sFirstDoc = $svc->documents->first(); @endphp
                    <div class="sd-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                    <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                </div>
                                <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                            </div>`,
                            files: @js($sFirstDoc ? [['source' => Storage::url($sFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                            fileMetaBySource: @js($sFirstDoc ? [Storage::url($sFirstDoc->file_path) => ['name' => $sFirstDoc->file_original_name]] : (object)[]),
                            deleteUrl: @js($sFirstDoc ? route('assets.services.documents.destroy', [$asset, $sFirstDoc]) : ''),
                            csrfToken: @js(csrf_token()),
                            revertUrlTemplate: () => @js(route('assets.services.documents.revert', $asset)),
                            server: {
                                process: {
                                    url: @js(route('assets.services.documents.store', [$asset, $svc])),
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                                    onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); },
                                    onerror: (e) => toastr.error('Upload failed.'),
                                },
                            },
                        })
                    ">
                        <input type="file" />
                    </div>

                    {{-- Additional documents (beyond first) --}}
                    @if ($svc->documents->count() > 1)
                        <div class="mt-2 space-y-1">
                            @foreach ($svc->documents->skip(1) as $doc)
                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />
                                    @endif
                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
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
                                    <form method="POST" action="{{ route('assets.services.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this, 'Delete this document?'); return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($svc->documents->isEmpty())
                        <div class="mt-3 flex flex-col items-center justify-center">
                            <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                        </div>
                    @endif
                </aside>{{-- end right column --}}

            </div>
        </x-modal>

        {{-- Add Part Modal --}}
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

    {{-- Records Grid --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            @php
                $nextDays    = $svc->daysUntilNextService();
                $nextOverdue = $svc->isNextServiceOverdue();
                $certDays    = $svc->daysUntilCertificationExpiry();
                $certExpired = $svc->isCertificationExpired();
                $partsCost   = $svc->totalPartsCost();
                $hasMeta     = $svc->vendor || $svc->service_agency || $svc->service_cost;
            @endphp

            <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">

                {{-- Card Header: type badge + date + actions only --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                            {{ $svc->service_type_label }}
                        </span>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $svc->service_date->format('d M Y') }}
                        </span>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'serviceid' => $svc->id]) }}"
                           title="{{ $svc->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border transition-colors {{ $svc->smartReminders->isNotEmpty() ? 'border-blue-500/40 text-blue-400 hover:bg-blue-500/10' : 'border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/10' }}">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-view-service-{{ $svc->id }}')"
                                title="View" aria-label="View service record"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-edit-service-{{ $svc->id }}')"
                                title="Edit" aria-label="Edit service record"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.services.destroy', [$asset, $svc]) }}"
                              onsubmit="confirmDelete(this, 'Delete this service record?'); return false;">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete" aria-label="Delete service record"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="flex-1 px-5 py-4">

                    {{-- Meta row: vendor + cost only --}}
                    @if ($svc->vendor || $svc->service_agency || $svc->service_cost)
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            @if ($svc->vendor || $svc->service_agency)
                                <span class="text-xs text-zinc-500">
                                    @if ($svc->vendor)
                                        <a href="{{ route('vendors.show', $svc->vendor) }}" wire:navigate class="text-accent hover:underline">{{ $svc->vendor->name }}</a>
                                    @else
                                        {{ $svc->service_agency }}
                                    @endif
                                </span>
                            @endif
                            @if ($svc->service_cost)
                                <span class="font-mono text-xs text-zinc-400">₹ {{ number_format($svc->service_cost, 2) }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Detail grid --}}
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
                                    @if ($nextOverdue) <span class="text-xs font-normal">(Overdue)</span>
                                    @elseif ($nextDays !== null && $nextDays <= 30) <span class="text-xs">({{ $nextDays }}d)</span>
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
                                    @if ($certExpired) <span class="text-xs font-normal">(Expired)</span>
                                    @elseif ($certDays !== null && $certDays <= 30) <span class="text-xs">({{ $certDays }}d left)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Parts summary --}}
                    @if ($svc->parts->isNotEmpty())
                        <div class="mt-3 flex flex-wrap items-center gap-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/30 dark:text-zinc-400">
                            <span>
                                <flux:icon.puzzle-piece class="mr-1 inline size-3" />
                                {{ $svc->parts->count() }} {{ Str::plural('part', $svc->parts->count()) }}
                                @if ($partsCost > 0) — ₹ {{ number_format($partsCost, 2) }} @endif
                            </span>
                            @if ($svc->service_cost && $partsCost > 0)
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">Total: ₹ {{ number_format($svc->grandTotalCost(), 2) }}</span>
                            @endif
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'parts']) }}" class="text-accent hover:underline">View parts →</a>
                        </div>
                    @endif

                    @if ($svc->work_done)
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Work Done</p>
                            <p class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->work_done }}</p>
                        </div>
                    @endif
                    @if ($svc->safety_notes)
                        <div class="mt-3">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Safety Notes</p>
                            <p class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->safety_notes }}</p>
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
                        <div class="mt-4 space-y-1.5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($svc->documents as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                    <span class="text-xs text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
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

                {{-- Card Footer --}}
                <div class="flex h-9 shrink-0 items-center gap-1.5 overflow-hidden border-t border-zinc-100 px-4 dark:border-zinc-800">
                    @if ($nextOverdue)
                        <span class="whitespace-nowrap rounded-full bg-red-400/10 px-2 py-0.5 text-[11px] font-medium text-red-400">Service Overdue</span>
                    @elseif ($nextDays !== null && $nextDays <= 30)
                        <span class="whitespace-nowrap rounded-full bg-yellow-400/10 px-2 py-0.5 text-[11px] font-medium text-yellow-400">Due in {{ $nextDays }}d</span>
                    @endif
                    @if ($certExpired)
                        <span class="whitespace-nowrap rounded-full bg-red-400/10 px-2 py-0.5 text-[11px] font-medium text-red-400">Cert Expired</span>
                    @elseif ($certDays !== null && $certDays <= 30)
                        <span class="whitespace-nowrap rounded-full bg-orange-400/10 px-2 py-0.5 text-[11px] font-medium text-orange-400">Cert in {{ $certDays }}d</span>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
            <flux:icon.cog-6-tooth class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->services->isEmpty() ? 'No Servicing Records' : 'Add Another Record' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Log preventive maintenance, repairs, inspections, and compliance checks here.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-service')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-500 transition-colors hover:text-zinc-700 dark:border-zinc-700 dark:hover:text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $asset->services->isEmpty() ? 'Add First Record' : 'Add Servicing Record' }}
                </button>
            </div>
        </div>
    </div>

</div>
