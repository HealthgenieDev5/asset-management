@php
use Illuminate\Support\Facades\Storage;
$v   = fn($f) => old($f, $part?->{$f});
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$sec = 'mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
$cal = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';

$pMode = old('warranty_tracking_mode', $part?->warranty_tracking_mode ?? 'time');
$pSrc  = old('warranty_meter_source', $part?->warranty_meter_source ?? 'meter');
$pUnit = old('warranty_unit', $part?->warranty_unit ?? '');

$unitPresets  = ['km', 'hours', 'prints', 'cycles', 'litres'];
$unitIsPreset = in_array(strtolower($pUnit), $unitPresets);
$unitCustom   = (!$unitIsPreset && $pUnit !== '') ? $pUnit : '';
$unitSelected = $unitIsPreset ? strtolower($pUnit) : ($pUnit !== '' ? '__custom__' : 'km');

$linkedReminder = $part?->id
    ? \App\Models\AssetSmartReminder::where('remindable_type', \App\Models\AssetServicePart::class)
        ->where('remindable_id', $part->id)->first()
    : null;
$srDaysArr = $linkedReminder?->reminder_days ?? [];

$formId = 'pf_' . ($part?->id ?? 'new');
@endphp

<div id="{{ $formId }}" class="space-y-4"
     x-data="{
         mode:       '{{ $pMode }}',
         src:        '{{ $pSrc }}',
         unitSel:    '{{ $unitSelected }}',
         customUnit: '{{ $unitCustom }}',
         get unit() { return this.unitSel === '__custom__' ? this.customUnit : this.unitSel; }
     }">

    <input type="hidden" name="warranty_tracking_mode" :value="mode">
    <input type="hidden" name="warranty_meter_source"  :value="mode === 'meter' ? src : null">

    {{-- ── Part Info ── --}}
    <div>
        <p class="{{ $sec }}">Part Info</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {{-- Part Name --}}
            <div class="relative sm:col-span-2">
                <input type="text" name="part_name" id="{{ $formId }}_part_name" value="{{ $v('part_name') }}" placeholder=" " class="{{ $inp }}" />
                <label for="{{ $formId }}_part_name" class="{{ $lbl }}">Part Name <span class="text-red-400">*</span></label>
                @error('part_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            {{-- Part Serial Number --}}
            <div class="relative sm:col-span-2">
                <input type="text" name="part_serial_number" id="{{ $formId }}_part_serial" value="{{ $v('part_serial_number') }}" placeholder=" " maxlength="255" class="{{ $inp }}" />
                <label for="{{ $formId }}_part_serial" class="{{ $lbl }}">Serial Number</label>
                @error('part_serial_number')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Vendor & Billing ── --}}
    <div>
        <p class="{{ $sec }}">Vendor & Billing</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {{-- Purchased From --}}
            <div class="relative sm:col-span-2">
                <input type="text" name="purchased_from" id="{{ $formId }}_purchased_from" value="{{ $v('purchased_from') }}" placeholder=" " class="{{ $inp }}" />
                <label for="{{ $formId }}_purchased_from" class="{{ $lbl }}">Purchased From</label>
                @error('purchased_from')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            {{-- Bill No --}}
            <div class="relative">
                <input type="text" name="bill_no" id="{{ $formId }}_bill_no" value="{{ $v('bill_no') }}" placeholder=" " class="{{ $inp }}" />
                <label for="{{ $formId }}_bill_no" class="{{ $lbl }}">Bill / Invoice No.</label>
                @error('bill_no')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            {{-- Part Cost --}}
            <div class="relative">
                <input type="number" name="part_cost" id="{{ $formId }}_part_cost" value="{{ $v('part_cost') }}" placeholder=" " min="0" step="0.01" class="{{ $inp }}" />
                <label for="{{ $formId }}_part_cost" class="{{ $lbl }}">Cost (₹)</label>
                @error('part_cost')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Warranty Period ── --}}
    <div>
        <p class="{{ $sec }}">Warranty Period</p>

        {{-- Tracking Mode toggle --}}
        <div class="mb-3 flex gap-2">
            <button type="button"
                @click="mode = 'time'"
                :class="mode === 'time'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Date-based
            </button>
            <button type="button"
                @click="mode = 'meter'"
                :class="mode === 'meter'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Meter-based
            </button>
            <button type="button"
                @click="mode = 'count'"
                :class="mode === 'count'
                    ? 'bg-accent text-accent-foreground'
                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Count-based
            </button>
        </div>

        {{-- Date-based fields --}}
        <div x-show="mode === 'time'" style="display:none"
             x-init="if (mode === 'time') $el.style.display = ''">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="warranty_till" id="{{ $formId }}_warranty_till"
                               value="{{ $part?->warranty_till?->format('Y-m-d') ?? old('warranty_till') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="{{ $formId }}_warranty_till" class="{{ $lbl }}">Warranty Expiry Date</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('warranty_till')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Meter / Count-based fields --}}
        <div x-show="mode === 'meter' || mode === 'count'" style="display:none">
            <div class="space-y-3">
                {{-- Unit pills --}}
                <div>
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        Unit <span class="text-red-400">*</span>
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (['km', 'hours', 'prints', 'cycles', 'litres'] as $preset)
                            <button type="button"
                                @click="unitSel = '{{ $preset }}'"
                                :class="unitSel === '{{ $preset }}'
                                    ? 'bg-accent text-accent-foreground shadow-sm'
                                    : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors capitalize">
                                {{ $preset }}
                            </button>
                        @endforeach
                        <button type="button"
                            @click="unitSel = '__custom__'; $nextTick(() => $refs.pCustomUnit_{{ $formId }}.focus())"
                            :class="unitSel === '__custom__'
                                ? 'bg-accent text-accent-foreground shadow-sm'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                            Other…
                        </button>
                        <input type="text"
                               x-ref="pCustomUnit_{{ $formId }}"
                               x-show="unitSel === '__custom__'"
                               x-cloak
                               x-model="customUnit"
                               placeholder="e.g. miles…"
                               class="w-32 rounded-lg border border-accent bg-white px-2.5 py-1.5 text-xs text-zinc-900 shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-accent dark:bg-zinc-800 dark:text-zinc-100" />
                    </div>
                    <input type="hidden" name="warranty_unit" :value="unit">
                    @error('warranty_unit')<p class="{{ $err }} mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Counter limit --}}
                <div class="relative">
                    <input type="number" name="warranty_counter_limit" id="{{ $formId }}_counter_limit"
                           value="{{ $v('warranty_counter_limit') }}" placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="{{ $formId }}_counter_limit" class="{{ $lbl }}">Warranty Limit (<span x-text="unit || 'units'"></span>)</label>
                    @error('warranty_counter_limit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Smart Reminder --}}
                <div x-data="{
                        open: {{ $linkedReminder ? 'true' : 'false' }},
                        days: {{ json_encode($srDaysArr) }},
                        inputVal: null,
                        add() {
                            const v = parseInt(this.inputVal);
                            if (v > 0 && !this.days.includes(v)) {
                                this.days.push(v);
                                this.days.sort((a, b) => b - a);
                            }
                            this.inputVal = null;
                        },
                        addPreset(v) {
                            if (!this.days.includes(v)) {
                                this.days.push(v);
                                this.days.sort((a, b) => b - a);
                            }
                        },
                        remove(i) { this.days.splice(i, 1); }
                    }">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between rounded-lg border border-dashed border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-500 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <flux:icon.bell-alert class="size-3.5" />
                            Smart Reminder for this Part Warranty
                            <template x-if="days.length > 0">
                                <span class="ml-1 rounded-full bg-blue-400/10 px-1.5 py-0.5 text-[10px] font-semibold text-blue-400" x-text="days.length + ' threshold' + (days.length > 1 ? 's' : '') + ' set'"></span>
                            </template>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                             class="size-3.5 transition-transform" :class="open ? 'rotate-180' : ''">
                            <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse x-cloak class="mt-2">
                        <input type="hidden" name="sr_reminder_days" :value="days.join(',')">
                        <p class="mb-1.5 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">
                            Remind when within
                            <span class="font-semibold" x-text="'N ' + (unit || 'units') + ' remaining'"></span>
                        </p>
                        <div class="rounded-lg border border-zinc-300 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap gap-1.5 mb-2 min-h-5.5">
                                <template x-for="(day, i) in days" :key="i">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2.5 py-0.5 text-xs font-semibold text-blue-400">
                                        <span x-text="day + ' ' + (unit || 'units') + ' remaining'"></span>
                                        <button type="button" @click="remove(i)"
                                                class="ml-0.5 rounded-full text-blue-400 hover:text-red-400 transition-colors leading-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-3.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                                        </button>
                                    </span>
                                </template>
                                <span x-show="days.length === 0" class="text-[11px] text-zinc-400 italic">No thresholds set</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" x-model.number="inputVal"
                                       @keydown.enter.prevent="add()"
                                       placeholder="e.g. 5000"
                                       class="w-28 rounded-md border border-zinc-300 bg-zinc-50 px-2.5 py-1 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                                <span class="text-xs text-zinc-500" x-text="unit || 'units'"></span>
                                <button type="button" @click="add()"
                                        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Add
                                </button>
                            </div>
                            <div class="mt-1.5 text-[11px] text-zinc-500">
                                Quick add:
                                @foreach ([500, 1000, 2000, 5000, 10000] as $p)
                                    <button type="button" @click="addPreset({{ $p }})"
                                            class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-mono text-zinc-600 hover:bg-blue-100 hover:text-blue-600 transition-colors dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ number_format($p) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @if ($linkedReminder)
                            <p class="mt-1 text-[11px] text-zinc-400">Updating thresholds will update the existing Smart Reminder "{{ $linkedReminder->reminder_name }}".</p>
                        @else
                            <p class="mt-1 text-[11px] text-zinc-400">A Smart Reminder linked to this part warranty will be created automatically when you save.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Notes ── --}}
    <div>
        <p class="{{ $sec }}">Notes</p>
        <div class="relative">
            <input type="text" name="remarks" id="{{ $formId }}_remarks" value="{{ $v('remarks') }}" placeholder=" " class="{{ $inp }}" />
            <label for="{{ $formId }}_remarks" class="{{ $lbl }}">Remarks</label>
            @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ── Document ── --}}
    <div>
        <style>
            .part-doc-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Document</p>
        @php $partDoc = $part?->documents->first(); @endphp
        <p class="mb-1 text-xs text-zinc-500">Warranty Document <span class="font-normal">(PDF / image, max 5 MB)</span></p>
        <div class="part-doc-upload" x-data x-init="initUploadPond($refs.partDoc, {
                acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                @if ($partDoc)
                files: [{ source: '{{ Storage::url($partDoc->file_path) }}', options: { type: 'local' } }],
                fileMetaBySource: { '{{ Storage::url($partDoc->file_path) }}': { name: '{{ addslashes($partDoc->file_original_name) }}' } },
                onremovefile: () => fetch('{{ route('assets.services.parts.documents.destroy', [$asset, $partDoc]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_method=DELETE'
                }),
                @endif
            })">
            <input type="file" name="part_doc" x-ref="partDoc" accept=".pdf,.jpg,.jpeg,.png,.webp" />
        </div>
        @error('part_doc')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>

</div>
