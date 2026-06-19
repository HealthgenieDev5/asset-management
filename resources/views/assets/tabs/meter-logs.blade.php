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
              enctype="multipart/form-data" class="mt-4 space-y-4">
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
                  enctype="multipart/form-data" class="mt-4 space-y-4">
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
    @php
        $grouped = $asset->meterLogs->groupBy('unit');

        // Index smart reminders by unit for progress bars
        $remindersByUnit = $asset->smartReminders
            ->filter(fn($r) => $r->reminder_mode !== 'time' && $r->threshold_unit)
            ->groupBy('threshold_unit');

        // Index active meter-based warranties by unit
        $warrantiesByUnit = $asset->warranties
            ->where('status', 'active')
            ->filter(fn($w) => ! $w->isTimeBased() && $w->unit)
            ->groupBy('unit');
    @endphp

    {{-- Stats Summary Cards --}}
    @if ($grouped->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($grouped as $unit => $logs)
                @php
                    $sorted          = $logs->sortByDesc('logged_at')->values();
                    $latest          = $sorted->first();
                    $prev            = $sorted->get(1);
                    $delta           = $prev ? ($latest->reading_value - $prev->reading_value) : null;
                    $unitWarranties  = $warrantiesByUnit->get($unit, collect());
                    $unitReminders   = $remindersByUnit->get($unit, collect());
                    $hasTracking     = $unitWarranties->isNotEmpty() || $unitReminders->isNotEmpty();
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">

                    {{-- Card top row --}}
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.chart-bar class="size-4 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ $unit }}</p>
                                <p class="text-[11px] text-zinc-400">{{ $sorted->count() }} {{ Str::plural('reading', $sorted->count()) }}</p>
                            </div>
                        </div>
                        <button type="button" x-on:click="$dispatch('open-modal-add-meter-log')"
                            title="Log new {{ $unit }} reading"
                            class="inline-flex size-7 items-center justify-center rounded-md border border-zinc-200 text-zinc-400 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        </button>
                    </div>

                    {{-- Reading value --}}
                    <p class="text-3xl font-bold tabular-nums text-zinc-800 dark:text-zinc-100 leading-none">
                        {{ number_format($latest->reading_value) }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-zinc-400">as of {{ $latest->logged_at->format('d M Y') }}</p>

                    {{-- Delta badge --}}
                    <div class="mt-2">
                        @if ($delta !== null && $delta > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2 py-0.5 text-[11px] font-semibold text-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="M8 14a.75.75 0 0 1-.75-.75V4.56L4.03 7.78a.75.75 0 0 1-1.06-1.06l4.5-4.5a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1-1.06 1.06L8.75 4.56v8.69A.75.75 0 0 1 8 14Z" clip-rule="evenodd"/></svg>
                                +{{ number_format($delta) }} since last
                            </span>
                        @elseif ($delta !== null && $delta < 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-400/10 px-2 py-0.5 text-[11px] font-semibold text-red-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="M8 2a.75.75 0 0 1 .75.75v8.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.22 3.22V2.75A.75.75 0 0 1 8 2Z" clip-rule="evenodd"/></svg>
                                {{ number_format($delta) }} since last
                            </span>
                        @elseif ($delta === null)
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-400 dark:bg-zinc-800">First reading</span>
                        @else
                            <span class="text-[11px] text-zinc-400">No change since last</span>
                        @endif
                    </div>

                    {{-- Warranty progress bars --}}
                    @foreach ($unitWarranties as $w)
                        @php
                            $wRemaining = $w->remainingUnits();
                            $wLimit     = $w->counter_limit;
                            $wPct       = ($wLimit && $wRemaining !== null) ? round(($latest->reading_value / $wLimit) * 100) : null;
                            $wBar       = match(true) {
                                $wPct === null  => 'bg-zinc-300 dark:bg-zinc-600',
                                $wPct >= 90     => 'bg-red-400',
                                $wPct >= 70     => 'bg-yellow-400',
                                default         => 'bg-green-400',
                            };
                            $wLabel = trim(($w->warrantyTypeLabel() ?? '') . ' Warranty' . ($w->scope === 'part' && $w->part_name ? ' · ' . $w->part_name : ''));
                        @endphp
                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.shield-check class="size-3 text-blue-400 shrink-0" />
                                    <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 truncate max-w-36" title="{{ $wLabel }}">{{ $wLabel }}</p>
                                </div>
                                <p class="text-[11px] shrink-0 ml-1 {{ $wPct >= 90 ? 'text-red-400 font-semibold' : ($wPct >= 70 ? 'text-yellow-500 font-semibold' : 'text-zinc-400') }}">
                                    @if ($wRemaining !== null)
                                        {{ number_format($wRemaining) }} left / {{ number_format($wLimit) }}
                                    @elseif ($wLimit)
                                        {{ number_format($wLimit) }} limit
                                    @endif
                                </p>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                @if ($wPct !== null)
                                    <div class="h-full rounded-full transition-all {{ $wBar }}" style="width: {{ min($wPct, 100) }}%"></div>
                                @endif
                            </div>
                            @if ($wPct !== null)
                                <p class="mt-0.5 text-[10px] text-zinc-400">{{ $wPct }}% of warranty limit used</p>
                            @endif
                        </div>
                    @endforeach

                    {{-- Smart reminder progress bars --}}
                    @foreach ($unitReminders as $sr)
                        @php
                            $srRemaining = $sr->remainingUnits();
                            $srLimit     = $sr->counter_limit;
                            $srPct       = ($srLimit && $srRemaining !== null) ? round(($latest->reading_value / $srLimit) * 100) : null;
                            $srBar       = match(true) {
                                $srPct === null => 'bg-zinc-300 dark:bg-zinc-600',
                                $srPct >= 90    => 'bg-red-400',
                                $srPct >= 70    => 'bg-yellow-400',
                                default         => 'bg-blue-400',
                            };
                        @endphp
                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.bell-alert class="size-3 text-violet-400 shrink-0" />
                                    <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400 truncate max-w-36" title="{{ $sr->reminder_name }}">{{ $sr->reminder_name }}</p>
                                </div>
                                <p class="text-[11px] shrink-0 ml-1 {{ $srPct >= 90 ? 'text-red-400 font-semibold' : ($srPct >= 70 ? 'text-yellow-500 font-semibold' : 'text-zinc-400') }}">
                                    @if ($srRemaining !== null)
                                        {{ number_format($srRemaining) }} left / {{ number_format($srLimit) }}
                                    @elseif ($srLimit)
                                        {{ number_format($srLimit) }} limit
                                    @endif
                                </p>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                @if ($srPct !== null)
                                    <div class="h-full rounded-full transition-all {{ $srBar }}" style="width: {{ min($srPct, 100) }}%"></div>
                                @endif
                            </div>
                            @if ($srPct !== null)
                                <p class="mt-0.5 text-[10px] text-zinc-400">{{ $srPct }}% of limit used</p>
                            @endif
                        </div>
                    @endforeach

                    {{-- Nudge when nothing is tracked for this unit --}}
                    @if (! $hasTracking)
                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <p class="text-[11px] text-zinc-400">
                                No warranty or reminder tracked for <strong>{{ $unit }}</strong>.
                                <button type="button" @click="$dispatch('set-tab', 'warranty')"
                                    class="text-accent hover:underline font-medium">Add Warranty →</button>
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($grouped as $unit => $logs)
        @php
            $logsArr = $logs->sortByDesc('logged_at')->values();
            $latest  = $logsArr->first();
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
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">Evidence</th>
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
                                @if ($log->evidence_path)
                                    <a href="{{ Storage::url($log->evidence_path) }}" target="_blank"
                                       title="{{ $log->evidence_original_name ?? 'Evidence' }}"
                                       class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-0.5 text-[11px] font-medium text-zinc-500 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700">
                                        <flux:icon.paper-clip class="size-3" />
                                        Evidence
                                    </a>
                                @else
                                    <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                @endif
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

    {{-- Always-visible placeholder --}}
    <div class="grid grid-cols-3 gap-4">
    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
        <flux:icon.chart-bar class="mx-auto size-10 text-zinc-600" />
        <flux:heading class="mt-4 text-zinc-400">
            {{ $asset->meterLogs->isEmpty() ? 'No Meter Readings Yet' : 'Log Another Reading' }}
        </flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-600">Track usage by km, hours, prints, or any unit to power meter-based reminders.</flux:text>
        <div class="mt-4">
            <button type="button" x-on:click="$dispatch('open-modal-add-meter-log')"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                {{ $asset->meterLogs->isEmpty() ? 'Log First Reading' : 'Log Reading' }}
            </button>
        </div>
    </div>
    </div>

</div>
