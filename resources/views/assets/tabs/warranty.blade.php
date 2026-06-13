@php
    use Illuminate\Support\Facades\Storage;
    $warrantyDocs = $asset->documents->whereIn('document_type', ['warranty_card', 'warranty_activation_image'])->values();
    $expired = $asset->warranty_lapse_date && $asset->warranty_lapse_date->isPast();
    $days    = $asset->warranty_lapse_date ? (int) now()->startOfDay()->diffInDays($asset->warranty_lapse_date->startOfDay(), false) : null;
    $soon    = ! $expired && $days !== null && $days <= 30;
    $hasWarranty = $asset->warranty_details || $asset->warranty_lapse_date || $asset->warranty_reminder_before_days || $warrantyDocs->isNotEmpty();
@endphp

<div class="space-y-5"
     x-data="{ showForm: {{ ($errors->any() && old('_form') === 'warranty') ? 'true' : 'false' }} }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Original Warranty</flux:heading>
            <flux:text class="mt-0.5 text-xs text-zinc-500">Manufacturer warranty details and documents</flux:text>
        </div>
        @if ($hasWarranty)
            <flux:button variant="ghost" size="sm" icon="pencil" @click="showForm = !showForm">
                Edit
            </flux:button>
        @else
            <flux:button variant="primary" size="sm" icon="plus" @click="showForm = !showForm">
                Add Warranty
            </flux:button>
        @endif
    </div>

    @if (! $hasWarranty)
        {{-- Add form --}}
        <div x-show="showForm" x-transition x-cloak
             class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <div class="px-5 py-5">
                <flux:heading class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-300">Add Original Warranty</flux:heading>
                <form method="POST" action="{{ route('assets.warranty.update', $asset) }}"
                      enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="warranty">

                    @include('assets.tabs._warranty-form', ['asset' => $asset])

                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">Save Warranty</flux:button>
                        <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Empty state --}}
        <div x-show="!showForm"
             class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Warranty</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">No warranty has been recorded for this asset.</flux:text>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" icon="plus" @click="showForm = true">
                    Add Warranty
                </flux:button>
            </div>
        </div>
    @endif

    @if ($hasWarranty)
        {{-- Detail card --}}
        <div x-show="!showForm"
             class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <div class="px-5 py-4">
                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Details</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->warranty_details ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                        <dd class="mt-0.5 text-sm {{ $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }}">
                            @if ($asset->warranty_lapse_date)
                                {{ $asset->warranty_lapse_date->format('d M Y') }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                                @endif
                            @else
                                <span class="text-zinc-500 font-normal">—</span>
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
                    <div class="mt-4 space-y-1.5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
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
                                            class="rounded-md border border-zinc-300 px-2 py-0.5 text-xs text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-zinc-500">No warranty documents uploaded yet.</p>
                @endif
            </div>
        </div>

        {{-- Edit form --}}
        <div x-show="showForm" x-transition x-cloak
             class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <div class="px-5 py-5">
                <flux:heading class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-300">Edit Original Warranty</flux:heading>
                <form method="POST" action="{{ route('assets.warranty.update', $asset) }}"
                      enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="warranty">

                    @include('assets.tabs._warranty-form', ['asset' => $asset])

                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                        <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
