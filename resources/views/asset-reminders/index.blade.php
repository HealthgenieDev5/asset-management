<x-layouts::app :title="__('Expiry Tracker')">
    @include('partials.flash')

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Expiry <span class="text-accent">Tracker</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Track coverage expiry dates across all assets — warranty, AMC, insurance, schedules and more.
            </flux:text>
        </div>
    </div>

    {{-- Filters --}}
    @php
        $activeFilters = array_filter(['type' => $type, 'search' => $search, 'asset_category_id' => $categoryId, 'asset_subcategory_id' => $subcatId]);
        $hasFilters    = count($activeFilters) > 0;
        $filterCount   = count($activeFilters);

        $subcatMap = $subcategories->groupBy('asset_category_id')
            ->map(fn($g) => $g->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values())
            ->toJson();
    @endphp

    <form method="GET" class="mb-5"
          x-data="{ categoryId: '{{ $categoryId }}', subcatId: '{{ $subcatId }}', subcatMap: {{ $subcatMap }} }"
          x-init="$watch('categoryId', () => { subcatId = '' })">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 8h10M11 12h2M9 16h6" />
                        </svg>
                        Filters
                    </div>
                    @if ($hasFilters)
                        <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-xs font-semibold text-accent">
                            {{ $filterCount }} active
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($hasFilters)
                        <a href="{{ route('asset-reminders.index', ['filter' => $filter]) }}"
                           class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                            Clear all
                        </a>
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-accent/90 active:scale-95">
                        Apply
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4">
                {{-- Category --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Category</label>
                    <select name="asset_category_id" x-model="categoryId"
                            x-on:change="$el.closest('form').submit()"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Subcategory (dependent) --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Subcategory</label>
                    <select name="asset_subcategory_id" x-model="subcatId"
                            x-on:change="$el.closest('form').submit()"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">All Subcategories</option>
                        <template x-if="categoryId && subcatMap[categoryId]">
                            <template x-for="s in subcatMap[categoryId]" :key="s.id">
                                <option :value="s.id" :selected="s.id == subcatId" x-text="s.name"></option>
                            </template>
                        </template>
                        <template x-if="!categoryId">
                            @foreach ($subcategories as $sub)
                                <option value="{{ $sub->id }}" @selected($subcatId == $sub->id)>{{ $sub->name }}</option>
                            @endforeach
                        </template>
                    </select>
                </div>
                {{-- Search --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Asset Name / Code</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search assets…"
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                </div>
                {{-- Reminder Type --}}
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Reminder Type</label>
                    <select name="type"
                            x-on:change="$el.closest('form').submit()"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">All Types</option>
                        @foreach ($typeOptions as $slug => $label)
                            <option value="{{ $slug }}" @selected($type === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    {{-- Tab Pills --}}
    @php
        $tabs = [
            'upcoming' => 'Upcoming',
            'expired'  => 'Expired',
            'all'      => 'All',
        ];
    @endphp
    <div class="mb-5 flex gap-2 border-b border-zinc-200 pb-0 dark:border-zinc-800">
        @foreach ($tabs as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['filter' => $key]) }}"
               class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                      {{ $filter === $key
                          ? 'border-accent text-accent'
                          : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ $label }}
                @if (isset($counts[$key]))
                    <span class="ml-1.5 rounded-full bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">{{ $counts[$key] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Content --}}
    @if ($items->isEmpty())
        <div class="flex items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-20 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:icon.bell-alert class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-500 dark:text-zinc-400">No Expiries Found</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-600">
                    @if ($filter === 'upcoming')
                        No upcoming expiry dates found.
                    @elseif ($filter === 'expired')
                        No expired items found.
                    @else
                        No expiry dates recorded yet. Add warranty, AMC, insurance, or schedule dates to assets.
                    @endif
                </flux:text>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    @php
                        $thCls  = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide';
                        $sortUrl = fn($col) => request()->fullUrlWithQuery([
                            'sort'      => $col,
                            'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
                        ]);
                        $sortIcon = fn($col) => $sort === $col
                            ? ($direction === 'asc' ? '↑' : '↓')
                            : '<span class="opacity-30">↕</span>';
                    @endphp
                    <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('asset') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Asset {!! $sortIcon('asset') !!}</a></th>
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('category') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Category {!! $sortIcon('category') !!}</a></th>
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('name') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Name / Detail {!! $sortIcon('name') !!}</a></th>
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('expiry') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Expiry Date {!! $sortIcon('expiry') !!}</a></th>
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('days_left') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Days Left {!! $sortIcon('days_left') !!}</a></th>
                        <th class="{{ $thCls }}"><a href="{{ $sortUrl('status') }}" class="flex items-center gap-1 text-zinc-500 hover:text-accent transition-colors">Status {!! $sortIcon('status') !!}</a></th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    @foreach ($items as $item)
                        @php
                            $daysLeft = $item['days_left'];
                            $status   = $item['status'];

                            [$statusLabel, $statusClass, $daysLabel, $daysClass] = match ($status) {
                                'expired' => ['Expired',       'bg-red-400/10 text-red-400',    abs($daysLeft) . 'd ago', 'text-red-400 font-semibold'],
                                'soon'    => ['Expiring Soon', 'bg-yellow-400/10 text-yellow-400', $daysLeft . 'd left',  'text-yellow-400 font-semibold'],
                                default   => ['Active',        'bg-green-400/10 text-green-400',  $daysLeft . 'd left',  'text-zinc-700 dark:text-zinc-300'],
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                            {{-- Asset --}}
                            <td class="px-4 py-3">
                                <a href="{{ route('assets.show', $item['asset_id']) }}" class="group flex items-center gap-2">
                                    <span class="font-mono text-xs text-accent group-hover:underline">{{ $item['asset_code'] }}</span>
                                    <span class="truncate max-w-36 text-zinc-700 group-hover:text-zinc-900 dark:text-zinc-300 dark:group-hover:text-zinc-100">{{ $item['asset_name'] }}</span>
                                </a>
                                @if ($item['category_name'])
                                    <p class="mt-0.5 text-[10px] text-zinc-400">{{ $item['category_name'] }}</p>
                                @endif
                            </td>
                            {{-- Category badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon :icon="$item['icon']" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['category_color'] }}">
                                        {{ $item['category'] }}
                                    </span>
                                </div>
                            </td>
                            {{-- Name / Detail --}}
                            <td class="px-4 py-3 max-w-52">
                                <p class="font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $item['name'] }}</p>
                                @if ($item['source'])
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $item['source'] }}</p>
                                @endif
                            </td>
                            {{-- Expiry Date --}}
                            <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200 whitespace-nowrap">
                                {{ $item['expiry']->format('d M Y') }}
                            </td>
                            {{-- Days Left --}}
                            <td class="px-4 py-3 whitespace-nowrap {{ $daysClass }}">{{ $daysLabel }}</td>
                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            {{-- Link --}}
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('assets.show', [$item['asset_id'], 'tab' => $item['tab']]) }}"
                                   class="text-xs text-zinc-500 hover:text-accent transition-colors dark:text-zinc-400">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-600">
            <span>Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }} {{ Str::plural('item', $items->total()) }}.</span>
            {{ $items->links() }}
        </div>
    @endif
</x-layouts::app>
