<x-layouts::app :title="__('Reminders')">
    @include('partials.flash')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Expiry <span class="text-accent">Reminders</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Upcoming and overdue expiry dates across all assets.</flux:text>
        </div>
    </div>

    {{-- Filter tabs --}}
    @php
        $filter = request('filter', 'upcoming');
        $filters = [
            'upcoming' => 'Upcoming (90 days)',
            'expired'  => 'Expired',
            'all'      => 'All',
        ];
    @endphp

    <div class="mb-5 flex gap-2 border-b border-zinc-200 pb-0 dark:border-zinc-800">
        @foreach ($filters as $key => $label)
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

    @if ($reminders->isEmpty())
        <div class="flex items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-20 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:icon.bell-alert class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-500 dark:text-zinc-400">No Reminders Found</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-600">
                    @if ($filter === 'upcoming')
                        No expiry dates falling within the next 90 days.
                    @elseif ($filter === 'expired')
                        No expired items found.
                    @else
                        No expiry dates have been recorded yet. Add warranty, AMC, or insurance dates to assets.
                    @endif
                </flux:text>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Asset</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Detail</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Expiry Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Days Left</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    @foreach ($reminders->filter(fn($r) => $r['asset'] !== null) as $reminder)
                        @php
                            $days    = (int) now()->startOfDay()->diffInDays($reminder['expiry']->copy()->startOfDay(), false);
                            $expired = $days < 0;
                            $soon    = ! $expired && $days <= ($reminder['reminder_days'] ?? 30);

                            if ($expired) {
                                $statusLabel = 'Expired';
                                $statusClass = 'bg-red-400/10 text-red-400';
                                $daysLabel   = abs($days) . 'd ago';
                                $daysClass   = 'text-red-400';
                            } elseif ($soon) {
                                $statusLabel = 'Expiring Soon';
                                $statusClass = 'bg-yellow-400/10 text-yellow-400';
                                $daysLabel   = $days . 'd';
                                $daysClass   = 'text-yellow-400';
                            } else {
                                $statusLabel = 'Active';
                                $statusClass = 'bg-green-400/10 text-green-400';
                                $daysLabel   = $days . 'd';
                                $daysClass   = 'text-zinc-800 dark:text-zinc-200';
                            }

                            $typeIcons = [
                                'Original Warranty'   => 'shield-check',
                                'Extended Warranty'   => 'shield-exclamation',
                                'AMC Contract'        => 'wrench-screwdriver',
                                'Insurance'           => 'building-library',
                            ];
                            $icon = $typeIcons[$reminder['type']] ?? 'bell-alert';
                        @endphp
                        <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('assets.show', $reminder['asset']) }}"
                                   class="group flex items-center gap-2">
                                    <span class="font-mono text-xs text-accent group-hover:underline">{{ $reminder['asset']->asset_code }}</span>
                                    <span class="truncate text-zinc-700 group-hover:text-zinc-900 max-w-40 dark:text-zinc-300 dark:group-hover:text-zinc-100">{{ $reminder['asset']->asset_name }}</span>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon :icon="$icon" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $reminder['type'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 max-w-45 truncate">
                                {{ $reminder['detail'] ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200 whitespace-nowrap">
                                {{ $reminder['expiry']->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 {{ $daysClass }} font-semibold whitespace-nowrap">
                                {{ $daysLabel }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('assets.show', [$reminder['asset'], 'tab' => $reminder['tab']]) }}"
                                   class="text-xs text-zinc-500 hover:text-accent transition-colors dark:text-zinc-400">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-600">
            Showing {{ count($reminders) }} {{ Str::plural('item', count($reminders)) }}.
        </div>
    @endif
</x-layouts::app>
