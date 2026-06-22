@php
    $vendor      ??= null;
    $fieldPrefix ??= '';

    $inputCls    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $selectCls   = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $labelCls    = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent dark:text-zinc-400 dark:peer-focus:text-accent';
    $labelSelCls = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
    $textareaCls = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';

    $selectedType = old('type', $vendor?->type ?? 'company');
@endphp

{{-- Basic Information --}}
<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Basic Information</flux:heading>

    <div class="grid gap-4 sm:grid-cols-2">

        {{-- Vendor Name --}}
        <div class="sm:col-span-2">
            <div class="relative">
                <input type="text" name="name" id="{{ $fieldPrefix }}name"
                    value="{{ old('name', $vendor?->name) }}" placeholder=" " required
                    class="{{ $inputCls }}" />
                <label for="{{ $fieldPrefix }}name" class="{{ $labelCls }}">Vendor Name <span class="text-red-400">*</span></label>
            </div>
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Type pill radios --}}
        <div>
            <p class="mb-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Type <span class="text-red-400">*</span></p>
            <div class="flex gap-2" x-data="{ type: '{{ $selectedType }}' }">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="company" class="sr-only peer"
                           x-model="type" {{ $selectedType === 'company' ? 'checked' : '' }}>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition
                        border-zinc-300 bg-zinc-50 text-zinc-600
                        peer-checked:border-accent peer-checked:bg-accent peer-checked:text-white
                        dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300
                        dark:peer-checked:border-accent dark:peer-checked:bg-accent dark:peer-checked:text-white">
                        <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                        Company
                    </span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="individual" class="sr-only peer"
                           x-model="type" {{ $selectedType === 'individual' ? 'checked' : '' }}>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition
                        border-zinc-300 bg-zinc-50 text-zinc-600
                        peer-checked:border-accent peer-checked:bg-accent peer-checked:text-white
                        dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300
                        dark:peer-checked:border-accent dark:peer-checked:bg-accent dark:peer-checked:text-white">
                        <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Individual
                    </span>
                </label>
            </div>
            @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Status --}}
        <div class="relative">
            <select name="status" id="{{ $fieldPrefix }}status" class="{{ $selectCls }}">
                <option value="active"   @selected(old('status', $vendor?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $vendor?->status) === 'inactive')>Inactive</option>
            </select>
            <label for="{{ $fieldPrefix }}status" class="{{ $labelSelCls }}">Status <span class="text-red-400">*</span></label>
            @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

    </div>
</div>

{{-- Contact Details --}}
<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Contact Details</flux:heading>

    <div class="grid gap-4 sm:grid-cols-2">

        {{-- Phone --}}
        <div class="relative">
            <input type="text" name="phone" id="{{ $fieldPrefix }}phone"
                value="{{ old('phone', $vendor?->phone) }}" placeholder=" "
                class="{{ $inputCls }}" />
            <label for="{{ $fieldPrefix }}phone" class="{{ $labelCls }}">Phone</label>
            @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Alt Phone --}}
        <div class="relative">
            <input type="text" name="alt_phone" id="{{ $fieldPrefix }}alt_phone"
                value="{{ old('alt_phone', $vendor?->alt_phone) }}" placeholder=" "
                class="{{ $inputCls }}" />
            <label for="{{ $fieldPrefix }}alt_phone" class="{{ $labelCls }}">Alternate Phone</label>
            @error('alt_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div class="relative">
            <input type="email" name="email" id="{{ $fieldPrefix }}email"
                value="{{ old('email', $vendor?->email) }}" placeholder=" "
                class="{{ $inputCls }}" />
            <label for="{{ $fieldPrefix }}email" class="{{ $labelCls }}">Email</label>
            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Alt Email --}}
        <div class="relative">
            <input type="email" name="alt_email" id="{{ $fieldPrefix }}alt_email"
                value="{{ old('alt_email', $vendor?->alt_email) }}" placeholder=" "
                class="{{ $inputCls }}" />
            <label for="{{ $fieldPrefix }}alt_email" class="{{ $labelCls }}">Alternate Email</label>
            @error('alt_email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Address --}}
        <div class="relative sm:col-span-2">
            <textarea name="address" id="{{ $fieldPrefix }}address" rows="2"
                placeholder=" "
                class="{{ $textareaCls }}">{{ old('address', $vendor?->address) }}</textarea>
            <label for="{{ $fieldPrefix }}address" class="{{ $labelCls }}">Address</label>
            @error('address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

    </div>
</div>
