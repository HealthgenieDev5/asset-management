@php
$isEdit  = isset($log) && $log !== null;
$v       = fn($f, $default = '') => old($f, $isEdit ? ($log?->$f ?? $default) : $default);
$uid     = $isEdit ? $log->id : 'new';
$inp     = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl     = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400';
$err     = 'mt-0.5 text-[11px] text-red-400';
$cal     = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';

$presets = ['km', 'hours', 'prints', 'cycles', 'litres'];
$initUnit = $v('unit');
$isPreset = in_array(strtolower($initUnit), $presets);
$initCustom = (!$isPreset && $initUnit !== '') ? $initUnit : '';
// Normalize preset value to lowercase match
$initSelected = $isPreset ? strtolower($initUnit) : ($initUnit !== '' ? '__custom__' : 'km');
@endphp

<div class="space-y-4"
     x-data="{
         unit: '{{ $initSelected }}',
         customUnit: '{{ $initCustom }}',
         get finalUnit() {
             return this.unit === '__custom__' ? this.customUnit : this.unit;
         }
     }">

    {{-- Unit Selector --}}
    <div>
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
            Unit <span class="text-red-400">*</span>
        </p>
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($presets as $preset)
                <button type="button"
                    @click="unit = '{{ $preset }}'"
                    :class="unit === '{{ $preset }}'
                        ? 'bg-accent text-accent-foreground shadow-sm'
                        : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors capitalize">
                    {{ $preset }}
                </button>
            @endforeach
            <button type="button"
                @click="unit = '__custom__'; $nextTick(() => $refs.customUnit.focus())"
                :class="unit === '__custom__'
                    ? 'bg-accent text-accent-foreground shadow-sm'
                    : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Other…
            </button>
            {{-- Custom unit input appears inline right after the Other button --}}
            <input type="text"
                   x-ref="customUnit"
                   x-show="unit === '__custom__'"
                   x-cloak
                   x-model="customUnit"
                   placeholder="e.g. miles…"
                   class="w-32 rounded-lg border border-accent bg-white px-2.5 py-1.5 text-xs text-zinc-900 shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-accent dark:bg-zinc-800 dark:text-zinc-100" />
        </div>

        {{-- Hidden field carries the final value --}}
        <input type="hidden" name="unit" :value="finalUnit">

        @error('unit') <p class="{{ $err }} mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Reading Value + Date Logged (single row) --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="relative">
            <input type="number" name="reading_value" id="reading_value_{{ $uid }}"
                   value="{{ $v('reading_value') }}" placeholder=" " min="0"
                   class="{{ $inp }}" />
            <label for="reading_value_{{ $uid }}" class="{{ $lbl }}">
                Current Reading <span class="text-red-400">*</span>
                <span x-show="finalUnit" class="font-normal text-zinc-400" x-text="'(' + finalUnit + ')'"></span>
            </label>
            @error('reading_value') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>

        <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true, defaultDate: '{{ $v('logged_at', now()->format('Y-m-d')) }}' })">
            <div class="relative w-full">
                <input type="text" inputmode="none" name="logged_at" id="logged_at_{{ $uid }}"
                       value="{{ $v('logged_at', now()->format('Y-m-d')) }}"
                       placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                <label for="logged_at_{{ $uid }}" class="{{ $lbl }}">
                    Date Logged <span class="text-red-400">*</span>
                </label>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
            </div>
            @error('logged_at') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Notes --}}
    <div class="relative">
        <textarea name="notes" id="notes_{{ $uid }}" rows="2" placeholder=" "
                  class="{{ $inp }}">{{ $v('notes') }}</textarea>
        <label for="notes_{{ $uid }}" class="{{ $lbl }}">Notes</label>
        @error('notes') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- Evidence Upload --}}
    <div>
        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
            Evidence
            <span class="font-normal normal-case tracking-normal text-zinc-400">(photo, receipt, PDF — max 5 MB)</span>
        </p>
        <style>
            .meter-evidence-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <div class="meter-evidence-upload" x-data x-init="initUploadPond($refs.evidencePond, {
                acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                labelIdle: 'Drag & Drop evidence or <span class=\'filepond--label-action\'>Browse</span>',
                @if ($isEdit && $log->evidence_path)
                files: [{ source: '{{ Storage::url($log->evidence_path) }}', options: { type: 'local' } }],
                fileMetaBySource: { '{{ Storage::url($log->evidence_path) }}': { name: '{{ addslashes($log->evidence_original_name ?? basename($log->evidence_path)) }}' } },
                onremovefile: () => {
                    let f = document.getElementById('remove_evidence_{{ $uid }}');
                    if (f) f.value = '1';
                },
                @endif
            })">
            <input type="file" name="evidence" x-ref="evidencePond" accept=".pdf,.jpg,.jpeg,.png,.webp" />
        </div>
        @if ($isEdit && $log->evidence_path)
            <input type="hidden" name="remove_evidence" id="remove_evidence_{{ $uid }}" value="0">
        @endif
        @error('evidence') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>
</div>
