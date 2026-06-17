@php
use Illuminate\Support\Facades\Storage;

$w      = $warranty ?? null;
$v      = fn($f) => old($f, $w?->{$f});
$inp    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$txa    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl    = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sec    = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err    = 'mt-0.5 text-[11px] text-red-400';
$cal    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';

$wType  = old('warranty_type', $w?->warranty_type ?? ($defaultType ?? 'original'));
$wScope = old('scope', $w?->scope ?? ($defaultScope ?? 'overall'));
$prefillPart = $prefillPart ?? null;
$wMode  = old('tracking_mode', $w?->tracking_mode ?? 'time');
$wSrc   = old('meter_source', $w?->meter_source ?? 'meter');
$wUnit  = old('unit', $w?->unit ?? '');
$currentCounter = $asset?->latestWarrantyCounter();

$formId = 'wf_' . ($w?->id ?? 'new');
@endphp

<div id="{{ $formId }}" class="space-y-4"
     x-data="{
         wtype: '{{ $wType }}',
         scope: '{{ $wScope }}',
         mode:  '{{ $wMode }}',
         src:   '{{ $wSrc }}',
         unit:  '{{ $wUnit }}'
     }">

    <input type="hidden" name="warranty_type" :value="wtype">
    <input type="hidden" name="scope"          :value="scope">
    <input type="hidden" name="tracking_mode"  :value="mode">
    <input type="hidden" name="meter_source"   :value="src">

    {{-- ── Warranty Type ── --}}
    <div>
        <p class="{{ $sec }}">Warranty Type</p>
        <div class="flex gap-2">
            <button type="button"
                @click="wtype = 'original'"
                :class="wtype === 'original'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Original (Manufacturer)
            </button>
            <button type="button"
                @click="wtype = 'extended'"
                :class="wtype === 'extended'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Extended (Purchased)
            </button>
        </div>
        @error('warranty_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

    {{-- ── Scope ── --}}
    <div>
        <p class="{{ $sec }}">Coverage Scope</p>
        <div class="flex gap-2">
            <button type="button"
                @click="scope = 'overall'"
                :class="scope === 'overall'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Overall Product
            </button>
            <button type="button"
                @click="scope = 'part'"
                :class="scope === 'part'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Specific Part
            </button>
        </div>
        @error('scope')<p class="{{ $err }}">{{ $message }}</p>@enderror

        {{-- Part fields (shown only when scope=part) --}}
        <div x-show="scope === 'part'" style="display:none" class="mt-3 grid grid-cols-2 gap-3">
            <div class="relative">
                <input type="text" name="part_name" id="{{ $formId }}_part_name"
                       value="{{ $v('part_name') ?? $prefillPart }}" placeholder=" " maxlength="100" class="{{ $inp }}" />
                <label for="{{ $formId }}_part_name" class="{{ $lbl }}">Part Name <span class="text-red-400">*</span></label>
                @error('part_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="part_serial_number" id="{{ $formId }}_part_serial"
                       value="{{ $v('part_serial_number') }}" placeholder=" " maxlength="100" class="{{ $inp }}" />
                <label for="{{ $formId }}_part_serial" class="{{ $lbl }}">Part Serial Number</label>
                @error('part_serial_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Tracking Mode ── --}}
    <div>
        <p class="{{ $sec }}">Tracking Mode</p>
        <div class="flex gap-2">
            <button type="button"
                @click="mode = 'time'"
                :class="mode === 'time'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Date-based
            </button>
            <button type="button"
                @click="mode = 'meter'"
                :class="mode === 'meter'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Meter-based
            </button>
            <button type="button"
                @click="mode = 'count'"
                :class="mode === 'count'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Count-based
            </button>
        </div>
        <p class="mt-1 text-[11px] text-zinc-400">
            <span x-show="mode === 'time'">Expires on a specific date</span>
            <span x-show="mode === 'meter'" style="display:none">Expires at a meter/odometer reading</span>
            <span x-show="mode === 'count'" style="display:none">Expires after a usage count (prints, cycles, etc.)</span>
        </p>
    </div>

    {{-- ── Dates / Counter ── --}}
    <div>
        <p class="{{ $sec }}">Warranty Period</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- Date-based fields --}}
            <div x-show="mode === 'time'" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="date_from" id="{{ $formId }}_date_from"
                               value="{{ $w?->date_from?->format('Y-m-d') ?? old('date_from') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="{{ $formId }}_date_from" class="{{ $lbl }}">Warranty From</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="expiry_date" id="{{ $formId }}_expiry_date"
                               value="{{ $w?->expiry_date?->format('Y-m-d') ?? old('expiry_date') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="{{ $formId }}_expiry_date" class="{{ $lbl }}">Warranty Expiry Date</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('expiry_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="number" name="reminder_before_days" id="{{ $formId }}_reminder_days"
                           value="{{ $v('reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                    <label for="{{ $formId }}_reminder_days" class="{{ $lbl }}">Reminder (days before)</label>
                    @error('reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Counter-based fields --}}
            <div x-show="mode !== 'time'" style="display:none" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="relative">
                    <input type="text" name="unit" id="{{ $formId }}_unit"
                           x-model="unit"
                           value="{{ old('unit', $w?->unit) }}"
                           placeholder=" " maxlength="20" class="{{ $inp }}" />
                    <label for="{{ $formId }}_unit" class="{{ $lbl }}">Unit (e.g. km, hours, prints)</label>
                    @error('unit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div x-show="mode === 'meter'" style="display:none" class="relative">
                    <select x-model="src" class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent">
                        <option value="mileage">Odometer / Distance (mileage)</option>
                        <option value="meter">Hour meter / Operating hours</option>
                    </select>
                    <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Reading Source</label>
                </div>

                <div class="relative">
                    <input type="number" name="counter_limit" id="{{ $formId }}_counter_limit"
                           value="{{ old('counter_limit', $w?->counter_limit) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="{{ $formId }}_counter_limit" class="{{ $lbl }}">Warranty Limit (<span x-text="unit || 'units'"></span>)</label>
                    @error('counter_limit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div class="relative">
                    <input type="number" name="reminder_before_units" id="{{ $formId }}_reminder_units"
                           value="{{ old('reminder_before_units', $w?->reminder_before_units) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="{{ $formId }}_reminder_units" class="{{ $lbl }}">Remind when within (<span x-text="unit || 'units'"></span>)</label>
                    @error('reminder_before_units')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                @if ($currentCounter !== null)
                    <div class="col-span-2 sm:col-span-4 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        Current reading from last service: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($currentCounter) }} <span x-text="unit || 'units'"></span></span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Vendor & Billing ── (hidden for original overall — manufacturer warranty has no purchase bill) --}}
    <div x-show="!(wtype === 'original' && scope === 'overall')" style="display:none">
        <p class="{{ $sec }}">Vendor & Billing</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative sm:col-span-2">
                <input type="text" name="vendor" id="{{ $formId }}_vendor"
                       value="{{ $v('vendor') }}" placeholder=" " class="{{ $inp }}" />
                <label for="{{ $formId }}_vendor" class="{{ $lbl }}">Vendor / Provider</label>
                @error('vendor')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="bill_no" id="{{ $formId }}_bill_no"
                       value="{{ $v('bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="{{ $formId }}_bill_no" class="{{ $lbl }}">Bill / Certificate No.</label>
                @error('bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="bill_amount" id="{{ $formId }}_bill_amount"
                       value="{{ $v('bill_amount') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="{{ $formId }}_bill_amount" class="{{ $lbl }}">Amount (₹)</label>
                @error('bill_amount')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Notes ── --}}
    <div>
        <p class="{{ $sec }}">Notes</p>
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <textarea name="details" id="{{ $formId }}_details" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ $v('details') }}</textarea>
                <label for="{{ $formId }}_details" class="{{ $lbl }}">Warranty Details</label>
                @error('details')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="terms" id="{{ $formId }}_terms" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ $v('terms') }}</textarea>
                <label for="{{ $formId }}_terms" class="{{ $lbl }}">Terms & Conditions</label>
                @error('terms')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            {{-- Remarks hidden for now --}}
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <style>
            .warranty-doc-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Document</p>
        @php $existingDocs = $w?->documents ?? collect(); @endphp
        @if ($existingDocs->isNotEmpty())
            <div class="mb-2 space-y-1">
                @foreach ($existingDocs as $doc)
                    <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-700 dark:bg-zinc-800/50">
                        @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                        <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="shrink-0 text-[11px] text-accent hover:underline">View</a>
                        <form method="POST" action="{{ route('assets.warranties.documents.destroy', [$asset, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700"><flux:icon.trash class="size-3" /></button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="warranty-doc-upload">
            <p class="mb-1 text-xs text-zinc-500">{{ $existingDocs->isNotEmpty() ? 'Replace / Add Document' : 'Warranty Document' }} <span class="font-normal">(PDF / image, max 5 MB)</span></p>
            <div x-data x-init="initUploadPond($refs.warrantyDoc, {
                    acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                })">
                <input type="file" name="warranty_doc" x-ref="warrantyDoc" accept=".pdf,.jpg,.jpeg,.png,.webp" />
            </div>
            @error('warranty_doc')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

</div>
