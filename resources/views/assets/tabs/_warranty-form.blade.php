@php
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div x-data x-init="
    $nextTick(() => {
        flatpickr($el.querySelector('[name=\'warranty_lapse_date\']'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true });
    });
" class="space-y-4">

    {{-- ── Details ── --}}
    <div>
        <p class="{{ $sec }}">Details</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="relative col-span-2 sm:col-span-4">
                <textarea name="warranty_details" id="warranty_details" rows="2"
                          placeholder=" " class="{{ $txa }}">{{ old('warranty_details', $asset->warranty_details) }}</textarea>
                <label for="warranty_details" class="{{ $lbl }}">Warranty Details</label>
                @error('warranty_details')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-date-picker name="warranty_lapse_date" label="Warranty Lapse Date"
                               value="{{ $asset->warranty_lapse_date?->format('Y-m-d') ?? old('warranty_lapse_date') }}" />
                @error('warranty_lapse_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="number" name="warranty_reminder_before_days" id="warranty_reminder_before_days"
                       value="{{ old('warranty_reminder_before_days', $asset->warranty_reminder_before_days) }}"
                       placeholder=" " min="1" max="365" class="{{ $inp }}" />
                <label for="warranty_reminder_before_days" class="{{ $lbl }}">Reminder (days)</label>
                @error('warranty_reminder_before_days')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Documents ── --}}
    <div>
        <p class="{{ $sec }}">Documents</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <p class="mb-1 text-xs text-zinc-500">Warranty Card <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $warrantyCard = $asset->documents->where('document_type', 'warranty_card')->last(); @endphp
                @if ($warrantyCard)
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                        <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $warrantyCard->file_original_name }}</span>
                        <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
                    </div>
                @endif
                <input type="file" name="warranty_card" accept=".pdf,.jpg,.jpeg,.png,.webp"
                       class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                              file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                              hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                @error('warranty_card')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <p class="mb-1 text-xs text-zinc-500">Activation Image <span class="font-normal">(PDF / image, max 5 MB)</span></p>
                @php $activationImg = $asset->documents->where('document_type', 'warranty_activation_image')->last(); @endphp
                @if ($activationImg)
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-3.5 shrink-0 text-zinc-400" />
                        <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $activationImg->file_original_name }}</span>
                        <span class="ml-auto shrink-0 text-zinc-400">Upload new to replace</span>
                    </div>
                @endif
                <input type="file" name="warranty_activation_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
                       class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-700
                              file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-700
                              hover:file:bg-zinc-200 focus:outline-none focus:ring-1 focus:ring-accent
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600" />
                @error('warranty_activation_image')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>
