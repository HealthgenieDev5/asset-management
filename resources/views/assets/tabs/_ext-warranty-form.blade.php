@php
use Illuminate\Support\Facades\Storage;
$v      = fn($f) => old($f, $ew?->{$f});
$inp    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$txa    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl    = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sec    = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err    = 'mt-0.5 text-[11px] text-red-400';
$cal    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
$ewAsset = $ew?->asset ?? $asset ?? null;
// Use the EW's own tracking mode (independent of the basic warranty)
$ewMode  = old('ew_tracking_mode', $ew?->ew_tracking_mode ?? 'time');
$ewSrc   = old('ew_meter_source',  $ew?->ew_meter_source  ?? 'meter');
$ewUnit  = old('ew_unit',          $ew?->ew_unit          ?? '');
$currentCounter = $ewAsset?->latestWarrantyCounter();
@endphp

<div class="space-y-4"
     x-data="{
         mode: '{{ $ewMode }}',
         src:  '{{ $ewSrc }}',
         unit: '{{ $ewUnit }}'
     }">

    {{-- Hidden inputs --}}
    <input type="hidden" name="ew_tracking_mode" :value="mode">
    <input type="hidden" name="ew_meter_source"  :value="src">

    {{-- ── Warranty Type ── --}}
    <div>
        <p class="{{ $sec }}">Warranty Type</p>
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
            <span x-show="mode === 'meter'" style="display:none">Expires at a distance/hours reading</span>
            <span x-show="mode === 'count'" style="display:none">Expires after a usage count</span>
        </p>
    </div>

    {{-- ── Details ── --}}
    <div>
        <p class="{{ $sec }}">Details</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

           
            {{-- Date-based fields --}}
            <div x-show="mode === 'time'" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="extended_warranty_date_from" id="extended_warranty_date_from"
                               value="{{ $ew?->extended_warranty_date_from?->format('Y-m-d') ?? old('extended_warranty_date_from') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="extended_warranty_date_from" class="{{ $lbl }}">Warranty From</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('extended_warranty_date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="extended_warranty_date_to" id="extended_warranty_date_to"
                               value="{{ $ew?->extended_warranty_date_to?->format('Y-m-d') ?? old('extended_warranty_date_to') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="extended_warranty_date_to" class="{{ $lbl }}">Warranty Lapse Date</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('extended_warranty_date_to')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="number" name="reminder_before_days" id="ew_reminder_before_days"
                           value="{{ $v('reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                    <label for="ew_reminder_before_days" class="{{ $lbl }}">Reminder (days before)</label>
                    @error('reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Counter-based fields (meter or count) --}}
            <div x-show="mode !== 'time'" style="display:none" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">

                {{-- Unit label --}}
                <div class="relative">
                    <input type="text" name="ew_unit" id="ew_unit"
                           x-model="unit"
                           value="{{ old('ew_unit', $ew?->ew_unit) }}"
                           placeholder=" " maxlength="20" class="{{ $inp }}" />
                    <label for="ew_unit" class="{{ $lbl }}">Unit (e.g. km, hours, prints)</label>
                    @error('ew_unit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Meter source — only for 'meter' mode --}}
                <div x-show="mode === 'meter'" style="display:none" class="relative">
                    <select x-model="src" class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent">
                        <option value="mileage">Odometer / Distance (mileage)</option>
                        <option value="meter">Hour meter / Operating hours</option>
                    </select>
                    <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Reading Source</label>
                </div>

                {{-- Counter limit --}}
                <div class="relative">
                    <input type="number" name="extended_warranty_counter_limit" id="extended_warranty_counter_limit"
                           value="{{ old('extended_warranty_counter_limit', $ew?->extended_warranty_counter_limit) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="extended_warranty_counter_limit" class="{{ $lbl }}">Warranty Limit (<span x-text="unit || 'units'"></span>)</label>
                    @error('extended_warranty_counter_limit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Reminder before units --}}
                <div class="relative">
                    <input type="number" name="extended_warranty_reminder_before_units" id="extended_warranty_reminder_before_units"
                           value="{{ old('extended_warranty_reminder_before_units', $ew?->extended_warranty_reminder_before_units) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="extended_warranty_reminder_before_units" class="{{ $lbl }}">Remind when within (<span x-text="unit || 'units'"></span>)</label>
                    @error('extended_warranty_reminder_before_units')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Current reading info --}}
                @if ($currentCounter !== null)
                    <div class="col-span-2 sm:col-span-4 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        Current reading from last service: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($currentCounter) }} <span x-text="unit || 'units'"></span></span>
                    </div>
                @endif
            </div>


             {{-- Vendor (always shown) --}}
            <div class="relative sm:col-span-2">
                <input type="text" name="extended_warranty_vendor" id="extended_warranty_vendor"
                       value="{{ $v('extended_warranty_vendor') }}" placeholder=" " class="{{ $inp }}" />
                <label for="extended_warranty_vendor" class="{{ $lbl }}">Vendor / Provider</label>
                @error('extended_warranty_vendor')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>


            {{-- Bill no & amount (always shown) --}}
            <div class="relative">
                <input type="text" name="extended_warranty_bill_no" id="extended_warranty_bill_no"
                       value="{{ $v('extended_warranty_bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="extended_warranty_bill_no" class="{{ $lbl }}">Bill Number</label>
                @error('extended_warranty_bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="extended_warranty_amount" id="extended_warranty_amount"
                       value="{{ $v('extended_warranty_amount') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="extended_warranty_amount" class="{{ $lbl }}">Amount (₹)</label>
                @error('extended_warranty_amount')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    {{-- ── Notes ── --}}
    <div>
        <p class="{{ $sec }}">Notes</p>
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <textarea name="extended_warranty_terms" id="extended_warranty_terms" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ $v('extended_warranty_terms') }}</textarea>
                <label for="extended_warranty_terms" class="{{ $lbl }}">Warranty Terms</label>
                @error('extended_warranty_terms')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="remarks" id="ew_remarks" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ $v('remarks') }}</textarea>
                <label for="ew_remarks" class="{{ $lbl }}">Remarks</label>
                @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Documents ── --}}
    <div>
        <style>
            .ext-warranty-doc-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Documents</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="ext-warranty-doc-upload">
                <p class="mb-1 text-xs text-zinc-500">Warranty Bill <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $ewBill = $ew?->documents->where('document_type', 'extended_warranty_bill')->last(); @endphp
                <div x-data x-init="initUploadPond($refs.ewBill, {
                        acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        files: @js($ewBill ? [['source' => Storage::url($ewBill->file_path), 'options' => ['type' => 'local']]] : []),
                        fileMetaBySource: @js($ewBill ? [Storage::url($ewBill->file_path) => ['name' => $ewBill->file_original_name]] : []),
                    })">
                    <input type="file" name="extended_warranty_bill" x-ref="ewBill" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                </div>
                @error('extended_warranty_bill')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="ext-warranty-doc-upload">
                <p class="mb-1 text-xs text-zinc-500">Activation Image <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $ewImg = $ew?->documents->where('document_type', 'extended_warranty_image')->last(); @endphp
                <div x-data x-init="initUploadPond($refs.ewImage, {
                        acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        files: @js($ewImg ? [['source' => Storage::url($ewImg->file_path), 'options' => ['type' => 'local']]] : []),
                        fileMetaBySource: @js($ewImg ? [Storage::url($ewImg->file_path) => ['name' => $ewImg->file_original_name]] : []),
                    })">
                    <input type="file" name="extended_warranty_image" x-ref="ewImage" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                </div>
                @error('extended_warranty_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>
