@php use Illuminate\Support\Facades\Storage; @endphp


<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">AMC Contracts</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->amcContracts->count() }} {{ Str::plural('contract', $asset->amcContracts->count()) }}
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-amc')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add AMC
        </button>
    </div>

    {{-- Add Modal --}}
    <x-modal name="add-amc" title="New AMC Contract" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'amc' && !old('_amc_id')">
        <form method="POST" action="{{ route('assets.amc.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="amc">

            @include('assets.tabs._amc-form', ['amc' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Contract</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-amc')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Modals (one per contract) --}}
    @foreach ($asset->amcContracts->sortByDesc('created_at') as $amc)
        <x-modal name="edit-amc-{{ $amc->id }}" title="Edit AMC Contract" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'amc' && (int) old('_amc_id') === $amc->id">
            <form method="POST" action="{{ route('assets.amc.update', [$asset, $amc]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="amc">
                <input type="hidden" name="_amc_id" value="{{ $amc->id }}">

                @include('assets.tabs._amc-form', ['amc' => $amc])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-amc-{{ $amc->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- Contract List --}}
    <div class="gap-4 grid grid-cols-3">
        @foreach ($asset->amcContracts->sortByDesc('created_at') as $amc)
            @php
                $days    = $amc->daysUntilExpiry();
                $expired = $amc->isExpired();
                $soon    = ! $expired && $days !== null && $days <= 30;
                $expiryClass = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Card header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-3 min-w-0">
                        <flux:icon.wrench-screwdriver class="size-4 shrink-0 text-zinc-400" />
                        <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $amc->vendor_name ?: 'AMC Contract' }}
                        </span>
                        @if ($amc->contract_number)
                            <span class="font-mono text-xs text-zinc-500 dark:text-zinc-500">{{ $amc->contract_number }}</span>
                        @endif
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
                                x-on:click="$dispatch('open-modal-edit-amc-{{ $amc->id }}')"
                                aria-label="Edit AMC contract"
                                title="Edit AMC contract"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.amc.destroy', [$asset, $amc]) }}"
                              onsubmit="return confirm('Delete this AMC contract?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete AMC contract"
                                    title="Delete AMC contract"
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
                            <dt class="text-xs font-medium text-zinc-500">Coverage Type</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $amc->coverage_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">From</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $amc->amc_date_from?->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Lapse Date</dt>
                            <dd class="mt-0.5 text-sm {{ $expiryClass }}">
                                {{ $amc->amc_date_to?->format('d M Y') ?: '—' }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Amount</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $amc->amc_amount ? '₹ ' . number_format($amc->amc_amount, 2) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $amc->amc_bill_no ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $amc->reminder_before_days ? $amc->reminder_before_days . ' days' : '—' }}
                            </dd>
                        </div>
                        @if ($amc->vendor_contact_person || $amc->vendor_phone || $amc->vendor_email)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Vendor Contact</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ implode(' · ', array_filter([$amc->vendor_contact_person, $amc->vendor_phone, $amc->vendor_email])) }}
                                </dd>
                            </div>
                        @endif
                        @if ($amc->coverage_details)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Coverage Details</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $amc->coverage_details }}</dd>
                            </div>
                        @endif
                        @if ($amc->remarks)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $amc->remarks }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Documents --}}
                    @if ($amc->documents->isNotEmpty())
                        <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($amc->documents as $doc)
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
        @endforeach

        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.wrench-screwdriver class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->amcContracts->isEmpty() ? 'No AMC Contracts' : 'Add Another Contract' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Add an Annual Maintenance Contract to track coverage and renewal dates.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-amc')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $asset->amcContracts->isEmpty() ? 'Add First Contract' : 'Add AMC Contract' }}
                </button>
            </div>
        </div>
    </div>
</div>
