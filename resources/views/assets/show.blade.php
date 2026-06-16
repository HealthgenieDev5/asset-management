<x-layouts::app :title="$asset->asset_code . ' — ' . $asset->asset_name">
    @include('partials.flash')

    {{-- Asset Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <flux:button href="{{ route('assets.index') }}" variant="ghost" size="sm" icon="arrow-left" class="mt-0.5" />
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="font-mono text-lg font-bold text-accent">{{ $asset->asset_code }}</span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                        {{ $asset->status_label }}
                    </span>
                </div>
                <flux:heading size="xl" class="font-extrabold mt-1">{{ $asset->asset_name }}</flux:heading>
                <flux:text class="text-zinc-500 text-sm mt-0.5 dark:text-zinc-400">
                    {{ $asset->category?->name }}
                    @if ($asset->subcategory)
                        <span class="text-zinc-400 dark:text-zinc-600"> / </span>{{ $asset->subcategory->name }}
                    @endif
                    @if ($asset->manufacturer || $asset->model)
                        <span class="text-zinc-400 dark:text-zinc-600"> · </span>{{ implode(' ', array_filter([$asset->manufacturer, $asset->model])) }}
                    @endif
                </flux:text>
            </div>
        </div>
        <flux:button href="{{ route('assets.edit', $asset) }}" variant="primary" icon="pencil">
            Edit Asset
        </flux:button>
    </div>

    @php
        $tabs = [
            'overview'  => ['label' => 'Overview',          'icon' => 'information-circle'],
            'reminders' => ['label' => 'Reminders',          'icon' => 'bell-alert'],
            'warranty'  => ['label' => 'Warranty',           'icon' => 'shield-check'],
            'ext-warranty' => ['label' => 'Ext. Warranty',   'icon' => 'shield-exclamation'],
            'services'   => ['label' => 'Servicing',   'icon' => 'cog-6-tooth'],
            'parts'      => ['label' => 'Parts',        'icon' => 'puzzle-piece'],
            'complaints' => ['label' => 'Complaints',   'icon' => 'exclamation-triangle'],
            'documents'  => ['label' => 'Documents',    'icon' => 'paper-clip'],
            'amc'        => ['label' => 'AMC',          'icon' => 'wrench-screwdriver'],
            'insurance'  => ['label' => 'Insurance',    'icon' => 'building-library'],
        ];
    @endphp

    <div class="flex gap-6 lg:flex-row flex-col" x-data="{
        tab: '{{ $tab }}',
        setTab(key) {
            this.tab = key;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            history.pushState(null, '', url.toString());
        }
    }" @popstate.window="tab = new URLSearchParams(window.location.search).get('tab') || 'overview'">
        {{-- Vertical Tab List --}}
        <nav class="lg:w-48 flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible shrink-0
                    border-b lg:border-b-0 lg:border-r border-zinc-200 pb-2 lg:pb-0 lg:pr-2 dark:border-zinc-800">
            @foreach ($tabs as $key => $tabInfo)
                <button type="button"
                        @click="setTab('{{ $key }}')"
                        :class="tab === '{{ $key }}'
                            ? 'bg-accent/10 text-accent border-l-2 border-accent lg:rounded-l-none'
                            : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200'"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap text-left w-full">
                    <flux:icon :icon="$tabInfo['icon']" class="size-4 shrink-0" />
                    {{ $tabInfo['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Tab Content --}}
        <div class="flex-1 min-w-0">
            @foreach ($tabs as $key => $tabInfo)
                <div x-show="tab === '{{ $key }}'" x-cloak
                     x-effect="if (tab === '{{ $key }}') $nextTick(() => window.dispatchEvent(new CustomEvent('tab-visible', { detail: '{{ $key }}' })))">
                    @include('assets.tabs.' . $key)
                </div>
            @endforeach
        </div>
    </div>
</x-layouts::app>
