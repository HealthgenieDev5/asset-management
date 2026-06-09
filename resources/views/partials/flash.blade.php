@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
        class="mb-4 flex items-center gap-3 rounded-lg border border-green-700 bg-green-950/40 px-4 py-3 text-sm text-green-300">
        <flux:icon.check-circle class="size-4 shrink-0" />
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center gap-3 rounded-lg border border-red-700 bg-red-950/40 px-4 py-3 text-sm text-red-300">
        <flux:icon.exclamation-circle class="size-4 shrink-0" />
        {{ session('error') }}
    </div>
@endif
