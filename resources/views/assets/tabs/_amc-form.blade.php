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

<div class="space-y-4">

    {{-- ── Contract ── --}}
    <div>
        <p class="{{ $sec }}">Contract</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="text" name="contract_number" id="contract_number" value="{{ $v('contract_number') }}" placeholder=" " class="{{ $inp }}" />
                <label for="contract_number" class="{{ $lbl }}">Contract Number</label>
                @error('contract_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            @php
                $vendorMapAmc = ($vendors ?? collect())->mapWithKeys(fn($v) => [$v->id => ['id' => $v->id, 'phone' => $v->phone, 'email' => $v->email]])->toJson();
            @endphp
            <div class="relative sm:col-span-2"
                 x-data="{
                     selectedId: '{{ old('vendor_id', $amc?->vendor_id ?? '') }}',
                     vendors: {{ $vendorMapAmc }},
                     get info() { return this.vendors[this.selectedId] ?? null; }
                 }">
                <select name="vendor_id" id="vendor_id" class="{{ $sel }}" x-model="selectedId">
                    <option value=""></option>
                    @foreach ($vendors ?? [] as $vnd)
                        <option value="{{ $vnd->id }}" @selected((int) old('vendor_id', $amc?->vendor_id) === $vnd->id)>
                            {{ $vnd->name }}
                        </option>
                    @endforeach
                </select>
                <label for="vendor_id" class="{{ $lbs }}">Vendor</label>
                @error('vendor_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
                <template x-if="info">
                    <div class="mt-1 rounded-lg bg-zinc-50 px-3 py-1.5 text-xs text-zinc-500 dark:bg-zinc-800 space-y-0.5">
                        <p x-text="info.phone"></p>
                        <p x-text="info.email"></p>
                    </div>
                </template>
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
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="amc_date_from" id="amc_date_from" value="{{ $amc?->amc_date_from?->format('Y-m-d') ?? old('amc_date_from') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="amc_date_from" class="{{ $lbl }}">AMC From</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('amc_date_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="amc_date_to" id="amc_date_to" value="{{ $amc?->amc_date_to?->format('Y-m-d') ?? old('amc_date_to') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="amc_date_to" class="{{ $lbl }}">AMC Lapse Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('amc_date_to')<p class="{{ $err }}">{{ $message }}</p>@enderror
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
            <div class="relative col-span-2">
                <input type="text" name="amc_bill_no" id="amc_bill_no" value="{{ $v('amc_bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="amc_bill_no" class="{{ $lbl }}">Bill Number</label>
                @error('amc_bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative"
            x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="amc_bill_date" id="amc_bill_date" value="{{ $amc?->amc_bill_date?->format('Y-m-d') ?? old('amc_bill_date') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="amc_bill_date" class="{{ $lbl }}">Bill Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('amc_bill_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
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
        <style>.amc-doc-upload .filepond--panel-root { border: 1px dashed #4b4b4c; border-radius: 10px; }</style>
        <p class="{{ $sec }}">Document</p>
        @php $amcDoc = $amc?->documents->first(); @endphp
        <div class="amc-doc-upload" x-data x-init="initUploadPond($refs.amcBill, {
                acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                @if ($amcDoc)
                files: [{ source: '{{ Storage::url($amcDoc->file_path) }}', options: { type: 'local' } }],
                fileMetaBySource: { '{{ Storage::url($amcDoc->file_path) }}': { name: '{{ addslashes($amcDoc->file_original_name) }}' } },
                onremovefile: () => fetch('{{ route('assets.amc.documents.destroy', [$asset, $amcDoc]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_method=DELETE'
                }),
                @endif
            })">
            <input type="file" name="amc_bill_image" x-ref="amcBill" accept=".pdf,.jpg,.jpeg,.png,.webp" />
        </div>
        <p class="mt-1 text-[11px] text-zinc-400">PDF, JPG, PNG, WEBP — max 5 MB</p>
        @error('amc_bill_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>{{-- end x-data wrapper --}}
