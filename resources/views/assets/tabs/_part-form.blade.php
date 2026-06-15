@php
$v = fn($f) => old($f, $part?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

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
    <div class="col-span-1 sm:col-span-2"
         x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
        <div class="relative w-full">
            <input type="text" inputmode="none" name="warranty_till" id="warranty_till" value="{{ $part?->warranty_till?->format('Y-m-d') ?? old('warranty_till') }}" placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
            <label for="warranty_till" class="{{ $lbl }}">Warranty Till</label>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg></span>
        </div>
        @error('warranty_till')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>
    {{-- Remarks --}}
    <div class="relative col-span-2 sm:col-span-4">
        <input type="text" name="remarks" id="remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
        <label for="remarks" class="{{ $lbl }}">Remarks</label>
        @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>
</div>
