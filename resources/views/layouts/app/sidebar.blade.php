<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-950">

        {{-- Mobile top bar --}}
        <div class="flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3 lg:hidden dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center gap-2">
                <div class="flex size-7 items-center justify-center rounded-lg bg-accent">
                    <flux:icon.cube class="size-4 text-zinc-950" />
                </div>
                <span class="font-extrabold text-zinc-900 dark:text-zinc-100">AssetManager</span>
            </div>
            <button id="mobile-menu-toggle" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                <flux:icon.bars-3 class="size-5" />
            </button>
        </div>

        <div class="flex min-h-screen lg:min-h-0">
            {{-- Sidebar --}}
            <aside id="sidebar"
                class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col bg-white border-r border-zinc-200
                       -translate-x-full transition-transform duration-200
                       lg:static lg:translate-x-0 lg:min-h-screen
                       dark:bg-zinc-900 dark:border-zinc-800">

                {{-- Brand --}}
                <div class="flex items-center gap-3 px-5 py-5">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-accent">
                        <flux:icon.cube class="size-5 text-zinc-950" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-extrabold text-zinc-900 leading-tight dark:text-zinc-100">AssetManager</p>
                        <p class="truncate text-xs text-zinc-500 leading-tight">Fixed Asset System</p>
                    </div>
                </div>

                {{-- Nav --}}
                <nav class="flex-1 overflow-y-auto px-3 pb-3">

                    @php
                        $item = fn(string $icon, string $label, string $route, string $match = '') => [
                            'icon'    => $icon,
                            'label'   => $label,
                            'route'   => $route,
                            'active'  => request()->routeIs($match ?: $route),
                        ];

                        $navItems = [
                            $item('home', 'Dashboard', 'dashboard'),
                        ];

                        $assetItems = [
                            $item('clipboard-document-list', 'Asset Register',  'assets.index',               'assets.*'),
                            $item('tag',                     'Categories',       'asset-categories.index',     'asset-categories.*'),
                            $item('queue-list',              'Subcategories',    'asset-subcategories.index',  'asset-subcategories.*'),
                        ];

                        $reminderItems = [
                            $item('bell-alert', 'Reminders', 'asset-reminders.index', 'asset-reminders.*'),
                        ];

                        $reportItems = [
                            $item('squares-2x2',        'All Reports',          'reports.index',              'reports.*'),
                            $item('document-text',      'Asset Register',       'reports.asset-register'),
                            $item('receipt-percent',    'Purchase / Bills',     'reports.purchase-bills'),
                            $item('wrench-screwdriver', 'Service Reports',      'reports.service-due',        'reports.service-*'),
                            $item('truck',              'Vehicle Depreciation', 'reports.vehicle-depreciation'),
                        ];
                    @endphp

                    {{-- Main --}}
                    <div class="mb-1">
                        @foreach ($navItems as $nav)
                            @include('layouts.app._nav-item', $nav)
                        @endforeach
                    </div>

                    {{-- Assets group --}}
                    <div class="mb-1">
                        <p class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-600">Assets</p>
                        @foreach ($assetItems as $nav)
                            @include('layouts.app._nav-item', $nav)
                        @endforeach
                    </div>

                    {{-- Reminders --}}
                    <div class="mb-1">
                        <p class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-600">Alerts</p>
                        @foreach ($reminderItems as $nav)
                            @include('layouts.app._nav-item', $nav)
                        @endforeach
                    </div>

                    {{-- Reports --}}
                    <div class="mb-1">
                        <p class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-600">Reports</p>
                        @foreach ($reportItems as $nav)
                            @include('layouts.app._nav-item', $nav)
                        @endforeach
                    </div>

                </nav>

                {{-- User footer --}}
                <div class="border-t border-zinc-200 px-3 py-3 dark:border-zinc-800">
                    <flux:dropdown position="top" align="start" class="w-full">
                        <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent/20 text-xs font-bold text-accent">
                                {{ auth()->user()->initials() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                            </div>
                            <flux:icon.chevron-up-down class="size-4 shrink-0 text-zinc-500" />
                        </button>

                        <flux:menu>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </aside>

            {{-- Mobile overlay --}}
            <div id="sidebar-overlay"
                 class="fixed inset-0 z-40 bg-black/60 hidden lg:hidden"
                 onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden')">
            </div>

            {{-- Main content --}}
            <div class="flex-1 min-w-0 overflow-y-auto bg-zinc-100 dark:bg-zinc-950">
                {{ $slot }}
            </div>
        </div>

        {{-- Mobile toggle script --}}
        <script>
            document.getElementById('mobile-menu-toggle')?.addEventListener('click', function () {
                const sidebar  = document.getElementById('sidebar');
                const overlay  = document.getElementById('sidebar-overlay');
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
        </script>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @stack('scripts')
        @fluxScripts
    </body>
</html>
