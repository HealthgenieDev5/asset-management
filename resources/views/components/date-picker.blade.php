@props([
    'name',
    'value'   => '',
    'label'   => '',
    'placeholder' => ' ',
    'minDate' => null,
    'maxDate' => null,
])
<div class="relative w-full">
    <input
        type="text"
        inputmode="none"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        data-datepicker
        @if($minDate) data-min-date="{{ $minDate }}" @endif
        @if($maxDate) data-max-date="{{ $maxDate }}" @endif
        class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 pr-9 text-sm text-zinc-900 shadow-sm transition
               placeholder:text-transparent
               focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100
               dark:focus:border-accent"
        {{ $attributes->except(['name', 'value', 'placeholder', 'label', 'min-date', 'max-date']) }}
    />
    @if($label)
    <label for="{{ $name }}"
        class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all
               peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400
               peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent
               dark:text-zinc-400 dark:peer-focus:text-accent">
        {{ $label }}
    </label>
    @endif
    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
        </svg>
    </span>
</div>
