@php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$warranties  = $asset->warranties->sortBy([['scope', 'asc'], ['warranty_type', 'asc'], ['id', 'asc']]);
$overallWars = $warranties->where('scope', 'overall');
$partWars    = $warranties->where('scope', 'part');
$prefillPart = request('prefill_part');

$cal = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';

// Shared classes
$dt = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$dd = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
@endphp


{{-- Lightbox overlay --}}
<div x-data="docLightbox()"
     x-on:keydown.escape.window="close()"
     x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
     x-show="open" style="display:none"
     class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm">
    <div class="flex shrink-0 items-center justify-between px-4 py-3">
        <span x-text="title" class="truncate text-sm font-medium text-white"></span>
        <button type="button" x-on:click="close()" class="ml-4 shrink-0 rounded p-1 text-white/70 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="min-h-0 flex-1 overflow-auto flex items-center justify-center p-4">
        <template x-if="isPdf">
            <iframe :src="src" class="h-full w-full rounded" style="min-height:70vh"></iframe>
        </template>
        <template x-if="!isPdf">
            <img :src="src" :alt="title" class="max-h-full max-w-full rounded object-contain" />
        </template>
    </div>
</div>

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Warranties</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">Product warranties for this asset</flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-warranty')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
                <path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/>
            </svg>
            Add Warranty
        </button>
    </div>

    {{-- Add Warranty Modal --}}
    <x-modal name="add-warranty" title="Add Warranty Entry" :dismissible="false"
        :auto-open="($errors->any() && old('_form') === 'add-warranty') || $prefillPart !== null">
        <form method="POST" action="{{ route('assets.warranties.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="add-warranty">
            @if ($prefillPart)
                <div class="rounded-lg bg-accent/10 border border-accent/30 px-3 py-2 text-xs text-accent">
                    Creating replacement warranty for part: <strong>{{ $prefillPart }}</strong>
                </div>
            @endif
            @include('assets.tabs._warranty-entry-form', [
                'warranty'     => null,
                'asset'        => $asset,
                'defaultType'  => 'original',
                'defaultScope' => $prefillPart ? 'part' : 'overall',
                'prefillPart'  => $prefillPart,
            ])
            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Warranty</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-warranty')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
            </div>
        </form>
    </x-modal>

    {{-- Per-warranty modals --}}
    @foreach ($warranties as $w)
        <x-modal name="edit-warranty-{{ $w->id }}" title="Edit Warranty" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'edit-warranty-' . $w->id">
            <form method="POST" action="{{ route('assets.warranties.update', [$asset, $w]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="edit-warranty-{{ $w->id }}">
                @include('assets.tabs._warranty-entry-form', ['warranty' => $w, 'asset' => $asset])
                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-warranty-{{ $w->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
                </div>
            </form>
        </x-modal>

        @if ($w->isActive())
            <x-modal name="dispose-warranty-{{ $w->id }}" title="Dispose / Retire Warranty" :dismissible="false">
                <form method="POST" action="{{ route('assets.warranties.dispose', [$asset, $w]) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    @php
                        $inp2 = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
                        $lbl2 = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
                    @endphp
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Mark this warranty as <strong>disposed/retired</strong>. It stays in history and stops triggering reminders.</p>
                    <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
                        <div class="relative w-full">
                            <input type="text" inputmode="none" name="disposed_at" placeholder=" " autocomplete="off"
                                   value="{{ now()->format('Y-m-d') }}" class="{{ $inp2 }} pr-9" />
                            <label class="{{ $lbl2 }}">Disposal / Replacement Date</label>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
                        </div>
                    </div>
                    <div class="relative">
                        <input type="text" name="disposed_reason" placeholder=" " maxlength="255" class="{{ $inp2 }}" />
                        <label class="{{ $lbl2 }}">Reason (e.g. Part replaced, Sold)</label>
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="danger" size="sm">Confirm Dispose</flux:button>
                        <button type="button" x-on:click="$dispatch('close-modal-dispose-warranty-{{ $w->id }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancel</button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach

    {{-- View Modals --}}
    @foreach ($warranties as $w)
        @php
            $vBadge   = $w->statusBadge();
            $vBadgeCls = match($vBadge) { 'expired' => 'bg-red-400/10 text-red-400', 'soon' => 'bg-yellow-400/10 text-yellow-400', 'disposed' => 'bg-zinc-400/10 text-zinc-400', default => 'bg-green-400/10 text-green-400' };
            $vBadgeLbl = match($vBadge) { 'expired' => 'Expired', 'soon' => 'Expiring Soon', 'disposed' => 'Disposed', default => 'Active' };
            $vExpiry   = $w->isTimeBased() ? ($w->expiry_date?->format('d M Y') ?? '—') : ($w->counter_limit ? number_format($w->counter_limit).' '.$w->unitLabel() : '—');
            $vExpiryLbl = $w->isTimeBased() ? 'Expiry Date' : 'Warranty Limit';
            $vExpiryCls = $vBadge === 'expired' ? 'text-sm font-semibold text-red-400' : ($vBadge === 'soon' ? 'text-sm text-yellow-400' : 'text-sm text-zinc-800 dark:text-zinc-100');
        @endphp
        <x-modal name="view-warranty-{{ $w->id }}" title="Warranty Details">
            <div class="space-y-5">
                {{-- Header row --}}
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                        <span class="rounded-full {{ $w->warranty_type === 'original' ? 'bg-blue-400/10 text-blue-400' : 'bg-purple-400/10 text-purple-400' }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $w->warrantyTypeLabel() }}</span>
                        @if ($w->scope === 'part')
                            <span class="rounded-full bg-orange-400/10 text-orange-400 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">Part</span>
                            @if ($w->part_name)
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $w->part_name }}</span>
                                @if ($w->part_serial_number)<span class="text-xs text-zinc-400">· {{ $w->part_serial_number }}</span>@endif
                            @endif
                        @endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $vBadgeCls }}">{{ $vBadgeLbl }}</span>
                </div>

                {{-- Detail grid --}}
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">{{ $vExpiryLbl }}</dt>
                        <dd class="mt-0.5 {{ $vExpiryCls }}">{{ $vExpiry }}</dd>
                    </div>
                    @if ($w->date_from)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">From</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->date_from->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if ($w->vendorRecord || $w->vendor)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Vendor</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                                @if ($w->vendorRecord)
                                    <a href="{{ route('vendors.show', $w->vendorRecord) }}" wire:navigate class="text-accent hover:underline">{{ $w->vendorRecord->name }}</a>
                                @else
                                    {{ $w->vendor }}
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($w->bill_no)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Bill No.</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->bill_no }}</dd>
                        </div>
                    @endif
                    @if ($w->bill_amount)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Amount</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">₹{{ number_format($w->bill_amount, 2) }}</dd>
                        </div>
                    @endif
                    @if ($w->smartReminders->isNotEmpty())
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Smart Reminders</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->smartReminders->count() }} {{ Str::plural('reminder', $w->smartReminders->count()) }}</dd>
                        </div>
                    @endif
                    @if ($w->isDisposed() && $w->disposed_at)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Disposed</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $w->disposed_at->format('d M Y') }}@if($w->disposed_reason) — {{ $w->disposed_reason }}@endif</dd>
                        </div>
                    @endif
                    @if ($w->details)
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs font-medium text-zinc-500">Details</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $w->details }}</dd>
                        </div>
                    @endif
                    @if ($w->terms)
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs font-medium text-zinc-500">Terms</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $w->terms }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Documents --}}
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                    @if ($w->documents->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($w->documents as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-xs text-accent hover:underline">Open</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No documents attached.</p>
                    @endif
                </div>
            </div>
        </x-modal>
    @endforeach

    {{-- ── WARRANTIES ── --}}
    <div>
        @if ($warranties->isEmpty())
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                    <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
                    <flux:heading class="mt-4 text-zinc-400">No Warranties</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600">Record manufacturer or supplier warranties for this asset.</flux:text>
                    <div class="mt-4">
                        <button type="button" x-on:click="$dispatch('open-modal-add-warranty')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                            Add Warranty
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($warranties as $w)
                    @php
                        $badge      = $w->statusBadge();
                        $isDisposed = $w->isDisposed();
                        $badgeClass = match($badge) { 'expired' => 'bg-red-400/10 text-red-400', 'soon' => 'bg-yellow-400/10 text-yellow-400', 'disposed' => 'bg-zinc-400/10 text-zinc-400', default => 'bg-green-400/10 text-green-400' };
                        $badgeLabel = match($badge) { 'expired' => 'Expired', 'soon' => 'Expiring Soon', 'disposed' => 'Disposed', default => 'Active' };
                        $expiryVal  = $w->isTimeBased() ? ($w->expiry_date?->format('d M Y') ?? '—') : ($w->counter_limit ? number_format($w->counter_limit).' '.$w->unitLabel() : '—');
                        $expiryLbl  = $w->isTimeBased() ? 'Expiry Date' : 'Warranty Limit';
                        $expiryClass = $badge === 'expired' ? 'mt-0.5 text-sm font-semibold text-red-400' : ($badge === 'soon' ? 'mt-0.5 text-sm text-yellow-400' : $dd);
                        $replacementUrl = ($isDisposed && $w->scope === 'part') ? route('assets.show', [$asset, 'tab' => 'warranty', 'prefill_part' => $w->part_name]) : null;
                    @endphp
                    <div class="rounded-xl border {{ $isDisposed ? 'border-zinc-700/50 opacity-60' : 'border-zinc-200 dark:border-zinc-800' }} bg-white dark:bg-zinc-900 overflow-hidden flex flex-col">
                        {{-- Card header: type/scope badges + part name + actions --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 {{ $isDisposed ? 'bg-zinc-800/20' : 'bg-zinc-50 dark:bg-zinc-800/40' }}">
                            <div class="flex flex-col gap-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                                    <span class="rounded-full {{ $w->warranty_type === 'original' ? 'bg-blue-400/10 text-blue-400' : 'bg-purple-400/10 text-purple-400' }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $w->warrantyTypeLabel() }}</span>
                                    @if ($w->scope === 'part')
                                        <span class="rounded-full bg-orange-400/10 text-orange-400 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">Part</span>
                                    @endif
                                    @if ($w->scope === 'part' && $w->part_name)
                                        <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">{{ $w->part_name }}</span>
                                        @if ($w->part_serial_number)<span class="text-[11px] text-zinc-400">· {{ $w->part_serial_number }}</span>@endif
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button type="button"
                                        x-on:click="$dispatch('open-modal-view-warranty-{{ $w->id }}')"
                                        aria-label="View warranty details"
                                        title="View warranty details"
                                        class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                    <flux:icon.eye class="size-3.5" />
                                </button>
                                @if (! $isDisposed)
                                    <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'warrantyid' => $w->id]) }}"
                                       title="{{ $w->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                                       class="inline-flex size-6 items-center justify-center rounded-md border transition-colors {{ $w->smartReminders->isNotEmpty() ? 'border-blue-500/40 text-blue-400 hover:bg-blue-500/10' : 'border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/10' }}">
                                        <flux:icon.bell-alert class="size-3.5" />
                                    </a>
                                    <button type="button" x-on:click="$dispatch('open-modal-edit-warranty-{{ $w->id }}')" title="Edit" class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300"><flux:icon.pencil class="size-3.5" /></button>
                                    <button type="button" x-on:click="$dispatch('open-modal-dispose-warranty-{{ $w->id }}')" title="Dispose" class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-yellow-500/60 hover:text-yellow-400 dark:border-zinc-700"><flux:icon.archive-box-x-mark class="size-3.5" /></button>
                                @elseif ($replacementUrl)
                                    <a href="{{ $replacementUrl }}" class="inline-flex items-center gap-1 rounded-lg border border-accent/40 px-2 py-0.5 text-[11px] font-medium text-accent hover:bg-accent/10 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                                        Create Replacement
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('assets.warranties.destroy', [$asset, $w]) }}" onsubmit="return confirm('Delete this warranty entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete" class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700"><flux:icon.trash class="size-3.5" /></button>
                                </form>
                            </div>
                        </div>

                        {{-- Card body: vendor + detail grid + details/terms + documents --}}
                        @if (! $isDisposed)
                            <div class="flex-1 px-4 py-3 space-y-3">
                                {{-- Vendor --}}
                                @if ($w->vendorRecord || $w->vendor)
                                    <div>
                                        <p class="{{ $dt }}">Vendor</p>
                                        <p class="{{ $dd }}">
                                            @if ($w->vendorRecord)
                                                <a href="{{ route('vendors.show', $w->vendorRecord) }}" wire:navigate class="text-accent hover:underline">{{ $w->vendorRecord->name }}</a>
                                            @else
                                                {{ $w->vendor }}
                                            @endif
                                        </p>
                                    </div>
                                @endif

                                {{-- Detail grid --}}
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-4">
                                    <div>
                                        <dt class="{{ $dt }}">{{ $expiryLbl }}</dt>
                                        <dd class="{{ $expiryClass }}">{{ $expiryVal }}</dd>
                                    </div>
                                    @if ($w->date_from)
                                        <div><dt class="{{ $dt }}">From</dt><dd class="{{ $dd }}">{{ $w->date_from->format('d M Y') }}</dd></div>
                                    @endif
                                    @if ($w->smartReminders->isNotEmpty())
                                        <div>
                                            <dt class="{{ $dt }}">Smart Reminders</dt>
                                            <dd class="{{ $dd }}">{{ $w->smartReminders->count() }} {{ Str::plural('reminder', $w->smartReminders->count()) }}</dd>
                                        </div>
                                    @endif
                                    @if (! $w->isTimeBased())
                                        @php
                                            $cur       = $w->latestCounter();
                                            $remaining = $w->remainingUnits();
                                        @endphp
                                        @if ($cur !== null)
                                            <div>
                                                <dt class="{{ $dt }}">Current Reading</dt>
                                                <dd class="{{ $dd }}">{{ number_format($cur) }} {{ $w->unitLabel() }}</dd>
                                            </div>
                                        @endif
                                        @if ($remaining !== null)
                                            <div>
                                                <dt class="{{ $dt }}">Remaining</dt>
                                                <dd class="mt-0.5 text-sm font-semibold {{ $remaining <= ($srThreshold ?? 0) ? 'text-yellow-400' : ($remaining === 0 ? 'text-red-400' : 'text-green-400') }}">
                                                    {{ number_format($remaining) }} {{ $w->unitLabel() }}
                                                </dd>
                                            </div>
                                        @elseif (! $w->isTimeBased() && $cur === null && $w->unit)
                                            <div>
                                                <dt class="{{ $dt }}">Current Reading</dt>
                                                <dd class="mt-0.5 text-xs text-zinc-400">No meter logs for {{ $w->unit }} yet</dd>
                                            </div>
                                        @endif
                                    @endif
                                    @if ($w->bill_no)
                                        <div><dt class="{{ $dt }}">Bill No.</dt><dd class="{{ $dd }}">{{ $w->bill_no }}</dd></div>
                                    @endif
                                    @if ($w->bill_amount)
                                        <div><dt class="{{ $dt }}">Amount</dt><dd class="{{ $dd }}">₹{{ number_format($w->bill_amount, 2) }}</dd></div>
                                    @endif
                                </dl>

                                {{-- Details & Terms --}}
                                @if ($w->details || $w->terms)
                                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @if ($w->details)
                                            <div><p class="{{ $dt }}">Details</p><p class="mt-0.5 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->details }}</p></div>
                                        @endif
                                        @if ($w->terms)
                                            <div><p class="{{ $dt }}">Terms</p><p class="mt-0.5 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->terms }}</p></div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Documents --}}
                                @if ($w->documents->isNotEmpty())
                                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-3">
                                        <p class="mb-1.5 {{ $dt }}">Documents</p>
                                        <div class="space-y-1">
                                            @foreach ($w->documents as $doc)
                                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                                    <button type="button"
                                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                                        title="View"
                                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                                        <flux:icon.eye class="size-3" />
                                                    </button>
                                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                                        title="Download"
                                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                                        <flux:icon.arrow-down-tray class="size-3" />
                                                    </a>
                                                    <form method="POST" action="{{ route('assets.warranties.documents.destroy', [$asset, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700"><flux:icon.trash class="size-3" /></button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Card footer: status badge --}}
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 px-4 py-2">
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            @if ($isDisposed && $w->disposed_at)
                                <span class="text-[11px] text-zinc-500">Disposed {{ $w->disposed_at->format('d M Y') }}@if($w->disposed_reason) — {{ $w->disposed_reason }}@endif</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>


</div>
