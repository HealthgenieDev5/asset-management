@php
use Illuminate\Support\Facades\Storage;
$v = fn($f) => old($f, $service?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div class="space-y-4">

    {{-- ── Service Info ── --}}
    <div>
        <p class="{{ $sec }}">Service Info</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative sm:col-span-2">
                <select name="service_type" id="service_type" class="{{ $sel }}">
                    @foreach(['preventive_maintenance'=>'Preventive Maintenance','corrective_maintenance'=>'Corrective Maintenance','inspection'=>'Inspection','repair'=>'Repair','calibration'=>'Calibration','cleaning'=>'Cleaning','other'=>'Other'] as $val=>$lbl2)
                        <option value="{{ $val }}" @selected($v('service_type')===$val)>{{ $lbl2 }}</option>
                    @endforeach
                </select>
                <label for="service_type" class="{{ $lbs }}">Service Type</label>
                @error('service_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <select name="condition_rating" id="condition_rating" class="{{ $sel }}">
                    <option value=""></option>
                    @foreach(['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair','poor'=>'Poor','critical'=>'Critical'] as $val=>$lbl2)
                        <option value="{{ $val }}" @selected($v('condition_rating')===$val)>{{ $lbl2 }}</option>
                    @endforeach
                </select>
                <label for="condition_rating" class="{{ $lbs }}">Condition</label>
                @error('condition_rating')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="technician_name" id="technician_name" value="{{ $v('technician_name') }}" placeholder=" " class="{{ $inp }}" />
                <label for="technician_name" class="{{ $lbl }}">Technician</label>
                @error('technician_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="service_date" id="service_date" value="{{ $service?->service_date?->format('Y-m-d') ?? old('service_date') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="service_date" class="{{ $lbl }}">Service Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('service_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="next_service_date" id="next_service_date" value="{{ $service?->next_service_date?->format('Y-m-d') ?? old('next_service_date') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="next_service_date" class="{{ $lbl }}">Next Service Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('next_service_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative sm:col-span-2">
                <input type="text" name="service_agency" id="service_agency" value="{{ $v('service_agency') }}" placeholder=" " class="{{ $inp }}" />
                <label for="service_agency" class="{{ $lbl }}">Service Agency / Company</label>
                @error('service_agency')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Billing ── --}}
    <div>
        <p class="{{ $sec }}">Billing</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="number" name="service_cost" id="service_cost" value="{{ $v('service_cost') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="service_cost" class="{{ $lbl }}">Service Cost (₹)</label>
                @error('service_cost')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="bill_no" id="bill_no" value="{{ $v('bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="bill_no" class="{{ $lbl }}">Bill Number</label>
                @error('bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="bill_date" id="bill_date" value="{{ $service?->bill_date?->format('Y-m-d') ?? old('bill_date') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="bill_date" class="{{ $lbl }}">Bill Date</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('bill_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Readings ── --}}
    <div>
        <p class="{{ $sec }}">Readings</p>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
            <div class="relative">
                <input type="number" name="meter_reading" id="meter_reading" value="{{ $v('meter_reading') }}" placeholder=" " min="0" class="{{ $inp }}" />
                <label for="meter_reading" class="{{ $lbl }}">Meter / Op. Hrs</label>
                @error('meter_reading')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="mileage_reading" id="mileage_reading" value="{{ $v('mileage_reading') }}" placeholder=" " min="0" class="{{ $inp }}" />
                <label for="mileage_reading" class="{{ $lbl }}">Odometer (km)</label>
                @error('mileage_reading')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="downtime_hours" id="downtime_hours" value="{{ $v('downtime_hours') }}" placeholder=" " min="0" step="0.5" class="{{ $inp }}" />
                <label for="downtime_hours" class="{{ $lbl }}">Downtime (hrs)</label>
                @error('downtime_hours')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Schedule ── --}}
    <div>
        <p class="{{ $sec }}">Schedule & Certification</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div class="relative">
                <input type="number" name="service_interval_value" id="service_interval_value" value="{{ $v('service_interval_value') }}" placeholder=" " min="1" class="{{ $inp }}" />
                <label for="service_interval_value" class="{{ $lbl }}">Interval Value</label>
                @error('service_interval_value')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <select name="service_interval_unit" id="service_interval_unit" class="{{ $sel }}">
                    <option value=""></option>
                    @foreach(['days'=>'Days','weeks'=>'Weeks','months'=>'Months','years'=>'Years','operating_hours'=>'Op. Hours','kilometers'=>'Km'] as $val=>$lbl2)
                        <option value="{{ $val }}" @selected($v('service_interval_unit')===$val)>{{ $lbl2 }}</option>
                    @endforeach
                </select>
                <label for="service_interval_unit" class="{{ $lbs }}">Interval Unit</label>
                @error('service_interval_unit')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="next_service_reminder_before_days" id="next_service_reminder_before_days" value="{{ $v('next_service_reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                <label for="next_service_reminder_before_days" class="{{ $lbl }}">Reminder (days)</label>
                @error('next_service_reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                <div class="relative w-full">
                    <input type="text" inputmode="none" name="certification_expiry" id="certification_expiry" value="{{ $service?->certification_expiry?->format('Y-m-d') ?? old('certification_expiry') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                    <label for="certification_expiry" class="{{ $lbl }}">Certification Expiry</label>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
                </div>
                @error('certification_expiry')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="certification_reminder_before_days" id="certification_reminder_before_days" value="{{ $v('certification_reminder_before_days') }}" placeholder=" " min="1" max="365" class="{{ $inp }}" />
                <label for="certification_reminder_before_days" class="{{ $lbl }}">Cert. Reminder (days)</label>
                @error('certification_reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Notes ── --}}
    <div>
        <p class="{{ $sec }}">Notes</p>
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <textarea name="work_done" id="work_done" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('work_done') }}</textarea>
                <label for="work_done" class="{{ $lbl }}">Work Done</label>
                @error('work_done')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="safety_notes" id="safety_notes" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('safety_notes') }}</textarea>
                <label for="safety_notes" class="{{ $lbl }}">Safety Notes</label>
                @error('safety_notes')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <style>
            .service-doc-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Document</p>
        @php $svcDoc = $service?->documents->first(); @endphp
        <div class="service-doc-upload" x-data x-init="initUploadPond($refs.serviceBill, {
                acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                @if ($svcDoc)
                files: [{ source: '{{ Storage::url($svcDoc->file_path) }}', options: { type: 'local' } }],
                fileMetaBySource: { '{{ Storage::url($svcDoc->file_path) }}': { name: '{{ addslashes($svcDoc->file_original_name) }}' } },
                onremovefile: () => fetch('{{ route('assets.services.documents.destroy', [$asset, $svcDoc]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_method=DELETE'
                }),
                @endif
            })">
            <input type="file" name="service_bill" x-ref="serviceBill" accept=".pdf,.jpg,.jpeg,.png,.webp" />
        </div>
        <p class="mt-1 text-[11px] text-zinc-400">PDF, JPG, PNG, WEBP — max 5 MB</p>
        @error('service_bill')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>
