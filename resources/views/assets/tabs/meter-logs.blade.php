<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Meter Logs</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                Log periodic readings (km, hours, prints, etc.) to track usage and power meter-based reminders.
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-meter-log')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Log Reading
        </button>
    </div>

    {{-- Add Modal --}}
    <x-modal name="add-meter-log" title="Log Meter Reading" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'meter_log' && !old('_log_id')">
        <form method="POST" action="{{ route('assets.meter-logs.store', $asset) }}"
              class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="meter_log">
            @include('assets.tabs._meter-log-form', ['log' => null])
            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                    Save Reading
                </button>
                <button type="button" x-on:click="$dispatch('close-modal-add-meter-log')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Modals --}}
    @foreach ($asset->meterLogs as $log)
        <x-modal name="edit-meter-log-{{ $log->id }}" title="Edit Meter Reading" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'meter_log' && (int) old('_log_id') === $log->id">
            <form method="POST" action="{{ route('assets.meter-logs.update', [$asset, $log]) }}"
                  class="mt-4 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="meter_log">
                <input type="hidden" name="_log_id" value="{{ $log->id }}">
                @include('assets.tabs._meter-log-form', ['log' => $log])
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Save Changes
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-meter-log-{{ $log->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- Content --}}
    @if ($asset->meterLogs->isEmpty())
        <div class="grid grid-cols-3 gap-4">
            <button type="button" x-on:click="$dispatch('open-modal-add-meter-log')"
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 py-10 text-center transition-colors hover:border-accent hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent dark:hover:bg-zinc-800/50">
                <div class="flex size-9 items-center justify-center rounded-full border-2 border-dashed border-zinc-300 dark:border-zinc-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-zinc-400"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">No Meter Readings Yet</p>
                    <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-500">Log a reading to start tracking usage<br>and enable meter-based reminders.</p>
                </div>
                <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">+ Log Reading</span>
            </button>
        </div>
    @else
        @php
            $grouped = $asset->meterLogs->groupBy('unit');
        @endphp

        @foreach ($grouped as $unit => $logs)
            @php
                $logsArr   = $logs->sortByDesc('logged_at')->values();
                $latest    = $logsArr->first();
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Group header --}}
                <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-2">
                        <flux:icon.chart-bar class="size-4 text-zinc-400" />
                        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 uppercase tracking-wide">{{ $unit }}</span>
                        <span class="rounded-full bg-zinc-200/60 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ $logsArr->count() }} {{ Str::plural('entry', $logsArr->count()) }}
                        </span>
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        Latest: <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ number_format($latest->reading_value) }} {{ $unit }}</span>
                        <span class="ml-1 text-zinc-400">on {{ $latest->logged_at->format('d M Y') }}</span>
                    </div>
                </div>

                {{-- Table --}}
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">Reading</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">Change</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">Notes</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($logsArr as $i => $log)
                            @php
                                $prev  = $logsArr->get($i + 1);
                                $delta = $prev ? ($log->reading_value - $prev->reading_value) : null;
                            @endphp
                            <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ $log->logged_at->format('d M Y') }}
                                    @if ($i === 0)
                                        <span class="ml-1.5 rounded-full bg-green-400/10 px-1.5 py-0.5 text-[10px] font-medium text-green-500">Latest</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ number_format($log->reading_value) }} <span class="font-normal text-zinc-400 text-xs">{{ $unit }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($delta !== null)
                                        @if ($delta > 0)
                                            <span class="text-xs font-medium text-blue-500">+{{ number_format($delta) }} {{ $unit }}</span>
                                        @elseif ($delta < 0)
                                            <span class="text-xs font-medium text-red-400">{{ number_format($delta) }} {{ $unit }}</span>
                                        @else
                                            <span class="text-xs text-zinc-400">No change</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 max-w-48 truncate">
                                    {{ $log->notes ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal-edit-meter-log-{{ $log->id }}')"
                                                title="Edit"
                                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                            <flux:icon.pencil class="size-3.5" />
                                        </button>
                                        <form method="POST" action="{{ route('assets.meter-logs.destroy', [$asset, $log]) }}"
                                              onsubmit="return confirm('Delete this meter reading?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete"
                                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                <flux:icon.trash class="size-3.5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

</div>
