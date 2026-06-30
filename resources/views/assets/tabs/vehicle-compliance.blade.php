@php
    use Illuminate\Support\Facades\Storage;

    $patchUrl  = route('assets.patch-field', $asset);
    $docStore  = route('assets.documents.store', $asset);
    $docRevert = route('assets.documents.revert', $asset);

    $dt     = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
    $dd     = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
    $vInp   = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
    $vBtnOk = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
    $vBtnX  = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
    $vPencil = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
    $vCheck  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
    $vX      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';

    $badge = function (?string $raw): array {
        if (!$raw) return ['label' => 'Not Set',      'cls' => 'bg-zinc-400/10 text-zinc-400 border border-zinc-400/20'];
        $d = \Carbon\Carbon::parse($raw);
        if ($d->isPast())                  return ['label' => 'Expired',       'cls' => 'bg-red-400/10 text-red-400 border border-red-400/20'];
        if ($d->diffInDays(now()) <= 30)   return ['label' => 'Expiring Soon', 'cls' => 'bg-amber-400/10 text-amber-400 border border-amber-400/20'];
        return                                      ['label' => 'Valid',         'cls' => 'bg-green-400/10 text-green-400 border border-green-400/20'];
    };

    $cards = [
        [
            'key'             => 'puc',
            'title'           => 'PUC Certificate',
            'icon'            => 'document-check',
            'desc'            => 'Pollution Under Control certificate.',
            'expiry_field'    => 'puc_expiry_date',
            'expiry_val'      => $asset->puc_expiry_date?->format('d M Y'),
            'expiry_raw'      => $asset->puc_expiry_date?->format('Y-m-d'),
            'compliance_type' => 'puc',
            'doc_type'        => 'puc_copy',
            'doc'             => $asset->documents->where('document_type', 'puc_copy')->sortByDesc('created_at')->first(),
        ],
        [
            'key'             => 'fitness',
            'title'           => 'Fitness Certificate',
            'icon'            => 'clipboard-document-check',
            'desc'            => 'Vehicle fitness certificate issued by RTO.',
            'expiry_field'    => 'fitness_expiry_date',
            'expiry_val'      => $asset->fitness_expiry_date?->format('d M Y'),
            'expiry_raw'      => $asset->fitness_expiry_date?->format('Y-m-d'),
            'compliance_type' => 'fitness',
            'doc_type'        => 'fitness_copy',
            'doc'             => $asset->documents->where('document_type', 'fitness_copy')->sortByDesc('created_at')->first(),
        ],
        [
            'key'             => 'road_tax',
            'title'           => 'Road Tax',
            'icon'            => 'banknotes',
            'desc'            => 'Road tax payment record for this vehicle.',
            'expiry_field'    => 'road_tax_expiry_date',
            'expiry_val'      => $asset->road_tax_expiry_date?->format('d M Y'),
            'expiry_raw'      => $asset->road_tax_expiry_date?->format('Y-m-d'),
            'compliance_type' => 'road_tax',
            'doc_type'        => 'road_tax_copy',
            'doc'             => $asset->documents->where('document_type', 'road_tax_copy')->sortByDesc('created_at')->first(),
        ],
    ];
@endphp

{{-- Doc Lightbox --}}
<div x-data="docLightbox()"
     x-on:keydown.escape.window="close()"
     x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
     x-show="open" style="display:none"
     class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-2.5">
        <p class="truncate text-sm font-medium text-white" x-text="title"></p>
        <button type="button" @click="close()" class="shrink-0 rounded-md p-1 text-white/60 hover:bg-white/10 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        </button>
    </div>
    <div class="flex flex-1 cursor-zoom-out items-center justify-center overflow-hidden p-4" @click.self="close()">
        <template x-if="isPdf"><iframe :src="src" class="h-full w-full max-w-4xl rounded-lg border-0 bg-white" style="min-height:70vh"></iframe></template>
        <template x-if="!isPdf"><img :src="src" :alt="title" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" /></template>
    </div>
</div>

{{-- ── Add Modals (one per compliance type) ── --}}
@foreach ($cards as $card)
    <x-modal name="add-compliance-{{ $card['key'] }}" title="Set {{ $card['title'] }}" max-width="30rem" :dismissible="false"
        :auto-open="$errors->any() && old('_compliance_type') === $card['key']">
        <form method="POST" action="{{ route('assets.compliance.save', [$asset, $card['key']]) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_compliance_type" value="{{ $card['key'] }}" />

            {{-- Expiry Date --}}
            <div x-data x-init="flatpickr($refs.fpAdd_{{ $card['key'] }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })">
                <label class="block text-xs font-medium text-zinc-500 mb-1">Expiry Date</label>
                <input type="text" x-ref="fpAdd_{{ $card['key'] }}" name="expiry_date"
                       value="{{ old('expiry_date', $card['expiry_raw']) }}"
                       class="{{ $vInp }} w-full" placeholder="Select date" />
                @error('expiry_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-compliance-{{ $card['key'] }}')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>
