@php
$v = fn($f) => old($f, $part?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div x-data="{
    initPickers() {
        const dialog = this.$el.closest('dialog');
        const el = this.$el.querySelector('[name=\'warranty_till\']');
        if (!el) return;
        if (el._flatpickr) el._flatpickr.destroy();
        flatpickr(el, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true, appendTo: dialog || document.body, static: !!dialog });
    },
    init() {
        document.addEventListener('modal-show', () => this.$nextTick(() => this.initPickers()));
        this.$nextTick(() => this.initPickers());
    }
}">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
        {{-- Part Name --}}
        <div class="relative col-span-2 sm:col-span-2">
            <input type="text" name="part_name" id="part_name" value="{{ $v('part_name') }}" placeholder=" " class="{{ $inp }}" />
            <label for="part_name" class="{{ $lbl }}">Part Name</label>
            @error('part_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        {{-- Purchased From --}}
        <div class="relative col-span-2 sm:col-span-2">
            <input type="text" name="purchased_from" id="purchased_from" value="{{ $v('purchased_from') }}" placeholder=" " class="{{ $inp }}" />
            <label for="purchased_from" class="{{ $lbl }}">Purchased From</label>
            @error('purchased_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        {{-- Quantity --}}
        <div class="relative col-span-1 sm:col-span-1">
            <input type="number" name="quantity" id="quantity" value="{{ $v('quantity') ?? 1 }}" placeholder=" " min="1" class="{{ $inp }}" />
            <label for="quantity" class="{{ $lbl }}">Qty</label>
            @error('quantity')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        {{-- Part Cost --}}
        <div class="relative col-span-1 sm:col-span-1">
            <input type="number" name="part_cost" id="part_cost" value="{{ $v('part_cost') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
            <label for="part_cost" class="{{ $lbl }}">Cost (₹/unit)</label>
            @error('part_cost')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        {{-- Warranty Till --}}
        <div class="col-span-1 sm:col-span-2">
            <x-date-picker name="warranty_till" label="Warranty Till" value="{{ $part?->warranty_till?->format('Y-m-d') ?? old('warranty_till') }}" />
            @error('warranty_till')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        {{-- Remarks --}}
        <div class="relative col-span-2 sm:col-span-4">
            <input type="text" name="remarks" id="remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
            <label for="remarks" class="{{ $lbl }}">Remarks</label>
            @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</div>{{-- end x-data wrapper --}}
