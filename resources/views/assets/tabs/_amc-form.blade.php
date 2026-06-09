@php $v = fn($f) => old($f, $amc?->{$f}); @endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <flux:field>
        <flux:label>Contract Number</flux:label>
        <flux:input name="contract_number" value="{{ $v('contract_number') }}" placeholder="e.g. AMC-2024-001" />
        @error('contract_number') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Vendor / Provider</flux:label>
        <flux:input name="vendor_name" value="{{ $v('vendor_name') }}" placeholder="Vendor company name" />
        @error('vendor_name') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Coverage Type</flux:label>
        <flux:select name="coverage_type">
            @foreach (['comprehensive' => 'Comprehensive', 'non_comprehensive' => 'Non-Comprehensive', 'parts_only' => 'Parts Only', 'labour_only' => 'Labour Only'] as $val => $label)
                <flux:select.option value="{{ $val }}" :selected="$v('coverage_type') === $val">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @error('coverage_type') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>AMC From</flux:label>
        <flux:input type="date" name="amc_date_from" value="{{ $amc?->amc_date_from?->format('Y-m-d') ?? old('amc_date_from') }}" />
        @error('amc_date_from') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>AMC Lapse Date</flux:label>
        <flux:input type="date" name="amc_date_to" value="{{ $amc?->amc_date_to?->format('Y-m-d') ?? old('amc_date_to') }}" />
        @error('amc_date_to') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Reminder Before (days)</flux:label>
        <flux:input type="number" name="reminder_before_days" value="{{ $v('reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
        @error('reminder_before_days') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>AMC Amount (₹)</flux:label>
        <flux:input type="number" name="amc_amount" value="{{ $v('amc_amount') }}" min="0" step="0.01" placeholder="0.00" />
        @error('amc_amount') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Number</flux:label>
        <flux:input name="amc_bill_no" value="{{ $v('amc_bill_no') }}" placeholder="Invoice / bill no" />
        @error('amc_bill_no') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Date</flux:label>
        <flux:input type="date" name="amc_bill_date" value="{{ $amc?->amc_bill_date?->format('Y-m-d') ?? old('amc_bill_date') }}" />
        @error('amc_bill_date') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Contact Person</flux:label>
        <flux:input name="vendor_contact_person" value="{{ $v('vendor_contact_person') }}" placeholder="Name" />
        @error('vendor_contact_person') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Vendor Phone</flux:label>
        <flux:input name="vendor_phone" value="{{ $v('vendor_phone') }}" placeholder="+91 …" />
        @error('vendor_phone') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Vendor Email</flux:label>
        <flux:input type="email" name="vendor_email" value="{{ $v('vendor_email') }}" placeholder="vendor@example.com" />
        @error('vendor_email') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

<flux:field>
    <flux:label>Coverage Details</flux:label>
    <flux:textarea name="coverage_details" rows="2" placeholder="What is covered under this contract">{{ $v('coverage_details') }}</flux:textarea>
    @error('coverage_details') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>AMC Terms</flux:label>
    <flux:textarea name="amc_terms" rows="2" placeholder="Terms and conditions">{{ $v('amc_terms') }}</flux:textarea>
    @error('amc_terms') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Remarks</flux:label>
    <flux:input name="remarks" value="{{ $v('remarks') }}" placeholder="Optional notes" />
    @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>AMC Bill / Document</flux:label>
    <input type="file" name="amc_bill_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
           class="block w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-zinc-200
                  file:mr-3 file:rounded-md file:border-0 file:bg-zinc-700 file:px-3 file:py-1
                  file:text-xs file:font-medium file:text-zinc-200 hover:file:bg-zinc-600 focus:outline-none focus:ring-1 focus:ring-accent" />
    <flux:description>PDF, JPG, PNG, WEBP — max 5 MB</flux:description>
    @if ($amc?->documents->isNotEmpty())
        <p class="text-xs text-zinc-500">Already uploaded: {{ $amc->documents->first()->file_original_name }} — upload a new file to replace.</p>
    @endif
    @error('amc_bill_image') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>
