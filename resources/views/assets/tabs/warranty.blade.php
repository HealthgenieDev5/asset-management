@php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$warranties  = $asset->warranties->sortBy([['scope', 'asc'], ['warranty_type', 'asc'], ['id', 'asc']]);
$prefillPart = request('prefill_part');

$servicePartsJson = $asset->services
    ->flatMap->parts
    ->map(fn($p) => ['id' => $p->id, 'name' => $p->part_name, 'serial' => $p->part_serial_number ?? ''])
    ->values()
    ->toJson();

$cal    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
@endphp
<style>
.wd-upload .filepond--panel-root { border: 2px dashed #3f3f46; border-radius: 12px; background: rgba(39,39,42,0.3); }
.wd-upload .filepond--root:hover .filepond--panel-root { border-color: var(--color-accent, #6366f1); background: rgba(39,39,42,0.5); }
.wd-upload .filepond--drop-label { min-height: 130px; }
.wd-upload .filepond--drop-label label { cursor: pointer; }
</style>
@php
$dt     = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$dd     = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
$inp2   = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl2   = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
@endphp

{{-- Lightbox overlay --}}
<div x-data="docLightbox()"
     x-on:keydown.escape.window="close()"
     x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
     x-show="open" style="display:none"
     class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm">
    <div class="flex shrink-0 items-center justify-between px-4 py-3">
        <span x-text="title" class="truncate text-sm font-medium text-white"></span>
        <button type="button" x-on:click="close()" class="ml-4 shrink-0 rounded p-1 text-white/70 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="min-h-0 flex-1 overflow-auto flex items-center justify-center p-4">
        <template x-if="isPdf">
            <iframe :src="src" class="h-full w-full rounded" style="min-height:70vh"></iframe>
        </template>
        <template x-if="!isPdf">
            <img :src="src" :alt="title" class="max-h-full max-w-full rounded object-contain" />
        </template>
    </div>
</div>

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <flux:heading class="font-semibold text-zinc-200">Warranties</flux:heading>
        <flux:text class="mt-0.5 text-xs text-zinc-500">Product warranties for this asset</flux:text>
    </div>

    {{-- Add Warranty Modal --}}
    <x-modal name="add-warranty" title="Add Warranty Entry" :dismissible="false"
        :auto-open="($errors->any() && old('_form') === 'add-warranty') || $prefillPart !== null">
        <form method="POST" action="{{ route('assets.warranties.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="add-warranty">
            @if ($prefillPart)
                <div class="rounded-lg bg-accent/10 border border-accent/30 px-3 py-2 text-xs text-accent">
                    Creating replacement warranty for part: <strong>{{ $prefillPart }}</strong>
                </div>
            @endif
            @include('assets.tabs._warranty-entry-form', [
                'warranty'         => null,
                'asset'            => $asset,
                'defaultType'      => 'original',
                'defaultScope'     => $prefillPart ? 'part' : 'overall',
                'prefillPart'      => $prefillPart,
                'servicePartsJson' => $servicePartsJson,
            ])
            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Warranty</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-warranty')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
            </div>
        </form>
    </x-modal>

    {{-- Warranty cards + per-warranty modals in one loop --}}
    @php
        $wBtnOk  = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
        $wBtnX   = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
        $wPencil = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
        $wCheck  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
        $wXSvg   = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
        $wInp    = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($warranties as $w)
        @php
            $isDisposed     = $w->isDisposed();
            $badge          = $w->statusBadge();
            $badgeClass     = match($badge) { 'expired' => 'bg-red-400/10 text-red-400', 'soon' => 'bg-yellow-400/10 text-yellow-400', 'disposed' => 'bg-zinc-400/10 text-zinc-400', default => 'bg-green-400/10 text-green-400' };
            $badgeLabel     = match($badge) { 'expired' => 'Expired', 'soon' => 'Expiring Soon', 'disposed' => 'Disposed', default => 'Active' };
            $expiryVal      = $w->isTimeBased() ? ($w->expiry_date?->format('d M Y') ?? '—') : ($w->counter_limit ? number_format($w->counter_limit).' '.$w->unitLabel() : '—');
            $expiryLbl      = $w->isTimeBased() ? 'Expiry Date' : 'Warranty Limit';
            $expiryClass    = $badge === 'expired' ? 'mt-0.5 text-sm font-semibold text-red-400' : ($badge === 'soon' ? 'mt-0.5 text-sm text-yellow-400' : $dd);
            $replacementUrl = ($isDisposed && $w->scope === 'part') ? route('assets.show', [$asset, 'tab' => 'warranty', 'prefill_part' => $w->part_name]) : null;
        @endphp

        {{-- Edit modal --}}
        <x-modal name="edit-warranty-{{ $w->id }}" title="Edit Warranty" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'edit-warranty-' . $w->id">
            <form method="POST" action="{{ route('assets.warranties.update', [$asset, $w]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="edit-warranty-{{ $w->id }}">
                @include('assets.tabs._warranty-entry-form', ['warranty' => $w, 'asset' => $asset, 'servicePartsJson' => $servicePartsJson])
                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-warranty-{{ $w->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
                </div>
            </form>
        </x-modal>

        {{-- View modal (with inline edit) --}}
        @php $wPatchUrl = route('assets.warranties.patch-field', [$asset, $w]); @endphp
        <x-modal name="view-warranty-{{ $w->id }}" title="Warranty Details">
            <x-slot:footer>
                <div class="flex items-center gap-2">
                    <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal-view-warranty-{{ $w->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                        <flux:icon.x-mark class="size-3.5" />
                        Close
                    </button>
                    <form method="POST" action="{{ route('assets.warranties.destroy', [$asset, $w]) }}" onsubmit="confirmDelete(this, 'Delete this warranty entry?'); return false;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300/60 px-3 py-1.5 text-xs font-medium text-red-500 transition-colors hover:border-red-500/60 hover:bg-red-500/5 dark:border-red-700/50 dark:text-red-400 dark:hover:border-red-500/60">
                            <flux:icon.trash class="size-3.5" />
                            Delete
                        </button>
                    </form>
                </div>
            </x-slot:footer>

            <div class="flex min-h-0 gap-5 mt-1" x-data="{
                trackingMode: '{{ $w->tracking_mode ?? 'time' }}',
                scope: '{{ $w->scope ?? 'overall' }}',
                wtype: '{{ $w->warranty_type ?? 'original' }}',
                vendorName: '{{ addslashes($w->vendorRecord?->name ?? $w->vendor ?? '') }}',
                billNo: '{{ addslashes($w->bill_no ?? '') }}',
                billAmt: '{{ $w->bill_amount ?? '' }}',
                expiryDate: '{{ $w->expiry_date?->format('d M Y') ?? '' }}',
                dateFrom: '{{ $w->date_from?->format('d M Y') ?? '' }}',
                counterLimit: '{{ $w->counter_limit ?? '' }}',
                unit: '{{ $w->unit ?? '' }}',
                partSerial: '{{ addslashes($w->part_serial_number ?? '') }}',
                partNameDisplay: '{{ addslashes($w->part_name ?? '') }}',
                details: {{ json_encode($w->details ?? '') }},
                terms: {{ json_encode($w->terms ?? '') }},
                async wp(field, value, extra) {
                    const fd = new URLSearchParams({ _method: 'PATCH', field, value });
                    if (extra) Object.entries(extra).forEach(([k,v]) => fd.append(k, v ?? ''));
                    const r = await fetch('{{ $wPatchUrl }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: fd
                    });
                    if (!r.ok) { toastr.error('Save failed.'); return false; }
                    toastr.success('Updated.');
                    if (field === 'tracking_mode') this.trackingMode = value;
                    if (field === 'scope') { this.scope = value; }
                    if (field === 'warranty_type') this.wtype = value;
                    if (field === 'vendor_id') this.vendorName = extra?._vendorLabel ?? value;
                    if (field === 'bill_no') this.billNo = value;
                    if (field === 'bill_amount') this.billAmt = value ? parseFloat(value).toLocaleString('en-IN', {minimumFractionDigits:2}) : '';
                    if (field === 'expiry_date') this.expiryDate = value;
                    if (field === 'date_from') this.dateFrom = value;
                    if (field === 'counter_limit') this.counterLimit = value;
                    if (field === 'unit') this.unit = value;
                    if (field === 'part_serial_number') this.partSerial = value;
                    if (field === 'scope' && extra?.part_name !== undefined) this.partNameDisplay = extra.part_name;
                    if (field === 'details') this.details = value;
                    if (field === 'terms') this.terms = value;
                    return true;
                }
            }">

                {{-- ── Left: editable fields ── --}}
                <div class="flex-1 min-w-0">

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Warranty Type --}}
                    <div x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Type</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing"
                                  :class="wtype==='original' ? 'bg-blue-400/10 text-blue-400' : 'bg-purple-400/10 text-purple-400'"
                                  class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                  x-text="wtype==='original' ? 'Original (Manufacturer)' : 'Extended (Purchased)'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <select x-ref="selwtype" class="{{ $wInp }}" :value="wtype">
                                        <option value="original">Original (Manufacturer)</option>
                                        <option value="extended">Extended (Purchased)</option>
                                    </select>
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('warranty_type',$refs.selwtype.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Coverage Scope --}}
                    <div x-data="{
                            editing: false,
                            selScope: '{{ $w->scope ?? 'overall' }}',
                            parts: {{ $servicePartsJson }},
                            selPart: '',
                            partName: '{{ addslashes($w->part_name ?? '') }}',
                            selectPart(id) {
                                this.selPart = id;
                                const p = this.parts.find(p => String(p.id) === String(id));
                                if (p) this.partName = p.name; else this.partName = '';
                            }
                         }" class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Coverage Scope</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" class="flex flex-wrap items-center gap-1.5">
                                <span class="text-sm text-zinc-800 dark:text-zinc-100" x-text="scope==='part' ? 'Specific Part' : 'Overall Product'"></span>
                                <span x-show="scope==='part' && partNameDisplay" class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="'— ' + partNameDisplay"></span>
                                <span x-show="scope==='part' && partSerial" class="text-xs text-zinc-400" x-text="'· ' + partSerial"></span>
                                <button type="button" @click="editing=true; selScope=scope; partName=partNameDisplay" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            </div>
                            <div x-show="editing" x-cloak class="mt-1 flex flex-wrap items-center gap-2">
                                <div class="flex gap-1">
                                    <button type="button" @click="selScope='overall'"
                                            :class="selScope==='overall' ? 'bg-accent text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                            class="rounded px-2 py-0.5 text-xs font-semibold transition-colors">Overall Product</button>
                                    <button type="button" @click="selScope='part'"
                                            :class="selScope==='part' ? 'bg-accent text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'"
                                            class="rounded px-2 py-0.5 text-xs font-semibold transition-colors">Specific Part</button>
                                </div>
                                <template x-if="selScope === 'part' && parts.length > 0">
                                    <select @change="selectPart($event.target.value)" class="{{ $wInp }} max-w-44">
                                        <option value="">— Select a part —</option>
                                        <template x-for="p in parts" :key="p.id">
                                            <option :value="p.id" :selected="String(selPart)===String(p.id)"
                                                    x-text="p.serial ? p.name+' · '+p.serial : p.name"></option>
                                        </template>
                                        <option value="__manual__">+ Enter manually</option>
                                    </select>
                                </template>
                                <template x-if="selScope === 'part' && (parts.length === 0 || selPart === '__manual__')">
                                    <input type="text" x-model="partName" placeholder="Part name…" class="{{ $wInp }} w-36">
                                </template>
                                <button type="button" class="{{ $wBtnOk }}"
                                        @click="if(await wp('scope', selScope, selScope==='part' ? {part_name: partName} : {})){editing=false}">{!! $wCheck !!}</button>
                                <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                            </div>
                        </dd>
                    </div>

                    {{-- Tracking Mode --}}
                    <div x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Tracking Mode</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100"
                                  x-text="{'time':'Date-based','meter':'Meter-based','count':'Count-based'}[trackingMode] ?? trackingMode"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <select x-ref="seltmode" class="{{ $wInp }}" :value="trackingMode">
                                        <option value="time">Date-based</option>
                                        <option value="meter">Meter-based</option>
                                        <option value="count">Count-based</option>
                                    </select>
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('tracking_mode',$refs.seltmode.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Part Serial (only when scope=part) --}}
                    <div x-show="scope === 'part'" x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Part Serial No.</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100" x-text="partSerial || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="text" x-ref="pserial" :value="partSerial" class="{{ $wInp }} w-36" x-init="$nextTick(()=>$refs.pserial?.focus())">
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('part_serial_number',$refs.pserial.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Expiry Date (time-based) --}}
                    <div x-show="trackingMode === 'time'" x-data="{ editing: false }"
                         x-init="$watch('editing', v => v && $nextTick(() => flatpickr($el.querySelector('.fp-exp-{{ $w->id }}'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })))">
                        <dt class="text-xs font-medium text-zinc-500">Expiry Date</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm {{ $expiryClass }}" x-text="expiryDate || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="text" inputmode="none" x-ref="expdt" value="{{ $w->expiry_date?->format('Y-m-d') }}" autocomplete="off" class="{{ $wInp }} fp-exp-{{ $w->id }} w-32">
                                    <button type="button" class="{{ $wBtnOk }}"
                                            @click="if(await wp('expiry_date',$refs.expdt._flatpickr?.altInput?.value||$refs.expdt.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Warranty From (time-based) --}}
                    <div x-show="trackingMode === 'time'" x-data="{ editing: false }"
                         x-init="$watch('editing', v => v && $nextTick(() => flatpickr($el.querySelector('.fp-from-{{ $w->id }}'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })))">
                        <dt class="text-xs font-medium text-zinc-500">From</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100" x-text="dateFrom || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="text" inputmode="none" x-ref="fromdt" value="{{ $w->date_from?->format('Y-m-d') }}" autocomplete="off" class="{{ $wInp }} fp-from-{{ $w->id }} w-32">
                                    <button type="button" class="{{ $wBtnOk }}"
                                            @click="if(await wp('date_from',$refs.fromdt._flatpickr?.altInput?.value||$refs.fromdt.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Counter Limit (meter/count-based) --}}
                    <div x-show="trackingMode !== 'time'" x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Limit</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm {{ $expiryClass }}"
                                  x-text="counterLimit ? Number(counterLimit).toLocaleString() + ' ' + (unit || 'units') : '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="number" x-ref="climit" :value="counterLimit" min="1" class="{{ $wInp }} w-28" x-init="$nextTick(()=>$refs.climit?.focus())">
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('counter_limit',$refs.climit.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Unit (meter/count-based) --}}
                    <div x-show="trackingMode !== 'time'" x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Unit</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100" x-text="unit || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <select x-ref="wunit" class="{{ $wInp }}" :value="unit">
                                        <option value="">-- select --</option>
                                        @foreach(['km'=>'km','miles'=>'miles','hours'=>'hours','cycles'=>'cycles','units'=>'units'] as $uv=>$ul)
                                            <option value="{{ $uv }}">{{ $ul }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('unit',$refs.wunit.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Vendor --}}
                    <div x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Vendor / Provider</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100" x-text="vendorName || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <select x-ref="wvnd" class="{{ $wInp }} max-w-44">
                                        <option value="">-- None --</option>
                                        @foreach ($vendors ?? [] as $vnd)
                                            <option value="{{ $vnd->id }}" {{ $w->vendor_id == $vnd->id ? 'selected' : '' }}>{{ $vnd->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="{{ $wBtnOk }}"
                                            @click="if(await wp('vendor_id',$refs.wvnd.value,{_vendorLabel:$refs.wvnd.options[$refs.wvnd.selectedIndex].text})){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Bill No --}}
                    <div x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Bill / Certificate No.</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100" x-text="billNo || '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="text" x-ref="wbillno" :value="billNo" class="{{ $wInp }} w-36" x-init="$nextTick(()=>$refs.wbillno?.focus())">
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('bill_no',$refs.wbillno.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Amount --}}
                    <div x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500">Amount (₹)</dt>
                        <dd class="mt-0.5 flex items-center gap-1.5">
                            <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-100"
                                  x-text="billAmt ? '₹ ' + billAmt : '--'"></span>
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                            <template x-if="editing">
                                <span class="flex items-center gap-1">
                                    <input type="number" x-ref="wamt" :value="billAmt" min="0" step="0.01" class="{{ $wInp }} w-28" x-init="$nextTick(()=>$refs.wamt?.focus())">
                                    <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('bill_amount',$refs.wamt.value)){editing=false}">{!! $wCheck !!}</button>
                                    <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                </span>
                            </template>
                        </dd>
                    </div>

                    {{-- Smart Alerts (read-only) --}}
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Smart Alerts</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->smartReminders->isNotEmpty() ? $w->smartReminders->count() . ' ' . Str::plural('alert', $w->smartReminders->count()) : '--' }}</dd>
                    </div>

                    @if ($isDisposed && $w->disposed_at)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Disposed</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->disposed_at->format('d M Y') }}{{ $w->disposed_reason ? ' — ' . $w->disposed_reason : '' }}</dd>
                        </div>
                    @endif

                    {{-- Details --}}
                    <div class="sm:col-span-2 lg:col-span-3" x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500 flex items-center gap-1.5">
                            Warranty Details
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                        </dt>
                        <dd class="mt-0.5">
                            <p x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100" x-text="details || '--'"></p>
                            <template x-if="editing">
                                <div class="space-y-1">
                                    <textarea x-ref="wdet" rows="3" class="{{ $wInp }} w-full" x-init="$nextTick(()=>{ if($refs.wdet){$refs.wdet.value=details;$refs.wdet.focus()} })"></textarea>
                                    <div class="flex gap-1">
                                        <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('details',$refs.wdet.value)){editing=false}">{!! $wCheck !!}</button>
                                        <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                    </div>
                                </div>
                            </template>
                        </dd>
                    </div>

                    {{-- Terms --}}
                    <div class="sm:col-span-2 lg:col-span-3" x-data="{ editing: false }">
                        <dt class="text-xs font-medium text-zinc-500 flex items-center gap-1.5">
                            Terms & Conditions
                            <button x-show="!editing" type="button" @click="editing=true" class="{{ $wBtnX }}">{!! $wPencil !!}</button>
                        </dt>
                        <dd class="mt-0.5">
                            <p x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100" x-text="terms || '--'"></p>
                            <template x-if="editing">
                                <div class="space-y-1">
                                    <textarea x-ref="wterms" rows="3" class="{{ $wInp }} w-full" x-init="$nextTick(()=>{ if($refs.wterms){$refs.wterms.value=terms;$refs.wterms.focus()} })"></textarea>
                                    <div class="flex gap-1">
                                        <button type="button" class="{{ $wBtnOk }}" @click="if(await wp('terms',$refs.wterms.value)){editing=false}">{!! $wCheck !!}</button>
                                        <button type="button" class="{{ $wBtnX }}" @click="editing=false">{!! $wXSvg !!}</button>
                                    </div>
                                </div>
                            </template>
                        </dd>
                    </div>

                </dl>

                </div>{{-- end left --}}

                {{-- ── Right: Document panel ── --}}
                @php $wFirstDoc = $w->documents->first(); $wExtraDocs = $w->documents->skip(1); @endphp
                <aside class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>

                    {{-- FilePond dropzone --}}
                    <div class="wd-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center group-hover:scale-110 transition-transform'>
                                    <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                </div>
                                <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                            </div>`,
                            files: @js($wFirstDoc ? [['source' => Storage::url($wFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                            fileMetaBySource: @js($wFirstDoc ? [Storage::url($wFirstDoc->file_path) => ['name' => $wFirstDoc->file_original_name]] : (object)[]),
                            deleteUrl: @js($wFirstDoc ? route('assets.warranties.documents.destroy', [$asset, $wFirstDoc]) : ''),
                            csrfToken: @js(csrf_token()),
                            revertUrlTemplate: () => @js(route('assets.warranties.documents.revert', $asset)),
                            server: {
                                process: {
                                    url: @js(route('assets.warranties.documents.store', [$asset, $w])),
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                                    onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); },
                                    onerror: (e) => toastr.error('Upload failed.'),
                                },
                            },
                        });
                    ">
                        <input type="file" accept="application/pdf,image/jpeg,image/png,image/webp" />
                    </div>

                    @if ($wExtraDocs->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach ($wExtraDocs as $doc)
                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                    <button type="button"
                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.eye class="size-3" />
                                    </button>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                    </a>
                                    <form method="POST" action="{{ route('assets.warranties.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this,'Delete this document?');return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if (!$wFirstDoc && $wExtraDocs->isEmpty())
                        <div class="mt-3 flex flex-col items-center justify-center">
                            <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                        </div>
                    @endif
                </aside>{{-- end right --}}

            </div>
        </x-modal>

        {{-- Dispose modal --}}
        @if ($w->isActive())
            <x-modal name="dispose-warranty-{{ $w->id }}" title="Dispose / Retire Warranty" :dismissible="false">
                <form method="POST" action="{{ route('assets.warranties.dispose', [$asset, $w]) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Mark this warranty as <strong>disposed/retired</strong>. It stays in history and stops triggering reminders.</p>
                    <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                        <div class="relative w-full">
                            <input type="text" inputmode="none" name="disposed_at" placeholder=" " autocomplete="off"
                                   value="{{ now()->format('Y-m-d') }}" class="{{ $inp2 }} pr-9" />
                            <label class="{{ $lbl2 }}">Disposal / Replacement Date</label>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                        </div>
                    </div>
                    <div class="relative">
                        <input type="text" name="disposed_reason" placeholder=" " maxlength="255" class="{{ $inp2 }}" />
                        <label class="{{ $lbl2 }}">Reason (e.g. Part replaced, Sold)</label>
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="danger" size="sm">Confirm Dispose</flux:button>
                        <button type="button" x-on:click="$dispatch('close-modal-dispose-warranty-{{ $w->id }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
                    </div>
                </form>
            </x-modal>
        @endif

        {{-- Warranty card --}}
        <div class="rounded-xl border {{ $isDisposed ? 'border-zinc-700/50 opacity-60' : 'border-zinc-200 dark:border-zinc-800' }} bg-white dark:bg-zinc-900 overflow-hidden flex flex-col">

                {{-- Card header --}}
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 {{ $isDisposed ? 'bg-zinc-800/20' : 'bg-zinc-50 dark:bg-zinc-800/40' }}">
                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                        <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                        <span class="rounded-full {{ $w->warranty_type === 'original' ? 'bg-blue-400/10 text-blue-400' : 'bg-purple-400/10 text-purple-400' }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $w->warrantyTypeLabel() }}</span>
                        @if ($w->scope === 'part')
                            <span class="rounded-full bg-orange-400/10 text-orange-400 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">Part</span>
                            @if ($w->part_name)
                                <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">{{ $w->part_name }}</span>
                                @if ($w->part_serial_number)<span class="text-[11px] text-zinc-400">· {{ $w->part_serial_number }}</span>@endif
                            @endif
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="button" x-on:click="$dispatch('open-modal-view-warranty-{{ $w->id }}')"
                                title="View warranty details"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        @if (! $isDisposed)
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'warrantyid' => $w->id]) }}"
                               title="{{ $w->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                               class="inline-flex size-6 items-center justify-center rounded-md border transition-colors {{ $w->smartReminders->isNotEmpty() ? 'border-blue-500/40 text-blue-400 hover:bg-blue-500/10' : 'border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/10' }}">
                                <flux:icon.bell-alert class="size-3.5" />
                            </a>
                            {{-- <button type="button" x-on:click="$dispatch('open-modal-edit-warranty-{{ $w->id }}')" title="Edit"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                <flux:icon.pencil class="size-3.5" />
                            </button> --}}
                            <button type="button" x-on:click="$dispatch('open-modal-dispose-warranty-{{ $w->id }}')" title="Dispose"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-yellow-500/60 hover:text-yellow-400 dark:border-zinc-700">
                                <flux:icon.archive-box-x-mark class="size-3.5" />
                            </button>
                        @elseif ($replacementUrl)
                            <a href="{{ $replacementUrl }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-accent/40 px-2 py-0.5 text-[11px] font-medium text-accent hover:bg-accent/10 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                                Create Replacement
                            </a>
                        @endif
                        {{-- <form method="POST" action="{{ route('assets.warranties.destroy', [$asset, $w]) }}" onsubmit="confirmDelete(this, 'Delete this warranty entry?'); return false;">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form> --}}
                    </div>
                </div>

                {{-- Card body --}}
                @if (! $isDisposed)
                    <div class="flex-1 px-4 py-3 space-y-3">
                        @if ($w->vendorRecord || $w->vendor)
                            <div>
                                <p class="{{ $dt }}">Vendor</p>
                                <p class="{{ $dd }}">
                                    @if ($w->vendorRecord)
                                        <a href="{{ route('vendors.show', $w->vendorRecord) }}" wire:navigate class="text-accent hover:underline">{{ $w->vendorRecord->name }}</a>
                                    @else
                                        {{ $w->vendor }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-4">
                            <div>
                                <dt class="{{ $dt }}">{{ $expiryLbl }}</dt>
                                <dd class="{{ $expiryClass }}">{{ $expiryVal }}</dd>
                            </div>
                            @if ($w->date_from)
                                <div>
                                    <dt class="{{ $dt }}">From</dt>
                                    <dd class="{{ $dd }}">{{ $w->date_from->format('d M Y') }}</dd>
                                </div>
                            @endif
                            @if ($w->smartReminders->isNotEmpty())
                                <div>
                                    <dt class="{{ $dt }}">Smart Reminders</dt>
                                    <dd class="{{ $dd }}">{{ $w->smartReminders->count() }} {{ Str::plural('reminder', $w->smartReminders->count()) }}</dd>
                                </div>
                            @endif
                            @if (! $w->isTimeBased())
                                @php
                                    $cur       = $w->latestCounter();
                                    $remaining = $w->remainingUnits();
                                @endphp
                                @if ($cur !== null)
                                    <div>
                                        <dt class="{{ $dt }}">Current Reading</dt>
                                        <dd class="{{ $dd }}">{{ number_format($cur) }} {{ $w->unitLabel() }}</dd>
                                    </div>
                                @endif
                                @if ($remaining !== null)
                                    <div>
                                        <dt class="{{ $dt }}">Remaining</dt>
                                        <dd class="mt-0.5 text-sm font-semibold {{ $remaining <= ($srThreshold ?? 0) ? 'text-yellow-400' : ($remaining === 0 ? 'text-red-400' : 'text-green-400') }}">
                                            {{ number_format($remaining) }} {{ $w->unitLabel() }}
                                        </dd>
                                    </div>
                                @elseif ($cur === null && $w->unit)
                                    <div>
                                        <dt class="{{ $dt }}">Current Reading</dt>
                                        <dd class="mt-0.5 text-xs text-zinc-400">No meter logs for {{ $w->unit }} yet</dd>
                                    </div>
                                @endif
                            @endif
                            @if ($w->bill_no)
                                <div>
                                    <dt class="{{ $dt }}">Bill No.</dt>
                                    <dd class="{{ $dd }}">{{ $w->bill_no }}</dd>
                                </div>
                            @endif
                            @if ($w->bill_amount)
                                <div>
                                    <dt class="{{ $dt }}">Amount</dt>
                                    <dd class="{{ $dd }}">₹{{ number_format($w->bill_amount, 2) }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if ($w->details || $w->terms)
                            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @if ($w->details)
                                    <div>
                                        <p class="{{ $dt }}">Details</p>
                                        <p class="mt-0.5 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->details }}</p>
                                    </div>
                                @endif
                                @if ($w->terms)
                                    <div>
                                        <p class="{{ $dt }}">Terms</p>
                                        <p class="mt-0.5 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->terms }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($w->documents->isNotEmpty())
                            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-3">
                                <p class="mb-1.5 {{ $dt }}">Documents</p>
                                <div class="space-y-1">
                                    @foreach ($w->documents as $doc)
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
                                            <form method="POST" action="{{ route('assets.warranties.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this, 'Delete this document?'); return false;">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Card footer --}}
                <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 px-4 py-2">
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    @if ($isDisposed && $w->disposed_at)
                        <span class="text-[11px] text-zinc-500">Disposed {{ $w->disposed_at->format('d M Y') }}@if($w->disposed_reason) — {{ $w->disposed_reason }}@endif</span>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Add Warranty option box --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
            <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $warranties->isEmpty() ? 'No Warranties' : 'Add Another Warranty' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Record manufacturer or supplier warranties for this asset.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-warranty')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $warranties->isEmpty() ? 'Add First Warranty' : 'Add Warranty' }}
                </button>
            </div>
        </div>
    </div>

</div>
