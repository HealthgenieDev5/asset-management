@php
    $type = $schedule->schedule_type;
@endphp

<div x-data="{ type: '{{ $type }}' }">

    {{-- Date-based completion --}}
    <div x-show="type === 'date'" class="mt-4"
         x-data
         x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', allowInput: true })">
        <div class="relative">
            <input type="text" name="completed_date" placeholder=" "
                   value="{{ old('completed_date', now()->format('Y-m-d')) }}"
                   class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                Completed Date <span class="text-red-400">*</span>
            </label>
        </div>
    </div>

    {{-- Mileage-based completion --}}
    <div x-show="type === 'mileage'" x-cloak class="mt-4">
        <div class="relative">
            <input type="number" name="completed_km" placeholder=" " min="0"
                   value="{{ old('completed_km', $schedule->effectiveLastDoneKm()) }}"
                   class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                Current Odometer (km) <span class="text-red-400">*</span>
            </label>
        </div>
        @php $latest = $schedule->latestMileage(); @endphp
        @if ($latest)
            <p class="mt-1 text-[11px] text-zinc-500">Latest logged reading: <strong>{{ number_format($latest) }} km</strong></p>
        @endif
    </div>

    {{-- Hours-based completion --}}
    <div x-show="type === 'operating_hours'" x-cloak class="mt-4">
        <div class="relative">
            <input type="number" name="completed_hours" placeholder=" " min="0"
                   value="{{ old('completed_hours', $schedule->effectiveLastDoneHours()) }}"
                   class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                Current Hours Reading <span class="text-red-400">*</span>
            </label>
        </div>
        @php $latestHrs = $schedule->latestHours(); @endphp
        @if ($latestHrs)
            <p class="mt-1 text-[11px] text-zinc-500">Latest logged reading: <strong>{{ number_format($latestHrs) }} hrs</strong></p>
        @endif
    </div>

    {{-- Service history hint --}}
    @php $lastSvc = $schedule->latestServiceRecord(); @endphp
    @if ($lastSvc)
        <p class="mt-3 text-[11px] text-zinc-400">
            Last <strong>{{ $schedule->serviceTypeLabel() }}</strong> service on record:
            <strong>{{ $lastSvc->service_date->format('d M Y') }}</strong>
            @if ($lastSvc->mileage_reading) · {{ number_format($lastSvc->mileage_reading) }} km @endif
            @if ($lastSvc->meter_reading) · {{ number_format($lastSvc->meter_reading) }} hrs @endif
        </p>
    @endif

    <p class="mt-3 text-[11px] text-zinc-500">
        Marking complete will record this as the last done point and recalculate the next due date/mileage/hours.
    </p>

</div>
