@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5" x-data="{ showForm: {{ $errors->any() && old('_form') === 'insurance' ? 'true' : 'false' }}, editId: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Insurance Policies</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->insurancePolicies->count() }} {{ Str::plural('policy', $asset->insurancePolicies->count()) }}
            </flux:text>
        </div>
        <flux:button variant="primary" size="sm" icon="plus" @click="showForm = !showForm; editId = null">
            Add Policy
        </flux:button>
    </div>

    {{-- Add Form --}}
    <div x-show="showForm && editId === null" x-transition x-cloak
         class="rounded-xl border border-zinc-700 bg-zinc-900 p-5">
        <flux:heading class="mb-4 font-semibold text-zinc-300">New Insurance Policy</flux:heading>

        <form method="POST" action="{{ route('assets.insurance.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="insurance">

            @include('assets.tabs._insurance-form', ['policy' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Policy</flux:button>
                <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
            </div>
        </form>
    </div>

    {{-- Policy List --}}
    @if ($asset->insurancePolicies->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-700 bg-zinc-900 py-14 text-center">
            <flux:icon.building-library class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Insurance Policies</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Add an insurance policy to track coverage and renewal dates.</flux:text>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" icon="plus" @click="showForm = true; editId = null">Add First Policy</flux:button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($asset->insurancePolicies->sortByDesc('created_at') as $policy)
                @php
                    $days    = $policy->daysUntilExpiry();
                    $expired = $policy->isExpired();
                    $soon    = ! $expired && $days !== null && $days <= 30;
                    $expiryClass = $expired ? 'text-red-400 font-semibold' : ($soon ? 'text-yellow-400' : 'text-zinc-200');
                @endphp

                <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
                    {{-- Card header --}}
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-800 bg-zinc-800/40 px-5 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <flux:icon.building-library class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate text-sm font-semibold text-zinc-200">
                                {{ $policy->insurer_name ?: 'Insurance Policy' }}
                            </span>
                            @if ($policy->policy_number)
                                <span class="font-mono text-xs text-zinc-500">{{ $policy->policy_number }}</span>
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
                                    @click="editId = editId === {{ $policy->id }} ? null : {{ $policy->id }}"
                                    class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-300 hover:border-accent hover:text-accent transition-colors">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('assets.insurance.destroy', [$asset, $policy]) }}"
                                  onsubmit="return confirm('Delete this insurance policy?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors">
                                    Delete
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
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $policy->policy_type }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">From</dt>
                                <dd class="mt-0.5 text-sm text-zinc-200">{{ $policy->policy_date_from?->format('d M Y') ?: '—' }}</dd>
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
                                <dd class="mt-0.5 text-sm text-zinc-200">
                                    {{ $policy->premium_amount ? '₹ ' . number_format($policy->premium_amount, 2) : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Sum Insured</dt>
                                <dd class="mt-0.5 text-sm text-zinc-200">
                                    {{ $policy->sum_insured ? '₹ ' . number_format($policy->sum_insured, 2) : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Reminder Before</dt>
                                <dd class="mt-0.5 text-sm text-zinc-200">
                                    {{ $policy->reminder_before_days ? $policy->reminder_before_days . ' days' : '—' }}
                                </dd>
                            </div>
                            @if ($policy->bill_no)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $policy->bill_no }}</dd>
                                </div>
                            @endif
                            @if ($policy->insurer_contact_person || $policy->insurer_phone || $policy->insurer_email)
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <dt class="text-xs font-medium text-zinc-500">Insurer Contact</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">
                                        {{ implode(' · ', array_filter([$policy->insurer_contact_person, $policy->insurer_phone, $policy->insurer_email])) }}
                                    </dd>
                                </div>
                            @endif
                            @if ($policy->coverage_details)
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <dt class="text-xs font-medium text-zinc-500">Coverage Details</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200 whitespace-pre-line">{{ $policy->coverage_details }}</dd>
                                </div>
                            @endif
                            @if ($policy->remarks)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $policy->remarks }}</dd>
                                </div>
                            @endif
                        </dl>

                        {{-- Documents --}}
                        @if ($policy->documents->isNotEmpty())
                            <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                                <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                                @foreach ($policy->documents as $doc)
                                    <div class="flex items-center gap-3 rounded-lg border border-zinc-800 bg-zinc-800/50 px-3 py-2">
                                        @if ($doc->isImage())
                                            <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                        @else
                                            <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                        @endif
                                        <span class="flex-1 truncate text-xs text-zinc-300">{{ $doc->file_original_name }}</span>
                                        <span class="text-xs text-zinc-600">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                           class="text-xs text-accent hover:underline">View</a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Inline edit form --}}
                    <div x-show="editId === {{ $policy->id }}" x-transition x-cloak
                         class="border-t border-zinc-800 bg-zinc-950/40 px-5 py-5">
                        <flux:heading class="mb-4 text-sm font-semibold text-zinc-300">Edit Insurance Policy</flux:heading>
                        <form method="POST" action="{{ route('assets.insurance.update', [$asset, $policy]) }}"
                              enctype="multipart/form-data" class="space-y-4">
                            @csrf @method('PUT')
                            <input type="hidden" name="_form" value="insurance">

                            @include('assets.tabs._insurance-form', ['policy' => $policy])

                            <div class="flex items-center gap-3 pt-1">
                                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                                <flux:button type="button" variant="ghost" size="sm" @click="editId = null">Cancel</flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
