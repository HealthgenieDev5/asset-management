@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5">
    {{-- Original Warranty Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Original Warranty</flux:heading>
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium text-zinc-500">Warranty Details</dt>
                <dd class="mt-1 text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->warranty_details ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                <dd class="mt-1 text-sm">
                    @if ($asset->warranty_lapse_date)
                        @php $expired = $asset->warranty_lapse_date->isPast(); @endphp
                        <span class="{{ $expired ? 'text-red-400 font-semibold' : 'text-zinc-800 dark:text-zinc-200' }}">
                            {{ $asset->warranty_lapse_date->format('d M Y') }}
                            @if ($expired) <span class="text-xs font-normal">(Expired)</span> @endif
                        </span>
                    @else
                        <span class="text-zinc-500">—</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                <dd class="mt-1 text-sm text-zinc-800 dark:text-zinc-200">
                    {{ $asset->warranty_reminder_before_days ? $asset->warranty_reminder_before_days . ' days' : '—' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Warranty Documents --}}
    @php
        $warrantyDocs = $asset->documents->whereIn('document_type', ['warranty_card', 'warranty_activation_image'])->values();
    @endphp

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading class="font-semibold text-zinc-700 dark:text-zinc-300">Warranty Documents</flux:heading>
            <flux:button href="{{ route('assets.edit', [$asset, '#warranty-docs']) }}" wire:navigate variant="ghost" size="sm" icon="arrow-up-tray">
                Upload
            </flux:button>
        </div>

        @if ($warrantyDocs->isEmpty())
            <p class="text-sm text-zinc-500">No warranty documents uploaded yet. Use Edit Asset to upload warranty card or activation image.</p>
        @else
            <div class="space-y-2">
                @foreach ($warrantyDocs as $doc)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                        @if ($doc->isImage())
                            <flux:icon.photo class="size-5 shrink-0 text-zinc-400" />
                        @else
                            <flux:icon.document class="size-5 shrink-0 text-zinc-400" />
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $doc->file_original_name }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ $doc->document_type_label }}
                                · {{ number_format($doc->file_size / 1024, 0) }} KB
                                · {{ $doc->created_at->format('d M Y') }}
                            </p>
                        </div>

                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                           class="shrink-0 rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                            View
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <p class="text-xs text-zinc-600">Extended warranties are tracked separately in the Ext. Warranty tab (Phase 5).</p>
</div>
