@php
use Illuminate\Support\Facades\Storage;
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
$cal = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
@endphp

<div class="space-y-4"
     x-data="{
         mode: '{{ old('warranty_tracking_mode', $asset->warranty_tracking_mode ?? 'time') }}',
         src:  '{{ old('warranty_meter_source',  $asset->warranty_meter_source  ?? 'meter') }}',
         unit: '{{ old('warranty_unit', $asset->warranty_unit ?? '') }}'
     }">

    {{-- Hidden inputs --}}
    <input type="hidden" name="warranty_tracking_mode" :value="mode">
    <input type="hidden" name="warranty_meter_source" :value="src">

    {{-- ── Warranty Type ── --}}
    <div>
        <p class="{{ $sec }}">Warranty Type</p>
        <div class="flex gap-2">
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
        <p class="mt-1 text-[11px] text-zinc-400">
            <span x-show="mode === 'time'">Expires on a specific date (e.g. ACs, IT equipment, appliances)</span>
            <span x-show="mode === 'meter'" style="display:none">Expires at a distance/hours reading (e.g. vehicles km, generator hours)</span>
            <span x-show="mode === 'count'" style="display:none">Expires after a usage count (e.g. printer pages, battery cycles)</span>
        </p>
    </div>

    {{-- ── Details ── --}}
    <div>
        <p class="{{ $sec }}">Details</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- Warranty Details (always visible) --}}
            <div class="relative col-span-2 sm:col-span-4">
                <textarea name="warranty_details" id="warranty_details" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ old('warranty_details', $asset->warranty_details) }}</textarea>
                <label for="warranty_details" class="{{ $lbl }}">Warranty Details</label>
                @error('warranty_details')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            {{-- Date-based fields --}}
            <div x-show="mode === 'time'" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                    <div class="relative w-full">
                        <input type="text" inputmode="none" name="warranty_lapse_date" id="warranty_lapse_date"
                               value="{{ $asset->warranty_lapse_date?->format('Y-m-d') ?? old('warranty_lapse_date') }}"
                               placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                        <label for="warranty_lapse_date" class="{{ $lbl }}">Warranty Lapse Date</label>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                    </div>
                    @error('warranty_lapse_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <input type="number" name="warranty_reminder_before_days" id="warranty_reminder_before_days"
                           value="{{ old('warranty_reminder_before_days', $asset->warranty_reminder_before_days) }}"
                           placeholder=" " min="1" max="365" class="{{ $inp }}" />
                    <label for="warranty_reminder_before_days" class="{{ $lbl }}">Reminder (days before)</label>
                    @error('warranty_reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Counter-based fields (meter or count) --}}
            <div x-show="mode !== 'time'" style="display:none" class="col-span-2 sm:col-span-4 grid grid-cols-2 gap-3 sm:grid-cols-4">

                {{-- Unit label --}}
                <div class="relative">
                    <input type="text" name="warranty_unit" id="warranty_unit"
                           x-model="unit"
                           value="{{ old('warranty_unit', $asset->warranty_unit) }}"
                           placeholder=" " maxlength="20" class="{{ $inp }}" />
                    <label for="warranty_unit" class="{{ $lbl }}">Unit (e.g. km, hours, prints)</label>
                    @error('warranty_unit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Meter source — only for 'meter' mode --}}
                <div x-show="mode === 'meter'" style="display:none" class="relative">
                    <select x-model="src" class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent">
                        <option value="mileage">Odometer / Distance (mileage)</option>
                        <option value="meter">Hour meter / Operating hours</option>
                    </select>
                    <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Reading Source</label>
                </div>

                {{-- Warranty counter limit --}}
                <div class="relative">
                    <input type="number" name="warranty_counter_limit" id="warranty_counter_limit"
                           value="{{ old('warranty_counter_limit', $asset->warranty_counter_limit) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="warranty_counter_limit" class="{{ $lbl }}">Warranty Limit (<span x-text="unit || 'units'"></span>)</label>
                    @error('warranty_counter_limit')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Reminder before units --}}
                <div class="relative">
                    <input type="number" name="warranty_reminder_before_units" id="warranty_reminder_before_units"
                           value="{{ old('warranty_reminder_before_units', $asset->warranty_reminder_before_units) }}"
                           placeholder=" " min="1" class="{{ $inp }}" />
                    <label for="warranty_reminder_before_units" class="{{ $lbl }}">Remind when within (<span x-text="unit || 'units'"></span>)</label>
                    @error('warranty_reminder_before_units')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                {{-- Current reading (read-only info) --}}
                @php $currentCounter = $asset->latestWarrantyCounter(); @endphp
                @if ($currentCounter !== null)
                    <div class="col-span-2 sm:col-span-4 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        Current reading from last service: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($currentCounter) }} {{ $asset->warrantyUnitLabel() }}</span>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ── Documents ── --}}
    <div>
        <style>
            .warranty-doc-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Documents</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {{-- Warranty Card --}}
            <div>
                <p class="mb-1 text-xs text-zinc-500">Warranty Card <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $warrantyCard = $asset->documents->where('document_type', 'warranty_card')->last(); @endphp
                <div class="warranty-doc-upload" x-data x-init="initUploadPond($refs.warrantyCard, {
                        acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        @if ($warrantyCard)
                        files: [{ source: '{{ Storage::url($warrantyCard->file_path) }}', options: { type: 'local' } }],
                        fileMetaBySource: { '{{ Storage::url($warrantyCard->file_path) }}': { name: '{{ addslashes($warrantyCard->file_original_name) }}' } },
                        onremovefile: () => fetch('{{ route('assets.documents.destroy', [$asset, $warrantyCard]) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: '_method=DELETE'
                        }),
                        @endif
                    })">
                    <input type="file" name="warranty_card" x-ref="warrantyCard" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                </div>
                @error('warranty_card')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            {{-- Activation Image --}}
            <div>
                <p class="mb-1 text-xs text-zinc-500">Activation Image <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $activationImg = $asset->documents->where('document_type', 'warranty_activation_image')->last(); @endphp
                <div class="warranty-doc-upload" x-data x-init="initUploadPond($refs.activationImage, {
                        acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        @if ($activationImg)
                        files: [{ source: '{{ Storage::url($activationImg->file_path) }}', options: { type: 'local' } }],
                        fileMetaBySource: { '{{ Storage::url($activationImg->file_path) }}': { name: '{{ addslashes($activationImg->file_original_name) }}' } },
                        onremovefile: () => fetch('{{ route('assets.documents.destroy', [$asset, $activationImg]) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: '_method=DELETE'
                        }),
                        @endif
                    })">
                    <input type="file" name="warranty_activation_image" x-ref="activationImage" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                </div>
                @error('warranty_activation_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>
