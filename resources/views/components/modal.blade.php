@props([
    'name',
    'title'       => '',
    'maxWidth'    => '64rem',
    'dismissible' => true,
    'autoOpen'    => false,
])

<div
    x-data="{ open: {{ $autoOpen ? 'true' : 'false' }} }"
    x-init="if (open) document.body.classList.add('overflow-hidden')"
    x-on:open-modal-{{ $name }}.window="open = true; document.body.classList.add('overflow-hidden')"
    x-on:close-modal-{{ $name }}.window="open = false; document.body.classList.remove('overflow-hidden')"
    x-on:keydown.escape.window="if (open) { open = false; document.body.classList.remove('overflow-hidden') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/75"
        @if($dismissible) x-on:click="open = false; document.body.classList.remove('overflow-hidden')" @endif
    ></div>

    {{-- Panel --}}
    <div
        class="relative z-10 w-full rounded-xl bg-white shadow-xl ring ring-black/5 dark:bg-zinc-800 dark:ring-zinc-700"
        style="max-width: {{ $maxWidth }}"
        x-on:click.stop
    >
        {{-- Header --}}
        @if($title)
        <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
            <button type="button" x-on:click="open = false; document.body.classList.remove('overflow-hidden')"
                class="rounded-md p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </div>
        @endif

        {{-- Scrollable body --}}
        <div class="max-h-[80vh] overflow-y-auto px-6 py-5"
             x-on:scroll="$el.querySelectorAll('input').forEach(el => { if (el._flatpickr?.isOpen) el._flatpickr._positionCalendar() })">
            {{ $slot }}
        </div>
    </div>
</div>
