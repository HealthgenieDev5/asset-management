@php
    use Illuminate\Support\Facades\Storage;
    $warrantyDocs = $asset->documents->whereIn('document_type', ['warranty_card', 'warranty_activation_image'])->values();
    $expired      = $asset->warranty_lapse_date && $asset->warranty_lapse_date->isPast();
    $days         = $asset->warranty_lapse_date ? (int) now()->startOfDay()->diffInDays($asset->warranty_lapse_date->startOfDay(), false) : null;
    $soon         = ! $expired && $days !== null && $days <= 30;
    $hasWarranty  = $asset->warranty_details || $asset->warranty_lapse_date || $asset->warranty_reminder_before_days || $warrantyDocs->isNotEmpty();
    $expiryClass  = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Original Warranty</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">Manufacturer warranty details and documents</flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-warranty')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
                @if ($hasWarranty)
                    <path d="M13.488 2.513a1.75 1.75 0 0 0-2.475 0L6.75 6.774a2.75 2.75 0 0 0-.596.892l-.848 2.047a.75.75 0 0 0 .98.98l2.047-.848a2.75 2.75 0 0 0 .892-.596l4.261-4.263a1.75 1.75 0 0 0 0-2.474ZM4.75 3.5A2.25 2.25 0 0 0 2.5 5.75v5.5a2.25 2.25 0 0 0 2.25 2.25h5.5a2.25 2.25 0 0 0 2.25-2.25v-2a.75.75 0 0 0-1.5 0v2a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-5.5a.75.75 0 0 1 .75-.75h2a.75.75 0 0 0 0-1.5h-2Z"/>
                @else
                    <path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/>
                @endif
            </svg>
            {{ $hasWarranty ? 'Edit Warranty' : 'Add Warranty' }}
        </button>
    </div>

    {{-- Modal (add & edit use same route/form) --}}
    <x-modal name="warranty" title="{{ $hasWarranty ? 'Edit Original Warranty' : 'New Original Warranty' }}" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'warranty'">
        <form method="POST" action="{{ route('assets.warranty.update', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="warranty">

            @include('assets.tabs._warranty-form', ['asset' => $asset])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">
                    {{ $hasWarranty ? 'Save Changes' : 'Save Warranty' }}
                </flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-warranty')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- View Modal --}}
    @if ($hasWarranty)
        <x-modal name="view-warranty" title="Original Warranty Details">
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                        <h3 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">Original Warranty</h3>
                    </div>
                    @if ($expired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($soon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $days }}d</span>
                    @elseif ($days !== null)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                </div>

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                        <dd class="mt-0.5 text-sm {{ $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                            {{ $asset->warranty_lapse_date?->format('d M Y') ?: '--' }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $asset->warranty_reminder_before_days ? $asset->warranty_reminder_before_days . ' days' : '--' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Details</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $asset->warranty_details ?: '--' }}</dd>
                    </div>
                </dl>

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                    @if ($warrantyDocs->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($warrantyDocs as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                       class="text-xs text-accent hover:underline">Open</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No documents attached.</p>
                    @endif
                </div>
            </div>
        </x-modal>
    @endif

    {{-- Content --}}
    <div class="grid grid-cols-3 gap-4">
        @if ($hasWarranty)
            {{-- Warranty card --}}
            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Card header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-3 min-w-0">
                        <flux:icon.shield-check class="size-4 shrink-0 text-zinc-400" />
                        <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">Original Warranty</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        @if ($expired)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                        @elseif ($soon)
                            <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $days }}d</span>
                        @elseif ($days !== null)
                            <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                        @endif
                        <button type="button"
                                x-on:click="$dispatch('open-modal-view-warranty')"
                                aria-label="View warranty"
                                title="View warranty"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-warranty')"
                                aria-label="Edit warranty"
                                title="Edit warranty"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                    </div>
                </div>

                {{-- Detail grid --}}
                <div class="px-5 py-4">
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($asset->warranty_details)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Warranty Details</dt>
                                <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->warranty_details }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                            <dd class="mt-0.5 text-sm {{ $expiryClass }}">
                                {{ $asset->warranty_lapse_date?->format('d M Y') ?: '—' }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $asset->warranty_reminder_before_days ? $asset->warranty_reminder_before_days . ' days' : '—' }}
                            </dd>
                        </div>
                    </dl>

                    {{-- Documents --}}
                    @if ($warrantyDocs->isNotEmpty())
                        <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($warrantyDocs as $doc)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                    @else
                                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                        <p class="text-[11px] text-zinc-500">{{ $doc->document_type_label }} · {{ number_format($doc->file_size / 1024, 0) }} KB</p>
                                    </div>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                       class="text-xs text-accent hover:underline">View</a>
                                    <form method="POST" action="{{ route('assets.warranty.documents.destroy', [$asset, $doc]) }}"
                                          onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                aria-label="Delete warranty document"
                                                title="Delete warranty document"
                                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3.5" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $hasWarranty ? 'Update Warranty' : 'No Warranty' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">
                {{ $hasWarranty ? 'Click Edit Warranty to update details or upload documents.' : 'No warranty has been recorded for this asset.' }}
            </flux:text>
            @if (! $hasWarranty)
                <div class="mt-4">
                    <button type="button" x-on:click="$dispatch('open-modal-warranty')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Add Warranty
                    </button>
                </div>
            @endif
        </div>
    </div>

</div>
