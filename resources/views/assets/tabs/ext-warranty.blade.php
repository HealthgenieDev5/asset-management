@php
    use Illuminate\Support\Facades\Storage;
    $ew = $asset->extendedWarranties->first();
@endphp

{{-- Lightbox overlay --}}
<div x-data="docLightbox()"
     x-on:keydown.escape.window="close()"
     x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
     x-show="open" style="display:none"
     class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-2.5">
        <p class="truncate text-sm font-medium text-white" x-text="title"></p>
        <button type="button" @click="close()"
                class="shrink-0 rounded-md p-1 text-white/60 hover:bg-white/10 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        </button>
    </div>
    <div class="flex flex-1 cursor-zoom-out items-center justify-center overflow-hidden p-4" @click.self="close()">
        <template x-if="isPdf">
            <iframe :src="src" class="h-full w-full max-w-4xl rounded-lg border-0 bg-white" style="min-height:70vh"></iframe>
        </template>
        <template x-if="!isPdf">
            <img :src="src" :alt="title" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
        </template>
    </div>
</div>

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Extended Warranty</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">
                {{ $ew ? 'Warranty recorded' : 'No warranty recorded' }}
            </flux:text>
        </div>
        @if (! $ew)
            <button type="button" x-on:click="$dispatch('open-modal-add-ext-warranty')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Add Extended Warranty
            </button>
        @endif
    </div>

    {{-- Add Modal (only when no existing EW) --}}
    @if (! $ew)
        <x-modal name="add-ext-warranty" title="New Extended Warranty" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'ext-warranty' && !old('_ew_id')">
            <form method="POST" action="{{ route('assets.ext-warranty.store', $asset) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="_form" value="ext-warranty">

                @include('assets.tabs._ext-warranty-form', ['ew' => null, 'asset' => $asset])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Warranty</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-add-ext-warranty')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Empty state in grid --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">No Extended Warranty</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">No extended warranty has been recorded for this asset.</flux:text>
                <div class="mt-4">
                    <button type="button" x-on:click="$dispatch('open-modal-add-ext-warranty')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Add Extended Warranty
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if ($ew)
        <x-modal name="edit-ext-warranty" title="Edit Extended Warranty" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'ext-warranty' && (int) old('_ew_id') === $ew->id">
            <form method="POST" action="{{ route('assets.ext-warranty.update', [$asset, $ew]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="ext-warranty">
                <input type="hidden" name="_ew_id" value="{{ $ew->id }}">

                @include('assets.tabs._ext-warranty-form', ['ew' => $ew, 'asset' => $asset])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-ext-warranty')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- View Modal --}}
        @php
            // Use the EW's own independent tracking mode
            $ewMode  = $ew->ewTrackingMode();
            $ewUnit  = $ew->ewUnitLabel();
            $ewCounter = $ew->latestCounter();

            $expired = $ewMode === 'time' && $ew->extended_warranty_date_to && $ew->extended_warranty_date_to->isPast();
            $days    = $ewMode === 'time' && $ew->extended_warranty_date_to
                         ? (int) now()->startOfDay()->diffInDays($ew->extended_warranty_date_to->startOfDay(), false)
                         : null;
            $soon    = ! $expired && $days !== null && $days <= 30;

            $ewCounterExpired   = $ewMode !== 'time' && $ewCounter !== null && $ew->extended_warranty_counter_limit !== null
                                   && $ewCounter >= $ew->extended_warranty_counter_limit;
            $ewCounterRemaining = ($ewMode !== 'time' && $ew->extended_warranty_counter_limit !== null && $ewCounter !== null)
                                   ? max(0, $ew->extended_warranty_counter_limit - $ewCounter)
                                   : null;
            $ewCounterSoon      = ! $ewCounterExpired && $ewCounterRemaining !== null
                                   && $ew->extended_warranty_reminder_before_units !== null
                                   && $ewCounterRemaining <= $ew->extended_warranty_reminder_before_units;

            $ewIsExpired = $expired || $ewCounterExpired;
            $ewIsSoon    = ! $ewIsExpired && ($soon || $ewCounterSoon);
            $ewIsActive  = ! $ewIsExpired && ($days !== null || $ewCounterRemaining !== null);
            $ewFirstDoc  = $ew->documents->first();
            $ewExtraDocs = $ew->documents->skip(1);
        @endphp
        <x-modal name="view-ext-warranty" title="Extended Warranty Details">
            <div class="flex gap-6">
                <div class="min-w-0 flex-1 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                            <h3 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $ew->extended_warranty_vendor ?: 'Extended Warranty' }}
                            </h3>
                        </div>
                        @if ($ew->extended_warranty_bill_no)
                            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $ew->extended_warranty_bill_no }}</p>
                        @endif
                    </div>
                    @if ($ewIsExpired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($ewIsSoon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">
                            @if ($ewMode === 'time') Expiring in {{ $days }}d
                            @else {{ number_format($ewCounterRemaining) }} {{ $ewUnit }} left
                            @endif
                        </span>
                    @elseif ($ewIsActive)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                </div>

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Vendor / Provider</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $ew->extended_warranty_vendor ?: '--' }}</dd>
                    </div>
                    @if ($ewMode === 'time')
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">From</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $ew->extended_warranty_date_from?->format('d M Y') ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                        <dd class="mt-0.5 text-sm {{ $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $ew->extended_warranty_date_to?->format('d M Y') ?: '--' }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $ew->reminder_before_days ? $ew->reminder_before_days . ' days' : '--' }}
                        </dd>
                    </div>
                    @else
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Warranty Limit</dt>
                        <dd class="mt-0.5 text-sm {{ $ewCounterExpired ? 'text-red-400 font-semibold' : ($ewCounterSoon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $ew->extended_warranty_counter_limit ? number_format($ew->extended_warranty_counter_limit) . ' ' . $ewUnit : '--' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Current Reading</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $ewCounter !== null ? number_format($ewCounter) . ' ' . $ewUnit : '--' }}
                        </dd>
                    </div>
                    @if ($ewCounterRemaining !== null)
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Remaining</dt>
                        <dd class="mt-0.5 text-sm {{ $ewCounterExpired ? 'text-red-400' : ($ewCounterSoon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $ewCounterExpired ? 'Expired' : number_format($ewCounterRemaining) . ' ' . $ewUnit }}
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Remind when within</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $ew->extended_warranty_reminder_before_units ? number_format($ew->extended_warranty_reminder_before_units) . ' ' . $ewUnit : '--' }}
                        </dd>
                    </div>
                    @endif
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Terms</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $ew->extended_warranty_terms ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $ew->remarks ?: '--' }}</dd>
                    </div>
                </dl>

                </div>{{-- end left --}}

                {{-- ── Right: Document panel ── --}}
                <aside class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>
                    <div class="ew-doc-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                    <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                </div>
                                <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                            </div>`,
                            files: @js($ewFirstDoc ? [['source' => Storage::url($ewFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                            fileMetaBySource: @js($ewFirstDoc ? [Storage::url($ewFirstDoc->file_path) => ['name' => $ewFirstDoc->file_original_name]] : (object)[]),
                            deleteUrl: @js($ewFirstDoc ? route('assets.ext-warranty.documents.destroy', [$asset, $ewFirstDoc]) : ''),
                            csrfToken: @js(csrf_token()),
                            revertUrlTemplate: () => @js(route('assets.ext-warranty.documents.revert', $asset)),
                            server: {
                                process: {
                                    url: @js(route('assets.ext-warranty.documents.store', [$asset, $ew])),
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                                    onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); },
                                    onerror: (e) => toastr.error('Upload failed.'),
                                },
                            },
                        })
                    "><input type="file" /></div>

                    @if ($ewExtraDocs->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach ($ewExtraDocs as $doc)
                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                    <button type="button"
                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.eye class="size-3" />
                                    </button>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                    </a>
                                    <form method="POST" action="{{ route('assets.ext-warranty.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this,'Delete this document?');return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if (!$ewFirstDoc && $ewExtraDocs->isEmpty())
                        <div class="mt-3 flex flex-col items-center justify-center">
                            <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                        </div>
                    @endif
                </aside>{{-- end right --}}
            </div>
        </x-modal>

        {{-- EW Card --}}
        <div class="grid grid-cols-3 gap-4">
        @php $expiryClass = $ewIsExpired ? 'text-red-400 font-semibold' : ($ewIsSoon ? 'text-yellow-400' : 'text-zinc-200'); @endphp

        <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            {{-- Card header --}}
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                <div class="flex items-center gap-3 min-w-0">
                    <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                    <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ $ew->extended_warranty_vendor ?: 'Extended Warranty' }}
                    </span>
                    @if ($ew->extended_warranty_bill_no)
                        <span class="font-mono text-xs text-zinc-500">{{ $ew->extended_warranty_bill_no }}</span>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-1.5">
                    @if ($ewIsExpired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($ewIsSoon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">
                            @if ($ewMode === 'time') Expiring in {{ $days }}d
                            @else {{ number_format($ewCounterRemaining) }} {{ $ewUnit }} left
                            @endif
                        </span>
                    @elseif ($ewIsActive)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                    <button type="button"
                            x-on:click="$dispatch('open-modal-view-ext-warranty')"
                            aria-label="View extended warranty"
                            title="View extended warranty"
                            class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                        <flux:icon.eye class="size-3.5" />
                    </button>
                    <button type="button"
                            x-on:click="$dispatch('open-modal-edit-ext-warranty')"
                            aria-label="Edit extended warranty"
                            title="Edit extended warranty"
                            class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                        <flux:icon.pencil class="size-3.5" />
                    </button>
                    <form method="POST" action="{{ route('assets.ext-warranty.destroy', [$asset, $ew]) }}"
                          onsubmit="confirmDelete(this, 'Delete extended warranty record?'); return false;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                aria-label="Delete extended warranty"
                                title="Delete extended warranty"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                            <flux:icon.trash class="size-3.5" />
                        </button>
                    </form>
                </div>
            </div>

            {{-- Detail grid --}}
            <div class="px-5 py-4">
                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Vendor / Provider</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $ew->extended_warranty_vendor ?: '—' }}</dd>
                    </div>
                    @if ($ewMode === 'time')
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">From</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $ew->extended_warranty_date_from?->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                            <dd class="mt-0.5 text-sm {{ $expiryClass }}">
                                {{ $ew->extended_warranty_date_to?->format('d M Y') ?: '—' }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $ew->reminder_before_days ? $ew->reminder_before_days . ' days' : '—' }}
                            </dd>
                        </div>
                    @else
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Warranty Limit</dt>
                            <dd class="mt-0.5 text-sm {{ $ewCounterExpired ? 'text-red-400 font-semibold' : ($ewCounterSoon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }}">
                                {{ $ew->extended_warranty_counter_limit ? number_format($ew->extended_warranty_counter_limit) . ' ' . $ewUnit : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Current Reading</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $ewCounter !== null ? number_format($ewCounter) . ' ' . $ewUnit : '—' }}
                            </dd>
                        </div>
                        @if ($ewCounterRemaining !== null)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Remaining</dt>
                                <dd class="mt-0.5 text-sm {{ $ewCounterExpired ? 'text-red-400' : ($ewCounterSoon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }}">
                                    {{ $ewCounterExpired ? 'Expired' : number_format($ewCounterRemaining) . ' ' . $ewUnit }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Remind when within</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $ew->extended_warranty_reminder_before_units ? number_format($ew->extended_warranty_reminder_before_units) . ' ' . $ewUnit : '—' }}
                            </dd>
                        </div>
                    @endif
                    @if ($ew->extended_warranty_terms)
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs font-medium text-zinc-500">Warranty Terms</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $ew->extended_warranty_terms }}</dd>
                        </div>
                    @endif
                    @if ($ew->remarks)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $ew->remarks }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Documents --}}
                @if ($ew->documents->isNotEmpty())
                    <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                        <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                        @foreach ($ew->documents as $doc)
                            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                @if ($doc->isImage())
                                    <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                @else
                                    <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                @endif
                                <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                <span class="text-xs text-zinc-600">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                <button type="button"
                                    x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                    title="View"
                                    class="inline-flex size-6 shrink-0 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                    <flux:icon.eye class="size-3.5" />
                                </button>
                                <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                    title="Download"
                                    class="inline-flex size-6 shrink-0 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                    <flux:icon.arrow-down-tray class="size-3.5" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        </div>{{-- /grid --}}
    @endif

</div>
