<x-layouts::app :title="'Edit Vendor — ' . $vendor->name">
    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('vendors.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.arrow-left class="size-4" />
        </flux:button>
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Edit <span class="text-accent">Vendor</span>
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-400">{{ $vendor->code }} · {{ $vendor->name }}</flux:text>
        </div>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('vendors._form', ['vendor' => $vendor])
            <div class="flex items-center gap-3 pt-2">
                <flux:button type="submit" variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                    Update Vendor
                </flux:button>
                <flux:button href="{{ route('vendors.index') }}" wire:navigate variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
