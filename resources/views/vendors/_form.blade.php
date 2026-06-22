@php $vendor ??= null; @endphp

{{-- Basic Info --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Basic Information</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:field class="sm:col-span-2">
            <flux:label for="name">Vendor Name <span class="text-red-400">*</span></flux:label>
            <flux:input id="name" name="name" type="text" value="{{ old('name', $vendor?->name) }}"
                        placeholder="e.g. Acme Tech Services" required autofocus />
            @error('name') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>

        <flux:field>
            <flux:label for="code">Vendor Code</flux:label>
            <flux:input id="code" name="code" type="text" value="{{ old('code', $vendor?->code) }}"
                        placeholder="Auto-generated (e.g. VEN-001)" class="font-mono uppercase" />
            <flux:description>Leave blank to auto-generate.</flux:description>
            @error('code') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>

        <flux:field>
            <flux:label for="status">Status <span class="text-red-400">*</span></flux:label>
            <flux:select id="status" name="status">
                <flux:select.option value="active"   :selected="old('status', $vendor?->status ?? 'active') === 'active'">Active</flux:select.option>
                <flux:select.option value="inactive" :selected="old('status', $vendor?->status) === 'inactive'">Inactive</flux:select.option>
            </flux:select>
            @error('status') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
    </div>
</div>

{{-- Contact --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Contact Details</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:field>
            <flux:label for="contact_person">Contact Person</flux:label>
            <flux:input id="contact_person" name="contact_person" type="text"
                        value="{{ old('contact_person', $vendor?->contact_person) }}"
                        placeholder="Full name" />
            @error('contact_person') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>

        <flux:field>
            <flux:label for="phone">Phone</flux:label>
            <flux:input id="phone" name="phone" type="text"
                        value="{{ old('phone', $vendor?->phone) }}"
                        placeholder="+91 98765 43210" />
            @error('phone') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>

        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" name="email" type="email"
                        value="{{ old('email', $vendor?->email) }}"
                        placeholder="vendor@example.com" />
            @error('email') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
    </div>

    <div class="mt-4">
        <flux:field>
            <flux:label for="address">Address</flux:label>
            <flux:textarea id="address" name="address" rows="2"
                           placeholder="Street, city, state…">{{ old('address', $vendor?->address) }}</flux:textarea>
            @error('address') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
    </div>
</div>

{{-- Service Scope --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Service Scope</h3>
    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">What types of services does this vendor provide?</p>
    @php
        $selectedTypes = old('service_types', $vendor?->service_types ?? []);
    @endphp
    <div class="flex flex-wrap gap-4">
        @foreach (['warranty' => 'Warranty', 'amc' => 'AMC', 'service' => 'Service', 'all' => 'All Types'] as $value => $label)
            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="service_types[]" value="{{ $value }}"
                       @checked(in_array($value, $selectedTypes))
                       class="size-4 rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('service_types') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
    @error('service_types.*') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
</div>

{{-- SLA --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">SLA Terms</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:field>
            <flux:label for="sla_response_hours">Response Time (hours)</flux:label>
            <flux:input id="sla_response_hours" name="sla_response_hours" type="number" min="0" max="9999"
                        value="{{ old('sla_response_hours', $vendor?->sla_response_hours) }}"
                        placeholder="e.g. 4" />
            <flux:description>Maximum hours until first response.</flux:description>
            @error('sla_response_hours') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>

        <flux:field>
            <flux:label for="sla_resolution_days">Resolution Time (days)</flux:label>
            <flux:input id="sla_resolution_days" name="sla_resolution_days" type="number" min="0" max="9999"
                        value="{{ old('sla_resolution_days', $vendor?->sla_resolution_days) }}"
                        placeholder="e.g. 2" />
            <flux:description>Maximum days until issue is resolved.</flux:description>
            @error('sla_resolution_days') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
    </div>
</div>

{{-- Notes --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Notes</h3>
    <flux:field>
        <flux:textarea id="notes" name="notes" rows="3"
                       placeholder="Any additional notes about this vendor…">{{ old('notes', $vendor?->notes) }}</flux:textarea>
        @error('notes') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>
