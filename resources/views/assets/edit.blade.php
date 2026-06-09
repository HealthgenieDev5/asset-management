<x-layouts::app :title="'Edit — ' . $asset->asset_code">
    @include('partials.flash')

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('assets.show', $asset) }}" wire:navigate variant="ghost" size="sm" icon="arrow-left" />
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Edit <span class="text-accent">{{ $asset->asset_code }}</span>
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-400">{{ $asset->asset_name }}</flux:text>
        </div>
    </div>

    <form method="POST" action="{{ route('assets.update', $asset) }}" id="asset-form" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('assets._form', ['asset' => $asset, 'categories' => $categories, 'subcategories' => $subcategories])
    </form>
</x-layouts::app>
