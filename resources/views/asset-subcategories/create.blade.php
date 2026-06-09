<x-layouts::app :title="__('Add Subcategory')">
        <div class="mb-6 flex items-center gap-3">
            <flux:button href="{{ route('asset-subcategories.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div>
                <flux:heading size="xl" class="font-extrabold">
                    Add <span class="text-accent">Subcategory</span>
                </flux:heading>
                <flux:text class="mt-0.5 text-zinc-400">Create a subcategory under an existing category.</flux:text>
            </div>
        </div>

        <div class="max-w-lg">
            <form method="POST" action="{{ route('asset-subcategories.store') }}" class="space-y-5">
                @csrf

                <flux:field>
                    <flux:label for="asset_category_id">Category <span class="text-red-400">*</span></flux:label>
                    <flux:select id="asset_category_id" name="asset_category_id" required>
                        <flux:select.option value="">— Select Category —</flux:select.option>
                        @foreach ($categories as $cat)
                            <flux:select.option value="{{ $cat->id }}" :selected="old('asset_category_id') == $cat->id">
                                {{ $cat->code }} — {{ $cat->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('asset_category_id')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label for="name">Subcategory Name <span class="text-red-400">*</span></flux:label>
                    <flux:input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="e.g. Car, Laptop, Split AC"
                        required
                        autofocus
                    />
                    @error('name')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label for="code">Code <span class="text-zinc-500 font-normal">(optional)</span></flux:label>
                    <flux:input
                        id="code"
                        name="code"
                        type="text"
                        value="{{ old('code') }}"
                        placeholder="Optional short code"
                        class="w-40"
                    />
                    @error('code')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label for="status">Status <span class="text-red-400">*</span></flux:label>
                    <flux:select id="status" name="status">
                        <flux:select.option value="active" :selected="old('status', 'active') === 'active'">Active</flux:select.option>
                        <flux:select.option value="inactive" :selected="old('status') === 'inactive'">Inactive</flux:select.option>
                    </flux:select>
                    @error('status')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                        Save Subcategory
                    </flux:button>
                    <flux:button href="{{ route('asset-subcategories.index') }}" wire:navigate variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
</x-layouts::app>
