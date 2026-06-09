@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5" x-data="{ showForm: {{ $errors->any() && old('_form') === 'service' ? 'true' : 'false' }}, editId: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Service History</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->services->count() }} {{ Str::plural('record', $asset->services->count()) }}
                @if ($asset->services->sum('service_cost') > 0)
                    &nbsp;·&nbsp; Total cost: ₹ {{ number_format($asset->services->sum('service_cost'), 2) }}
                @endif
            </flux:text>
        </div>
        <flux:button variant="primary" size="sm" icon="plus" @click="showForm = !showForm; editId = null">
            Add Service
        </flux:button>
    </div>

    {{-- Add Form --}}
    <div x-show="showForm && editId === null" x-transition x-cloak
         class="rounded-xl border border-zinc-700 bg-zinc-900 p-5">
        <flux:heading class="mb-4 font-semibold text-zinc-300">New Service Record</flux:heading>

        <form method="POST" action="{{ route('assets.services.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="service">

            @include('assets.tabs._service-form', ['service' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Record</flux:button>
                <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
            </div>
        </form>
    </div>

    {{-- Records List --}}
    @if ($asset->services->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-700 bg-zinc-900 py-16 text-center">
            <flux:icon.cog-6-tooth class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Service Records</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Log preventive maintenance, repairs, inspections, and compliance checks here.</flux:text>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" icon="plus" @click="showForm = true; editId = null">Add First Record</flux:button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($asset->services->sortByDesc('service_date') as $svc)
                @php
                    $nextDays       = $svc->daysUntilNextService();
                    $nextOverdue    = $svc->isNextServiceOverdue();
                    $certDays       = $svc->daysUntilCertificationExpiry();
                    $certExpired    = $svc->isCertificationExpired();
                @endphp

                <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
                    {{-- Card Header --}}
                    <div class="flex items-center justify-between gap-3 bg-zinc-800/40 px-5 py-3"
                         :class="editId === {{ $svc->id }} ? '' : 'border-b border-zinc-800'">
                        <div class="flex items-center gap-3 min-w-0 flex-wrap">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                                {{ $svc->service_type_label }}
                            </span>
                            <span class="text-sm font-semibold text-zinc-200">
                                {{ $svc->service_date->format('d M Y') }}
                            </span>
                            @if ($svc->service_agency)
                                <span class="text-xs text-zinc-500">{{ $svc->service_agency }}</span>
                            @endif
                            @if ($svc->service_cost)
                                <span class="text-xs font-mono text-zinc-400">₹ {{ number_format($svc->service_cost, 2) }}</span>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2 flex-wrap justify-end" x-show="editId !== {{ $svc->id }}">
                            {{-- Next service badge --}}
                            @if ($nextOverdue)
                                <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Service Overdue</span>
                            @elseif ($nextDays !== null && $nextDays <= 30)
                                <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Due in {{ $nextDays }}d</span>
                            @endif
                            {{-- Certification badge --}}
                            @if ($certExpired)
                                <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Cert Expired</span>
                            @elseif ($certDays !== null && $certDays <= 30)
                                <span class="rounded-full bg-orange-400/10 px-2 py-0.5 text-xs font-medium text-orange-400">Cert in {{ $certDays }}d</span>
                            @endif
                            <button type="button"
                                    @click="editId = editId === {{ $svc->id }} ? null : {{ $svc->id }}"
                                    class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-300 hover:border-accent hover:text-accent transition-colors">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('assets.services.destroy', [$asset, $svc]) }}"
                                  onsubmit="return confirm('Delete this service record?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Detail Grid --}}
                    <div class="px-5 py-4" x-show="editId !== {{ $svc->id }}">
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                            @if ($svc->technician_name)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Technician</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $svc->technician_name }}</dd>
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
                                    <dd class="mt-0.5 text-sm text-zinc-200">
                                        Every {{ $svc->service_interval_value }} {{ $svc->service_interval_unit }}
                                    </dd>
                                </div>
                            @endif

                            @if ($svc->meter_reading)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Meter / Op. Hours</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ number_format($svc->meter_reading) }}</dd>
                                </div>
                            @endif

                            @if ($svc->mileage_reading)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Odometer (km)</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ number_format($svc->mileage_reading) }}</dd>
                                </div>
                            @endif

                            @if ($svc->downtime_hours)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Downtime</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $svc->downtime_hours }} hrs</dd>
                                </div>
                            @endif

                            @if ($svc->bill_no)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500">Bill No</dt>
                                    <dd class="mt-0.5 text-sm text-zinc-200">{{ $svc->bill_no }}</dd>
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
                            <div class="mt-3 flex flex-wrap gap-4 rounded-lg border border-zinc-800 bg-zinc-800/30 px-4 py-2 text-xs text-zinc-400">
                                <span>
                                    <flux:icon.puzzle-piece class="inline size-3 mr-1" />
                                    {{ $svc->parts->count() }} {{ Str::plural('part', $svc->parts->count()) }}
                                    @if ($partsCost > 0) — ₹ {{ number_format($partsCost, 2) }} @endif
                                </span>
                                @if ($svc->service_cost && $partsCost > 0)
                                    <span class="font-semibold text-zinc-200">
                                        Total: ₹ {{ number_format($svc->grandTotalCost(), 2) }}
                                    </span>
                                @endif
                                <a href="{{ route('assets.show', [$asset, 'tab' => 'parts']) }}"
                                   class="text-accent hover:underline">View parts →</a>
                            </div>
                        @endif

                        @if ($svc->work_done)
                            <div class="mt-4 border-t border-zinc-800 pt-4">
                                <p class="mb-1 text-xs font-medium text-zinc-500">Work Done</p>
                                <p class="text-sm text-zinc-200 whitespace-pre-line">{{ $svc->work_done }}</p>
                            </div>
                        @endif

                        @if ($svc->safety_notes)
                            <div class="mt-3">
                                <p class="mb-1 text-xs font-medium text-zinc-500">Safety Notes</p>
                                <p class="text-sm text-zinc-200 whitespace-pre-line">{{ $svc->safety_notes }}</p>
                            </div>
                        @endif

                        @if ($svc->remarks)
                            <div class="mt-3">
                                <p class="mb-1 text-xs font-medium text-zinc-500">Remarks</p>
                                <p class="text-sm text-zinc-300">{{ $svc->remarks }}</p>
                            </div>
                        @endif

                        {{-- Documents --}}
                        @if ($svc->documents->isNotEmpty())
                            <div class="mt-4 space-y-1.5 border-t border-zinc-800 pt-4">
                                <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                                @foreach ($svc->documents as $doc)
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

                    {{-- Inline Edit Form --}}
                    <div x-show="editId === {{ $svc->id }}" x-transition x-cloak
                         class="border-t border-zinc-800 bg-zinc-950/40 px-5 py-5">
                        <flux:heading class="mb-4 text-sm font-semibold text-zinc-300">Edit Service Record</flux:heading>
                        <form method="POST" action="{{ route('assets.services.update', [$asset, $svc]) }}"
                              enctype="multipart/form-data" class="space-y-4">
                            @csrf @method('PUT')
                            <input type="hidden" name="_form" value="service">

                            @include('assets.tabs._service-form', ['service' => $svc])

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
