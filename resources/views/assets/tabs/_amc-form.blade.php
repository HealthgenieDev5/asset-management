@php
$v = fn($f) => old($f, $amc?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div x-data x-init="
    $nextTick(() => {
        flatpickr($el.querySelector('[name=\'amc_date_from\']'),  { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'amc_date_to\']'),    { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'amc_bill_date\']'),  { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
" class="space-y-4">

    {{-- ── Contract ── --}}
    <div>
        <p class="{{ $sec }}">Contract</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="text" name="contract_number" id="contract_number" value="{{ $v('contract_number') }}" placeholder=" " class="{{ $inp }}" />
                <label for="contract_number" class="{{ $lbl }}">Contract Number</label>
                @error('contract_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="text" name="vendor_name" id="vendor_name" value="{{ $v('vendor_name') }}" placeholder=" " class="{{ $inp }}" />
                <label for="vendor_name" class="{{ $lbl }}">Vendor / Provider</label>
                @error('vendor_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <select name="coverage_type" id="coverage_type" class="{{ $sel }}">
                    @foreach(['comprehensive'=>'Comprehensive','non_comprehensive'=>'Non-Comprehensive','parts_only'=>'Parts Only','labour_only'=>'Labour Only'] as $val=>$lbl2)
                        <option value="{{ $val }}" @selected($v('coverage_type')===$val)>{{ $lbl2 }}</option>
                    @endforeach
                </select>
                <label for="coverage_type" class="{{ $lbs }}">Coverage Type</label>
                @error('coverage_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="amc_date_from" label="AMC From" value="{{ $amc?->amc_date_from?->format('Y-m-d') ?? old('amc_date_from') }}" />
                @error('amc_date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="amc_date_to" label="AMC Lapse Date" value="{{ $amc?->amc_date_to?->format('Y-m-d') ?? old('amc_date_to') }}" />
                @error('amc_date_to')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="reminder_before_days" id="reminder_before_days" value="{{ $v('reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                <label for="reminder_before_days" class="{{ $lbl }}">Reminder (days)</label>
                @error('reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Billing ── --}}
    <div>
        <p class="{{ $sec }}">Billing</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="number" name="amc_amount" id="amc_amount" value="{{ $v('amc_amount') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="amc_amount" class="{{ $lbl }}">AMC Amount (₹)</label>
                @error('amc_amount')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="amc_bill_no" id="amc_bill_no" value="{{ $v('amc_bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="amc_bill_no" class="{{ $lbl }}">Bill Number</label>
                @error('amc_bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="amc_bill_date" label="Bill Date" value="{{ $amc?->amc_bill_date?->format('Y-m-d') ?? old('amc_bill_date') }}" />
                @error('amc_bill_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Contact ── --}}
    <div>
        <p class="{{ $sec }}">Vendor Contact</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="text" name="vendor_contact_person" id="vendor_contact_person" value="{{ $v('vendor_contact_person') }}" placeholder=" " class="{{ $inp }}" />
                <label for="vendor_contact_person" class="{{ $lbl }}">Contact Person</label>
                @error('vendor_contact_person')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="vendor_phone" id="vendor_phone" value="{{ $v('vendor_phone') }}" placeholder=" " class="{{ $inp }}" />
                <label for="vendor_phone" class="{{ $lbl }}">Phone</label>
                @error('vendor_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="email" name="vendor_email" id="vendor_email" value="{{ $v('vendor_email') }}" placeholder=" " class="{{ $inp }}" />
                <label for="vendor_email" class="{{ $lbl }}">Email</label>
                @error('vendor_email')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Notes ── --}}
    <div>
        <p class="{{ $sec }}">Notes</p>
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <textarea name="coverage_details" id="coverage_details" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('coverage_details') }}</textarea>
                <label for="coverage_details" class="{{ $lbl }}">Coverage Details</label>
                @error('coverage_details')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="amc_terms" id="amc_terms" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('amc_terms') }}</textarea>
                <label for="amc_terms" class="{{ $lbl }}">AMC Terms</label>
                @error('amc_terms')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative col-span-2">
                <input type="text" name="remarks" id="remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
                <label for="remarks" class="{{ $lbl }}">Remarks</label>
                @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <p class="{{ $sec }}">Document</p>
        <input type="file" name="amc_bill_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
               class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                      file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                      hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
        <p class="mt-1 text-[11px] text-zinc-400">PDF, JPG, PNG, WEBP — max 5 MB</p>
        @if ($amc?->documents->isNotEmpty())
            <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $amc->documents->first()->file_original_name }}</span>
                <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
            </div>
        @endif
        @error('amc_bill_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>{{-- end x-data wrapper --}}
