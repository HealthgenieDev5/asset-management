@php
use Illuminate\Support\Facades\Storage;

$badge     = $w->statusBadge();
$isDisposed = $w->isDisposed();

// Status colour
$badgeClass = match ($badge) {
    'expired'  => 'bg-red-400/10 text-red-400',
    'soon'     => 'bg-yellow-400/10 text-yellow-400',
    'disposed' => 'bg-zinc-400/10 text-zinc-400 line-through',
    default    => 'bg-green-400/10 text-green-400',
};
$badgeLabel = match ($badge) {
    'expired'  => 'Expired',
    'soon'     => 'Expiring Soon',
    'disposed' => 'Disposed',
    default    => 'Active',
};

// Expiry summary line
if ($w->isTimeBased()) {
    $expiryLine = $w->expiry_date ? $w->expiry_date->format('d M Y') : null;
} else {
    $expiryLine = $w->counter_limit ? number_format($w->counter_limit) . ' ' . $w->unitLabel() : null;
}

// Part-level "Create Replacement" pre-fill URL
$replacementUrl = null;
if ($isDisposed && $w->scope === 'part') {
    $replacementUrl = route('assets.show', [$asset, 'tab' => 'warranty', 'prefill_part' => $w->part_name]);
}
@endphp

<div class="rounded-xl border {{ $isDisposed ? 'border-zinc-700/50 opacity-60' : 'border-zinc-200 dark:border-zinc-800' }} bg-white dark:bg-zinc-900 overflow-hidden">
    {{-- Row header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 {{ $isDisposed ? 'bg-zinc-50/50 dark:bg-zinc-800/20' : 'bg-zinc-50 dark:bg-zinc-800/40' }}">
        <div class="flex min-w-0 items-center gap-2.5">
            <flux:icon.shield-check class="size-4 shrink-0 {{ $isDisposed ? 'text-zinc-500' : 'text-zinc-400' }}" />
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                    @if ($w->scope === 'part')
                        <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">{{ $w->part_name }}</span>
                        @if ($w->part_serial_number)
                            <span class="text-xs text-zinc-400">({{ $w->part_serial_number }})</span>
                        @endif
                    @endif
                    <span class="rounded-full {{ $w->warranty_type === 'original' ? 'bg-blue-400/10 text-blue-400' : 'bg-purple-400/10 text-purple-400' }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                        {{ $w->warrantyTypeLabel() }}
                    </span>
                    @if ($w->vendor)
                        <span class="text-xs text-zinc-500">{{ $w->vendor }}</span>
                    @endif
                    @if ($expiryLine)
                        <span class="text-xs text-zinc-500">·</span>
                        <span class="text-xs {{ $badge === 'expired' ? 'text-red-400 font-semibold' : ($badge === 'soon' ? 'text-yellow-400' : 'text-zinc-500') }}">
                            {{ $expiryLine }}
                        </span>
                    @endif
                </div>
                @if ($isDisposed && $w->disposed_at)
                    <p class="mt-0.5 text-[11px] text-zinc-500">
                        Disposed {{ $w->disposed_at->format('d M Y') }}
                        @if ($w->disposed_reason) — {{ $w->disposed_reason }} @endif
                    </p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>

            @if (! $isDisposed)
                {{-- Edit --}}
                <button type="button"
                    x-on:click="$dispatch('open-modal-edit-warranty-{{ $w->id }}')"
                    aria-label="Edit warranty"
                    title="Edit warranty"
                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                    <flux:icon.pencil class="size-3.5" />
                </button>
                {{-- Dispose --}}
                <button type="button"
                    x-on:click="$dispatch('open-modal-dispose-warranty-{{ $w->id }}')"
                    aria-label="Dispose warranty"
                    title="Dispose / Retire"
                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-yellow-500/60 hover:text-yellow-400 dark:border-zinc-700">
                    <flux:icon.archive-box-x-mark class="size-3.5" />
                </button>
            @else
                @if ($replacementUrl)
                    {{-- Create Replacement --}}
                    <a href="{{ $replacementUrl }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-accent/40 px-2 py-0.5 text-[11px] font-medium text-accent hover:bg-accent/10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3">
                            <path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/>
                        </svg>
                        Create Replacement
                    </a>
                @endif
            @endif

            {{-- Delete --}}
            <form method="POST" action="{{ route('assets.warranties.destroy', [$asset, $w]) }}"
                  onsubmit="confirmDelete(this, 'Delete this warranty entry? This cannot be undone.'); return false;">
                @csrf @method('DELETE')
                <button type="submit"
                    aria-label="Delete warranty"
                    title="Delete warranty entry"
                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                    <flux:icon.trash class="size-3.5" />
                </button>
            </form>
        </div>
    </div>

    {{-- Expanded detail (only for active/non-disposed) --}}
    @if (! $isDisposed && ($w->details || $w->terms || $w->remarks || $w->bill_no || $w->documents->isNotEmpty()))
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
            @if ($w->bill_no || $w->bill_amount)
                <div class="flex flex-wrap gap-4 mb-3">
                    @if ($w->bill_no)
                        <div>
                            <p class="text-[10px] font-medium text-zinc-500">Bill No.</p>
                            <p class="text-sm text-zinc-800 dark:text-zinc-200">{{ $w->bill_no }}</p>
                        </div>
                    @endif
                    @if ($w->bill_amount)
                        <div>
                            <p class="text-[10px] font-medium text-zinc-500">Amount</p>
                            <p class="text-sm text-zinc-800 dark:text-zinc-200">₹{{ number_format($w->bill_amount, 2) }}</p>
                        </div>
                    @endif
                    @if ($w->date_from)
                        <div>
                            <p class="text-[10px] font-medium text-zinc-500">From</p>
                            <p class="text-sm text-zinc-800 dark:text-zinc-200">{{ $w->date_from->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($w->details)
                <p class="mb-1 text-[10px] font-medium text-zinc-500">Details</p>
                <p class="mb-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->details }}</p>
            @endif
            @if ($w->terms)
                <p class="mb-1 text-[10px] font-medium text-zinc-500">Terms</p>
                <p class="mb-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $w->terms }}</p>
            @endif

            {{-- Documents --}}
            @if ($w->documents->isNotEmpty())
                <p class="mb-1.5 text-[10px] font-medium text-zinc-500">Documents</p>
                <div class="space-y-1.5">
                    @foreach ($w->documents as $doc)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                            @if ($doc->isImage())
                                <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                            @else
                                <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                <p class="text-[11px] text-zinc-500">{{ number_format($doc->file_size / 1024, 0) }} KB</p>
                            </div>
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
                            <form method="POST" action="{{ route('assets.warranties.documents.destroy', [$asset, $doc]) }}"
                                  onsubmit="confirmDelete(this, 'Delete this document?'); return false;">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                    <flux:icon.trash class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
