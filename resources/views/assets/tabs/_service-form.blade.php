@php
$v = fn($f) => old($f, $service?->{$f});
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
        flatpickr($el.querySelector('[name=\'service_date\']'),         { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'next_service_date\']'),    { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'bill_date\']'),            { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'certification_expiry\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
" class="space-y-4">

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
            <div>
                <x-date-picker name="service_date" label="Service Date" value="{{ $service?->service_date?->format('Y-m-d') ?? old('service_date') }}" />
                @error('service_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="next_service_date" label="Next Service Date" value="{{ $service?->next_service_date?->format('Y-m-d') ?? old('next_service_date') }}" />
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
            <div>
                <x-date-picker name="bill_date" label="Bill Date" value="{{ $service?->bill_date?->format('Y-m-d') ?? old('bill_date') }}" />
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
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
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
            <div>
                <x-date-picker name="certification_expiry" label="Certification Expiry" value="{{ $service?->certification_expiry?->format('Y-m-d') ?? old('certification_expiry') }}" />
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
        <div class="space-y-3">
            <div class="relative">
                <textarea name="work_done" id="work_done" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('work_done') }}</textarea>
                <label for="work_done" class="{{ $lbl }}">Work Done</label>
                @error('work_done')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="relative">
                    <textarea name="safety_notes" id="safety_notes" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('safety_notes') }}</textarea>
                    <label for="safety_notes" class="{{ $lbl }}">Safety Notes</label>
                    @error('safety_notes')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="text" name="remarks" id="remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
                    <label for="remarks" class="{{ $lbl }}">Remarks</label>
                    @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <p class="{{ $sec }}">Document</p>
        <input type="file" name="service_bill" accept=".pdf,.jpg,.jpeg,.png,.webp"
               class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                      file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                      hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
        <p class="mt-1 text-[11px] text-zinc-400">PDF, JPG, PNG, WEBP — max 5 MB</p>
        @if ($service?->documents->isNotEmpty())
            <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $service->documents->first()->file_original_name }}</span>
                <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
            </div>
        @endif
        @error('service_bill')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>{{-- end x-data wrapper --}}
