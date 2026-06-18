@php
$isEdit  = isset($log) && $log !== null;
$v       = fn($f, $default = '') => old($f, $isEdit ? ($log?->$f ?? $default) : $default);
$uid     = $isEdit ? $log->id : 'new';
$inp     = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl     = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400';
$err     = 'mt-0.5 text-[11px] text-red-400';
$cal     = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
@endphp

<div class="space-y-4">
    {{-- Unit --}}
    <div class="relative">
        <input type="text" name="unit" id="unit_{{ $uid }}"
               value="{{ $v('unit') }}" placeholder=" "
               list="meter-unit-suggestions-{{ $uid }}"
               class="{{ $inp }}" autocomplete="off" />
        <label for="unit_{{ $uid }}" class="{{ $lbl }}">
            Unit <span class="text-red-400">*</span>
            <span class="font-normal text-zinc-400">(e.g. km, hours, prints)</span>
        </label>
        <datalist id="meter-unit-suggestions-{{ $uid }}">
            <option value="km">
            <option value="hours">
            <option value="prints">
            <option value="cycles">
            <option value="litres">
        </datalist>
        @error('unit') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Reading Value --}}
    <div class="relative">
        <input type="number" name="reading_value" id="reading_value_{{ $uid }}"
               value="{{ $v('reading_value') }}" placeholder=" " min="0"
               class="{{ $inp }}" />
        <label for="reading_value_{{ $uid }}" class="{{ $lbl }}">
            Current Reading <span class="text-red-400">*</span>
        </label>
        @error('reading_value') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Logged At --}}
    <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true, defaultDate: '{{ $v('logged_at', now()->format('Y-m-d')) }}' })">
        <div class="relative w-full">
            <input type="text" inputmode="none" name="logged_at" id="logged_at_{{ $uid }}"
                   value="{{ $v('logged_at', now()->format('Y-m-d')) }}"
                   placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
            <label for="logged_at_{{ $uid }}" class="{{ $lbl }}">
                Date Logged <span class="text-red-400">*</span>
            </label>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
        </div>
        @error('logged_at') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Notes --}}
    <div class="relative">
        <textarea name="notes" id="notes_{{ $uid }}" rows="2" placeholder=" "
                  class="{{ $inp }}">{{ $v('notes') }}</textarea>
        <label for="notes_{{ $uid }}" class="{{ $lbl }}">Notes</label>
        @error('notes') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>
</div>
