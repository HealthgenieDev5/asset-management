@php
$v = fn($f) => old($f, $ew?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div x-data x-init="
    $nextTick(() => {
        flatpickr($el.querySelector('[name=\'extended_warranty_date_from\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'extended_warranty_date_to\']'),   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
" class="space-y-4">

    {{-- ── Details ── --}}
    <div>
        <p class="{{ $sec }}">Details</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative sm:col-span-2">
                <input type="text" name="extended_warranty_vendor" id="extended_warranty_vendor"
                       value="{{ $v('extended_warranty_vendor') }}" placeholder=" " class="{{ $inp }}" />
                <label for="extended_warranty_vendor" class="{{ $lbl }}">Vendor / Provider</label>
                @error('extended_warranty_vendor')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="extended_warranty_date_from" label="Warranty From"
                               value="{{ $ew?->extended_warranty_date_from?->format('Y-m-d') ?? old('extended_warranty_date_from') }}" />
                @error('extended_warranty_date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="extended_warranty_date_to" label="Warranty Lapse Date"
                               value="{{ $ew?->extended_warranty_date_to?->format('Y-m-d') ?? old('extended_warranty_date_to') }}" />
                @error('extended_warranty_date_to')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
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
            <div class="relative">
                <input type="number" name="reminder_before_days" id="ew_reminder_before_days"
                       value="{{ $v('reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                <label for="ew_reminder_before_days" class="{{ $lbl }}">Reminder (days)</label>
                @error('reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
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
        <p class="{{ $sec }}">Documents</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <p class="mb-1 text-xs text-zinc-500">Warranty Bill <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @if ($ew && ($ewBill = $ew->documents->where('document_type', 'extended_warranty_bill')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                        <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $ewBill->file_original_name }}</span>
                        <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
                    </div>
                @endif
                <input type="file" name="extended_warranty_bill" accept=".pdf,.jpg,.jpeg,.png,.webp"
                       class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                              file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                              hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                @error('extended_warranty_bill')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <p class="mb-1 text-xs text-zinc-500">Activation Image <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @if ($ew && ($ewImg = $ew->documents->where('document_type', 'extended_warranty_image')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                        <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $ewImg->file_original_name }}</span>
                        <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
                    </div>
                @endif
                <input type="file" name="extended_warranty_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
                       class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                              file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                              hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                @error('extended_warranty_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>
