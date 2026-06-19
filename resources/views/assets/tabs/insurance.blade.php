@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Insurance Policies</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->insurancePolicies->count() }} {{ Str::plural('policy', $asset->insurancePolicies->count()) }}
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-insurance')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Policy
        </button>
    </div>

    {{-- Add Modal --}}
    <x-modal name="add-insurance" title="New Insurance Policy" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'insurance' && !old('_policy_id')">
        <form method="POST" action="{{ route('assets.insurance.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="insurance">

            @include('assets.tabs._insurance-form', ['policy' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Policy</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-insurance')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Modals (one per policy) --}}
    @foreach ($asset->insurancePolicies->sortByDesc('created_at') as $policy)
        <x-modal name="edit-insurance-{{ $policy->id }}" title="Edit Insurance Policy" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'insurance' && (int) old('_policy_id') === $policy->id">
            <form method="POST" action="{{ route('assets.insurance.update', [$asset, $policy]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="insurance">
                <input type="hidden" name="_policy_id" value="{{ $policy->id }}">

                @include('assets.tabs._insurance-form', ['policy' => $policy])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-insurance-{{ $policy->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- View Modals (one per policy) --}}
    @foreach ($asset->insurancePolicies->sortByDesc('created_at') as $policy)
        @php
            $viewDays    = $policy->daysUntilExpiry();
            $viewExpired = $policy->isExpired();
            $viewSoon    = ! $viewExpired && $viewDays !== null && $viewDays <= 30;
            $viewExpiryClass = $viewExpired ? 'text-red-400 font-semibold' : ($viewSoon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100');
        @endphp
        <x-modal name="view-insurance-{{ $policy->id }}" title="Insurance Policy Details">
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:icon.building-library class="size-4 shrink-0 text-zinc-400" />
                            <h3 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $policy->insurer_name ?: 'Insurance Policy' }}
                            </h3>
                        </div>
                        @if ($policy->policy_number)
                            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $policy->policy_number }}</p>
                        @endif
                    </div>
                    @if ($viewExpired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($viewSoon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $viewDays }}d</span>
                    @elseif ($viewDays !== null)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                </div>

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Policy Type</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->policy_type ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">From</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->policy_date_from?->format('d M Y') ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Expiry Date</dt>
                        <dd class="mt-0.5 text-sm {{ $viewExpiryClass }}">
                            {{ $policy->policy_date_to?->format('d M Y') ?: '--' }}
                            @if ($viewExpired) <span class="text-xs font-normal">(Expired)</span>
                            @elseif ($viewSoon) <span class="text-xs">({{ $viewDays }}d left)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Premium Amount</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $policy->premium_amount ? 'Rs. ' . number_format($policy->premium_amount, 2) : '--' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Sum Insured</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $policy->sum_insured ? 'Rs. ' . number_format($policy->sum_insured, 2) : '--' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $policy->reminder_before_days ? $policy->reminder_before_days . ' days' : '--' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->bill_no ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Contact Person</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->insurer_contact_person ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Phone</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->insurer_phone ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Email</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->insurer_email ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Coverage Details</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->coverage_details ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $policy->remarks ?: '--' }}</dd>
                    </div>
                </dl>

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                    @if ($policy->documents->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($policy->documents as $doc)
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
    @endforeach

    {{-- Policy Grid --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($asset->insurancePolicies->sortByDesc('created_at') as $policy)
            @php
                $days    = $policy->daysUntilExpiry();
                $expired = $policy->isExpired();
                $soon    = ! $expired && $days !== null && $days <= 30;
                $expiryClass = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Card header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-3 min-w-0">
                        <flux:icon.building-library class="size-4 shrink-0 text-zinc-400" />
                        <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $policy->insurer_name ?: 'Insurance Policy' }}
                        </span>
                        @if ($policy->policy_number)
                            <span class="font-mono text-xs text-zinc-500">{{ $policy->policy_number }}</span>
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
                                x-on:click="$dispatch('open-modal-view-insurance-{{ $policy->id }}')"
                                aria-label="View insurance policy"
                                title="View insurance policy"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-edit-insurance-{{ $policy->id }}')"
                                aria-label="Edit insurance policy"
                                title="Edit insurance policy"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.insurance.destroy', [$asset, $policy]) }}"
                              onsubmit="return confirm('Delete this insurance policy?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete insurance policy"
                                    title="Delete insurance policy"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Detail grid --}}
                <div class="px-5 py-4">
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($policy->policy_type)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Policy Type</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $policy->policy_type }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">From</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $policy->policy_date_from?->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Expiry Date</dt>
                            <dd class="mt-0.5 text-sm {{ $expiryClass }}">
                                {{ $policy->policy_date_to?->format('d M Y') ?: '—' }}
                                @if ($expired) <span class="text-xs font-normal">(Expired)</span>
                                @elseif ($soon) <span class="text-xs">({{ $days }}d left)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Premium Amount</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $policy->premium_amount ? '₹ ' . number_format($policy->premium_amount, 2) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Sum Insured</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $policy->sum_insured ? '₹ ' . number_format($policy->sum_insured, 2) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                            <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                {{ $policy->reminder_before_days ? $policy->reminder_before_days . ' days' : '—' }}
                            </dd>
                        </div>
                        @if ($policy->bill_no)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $policy->bill_no }}</dd>
                            </div>
                        @endif
                        @if ($policy->insurer_contact_person || $policy->insurer_phone || $policy->insurer_email)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Insurer Contact</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ implode(' · ', array_filter([$policy->insurer_contact_person, $policy->insurer_phone, $policy->insurer_email])) }}
                                </dd>
                            </div>
                        @endif
                        @if ($policy->coverage_details)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Coverage Details</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $policy->coverage_details }}</dd>
                            </div>
                        @endif
                        @if ($policy->remarks)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $policy->remarks }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Documents --}}
                    @if ($policy->documents->isNotEmpty())
                        <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($policy->documents as $doc)
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

        {{-- Placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
            <flux:icon.building-library class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->insurancePolicies->isEmpty() ? 'No Insurance Policies' : 'Add Another Policy' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Add an insurance policy to track coverage and renewal dates.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-insurance')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $asset->insurancePolicies->isEmpty() ? 'Add First Policy' : 'Add Insurance Policy' }}
                </button>
            </div>
        </div>
    </div>

</div>
