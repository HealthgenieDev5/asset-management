<x-layouts::app :title="__('Add Asset')">
    @include('partials.flash')

    <div class="mb-6 flex items-center gap-3">
        <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" size="sm" icon="arrow-left" />
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Add <span class="text-accent">Asset</span>
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-400">Register a new asset in the system.</flux:text>
        </div>
    </div>

    <form method="POST" action="{{ route('assets.store') }}" id="asset-form" enctype="multipart/form-data">
        @csrf
        @include('assets._form', ['asset' => null, 'categories' => $categories, 'subcategories' => collect()])
    </form>
</x-layouts::app>
