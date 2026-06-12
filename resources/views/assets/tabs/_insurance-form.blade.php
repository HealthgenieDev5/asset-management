@php $v = fn($f) => old($f, $policy?->{$f}); @endphp

<div x-data x-init="
    $nextTick(() => {
        flatpickr($el.querySelector('[name=\'policy_date_from\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'policy_date_to\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
        flatpickr($el.querySelector('[name=\'bill_date\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
">

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <flux:field>
        <flux:label>Policy Number</flux:label>
        <flux:input name="policy_number" value="{{ $v('policy_number') }}" placeholder="e.g. POL-2024-001" />
        @error('policy_number') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Insurer Name</flux:label>
        <flux:input name="insurer_name" value="{{ $v('insurer_name') }}" placeholder="Insurance company name" />
        @error('insurer_name') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Policy Type</flux:label>
        <flux:input name="policy_type" value="{{ $v('policy_type') }}" placeholder="e.g. Comprehensive, Fire & Theft" />
        @error('policy_type') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Policy From</flux:label>
        <x-date-picker name="policy_date_from" value="{{ $policy?->policy_date_from?->format('Y-m-d') ?? old('policy_date_from') }}" />
        @error('policy_date_from') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Policy Expiry Date</flux:label>
        <x-date-picker name="policy_date_to" value="{{ $policy?->policy_date_to?->format('Y-m-d') ?? old('policy_date_to') }}" />
        @error('policy_date_to') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Reminder Before (days)</flux:label>
        <flux:input type="number" name="reminder_before_days" value="{{ $v('reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
        @error('reminder_before_days') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Premium Amount (₹)</flux:label>
        <flux:input type="number" name="premium_amount" value="{{ $v('premium_amount') }}" min="0" step="0.01" placeholder="0.00" />
        @error('premium_amount') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Sum Insured (₹)</flux:label>
        <flux:input type="number" name="sum_insured" value="{{ $v('sum_insured') }}" min="0" step="0.01" placeholder="0.00" />
        @error('sum_insured') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Number</flux:label>
        <flux:input name="bill_no" value="{{ $v('bill_no') }}" placeholder="Invoice / bill no" />
        @error('bill_no') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Bill Date</flux:label>
        <x-date-picker name="bill_date" value="{{ $policy?->bill_date?->format('Y-m-d') ?? old('bill_date') }}" />
        @error('bill_date') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Contact Person</flux:label>
        <flux:input name="insurer_contact_person" value="{{ $v('insurer_contact_person') }}" placeholder="Name" />
        @error('insurer_contact_person') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Insurer Phone</flux:label>
        <flux:input name="insurer_phone" value="{{ $v('insurer_phone') }}" placeholder="+91 …" />
        @error('insurer_phone') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field class="sm:col-span-2 lg:col-span-3">
        <flux:label>Insurer Email</flux:label>
        <flux:input type="email" name="insurer_email" value="{{ $v('insurer_email') }}" placeholder="insurer@example.com" class="max-w-sm" />
        @error('insurer_email') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

<flux:field>
    <flux:label>Coverage Details</flux:label>
    <flux:textarea name="coverage_details" rows="2" placeholder="What risks are covered">{{ $v('coverage_details') }}</flux:textarea>
    @error('coverage_details') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Remarks</flux:label>
    <flux:input name="remarks" value="{{ $v('remarks') }}" placeholder="Optional notes" />
    @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

<flux:field>
    <flux:label>Policy Document</flux:label>
    <input type="file" name="insurance_document" accept=".pdf,.jpg,.jpeg,.png,.webp"
           class="block w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-zinc-200
                  file:mr-3 file:rounded-md file:border-0 file:bg-zinc-700 file:px-3 file:py-1
                  file:text-xs file:font-medium file:text-zinc-200 hover:file:bg-zinc-600 focus:outline-none focus:ring-1 focus:ring-accent" />
    <flux:description>PDF, JPG, PNG, WEBP — max 5 MB</flux:description>
    @if ($policy?->documents->isNotEmpty())
        <p class="text-xs text-zinc-500">Already uploaded: {{ $policy->documents->first()->file_original_name }} — upload a new file to replace.</p>
    @endif
    @error('insurance_document') <flux:error>{{ $message }}</flux:error> @enderror
</flux:field>

</div>{{-- end x-data wrapper --}}
