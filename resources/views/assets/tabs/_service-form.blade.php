@php $v = fn($f) => old($f, $service?->{$f}); @endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <flux:field>
        <flux:label>Service Type</flux:label>
        <flux:select name="service_type">
            @foreach ([
                'preventive_maintenance' => 'Preventive Maintenance',
                'corrective_maintenance' => 'Corrective Maintenance',
                'inspection'             => 'Inspection',
                'repair'                 => 'Repair',
                'calibration'            => 'Calibration',
                'cleaning'               => 'Cleaning',
                'other'                  => 'Other',
            ] as $val => $label)
                <flux:select.option value="{{ $val }}" :selected="$v('service_type') === $val">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @error('service_type') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Service Date</flux:label>
        <flux:input type="date" name="service_date"
                    value="{{ $service?->service_date?->format('Y-m-d') ?? old('service_date') }}" />
        @error('service_date') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Next Service Date</flux:label>
        <flux:input type="date" name="next_service_date"
                    value="{{ $service?->next_service_date?->format('Y-m-d') ?? old('next_service_date') }}" />
        @error('next_service_date') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Service Agency / Company</flux:label>
        <flux:input name="service_agency" value="{{ $v('service_agency') }}" placeholder="Vendor or workshop name" />
        @error('service_agency') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Technician Name</flux:label>
        <flux:input name="technician_name" value="{{ $v('technician_name') }}" placeholder="Engineer / technician" />
        @error('technician_name') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Condition After Service</flux:label>
        <flux:select name="condition_rating">
            <flux:select.option value="">— Select —</flux:select.option>
            @foreach (['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'critical' => 'Critical'] as $val => $label)
                <flux:select.option value="{{ $val }}" :selected="$v('condition_rating') === $val">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @error('condition_rating') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Service Cost (₹)</flux:label>
        <flux:input type="number" name="service_cost" value="{{ $v('service_cost') }}" min="0" step="0.01" placeholder="0.00" />
        @error('service_cost') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Number</flux:label>
        <flux:input name="bill_no" value="{{ $v('bill_no') }}" placeholder="Invoice / bill no" />
        @error('bill_no') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Date</flux:label>
        <flux:input type="date" name="bill_date"
                    value="{{ $service?->bill_date?->format('Y-m-d') ?? old('bill_date') }}" />
        @error('bill_date') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Meter / Operating Hours</flux:label>
        <flux:input type="number" name="meter_reading" value="{{ $v('meter_reading') }}" min="0" placeholder="e.g. 12500" />
        @error('meter_reading') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Mileage / Odometer (km)</flux:label>
        <flux:input type="number" name="mileage_reading" value="{{ $v('mileage_reading') }}" min="0" placeholder="km at service" />
        @error('mileage_reading') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Downtime (hours)</flux:label>
        <flux:input type="number" name="downtime_hours" value="{{ $v('downtime_hours') }}" min="0" step="0.5" placeholder="0" />
        @error('downtime_hours') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

{{-- Service Interval --}}
<div class="grid gap-4 sm:grid-cols-3">
    <flux:field class="sm:col-span-1">
        <flux:label>Service Interval</flux:label>
        <flux:input type="number" name="service_interval_value" value="{{ $v('service_interval_value') }}" min="1" placeholder="e.g. 3" />
        @error('service_interval_value') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
    <flux:field class="sm:col-span-1">
        <flux:label>&nbsp;</flux:label>
        <flux:select name="service_interval_unit">
            <flux:select.option value="">— Unit —</flux:select.option>
            @foreach (['days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months', 'years' => 'Years', 'operating_hours' => 'Operating Hours', 'kilometers' => 'Kilometers'] as $val => $label)
                <flux:select.option value="{{ $val }}" :selected="$v('service_interval_unit') === $val">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @error('service_interval_unit') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
    <flux:field class="sm:col-span-1">
        <flux:label>Next Service Reminder (days before)</flux:label>
        <flux:input type="number" name="next_service_reminder_before_days" value="{{ $v('next_service_reminder_before_days') }}" min="1" max="365" placeholder="e.g. 14" />
        @error('next_service_reminder_before_days') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

{{-- Inspection / Certification (shown for all types — relevant for inspection type) --}}
<div class="grid gap-4 sm:grid-cols-2">
    <flux:field>
        <flux:label>Certification Expiry</flux:label>
        <flux:input type="date" name="certification_expiry"
                    value="{{ $service?->certification_expiry?->format('Y-m-d') ?? old('certification_expiry') }}" />
        <flux:description>For inspection records — leave blank if not applicable.</flux:description>
        @error('certification_expiry') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
    <flux:field>
        <flux:label>Certification Reminder (days before)</flux:label>
        <flux:input type="number" name="certification_reminder_before_days"
                    value="{{ $v('certification_reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
        @error('certification_reminder_before_days') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

<flux:field>
    <flux:label>Work Done</flux:label>
    <flux:textarea name="work_done" rows="3" placeholder="Describe the work performed, parts checked, observations…">{{ $v('work_done') }}</flux:textarea>
    @error('work_done') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Safety Notes</flux:label>
    <flux:textarea name="safety_notes" rows="2" placeholder="Safety observations, hazards noted, compliance status…">{{ $v('safety_notes') }}</flux:textarea>
    @error('safety_notes') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Remarks</flux:label>
    <flux:input name="remarks" value="{{ $v('remarks') }}" placeholder="Additional notes" />
    @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Service Bill / Document</flux:label>
    <input type="file" name="service_bill" accept=".pdf,.jpg,.jpeg,.png,.webp"
           class="block w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-zinc-200
                  file:mr-3 file:rounded-md file:border-0 file:bg-zinc-700 file:px-3 file:py-1
                  file:text-xs file:font-medium file:text-zinc-200 hover:file:bg-zinc-600 focus:outline-none focus:ring-1 focus:ring-accent" />
    <flux:description>PDF, JPG, PNG, WEBP — max 5 MB</flux:description>
    @if ($service?->documents->isNotEmpty())
        <p class="text-xs text-zinc-500">Already uploaded: {{ $service->documents->first()->file_original_name }} — upload a new file to replace.</p>
    @endif
    @error('service_bill') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>
