<x-layouts::app :title="__('Asset Subcategories')">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="font-extrabold">
                    Asset <span class="text-accent">Subcategories</span>
                </flux:heading>
                <flux:text class="mt-1 text-zinc-400">Manage asset subcategories grouped under categories.</flux:text>
            </div>
            <flux:button href="{{ route('asset-subcategories.create') }}" wire:navigate variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                <flux:icon.plus class="size-4" />
                Add Subcategory
            </flux:button>
        </div>

        @include('partials.flash')

        {{-- Filters --}}
        <form method="GET" class="mb-4 flex flex-wrap gap-3">
            <flux:input
                name="search"
                placeholder="Search name or code…"
                value="{{ request('search') }}"
                class="w-64"
            />
            <flux:select name="category_id" class="w-48" placeholder="All categories">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach ($categories as $cat)
                    <flux:select.option value="{{ $cat->id }}" :selected="request('category_id') == $cat->id">
                        {{ $cat->code }} — {{ $cat->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:select name="status" class="w-40" placeholder="All statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="active" :selected="request('status') === 'active'">Active</flux:select.option>
                <flux:select.option value="inactive" :selected="request('status') === 'inactive'">Inactive</flux:select.option>
            </flux:select>
            <flux:button type="submit" variant="ghost">Filter</flux:button>
            @if(request('search') || request('category_id') || request('status'))
                <flux:button href="{{ route('asset-subcategories.index') }}" wire:navigate variant="ghost">Clear</flux:button>
            @endif
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800 text-left text-zinc-400">
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Code</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Created</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @forelse ($subcategories as $sub)
                        <tr class="hover:bg-accent/5 transition-colors">
                            <td class="px-4 py-3 text-zinc-500">{{ $subcategories->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-zinc-800 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-accent">
                                    {{ $sub->category->code ?? '—' }}
                                </span>
                                <span class="ml-1.5 text-zinc-400">{{ $sub->category->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-100">{{ $sub->name }}</td>
                            <td class="px-4 py-3 text-zinc-400">{{ $sub->code ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($sub->status === 'active')
                                    <span class="inline-flex items-center rounded-full bg-green-900/40 px-2.5 py-0.5 text-xs font-medium text-green-400 ring-1 ring-green-700">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-800 px-2.5 py-0.5 text-xs font-medium text-zinc-400 ring-1 ring-zinc-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $sub->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:button href="{{ route('asset-subcategories.edit', $sub) }}" wire:navigate size="sm" variant="ghost">
                                        <flux:icon.pencil class="size-3.5" />
                                        Edit
                                    </flux:button>
                                    <form method="POST" action="{{ route('asset-subcategories.destroy', $sub) }}" onsubmit="return confirm('Delete this subcategory?')">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" size="sm" variant="ghost" class="text-red-400 hover:text-red-300">
                                            <flux:icon.trash class="size-3.5" />
                                            Delete
                                        </flux:button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                                No subcategories found.
                                <a href="{{ route('asset-subcategories.create') }}" class="ml-1 text-accent hover:underline">Add one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $subcategories->links() }}
        </div>
</x-layouts::app>
