@php use Illuminate\Support\Facades\Storage; @endphp

@php $ew = $asset->extendedWarranties->first(); @endphp

<div class="space-y-5">
    @if (! $ew)
        <div class="rounded-xl border border-dashed border-zinc-700 bg-zinc-900 py-16 text-center">
            <flux:icon.shield-exclamation class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Extended Warranty</flux:heading>
            <flux:text class="mt-1 text-zinc-600 text-sm">No extended warranty has been recorded for this asset.</flux:text>
            <div class="mt-4">
                <flux:button href="{{ route('assets.edit', $asset) }}" wire:navigate variant="ghost" size="sm" icon="plus">
                    Add Extended Warranty
                </flux:button>
            </div>
        </div>
    @else
        {{-- Details Card --}}
        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading class="font-semibold text-zinc-300">Extended Warranty Details</flux:heading>
                <flux:button href="{{ route('assets.edit', $asset) }}" wire:navigate variant="ghost" size="sm" icon="pencil">
                    Edit
                </flux:button>
            </div>

            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-zinc-500">Vendor / Provider</dt>
                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $ew->extended_warranty_vendor ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-zinc-500">Warranty From</dt>
                    <dd class="mt-0.5 text-sm text-zinc-200">
                        {{ $ew->extended_warranty_date_from?->format('d M Y') ?: '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-zinc-500">Warranty Lapse Date</dt>
                    <dd class="mt-0.5 text-sm">
                        @if ($ew->extended_warranty_date_to)
                            @php
                                $expired = $ew->extended_warranty_date_to->isPast();
                                $soon    = ! $expired && $ew->extended_warranty_date_to->diffInDays(now()) <= 30;
                            @endphp
                            <span class="{{ $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200') }}">
                                {{ $ew->extended_warranty_date_to->format('d M Y') }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">(Expiring soon)</span>
                                @endif
                            </span>
                        @else
                            <span class="text-zinc-500">—</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-zinc-500">Bill Number</dt>
                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $ew->extended_warranty_bill_no ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-zinc-500">Bill Amount</dt>
                    <dd class="mt-0.5 text-sm text-zinc-200">
                        {{ $ew->extended_warranty_amount ? '₹ ' . number_format($ew->extended_warranty_amount, 2) : '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                    <dd class="mt-0.5 text-sm text-zinc-200">
                        {{ $ew->reminder_before_days ? $ew->reminder_before_days . ' days' : '—' }}
                    </dd>
                </div>

                @if ($ew->extended_warranty_terms)
                    <div class="sm:col-span-2 lg:col-span-2">
                        <dt class="text-xs font-medium text-zinc-500">Warranty Terms</dt>
                        <dd class="mt-0.5 text-sm text-zinc-200 whitespace-pre-line">{{ $ew->extended_warranty_terms }}</dd>
                    </div>
                @endif

                @if ($ew->remarks)
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                        <dd class="mt-0.5 text-sm text-zinc-200">{{ $ew->remarks }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Documents --}}
        @php
            $ewDocs = $ew->documents->whereIn('document_type', ['extended_warranty_bill', 'extended_warranty_image'])->values();
        @endphp

        <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading class="font-semibold text-zinc-300">Extended Warranty Documents</flux:heading>
                <flux:button href="{{ route('assets.edit', $asset) }}" wire:navigate variant="ghost" size="sm" icon="arrow-up-tray">
                    Upload
                </flux:button>
            </div>

            @if ($ewDocs->isEmpty())
                <p class="text-sm text-zinc-500">No documents uploaded. Use Edit Asset to attach the extended warranty bill or activation image.</p>
            @else
                <div class="space-y-2">
                    @foreach ($ewDocs as $doc)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-800 bg-zinc-800/50 px-4 py-3">
                            @if ($doc->isImage())
                                <flux:icon.photo class="size-5 shrink-0 text-zinc-400" />
                            @else
                                <flux:icon.document class="size-5 shrink-0 text-zinc-400" />
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-200">{{ $doc->file_original_name }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $doc->document_type_label }}
                                    · {{ number_format($doc->file_size / 1024, 0) }} KB
                                    · {{ $doc->created_at->format('d M Y') }}
                                </p>
                            </div>
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                               class="shrink-0 rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-300 hover:border-accent hover:text-accent transition-colors">
                                View
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
