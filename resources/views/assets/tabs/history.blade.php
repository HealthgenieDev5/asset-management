<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Change History</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5 dark:text-zinc-400">
                All changes made to this asset and its related records.
            </flux:text>
        </div>
    </div>

    @if ($auditLogs && $auditLogs->isNotEmpty())

        <div class="relative">

            {{-- Vertical line --}}
            <div class="absolute left-5 top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-800"></div>

            <div class="space-y-6">
                @foreach ($auditLogs as $log)
                    @php
                        $hasFields  = ! empty($log->new_values) || ! empty($log->old_values);
                        $allKeys    = array_unique(array_merge(array_keys($log->new_values ?? []), array_keys($log->old_values ?? [])));
                        $exclude    = ['id', 'asset_id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];
                        $fields     = array_values(array_diff($allKeys, $exclude));

                        $dotColor = match($log->event) {
                            'created'  => 'bg-green-500 ring-green-500/20',
                            'updated'  => 'bg-blue-500 ring-blue-500/20',
                            'deleted'  => 'bg-red-500 ring-red-500/20',
                            'restored' => 'bg-amber-500 ring-amber-500/20',
                            default    => 'bg-zinc-400 ring-zinc-400/20',
                        };
                        $iconColor = match($log->event) {
                            'created'  => 'text-green-500',
                            'updated'  => 'text-blue-500',
                            'deleted'  => 'text-red-500',
                            'restored' => 'text-amber-500',
                            default    => 'text-zinc-400',
                        };
                        $icon = match($log->event) {
                            'created'  => 'plus-circle',
                            'updated'  => 'pencil-square',
                            'deleted'  => 'trash',
                            'restored' => 'arrow-uturn-left',
                            default    => 'clock',
                        };
                        $fmt = fn($v) => match(true) {
                            is_array($v)                                              => json_encode($v),
                            is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}/', $v) => \Carbon\Carbon::parse($v)->format('d M Y'),
                            default                                                   => (string) ($v ?? ''),
                        };
                    @endphp

                    <div x-data="{ open: false }" class="relative flex gap-4 pl-1">

                        {{-- Timeline dot --}}
                        <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full ring-4 {{ $dotColor }} ring-offset-white dark:ring-offset-zinc-950">
                            <flux:icon :icon="$icon" class="size-4 text-white" />
                        </div>

                        {{-- Card --}}
                        <div class="flex-1 min-w-0 rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden mb-1">

                            {{-- Header --}}
                            <div class="flex items-start justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide {{ $iconColor }}">
                                            {{ $log->modelLabel() }}
                                        </span>
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                            {{ $log->description }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-zinc-400 dark:text-zinc-500">
                                        <span class="flex items-center gap-1">
                                            <flux:icon.clock class="size-3" />
                                            <span title="{{ $log->created_at->format('d M Y, H:i:s') }}">
                                                {{ $log->created_at->format('d M Y, h:i A') }}
                                                &middot; {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <flux:icon.user class="size-3" />
                                            {{ $log->causer?->name ?? 'System' }}
                                        </span>
                                    </div>
                                </div>

                                @if ($hasFields && count($fields) > 0)
                                    <button type="button"
                                            @click="open = !open"
                                            class="shrink-0 flex items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1.5 text-xs font-medium text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200 transition-colors">
                                        <flux:icon.chevron-down class="size-3.5 transition-transform duration-200" ::class="open ? 'rotate-180' : ''" />
                                        {{ count($fields) }} {{ Str::plural('field', count($fields)) }}
                                    </button>
                                @endif
                            </div>

                            {{-- Field diff --}}
                            @if ($hasFields && count($fields) > 0)
                                <div x-show="open"
                                     x-collapse
                                     x-cloak>
                                    <div class="border-t border-zinc-100 dark:border-zinc-800">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 w-1/4">Field</th>
                                                    @if ($log->event === 'updated')
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 w-[37.5%]">Before</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 w-[37.5%]">After</th>
                                                    @else
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500">Value</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                                @foreach ($fields as $field)
                                                    @php
                                                        $oldVal     = isset($log->old_values[$field]) ? $fmt($log->old_values[$field]) : '';
                                                        $newVal     = isset($log->new_values[$field]) ? $fmt($log->new_values[$field]) : '';
                                                        $fieldLabel = ucwords(str_replace('_', ' ', $field));
                                                    @endphp
                                                    <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                                        <td class="px-4 py-2.5 text-xs font-medium text-zinc-500 dark:text-zinc-400 align-top">
                                                            {{ $fieldLabel }}
                                                        </td>
                                                        @if ($log->event === 'updated')
                                                            <td class="px-4 py-2.5 align-top">
                                                                @if ($oldVal !== '')
                                                                    <span class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-0.5 text-xs text-red-600 dark:bg-red-500/10 dark:text-red-400 break-all">
                                                                        <flux:icon.minus class="size-3 shrink-0" />
                                                                        {{ $oldVal }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-2.5 align-top">
                                                                @if ($newVal !== '')
                                                                    <span class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-0.5 text-xs text-green-600 dark:bg-green-500/10 dark:text-green-400 break-all">
                                                                        <flux:icon.plus class="size-3 shrink-0" />
                                                                        {{ $newVal }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                                                @endif
                                                            </td>
                                                        @else
                                                            <td class="px-4 py-2.5 align-top" @if($log->event === 'updated') colspan="2" @endif>
                                                                @php $val = $newVal ?: $oldVal; @endphp
                                                                @if ($val !== '')
                                                                    <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs break-all
                                                                        {{ $log->event === 'created' ? 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' }}">
                                                                        {{ $val }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($auditLogs->hasPages())
            <div class="pl-13 mt-2">
                {{ $auditLogs->links() }}
            </div>
        @endif

    @else
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.clock class="mx-auto size-10 text-zinc-400 dark:text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400 dark:text-zinc-500">No History Yet</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-600">
                Changes to this asset and its related records will appear here.
            </flux:text>
        </div>
    @endif

</div>
