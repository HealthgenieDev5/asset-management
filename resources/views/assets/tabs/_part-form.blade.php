@php $v = fn($f) => old($f, $part?->{$f}); @endphp

<div x-data x-init="
    $nextTick(() => {
        flatpickr($el.querySelector('[name=\'warranty_till\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
">

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <flux:field class="sm:col-span-2 lg:col-span-1">
        <flux:label>Part Name</flux:label>
        <flux:input name="part_name" value="{{ $v('part_name') }}" placeholder="e.g. Oil Filter, Drive Belt" />
        @error('part_name') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Quantity</flux:label>
        <flux:input type="number" name="quantity" value="{{ $v('quantity') ?? 1 }}" min="1" />
        @error('quantity') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Part Cost (₹ per unit)</flux:label>
        <flux:input type="number" name="part_cost" value="{{ $v('part_cost') }}" min="0" step="0.01" placeholder="0.00" />
        @error('part_cost') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Purchased From</flux:label>
        <flux:input name="purchased_from" value="{{ $v('purchased_from') }}" placeholder="Vendor / supplier name" />
        @error('purchased_from') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Warranty Till</flux:label>
        <x-date-picker name="warranty_till" value="{{ $part?->warranty_till?->format('Y-m-d') ?? old('warranty_till') }}" />
        @error('warranty_till') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>

    <flux:field>
        <flux:label>Remarks</flux:label>
        <flux:input name="remarks" value="{{ $v('remarks') }}" placeholder="Optional notes" />
        @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
    </flux:field>
</div>

</div>{{-- end x-data wrapper --}}
