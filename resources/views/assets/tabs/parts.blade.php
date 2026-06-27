@php use Illuminate\Support\Facades\Storage; @endphp
<style>
.part-doc-upload .filepond--panel-root { border: 2px dashed #3f3f46; border-radius: 12px; background: rgba(39,39,42,0.3); }
.part-doc-upload .filepond--root:hover .filepond--panel-root { border-color: var(--color-accent, #6366f1); background: rgba(39,39,42,0.5); }
.part-doc-upload .filepond--drop-label { min-height: 130px; }
.part-doc-upload .filepond--drop-label label { cursor: pointer; }
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
    {{-- Header bar --}}
    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-2.5">
        <p class="truncate text-sm font-medium text-white" x-text="title"></p>
        <button type="button" @click="close()"
                class="shrink-0 rounded-md p-1 text-white/60 hover:bg-white/10 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        </button>
    </div>
    {{-- Content --}}
    <div class="flex flex-1 cursor-zoom-out items-center justify-center overflow-hidden p-4" @click.self="close()">
        <template x-if="isPdf">
            <iframe :src="src" class="h-full w-full max-w-4xl rounded-lg border-0 bg-white" style="min-height:70vh"></iframe>
        </template>
        <template x-if="!isPdf">
            <img :src="src" :alt="title" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
        </template>
    </div>
</div>

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Parts Replacement History</flux:heading>
            @php
                $totalParts     = $asset->services->flatMap->parts;
                $totalPartsCost = $totalParts->sum(fn($p) => $p->part_cost ?? 0);
                $totalSvcCost   = $asset->services->sum('service_cost');
            @endphp
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $totalParts->count() }} {{ Str::plural('part', $totalParts->count()) }}
                @if ($totalPartsCost > 0)
                    &nbsp;·&nbsp; Parts cost: ₹ {{ number_format($totalPartsCost, 2) }}
                @endif
                @if ($totalSvcCost > 0 && $totalPartsCost > 0)
                    &nbsp;·&nbsp; Combined: ₹ {{ number_format($totalSvcCost + $totalPartsCost, 2) }}
                @endif
            </flux:text>
        </div>
    </div>

    @if ($asset->services->isEmpty())
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">No Servicing Records Yet</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Add a servicing record first, then log parts replaced during that service.</flux:text>
                <div class="mt-4">
                    <flux:button href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}" wire:navigate variant="ghost" size="sm">
                        Go to Servicing Tab
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        {{-- Add Part Modals (one per service) --}}
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            <x-modal name="add-part-{{ $svc->id }}" title="Add Part — {{ $svc->service_date->format('d M Y') }}" :dismissible="false"
                :auto-open="$errors->any() && old('_form') === 'part' && (int) old('_service_id') === $svc->id && !old('_part_id')">
                <form method="POST" action="{{ route('assets.services.parts.store', [$asset, $svc]) }}" class="space-y-4" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_form" value="part">
                    <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                    @include('assets.tabs._part-form', ['part' => null])

                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">Save Part</flux:button>
                        <button type="button" x-on:click="$dispatch('close-modal-add-part-{{ $svc->id }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </x-modal>

            {{-- Edit Part Modals --}}
            @foreach ($svc->parts as $part)
                <x-modal name="edit-part-{{ $part->id }}" title="Edit Part" :dismissible="false"
                    :auto-open="$errors->any() && old('_form') === 'part' && (int) old('_part_id') === $part->id">
                    <form method="POST" action="{{ route('assets.services.parts.update', [$asset, $svc, $part]) }}" class="space-y-4" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <input type="hidden" name="_form" value="part">
                        <input type="hidden" name="_service_id" value="{{ $svc->id }}">
                        <input type="hidden" name="_part_id" value="{{ $part->id }}">

                        @include('assets.tabs._part-form', ['part' => $part])

                        <div class="flex items-center gap-3 pt-1">
                            <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                            <button type="button" x-on:click="$dispatch('close-modal-edit-part-{{ $part->id }}')"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </x-modal>

                @php
                    $pPatchUrl = route('assets.services.parts.patch-field', [$asset, $svc, $part]);
                    $pInp      = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
                    $pBtnOk    = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
                    $pBtnX     = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
                    $pPencil   = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
                    $pCheck    = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
                    $pX        = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
                    $pDt       = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
                    $pDd       = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
                    $pFirstDoc = $part->documents->first();
                    $partWarrantyExpired = $part->warranty_till && $part->warranty_till->lt(now()->startOfDay());
                @endphp
                <x-modal name="view-part-{{ $part->id }}" title="Part Replacement Details">
                    <x-slot:footer>
                        <div class="flex items-center gap-2">
                            <flux:icon.puzzle-piece class="size-4 shrink-0 text-zinc-400" />
                            <span class="text-xs text-zinc-500">{{ $svc->service_type_label }} · {{ $svc->service_date->format('d M Y') }}</span>
                            @if ($part->warranty_till)
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $partWarrantyExpired ? 'bg-red-400/10 text-red-400' : 'bg-green-400/10 text-green-400' }}">
                                    {{ $partWarrantyExpired ? 'Warranty Expired' : 'Under Warranty' }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" x-on:click="$dispatch('close-modal-view-part-{{ $part->id }}')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                                <flux:icon.x-mark class="size-3.5" />
                                Close
                            </button>
                            <form method="POST" action="{{ route('assets.services.parts.destroy', [$asset, $svc, $part]) }}" onsubmit="confirmDelete(this, 'Delete this part?'); return false;">
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
                        partName:      {{ json_encode($part->part_name) }},
                        serialNo:      {{ json_encode($part->part_serial_number ?? '') }},
                        partCost:      '{{ $part->part_cost ?? '' }}',
                        vendorLabel:   {{ json_encode($part->vendor?->name ?? $part->purchased_from ?? '') }},
                        purchasedFrom: {{ json_encode($part->purchased_from ?? '') }},
                        billNo:        {{ json_encode($part->bill_no ?? '') }},
                        warrantyMode:  '{{ $part->warranty_tracking_mode ?? 'time' }}',
                        warrantyTill:  '{{ $part->warranty_till?->format('d M Y') ?? '' }}',
                        warrantyUnit:  '{{ $part->warranty_unit ?? '' }}',
                        counterLimit:  '{{ $part->warranty_counter_limit ?? '' }}',
                        remindDays:    '{{ $part->warranty_reminder_before_days ?? '' }}',
                        remindUnits:   '{{ $part->warranty_reminder_before_units ?? '' }}',
                        remarks:       {{ json_encode($part->remarks ?? '') }},
                        async pp(field, value) {
                            const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                            const r = await fetch('{{ $pPatchUrl }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                                body: fd
                            });
                            if (!r.ok) { toastr.error('Save failed.'); return false; }
                            const d = await r.json();
                            toastr.success('Updated.');
                            if (field === 'vendor_id') { this.vendorLabel = d.label || ''; }
                            return true;
                        }
                    }" class="flex min-h-0 gap-5 mt-1">

                        {{-- ── Left: fields ── --}}
                        <div class="flex-1 min-w-0 space-y-5">

                            {{-- ── Part Info ── --}}
                            <div>
                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Part Info</p>
                                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                                    {{-- Part Name --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Part Name</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="partName"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="text" x-ref="inpName" class="{{ $pInp }} w-40" :value="partName" maxlength="255" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('part_name',$refs.inpName.value)){partName=$refs.inpName.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Serial Number --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Serial Number</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="serialNo || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="text" x-ref="inpSerial" class="{{ $pInp }} w-36" :value="serialNo" maxlength="255" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('part_serial_number',$refs.inpSerial.value)){serialNo=$refs.inpSerial.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                </dl>
                            </div>

                            {{-- ── Vendor / Provider ── --}}
                            <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Vendor / Provider</p>
                                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                                    {{-- Vendor (select) --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Vendor / Provider</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="vendorLabel || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <select x-ref="selVendor" class="{{ $pInp }} max-w-44">
                                                        <option value="">— None —</option>
                                                        @foreach ($vendors ?? [] as $vnd)
                                                            <option value="{{ $vnd->id }}" {{ $part->vendor_id == $vnd->id ? 'selected' : '' }}>{{ $vnd->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="{{ $pBtnOk }}"
                                                        @click="if(await pp('vendor_id',$refs.selVendor.value)){vendorLabel=$refs.selVendor.options[$refs.selVendor.selectedIndex].text==='— None —'?'':$refs.selVendor.options[$refs.selVendor.selectedIndex].text;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Bill No --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Bill / Invoice No.</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="billNo || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="text" x-ref="inpBill" class="{{ $pInp }} w-32" :value="billNo" maxlength="255" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('bill_no',$refs.inpBill.value)){billNo=$refs.inpBill.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Part Cost --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Cost (₹)</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="partCost ? '₹ ' + parseFloat(partCost).toLocaleString('en-IN',{minimumFractionDigits:2}) : '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="number" x-ref="inpCost" class="{{ $pInp }} w-28" :value="partCost" min="0" step="0.01" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('part_cost',$refs.inpCost.value)){partCost=$refs.inpCost.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Service Labour Cost (read-only context) --}}
                                    <div>
                                        <dt class="{{ $pDt }}">Service Labour Cost</dt>
                                        <dd class="{{ $pDd }}">{{ $svc->service_cost ? '₹ ' . number_format($svc->service_cost, 2) : '--' }}</dd>
                                    </div>

                                </dl>
                            </div>

                            {{-- ── Warranty ── --}}
                            <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Warranty</p>
                                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                                    {{-- Tracking Mode --}}
                                    <div x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Tracking Mode</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="{ time:'Date-based', meter:'Meter-based', count:'Count-based' }[warrantyMode] || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <select x-ref="selMode" class="{{ $pInp }}" :value="warrantyMode">
                                                        <option value="time">Date-based</option>
                                                        <option value="meter">Meter-based</option>
                                                        <option value="count">Count-based</option>
                                                    </select>
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_tracking_mode',$refs.selMode.value)){warrantyMode=$refs.selMode.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Warranty Till (time) --}}
                                    <div x-show="warrantyMode === 'time'" x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpWTill, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                        <dt class="{{ $pDt }}">Warranty Expiry</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="text-sm {{ $partWarrantyExpired ? 'font-semibold text-red-400' : 'text-zinc-800 dark:text-zinc-200' }}" x-text="warrantyTill || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="text" x-ref="fpWTill" class="{{ $pInp }} w-32" placeholder="Date" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_till',$refs.fpWTill._flatpickr?.input.value||$refs.fpWTill.value)){warrantyTill=$refs.fpWTill._flatpickr?.altInput?.value||$refs.fpWTill.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Reminder Before Days (time) --}}
                                    <div x-show="warrantyMode === 'time'" x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Reminder (days before)</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="remindDays ? remindDays + ' days before' : '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="number" x-ref="inpRDays" class="{{ $pInp }} w-20" :value="remindDays" min="1" max="365" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_reminder_before_days',$refs.inpRDays.value)){remindDays=$refs.inpRDays.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Unit (meter/count) --}}
                                    <div x-show="warrantyMode === 'meter' || warrantyMode === 'count'" x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Warranty Unit</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="warrantyUnit || '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="text" x-ref="inpUnit" class="{{ $pInp }} w-24" :value="warrantyUnit" maxlength="20" placeholder="km, hrs…" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_unit',$refs.inpUnit.value)){warrantyUnit=$refs.inpUnit.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Counter Limit (meter/count) --}}
                                    <div x-show="warrantyMode === 'meter' || warrantyMode === 'count'" x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Warranty Limit</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="counterLimit ? Number(counterLimit).toLocaleString() + ' ' + (warrantyUnit || 'units') : '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="number" x-ref="inpLimit" class="{{ $pInp }} w-24" :value="counterLimit" min="1" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_counter_limit',$refs.inpLimit.value)){counterLimit=$refs.inpLimit.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                    {{-- Reminder Before Units (meter/count) --}}
                                    <div x-show="warrantyMode === 'meter' || warrantyMode === 'count'" x-data="{ editing: false }">
                                        <dt class="{{ $pDt }}">Reminder (units before)</dt>
                                        <dd class="mt-0.5 flex items-center gap-1.5">
                                            <span x-show="!editing" class="{{ $pDd }}" x-text="remindUnits ? Number(remindUnits).toLocaleString() + ' ' + (warrantyUnit || 'units') + ' before' : '--'"></span>
                                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }}">{!! $pPencil !!}</button>
                                            <template x-if="editing">
                                                <span class="flex items-center gap-1">
                                                    <input type="number" x-ref="inpRUnits" class="{{ $pInp }} w-24" :value="remindUnits" min="1" />
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('warranty_reminder_before_units',$refs.inpRUnits.value)){remindUnits=$refs.inpRUnits.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </template>
                                        </dd>
                                    </div>

                                </dl>
                            </div>

                            {{-- ── Remarks ── --}}
                            <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <div x-data="{ editing: false }">
                                    <dt class="{{ $pDt }}">Remarks</dt>
                                    <dd class="mt-0.5 flex items-start gap-1.5">
                                        <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="remarks || '--'"></span>
                                        <button x-show="!editing" type="button" @click="editing=true" class="{{ $pBtnX }} mt-0.5">{!! $pPencil !!}</button>
                                        <template x-if="editing">
                                            <span class="flex w-full flex-col gap-1">
                                                <textarea x-ref="taRemarks" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="remarks"></textarea>
                                                <span class="flex gap-1">
                                                    <button type="button" class="{{ $pBtnOk }}" @click="if(await pp('remarks',$refs.taRemarks.value)){remarks=$refs.taRemarks.value;editing=false}">{!! $pCheck !!}</button>
                                                    <button type="button" class="{{ $pBtnX }}" @click="editing=false">{!! $pX !!}</button>
                                                </span>
                                            </span>
                                        </template>
                                    </dd>
                                </div>
                            </div>

                        </div>{{-- end left --}}

                        {{-- ── Right: Document panel ── --}}
                        <aside class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                            <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>
                            <div class="part-doc-upload" x-data x-init="
                                initUploadPond($el.querySelector('input'), {
                                    acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                                    labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                        <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                            <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                        </div>
                                        <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                        <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                                    </div>`,
                                    files: @js($pFirstDoc ? [['source' => Storage::url($pFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                                    fileMetaBySource: @js($pFirstDoc ? [Storage::url($pFirstDoc->file_path) => ['name' => $pFirstDoc->file_original_name]] : (object)[]),
                                    deleteUrl: @js($pFirstDoc ? route('assets.services.parts.documents.destroy', [$asset, $pFirstDoc]) : ''),
                                    csrfToken: @js(csrf_token()),
                                    revertUrlTemplate: () => @js(route('assets.services.parts.documents.revert', $asset)),
                                    server: {
                                        process: {
                                            url: @js(route('assets.services.parts.documents.store', [$asset, $part])),
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
                            @if ($part->documents->count() > 1)
                                <div class="mt-2 space-y-1">
                                    @foreach ($part->documents->skip(1) as $doc)
                                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                            @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                            <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
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
                                            <form method="POST" action="{{ route('assets.services.parts.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this, 'Delete this document?'); return false;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($part->documents->isEmpty())
                                <div class="mt-3 flex flex-col items-center justify-center">
                                    <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                                </div>
                            @endif
                        </aside>{{-- end right --}}

                    </div>
                </x-modal>
            @endforeach
        @endforeach

        {{-- Service cards grid --}}
        <div class="grid grid-cols-3 gap-4">
            @foreach ($asset->services->sortByDesc('service_date') as $svc)
                @php
                    $partsCostTotal = $svc->parts->sum(fn($p) => $p->part_cost ?? 0);
                    $grandTotal     = ($svc->service_cost ?? 0) + $partsCostTotal;
                @endphp

                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                    {{-- Card header --}}
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="flex items-center gap-2 min-w-0 flex-wrap">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                                {{ $svc->service_type_label }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $svc->service_date->format('d M Y') }}</span>
                            @if ($svc->service_agency)
                                <span class="truncate text-xs text-zinc-500">{{ $svc->service_agency }}</span>
                            @endif
                        </div>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-add-part-{{ $svc->id }}')"
                                class="shrink-0 inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Add
                        </button>
                    </div>

                    {{-- Parts list --}}
                    @if ($svc->parts->isEmpty())
                        <div class="px-4 py-6 text-center">
                            <flux:text class="text-xs text-zinc-500">No parts recorded.</flux:text>
                            <button type="button"
                                    x-on:click="$dispatch('open-modal-add-part-{{ $svc->id }}')"
                                    class="mt-2 inline-flex items-center gap-1 text-xs text-accent hover:underline">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add Part
                            </button>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($svc->parts as $part)
                                <div class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $part->part_name }}</p>
                                            @if ($part->part_cost !== null)
                                            <p class="mt-0.5 text-[11px] text-zinc-500">₹ {{ number_format($part->part_cost, 2) }}</p>
                                            @endif
                                            @if ($part->purchased_from)
                                                <p class="text-[11px] text-zinc-500">{{ $part->purchased_from }}</p>
                                            @endif
                                            @if ($part->warranty_till)
                                                <p class="text-[11px] {{ $part->warranty_till->lt(now()->startOfDay()) ? 'text-red-400' : 'text-zinc-500' }}">
                                                    Warranty: {{ $part->warranty_till->format('d M Y') }}
                                                    @if ($part->warranty_till->lt(now()->startOfDay())) (Expired) @endif
                                                </p>
                                            @endif
                                            @if ($part->remarks)
                                                <p class="mt-0.5 text-[11px] text-zinc-500 italic">{{ $part->remarks }}</p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'partid' => $part->id]) }}"
                                               title="{{ $part->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                                               class="inline-flex size-5 items-center justify-center rounded border border-accent text-accent hover:bg-accent/10 transition-colors">
                                                <flux:icon.bell-alert class="size-3" />
                                            </a>
                                            <button type="button"
                                                    x-on:click="$dispatch('open-modal-view-part-{{ $part->id }}')"
                                                    aria-label="View part record"
                                                    title="View part record"
                                                    class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                                <flux:icon.eye class="size-3" />
                                            </button>
                                            <button type="button"
                                                    x-on:click="$dispatch('open-modal-edit-part-{{ $part->id }}')"
                                                    aria-label="Edit part record"
                                                    title="Edit part record"
                                                    class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                                <flux:icon.pencil class="size-3" />
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('assets.services.parts.destroy', [$asset, $svc, $part]) }}"
                                                  onsubmit="confirmDelete(this, 'Delete this part record?'); return false;">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        aria-label="Delete part record"
                                                        title="Delete part record"
                                                        class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @if ($part->documents->isNotEmpty())
                                        <div class="mt-2 space-y-1">
                                            @foreach ($part->documents as $doc)
                                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-700 dark:bg-zinc-800/50">
                                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
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
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Cost footer --}}
                        @if ($grandTotal > 0)
                            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-800 dark:bg-zinc-800/20">
                                <div class="flex flex-wrap items-center justify-end gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
                                    @if ($svc->service_cost)
                                        <span>Labour: <span class="font-semibold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($svc->service_cost, 2) }}</span></span>
                                    @endif
                                    @if ($partsCostTotal > 0)
                                        <span>Parts: <span class="font-semibold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($partsCostTotal, 2) }}</span></span>
                                    @endif
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">Total: ₹ {{ number_format($grandTotal, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

         
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">Add Another Service</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Log a new servicing record to track more replaced parts.</flux:text>
                <div class="mt-4">
                    <a href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Go to Servicing Tab
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
