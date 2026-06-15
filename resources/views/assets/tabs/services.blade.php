@php use Illuminate\Support\Facades\Storage; @endphp


<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Servicing History</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->services->count() }} {{ Str::plural('record', $asset->services->count()) }}
                @if ($asset->services->sum('service_cost') > 0)
                    &nbsp;·&nbsp; Total cost: ₹ {{ number_format($asset->services->sum('service_cost'), 2) }}
                @endif
            </flux:text>
        </div>
        <flux:modal.trigger name="add-service">
            <flux:button variant="primary" size="sm" icon="plus">Add Servicing</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Add Modal --}}
    <flux:modal name="add-service" :show="$errors->any() && old('_form') === 'service' && !old('_service_id')" focusable :dismissible="false">
        <flux:heading class="font-semibold">New Servicing Record</flux:heading>

        <form method="POST" action="{{ route('assets.services.store', $asset) }}"
              enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="service">

            @include('assets.tabs._service-form', ['service' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Record</flux:button>
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" size="sm">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modals (one per service record) --}}
    @foreach ($asset->services->sortByDesc('service_date') as $svc)
        <flux:modal name="edit-service-{{ $svc->id }}"
                    :show="$errors->any() && old('_form') === 'service' && (int) old('_service_id') === $svc->id"
                    :dismissible="false"
                    focusable>
            <flux:heading class="font-semibold">Edit Servicing Record</flux:heading>

            <form method="POST" action="{{ route('assets.services.update', [$asset, $svc]) }}"
                  enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="service">
                <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                @include('assets.tabs._service-form', ['service' => $svc])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" size="sm">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>
    @endforeach

    {{-- Add Part Modals (one per service record) --}}
    @foreach ($asset->services as $svc)
        <flux:modal name="add-part-{{ $svc->id }}"
                    :show="$errors->any() && old('_form') === 'part' && (int) old('_service_id') === $svc->id"
                    :dismissible="false"
                    focusable>
            <flux:heading class="font-semibold">Add Part</flux:heading>

            <form method="POST" action="{{ route('assets.services.parts.store', [$asset, $svc]) }}"
                  class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="part">
                <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                @include('assets.tabs._part-form', ['part' => null])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Part</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" size="sm">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>
    @endforeach

    {{-- Records List --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            @php
                $nextDays    = $svc->daysUntilNextService();
                $nextOverdue = $svc->isNextServiceOverdue();
                $certDays    = $svc->daysUntilCertificationExpiry();
                $certExpired = $svc->isCertificationExpired();
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                {{-- Card Header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-3 min-w-0 flex-wrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                            {{ $svc->service_type_label }}
                        </span>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $svc->service_date->format('d M Y') }}
                        </span>
                        @if ($svc->service_agency)
                            <span class="text-xs text-zinc-500 dark:text-zinc-500">{{ $svc->service_agency }}</span>
                        @endif
                        @if ($svc->service_cost)
                            <span class="text-xs font-mono text-zinc-400">₹ {{ number_format($svc->service_cost, 2) }}</span>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-2 flex-wrap justify-end">
                        @if ($nextOverdue)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Service Overdue</span>
                        @elseif ($nextDays !== null && $nextDays <= 30)
                            <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Due in {{ $nextDays }}d</span>
                        @endif
                        @if ($certExpired)
                            <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Cert Expired</span>
                        @elseif ($certDays !== null && $certDays <= 30)
                            <span class="rounded-full bg-orange-400/10 px-2 py-0.5 text-xs font-medium text-orange-400">Cert in {{ $certDays }}d</span>
                        @endif
                        <flux:modal.trigger name="edit-service-{{ $svc->id }}">
                            <button type="button"
                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                                Edit
                            </button>
                        </flux:modal.trigger>
                        <form method="POST" action="{{ route('assets.services.destroy', [$asset, $svc]) }}"
                              onsubmit="return confirm('Delete this service record?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Detail Grid --}}
                <div class="px-5 py-4">
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($svc->technician_name)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Technician</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->technician_name }}</dd>
                            </div>
                        @endif

                        @if ($svc->condition_rating)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Condition</dt>
                                <dd class="mt-0.5 text-sm font-medium {{ $svc->condition_rating_color }}">{{ $svc->condition_rating_label }}</dd>
                            </div>
                        @endif

                        @if ($svc->next_service_date)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Next Service Due</dt>
                                <dd class="mt-0.5 text-sm {{ $nextOverdue ? 'text-red-400 font-semibold' : ($nextDays !== null && $nextDays <= 30 ? 'text-yellow-400' : 'text-zinc-200') }}">
                                    {{ $svc->next_service_date->format('d M Y') }}
                                    @if ($nextOverdue)
                                        <span class="text-xs font-normal">(Overdue)</span>
                                    @elseif ($nextDays !== null && $nextDays <= 30)
                                        <span class="text-xs">({{ $nextDays }}d)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif

                        @if ($svc->service_interval_value && $svc->service_interval_unit)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Interval</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">Every {{ $svc->service_interval_value }} {{ $svc->service_interval_unit }}</dd>
                            </div>
                        @endif

                        @if ($svc->meter_reading)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Meter / Op. Hours</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ number_format($svc->meter_reading) }}</dd>
                            </div>
                        @endif

                        @if ($svc->mileage_reading)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Odometer (km)</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ number_format($svc->mileage_reading) }}</dd>
                            </div>
                        @endif

                        @if ($svc->downtime_hours)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Downtime</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->downtime_hours }} hrs</dd>
                            </div>
                        @endif

                        @if ($svc->bill_no)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $svc->bill_no }}</dd>
                            </div>
                        @endif

                        @if ($svc->certification_expiry)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Certification Expiry</dt>
                                <dd class="mt-0.5 text-sm {{ $certExpired ? 'text-red-400 font-semibold' : ($certDays !== null && $certDays <= 30 ? 'text-orange-400' : 'text-zinc-200') }}">
                                    {{ $svc->certification_expiry->format('d M Y') }}
                                    @if ($certExpired)
                                        <span class="text-xs font-normal">(Expired)</span>
                                    @elseif ($certDays !== null && $certDays <= 30)
                                        <span class="text-xs">({{ $certDays }}d left)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Parts cost summary --}}
                    @if ($svc->parts->isNotEmpty())
                        @php $partsCost = $svc->totalPartsCost(); @endphp
                        <div class="mt-3 flex flex-wrap gap-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/30 dark:text-zinc-400">
                            <span>
                                <flux:icon.puzzle-piece class="inline size-3 mr-1" />
                                {{ $svc->parts->count() }} {{ Str::plural('part', $svc->parts->count()) }}
                                @if ($partsCost > 0) — ₹ {{ number_format($partsCost, 2) }} @endif
                            </span>
                            @if ($svc->service_cost && $partsCost > 0)
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                    Total: ₹ {{ number_format($svc->grandTotalCost(), 2) }}
                                </span>
                            @endif
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'parts']) }}"
                               class="text-accent hover:underline">View parts →</a>
                        </div>
                    @endif

                    @if ($svc->work_done)
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Work Done</p>
                            <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $svc->work_done }}</p>
                        </div>
                    @endif

                    @if ($svc->safety_notes)
                        <div class="mt-3">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Safety Notes</p>
                            <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $svc->safety_notes }}</p>
                        </div>
                    @endif

                    @if ($svc->remarks)
                        <div class="mt-3">
                            <p class="mb-1 text-xs font-medium text-zinc-500">Remarks</p>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $svc->remarks }}</p>
                        </div>
                    @endif

                    {{-- Documents --}}
                    @if ($svc->documents->isNotEmpty())
                        <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            @foreach ($svc->documents as $doc)
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

                    {{-- Add Part --}}
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-zinc-500">Parts ({{ $svc->parts->count() }})</p>
                            <flux:modal.trigger name="add-part-{{ $svc->id }}">
                                <button type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Add Part
                                </button>
                            </flux:modal.trigger>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Always-visible placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.cog-6-tooth class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->services->isEmpty() ? 'No Servicing Records' : 'Add Another Record' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Log preventive maintenance, repairs, inspections, and compliance checks here.</flux:text>
            <div class="mt-4">
                <flux:modal.trigger name="add-service">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        {{ $asset->services->isEmpty() ? 'Add First Record' : 'Add Servicing Record' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
</div>
