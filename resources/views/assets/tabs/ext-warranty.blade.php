@php
    use Illuminate\Support\Facades\Storage;
    $ew = $asset->extendedWarranties->first();
@endphp

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

                @include('assets.tabs._ext-warranty-form', ['ew' => null])

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
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
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

                @include('assets.tabs._ext-warranty-form', ['ew' => $ew])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-ext-warranty')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- EW Card --}}
        <div class="grid grid-cols-3 gap-4">
        @php
            $expired     = $ew->extended_warranty_date_to && $ew->extended_warranty_date_to->isPast();
            $days        = $ew->extended_warranty_date_to ? (int) now()->startOfDay()->diffInDays($ew->extended_warranty_date_to->startOfDay(), false) : null;
            $soon        = ! $expired && $days !== null && $days <= 30;
            $expiryClass = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
        @endphp

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
                <div class="flex shrink-0 items-center gap-2">
                    @if ($expired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($soon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $days }}d</span>
                    @elseif ($days !== null)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                    <button type="button"
                            x-on:click="$dispatch('open-modal-edit-ext-warranty')"
                            class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('assets.ext-warranty.destroy', [$asset, $ew]) }}"
                          onsubmit="return confirm('Delete extended warranty record?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                            Delete
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
                        <dt class="text-xs font-medium text-zinc-500">Bill Number</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $ew->extended_warranty_bill_no ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Amount</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                            {{ $ew->extended_warranty_amount ? '₹ ' . number_format($ew->extended_warranty_amount, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                            {{ $ew->reminder_before_days ? $ew->reminder_before_days . ' days' : '—' }}
                        </dd>
                    </div>
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
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                   class="text-xs text-accent hover:underline">View</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        </div>{{-- /grid --}}
    @endif

</div>
