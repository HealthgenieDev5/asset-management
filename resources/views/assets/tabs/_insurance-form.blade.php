@php
$v = fn($f) => old($f, $policy?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
$cal = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
@endphp

<div class="space-y-4">

    {{-- ── Policy Details ── --}}
    <div>
        <p class="{{ $sec }}">Policy Details</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="text" name="policy_number" id="policy_number" value="{{ $v('policy_number') }}" placeholder=" " class="{{ $inp }}" />
                <label for="policy_number" class="{{ $lbl }}">Policy Number</label>
                @error('policy_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="insurer_name" id="insurer_name" value="{{ $v('insurer_name') }}" placeholder=" " class="{{ $inp }}" />
                <label for="insurer_name" class="{{ $lbl }}">Insurer Name</label>
                @error('insurer_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="text" name="policy_type" id="policy_type" value="{{ $v('policy_type') }}" placeholder=" " class="{{ $inp }}" />
                <label for="policy_type" class="{{ $lbl }}">Policy Type</label>
                @error('policy_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="policy_date_from" id="policy_date_from" value="{{ $policy?->policy_date_from?->format('Y-m-d') ?? old('policy_date_from') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="policy_date_from" class="{{ $lbl }}">Policy From</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                </div>
                @error('policy_date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="policy_date_to" id="policy_date_to" value="{{ $policy?->policy_date_to?->format('Y-m-d') ?? old('policy_date_to') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="policy_date_to" class="{{ $lbl }}">Policy Expiry</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                </div>
                @error('policy_date_to')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Financials ── --}}
    <div>
        <p class="{{ $sec }}">Financials</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="number" name="premium_amount" id="premium_amount" value="{{ $v('premium_amount') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="premium_amount" class="{{ $lbl }}">Premium (₹)</label>
                @error('premium_amount')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="sum_insured" id="sum_insured" value="{{ $v('sum_insured') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="sum_insured" class="{{ $lbl }}">Sum Insured (₹)</label>
                @error('sum_insured')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="bill_no" id="bill_no" value="{{ $v('bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="bill_no" class="{{ $lbl }}">Bill Number</label>
                @error('bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="bill_date" id="bill_date" value="{{ $policy?->bill_date?->format('Y-m-d') ?? old('bill_date') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="bill_date" class="{{ $lbl }}">Bill Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                </div>
                @error('bill_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Contact ── --}}
    <div>
        <p class="{{ $sec }}">Contact</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="text" name="insurer_contact_person" id="insurer_contact_person" value="{{ $v('insurer_contact_person') }}" placeholder=" " class="{{ $inp }}" />
                <label for="insurer_contact_person" class="{{ $lbl }}">Contact Person</label>
                @error('insurer_contact_person')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="insurer_phone" id="insurer_phone" value="{{ $v('insurer_phone') }}" placeholder=" " class="{{ $inp }}" />
                <label for="insurer_phone" class="{{ $lbl }}">Phone</label>
                @error('insurer_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="email" name="insurer_email" id="insurer_email" value="{{ $v('insurer_email') }}" placeholder=" " class="{{ $inp }}" />
                <label for="insurer_email" class="{{ $lbl }}">Email</label>
                @error('insurer_email')<p class="{{ $err }}">{{ $message }}</p>@enderror
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
                <input type="text" name="remarks" id="remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
                <label for="remarks" class="{{ $lbl }}">Remarks</label>
                @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <style>.insurance-doc-upload .filepond--panel-root { border: 1px dashed #4b4b4c; border-radius: 10px; }</style>
        <p class="{{ $sec }}">Document</p>
        @php $insDoc = $policy?->documents->first(); @endphp
        <div class="insurance-doc-upload" x-data x-init="initUploadPond($refs.insDoc, {
                acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                @if ($insDoc)
                files: [{ source: '{{ Storage::url($insDoc->file_path) }}', options: { type: 'local' } }],
                fileMetaBySource: { '{{ Storage::url($insDoc->file_path) }}': { name: '{{ addslashes($insDoc->file_original_name) }}' } },
                onremovefile: () => fetch('{{ route('assets.insurance.documents.destroy', [$asset, $insDoc]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_method=DELETE'
                }),
                @endif
            })">
            <input type="file" name="insurance_document" x-ref="insDoc" accept="application/pdf,image/jpeg,image/png,image/webp" />
        </div>
        <p class="mt-1 text-[11px] text-zinc-400">PDF, JPG, PNG, WEBP — max 5 MB</p>
        @error('insurance_document')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>