@endforeach

{{-- ── View Modals (one per compliance type) ── --}}
@foreach ($cards as $card)
    @php
        $cardDoc        = $card['doc'];
        $cardKey        = $card['key'];
        $delUrl         = $cardDoc ? route('assets.documents.destroy', [$asset, $cardDoc]) : '';
        $srList         = $asset->smartReminders->where('reminder_type', $card['compliance_type'])->where('is_active', true);
        $addReminderUrl = route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => 1, $card['compliance_type'] => 1]);
    @endphp

    <x-modal name="view-compliance-{{ $cardKey }}" title="{{ $card['title'] }} Details">
        <x-slot:footer>
            <div class="flex items-center gap-2 flex-wrap">
                <flux:icon :icon="$card['icon']" class="size-4 shrink-0 text-zinc-400" />
                <span class="text-xs text-zinc-500">{{ $card['title'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="$dispatch('close-modal-view-compliance-{{ $cardKey }}')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                    <flux:icon.x-mark class="size-3.5" />
                    Close
                </button>
                <button type="button"
                        x-on:click="$dispatch('close-modal-view-compliance-{{ $cardKey }}'); $nextTick(() => $dispatch('open-modal-add-compliance-{{ $cardKey }}'))"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300">
                    <flux:icon.pencil class="size-3.5" />
                    Edit
                </button>
            </div>
        </x-slot:footer>

        <div x-data="{
            expiry: {{ json_encode($card['expiry_val'] ?? '') }},
            async patch(field, value) {
                const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                const r = await fetch({{ json_encode($patchUrl) }}, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': {{ json_encode(csrf_token()) }}, 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: fd,
                });
                if (!r.ok) { toastr.error('Save failed.'); return false; }
                toastr.success('Updated.');
                return true;
            }
        }" class="flex min-h-0 gap-5">

            {{-- Left: fields --}}
            <div class="flex-1 min-w-0 space-y-4">

                <div>
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ $card['title'] }}</p>
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

                        {{-- Expiry Date --}}
                        <div x-data="{ editing: false }"
                             x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpV_{{ $cardKey }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $dt }}">Expiry Date</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $dd }}" x-text="expiry || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $vBtnX }}">{!! $vPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpV_{{ $cardKey }}" class="{{ $vInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $vBtnOk }}"
                                            @click="const raw=$refs.fpV_{{ $cardKey }}._flatpickr?.input?.value||$refs.fpV_{{ $cardKey }}.value; const alt=$refs.fpV_{{ $cardKey }}._flatpickr?.altInput?.value||raw; if(await patch({{ json_encode($card['expiry_field']) }},raw)){expiry=alt;editing=false}">{!! $vCheck !!}</button>
                                        <button type="button" class="{{ $vBtnX }}" @click="editing=false">{!! $vX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- Smart Reminder --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Smart Reminder</p>
                    @if ($srList->isEmpty())
                        <a href="{{ $addReminderUrl }}"
                           class="inline-flex items-center gap-1.5 text-xs text-zinc-400 hover:text-accent transition-colors">
                            <flux:icon.bell-alert class="size-3.5" /> Add Smart Reminder
                        </a>
                    @else
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($srList as $sr)
                                <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders']) }}"
                                   class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-medium text-accent hover:bg-accent/20 transition-colors">
                                    <flux:icon.bell class="size-2.5" />
                                    {{ implode(', ', $sr->reminder_days) }}d
                                </a>
                            @endforeach
                            <a href="{{ $addReminderUrl }}" class="text-[11px] text-zinc-400 hover:text-accent transition-colors">+ Add</a>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right: document upload --}}
            <aside class="w-52 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>
                <div x-data x-init="
                    initUploadPond($el.querySelector('input'), {
                        acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                        labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                            <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                            </div>
                            <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                            <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                        </div>`,
                        files: @js($cardDoc ? [['source' => Storage::url($cardDoc->file_path), 'options' => ['type' => 'local']]] : []),
                        fileMetaBySource: @js($cardDoc ? [Storage::url($cardDoc->file_path) => ['name' => $cardDoc->file_original_name]] : (object)[]),
                        deleteUrl: @js($delUrl),
                        csrfToken: @js(csrf_token()),
                        revertUrlTemplate: () => @js($docRevert),
                        server: {
                            process: {
                                url: @js($docStore),
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                                ondata: (fd) => { fd.append('document_type', @js($card['doc_type'])); return fd; },
                                onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); },
                                onerror: () => toastr.error('Upload failed.'),
                            },
                        },
                    })
                "><input type="file" /></div>
            </aside>

        </div>
    </x-modal>
@endforeach

<div class="space-y-5">

    {{-- Header --}}
    <div>
        <flux:heading class="font-semibold text-zinc-200">Vehicle Compliance</flux:heading>
        <flux:text class="mt-0.5 text-xs text-zinc-500">Manage registration, PUC, Fitness, Road Tax and depreciation for this vehicle.</flux:text>
    </div>

    {{-- ── Registration + Depreciation card ── --}}
    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent"
         x-data="{
            regNum:  {{ json_encode($asset->registration_number ?? '') }},
            obv:     {{ json_encode((string)($asset->vehicle_obv ?? '')) }},
            deprPct: {{ json_encode((string)($asset->vehicle_depreciation_percent ?? '')) }},
            bookVal: {{ json_encode((string)($asset->vehicle_depreciation_book_value ?? '')) }},
            async patch(field, value) {
                const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                const r = await fetch({{ json_encode($patchUrl) }}, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': {{ json_encode(csrf_token()) }}, 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: fd,
                });
                if (!r.ok) { toastr.error('Save failed.'); return false; }
                toastr.success('Updated.');
                return true;
            }
         }">
        <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Vehicle Details</p>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">

            <div class="sm:col-span-2 lg:col-span-4" x-data="{ editing: false }">
                <dt class="{{ $dt }}">Registration Number</dt>
                <dd class="mt-0.5 flex items-center gap-1.5">
                    <span x-show="!editing" class="font-mono text-sm font-semibold uppercase text-zinc-800 dark:text-zinc-200" x-text="regNum || '--'"></span>
                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $vBtnX }}">{!! $vPencil !!}</button>
                    <template x-if="editing">
                        <span class="flex items-center gap-1">
                            <input type="text" x-ref="inpReg" class="{{ $vInp }} w-36 uppercase" :value="regNum" maxlength="20"
                                   x-init="$nextTick(() => $refs.inpReg?.focus())" />
                            <button type="button" class="{{ $vBtnOk }}"
                                @click="if(await patch('registration_number',$refs.inpReg.value)){regNum=$refs.inpReg.value.toUpperCase();editing=false}">{!! $vCheck !!}</button>
                            <button type="button" class="{{ $vBtnX }}" @click="editing=false">{!! $vX !!}</button>
                        </span>
                    </template>
                </dd>
            </div>

            <div x-data="{ editing: false }">
                <dt class="{{ $dt }}">Original Book Value (OBV)</dt>
                <dd class="mt-0.5 flex items-center gap-1.5">
                    <span x-show="!editing" class="{{ $dd }}" x-text="obv ? '₹ ' + Number(obv).toLocaleString('en-IN', {minimumFractionDigits:2}) : '--'"></span>
                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $vBtnX }}">{!! $vPencil !!}</button>
                    <template x-if="editing">
                        <span class="flex items-center gap-1">
                            <input type="number" x-ref="inpObv" class="{{ $vInp }} w-28" :value="obv" min="0" step="0.01" />
                            <button type="button" class="{{ $vBtnOk }}"
                                @click="if(await patch('vehicle_obv',$refs.inpObv.value)){obv=$refs.inpObv.value;editing=false}">{!! $vCheck !!}</button>
                            <button type="button" class="{{ $vBtnX }}" @click="editing=false">{!! $vX !!}</button>
                        </span>
                    </template>
                </dd>
            </div>

            <div x-data="{ editing: false }">
                <dt class="{{ $dt }}">Depreciation %</dt>
                <dd class="mt-0.5 flex items-center gap-1.5">
                    <span x-show="!editing" class="{{ $dd }}" x-text="deprPct ? deprPct + '%' : '--'"></span>
                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $vBtnX }}">{!! $vPencil !!}</button>
                    <template x-if="editing">
                        <span class="flex items-center gap-1">
                            <input type="number" x-ref="inpDep" class="{{ $vInp }} w-20" :value="deprPct" min="0" max="100" step="0.01" />
                            <button type="button" class="{{ $vBtnOk }}"
                                @click="if(await patch('vehicle_depreciation_percent',$refs.inpDep.value)){deprPct=$refs.inpDep.value;editing=false}">{!! $vCheck !!}</button>
                            <button type="button" class="{{ $vBtnX }}" @click="editing=false">{!! $vX !!}</button>
                        </span>
                    </template>
                </dd>
            </div>

            <div x-data="{ editing: false }">
                <dt class="{{ $dt }}">Book Value</dt>
                <dd class="mt-0.5 flex items-center gap-1.5">
                    <span x-show="!editing" class="{{ $dd }}" x-text="bookVal ? '₹ ' + Number(bookVal).toLocaleString('en-IN', {minimumFractionDigits:2}) : '--'"></span>
                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $vBtnX }}">{!! $vPencil !!}</button>
                    <template x-if="editing">
                        <span class="flex items-center gap-1">
                            <input type="number" x-ref="inpBook" class="{{ $vInp }} w-28" :value="bookVal" min="0" step="0.01" />
                            <button type="button" class="{{ $vBtnOk }}"
                                @click="if(await patch('vehicle_depreciation_book_value',$refs.inpBook.value)){bookVal=$refs.inpBook.value;editing=false}">{!! $vCheck !!}</button>
                            <button type="button" class="{{ $vBtnX }}" @click="editing=false">{!! $vX !!}</button>
                        </span>
                    </template>
                </dd>
            </div>

        </dl>
    </div>

    {{-- ── PUC / Fitness / Road Tax 3-column grid ── --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($cards as $card)
            @php
                $b              = $badge($card['expiry_raw']);
                $cardKey        = $card['key'];
                $hasData        = (bool) $card['expiry_raw'];
                $cardSrList     = $asset->smartReminders->where('reminder_type', $card['compliance_type'])->where('is_active', true);
                $addReminderUrl = route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => 1, $card['compliance_type'] => 1]);
            @endphp

            @if (! $hasData)
                {{-- Empty state card --}}
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                    <flux:icon :icon="$card['icon']" class="mx-auto size-10 text-zinc-600" />
                    <flux:heading class="mt-4 text-zinc-400">{{ $card['title'] }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600">{{ $card['desc'] }}</flux:text>
                    <div class="mt-4">
                        <button type="button" x-on:click="$dispatch('open-modal-add-compliance-{{ $cardKey }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                            Set {{ $card['title'] }}
                        </button>
                    </div>
                </div>

            @else
                {{-- Data state card --}}
                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">

                    {{-- Card Header --}}
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="flex items-center gap-2">
                            <flux:icon :icon="$card['icon']" class="size-4 shrink-0 text-zinc-400" />
                            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $card['title'] }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $b['cls'] }}">{{ $b['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ $addReminderUrl }}"
                               title="{{ $cardSrList->isNotEmpty() ? 'Manage Smart Reminders' : 'Add Smart Reminder' }}"
                               class="inline-flex size-6 items-center justify-center rounded-md border transition-colors {{ $cardSrList->isNotEmpty() ? 'border-blue-500/40 text-blue-400 hover:bg-blue-500/10' : 'border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/10' }}">
                                <flux:icon.bell-alert class="size-3.5" />
                            </a>
                            <button type="button"
                                    x-on:click="$dispatch('open-modal-view-compliance-{{ $cardKey }}')"
                                    aria-label="View details"
                                    title="View details"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                <flux:icon.eye class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="px-4 py-3 space-y-2">
                        <div>
                            <p class="{{ $dt }}">Expiry Date</p>
                            <p class="{{ $dd }}">{{ $card['expiry_val'] }}</p>
                        </div>
                        @if ($cardSrList->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                @foreach ($cardSrList as $sr)
                                    <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders']) }}"
                                       class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-medium text-accent hover:bg-accent/20 transition-colors">
                                        <flux:icon.bell class="size-2.5" />{{ implode(', ', $sr->reminder_days) }}d
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        @if ($card['doc'])
                            <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50 mt-2">
                                <flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />
                                <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $card['doc']->file_original_name }}</p>
                                <button type="button"
                                    x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($card['doc']->file_path) }}', title: '{{ addslashes($card['doc']->file_original_name) }}', isPdf: {{ $card['doc']->isImage() ? 'false' : 'true' }} })"
                                    class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                    <flux:icon.eye class="size-3" />
                                </button>
                            </div>
                        @endif
                    </div>

                </div>
            @endif

        @endforeach
    </div>

</div>
