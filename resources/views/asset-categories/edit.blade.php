<x-layouts::app :title="__('Edit Category')">
        <div class="mb-6 flex items-center gap-3">
            <flux:button href="{{ route('asset-categories.index') }}" wire:navigate variant="ghost" size="sm">
                <flux:icon.arrow-left class="size-4" />
            </flux:button>
            <div>
                <flux:heading size="xl" class="font-extrabold">
                    Edit <span class="text-accent">Category</span>
                </flux:heading>
                <flux:text class="mt-0.5 text-zinc-400">Update category name, code, or status.</flux:text>
            </div>
        </div>

        <div class="max-w-lg">
            <form method="POST" action="{{ route('asset-categories.update', $category) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <flux:field>
                    <flux:label for="name">Category Name <span class="text-red-400">*</span></flux:label>
                    <flux:input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $category->name) }}"
                        placeholder="e.g. Vehicle, Air Conditioner"
                        required
                        autofocus
                    />
                    @error('name')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label for="code">Category Code <span class="text-red-400">*</span></flux:label>
                    <flux:input
                        id="code"
                        name="code"
                        type="text"
                        value="{{ old('code', $category->code) }}"
                        placeholder="e.g. VE, AC, IT"
                        maxlength="2"
                        class="uppercase w-28"
                        oninput="this.value = this.value.toUpperCase()"
                        required
                    />
                    <flux:description>2 uppercase letters, unique. Used to generate asset codes (e.g. <strong>{{ $category->code }}</strong>-1).</flux:description>
                    @error('code')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label for="status">Status <span class="text-red-400">*</span></flux:label>
                    <flux:select id="status" name="status">
                        <flux:select.option value="active" :selected="old('status', $category->status) === 'active'">Active</flux:select.option>
                        <flux:select.option value="inactive" :selected="old('status', $category->status) === 'inactive'">Inactive</flux:select.option>
                    </flux:select>
                    @error('status')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                        Update Category
                    </flux:button>
                    <flux:button href="{{ route('asset-categories.index') }}" wire:navigate variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
</x-layouts::app>
