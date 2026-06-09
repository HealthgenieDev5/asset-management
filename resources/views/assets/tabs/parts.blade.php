<div class="space-y-6" x-data="{ showForm: {{ $errors->any() && old('_form') === 'part' ? 'true' : 'false' }}, formServiceId: {{ old('_service_id', 'null') }}, editId: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Parts Replacement History</flux:heading>
            @php
                $totalParts     = $asset->services->flatMap->parts;
                $totalPartsCost = $totalParts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
                $totalSvcCost   = $asset->services->sum('service_cost');
            @endphp
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $totalParts->count() }} {{ Str::plural('part', $totalParts->count()) }}
                @if ($totalPartsCost > 0)
                    &nbsp;·&nbsp; Parts cost: ₹ {{ number_format($totalPartsCost, 2) }}
                @endif
                @if ($totalSvcCost > 0 && $totalPartsCost > 0)
                    &nbsp;·&nbsp; Combined: ₹ {{ number_format($totalSvcCost + $totalPartsCost, 2) }}
                @endif
            </flux:text>
        </div>
    </div>

    @if ($asset->services->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-700 bg-zinc-900 py-16 text-center">
            <flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Service Records Yet</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Add a service record first, then log parts replaced during that service.</flux:text>
            <div class="mt-4">
                <flux:button href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}" wire:navigate variant="ghost" size="sm">
                    Go to Services Tab
                </flux:button>
            </div>
        </div>
    @else
        {{-- Per-service grouped parts --}}
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">

                {{-- Service header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-800 bg-zinc-800/40 px-5 py-3">
                    <div class="flex items-center gap-3 flex-wrap min-w-0">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                            {{ $svc->service_type_label }}
                        </span>
                        <span class="text-sm font-semibold text-zinc-200">{{ $svc->service_date->format('d M Y') }}</span>
                        @if ($svc->service_agency)
                            <span class="text-xs text-zinc-500">{{ $svc->service_agency }}</span>
                        @endif
                        @if ($svc->parts->isNotEmpty())
                            @php $partsCost = $svc->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity); @endphp
                            <span class="text-xs text-zinc-400">
                                {{ $svc->parts->count() }} {{ Str::plural('part', $svc->parts->count()) }}
                                @if ($partsCost > 0) — ₹ {{ number_format($partsCost, 2) }} @endif
                                @if ($svc->service_cost) &nbsp;+&nbsp; ₹ {{ number_format($svc->service_cost, 2) }} labour @endif
                            </span>
                        @endif
                    </div>
                    <flux:button variant="ghost" size="sm" icon="plus"
                                 @click="showForm = true; formServiceId = {{ $svc->id }}">
                        Add Part
                    </flux:button>
                </div>

                {{-- Add Part Form (shown per service) --}}
                <div x-show="showForm && formServiceId === {{ $svc->id }}" x-transition x-cloak
                     data-add-form="{{ $svc->id }}"
                     class="border-b border-zinc-800 bg-zinc-950/40 px-5 py-5">
                    <flux:heading class="mb-4 text-sm font-semibold text-zinc-300">Add Part for this Service</flux:heading>
                    <form method="POST"
                          action="{{ route('assets.services.parts.store', [$asset, $svc]) }}"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="part">
                        <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                        @include('assets.tabs._part-form', ['part' => null])

                        <div class="flex items-center gap-3 pt-1">
                            <flux:button type="submit" variant="primary" size="sm" icon="check">Save Part</flux:button>
                            <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
                        </div>
                    </form>
                </div>

                {{-- Parts list --}}
                @if ($svc->parts->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <flux:text class="text-sm text-zinc-600">No parts recorded for this service.</flux:text>
                        <flux:button variant="ghost" size="sm" icon="plus" class="mt-2"
                                     @click="showForm = true; formServiceId = {{ $svc->id }}">
                            Add Part
                        </flux:button>
                    </div>
                @else
                    <div class="divide-y divide-zinc-800/60">
                        @foreach ($svc->parts as $part)
                            @php $lineTotal = $part->part_cost !== null ? (float) $part->part_cost * $part->quantity : null; @endphp
                            <div class="px-5 py-3">
                                <div x-show="editId !== {{ $part->id }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="text-sm font-semibold text-zinc-200">{{ $part->part_name }}</span>
                                                <span class="text-xs text-zinc-500">Qty: {{ $part->quantity }}</span>
                                                @if ($part->part_cost !== null)
                                                    <span class="text-xs text-zinc-400">
                                                        ₹ {{ number_format($part->part_cost, 2) }} ea.
                                                        @if ($part->quantity > 1)
                                                            = ₹ {{ number_format($lineTotal, 2) }}
                                                        @endif
                                                    </span>
                                                @endif
                                                @if ($part->purchased_from)
                                                    <span class="text-xs text-zinc-500">from {{ $part->purchased_from }}</span>
                                                @endif
                                                @if ($part->warranty_till)
                                                    <span class="text-xs {{ $part->warranty_till->isPast() ? 'text-red-400' : 'text-zinc-500' }}">
                                                        Warranty: {{ $part->warranty_till->format('d M Y') }}
                                                        @if ($part->warranty_till->isPast()) (Expired) @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($part->remarks)
                                                <p class="mt-0.5 text-xs text-zinc-500">{{ $part->remarks }}</p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button type="button"
                                                    @click="editId = {{ $part->id }}"
                                                    class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-300 hover:border-accent hover:text-accent transition-colors">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('assets.services.parts.destroy', [$asset, $svc, $part]) }}"
                                                  onsubmit="return confirm('Delete this part record?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Inline Edit --}}
                                <div x-show="editId === {{ $part->id }}" x-cloak class="mt-1">
                                    <form method="POST"
                                          action="{{ route('assets.services.parts.update', [$asset, $svc, $part]) }}"
                                          class="space-y-3">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="_form" value="part">
                                        <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                                        @include('assets.tabs._part-form', ['part' => $part])

                                        <div class="flex items-center gap-3">
                                            <flux:button type="submit" variant="primary" size="sm" icon="check">Save</flux:button>
                                            <flux:button type="button" variant="ghost" size="sm" @click="editId = null">Cancel</flux:button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Parts cost footer --}}
                    @if ($svc->parts->isNotEmpty())
                        @php
                            $partsCostTotal = $svc->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
                            $grandTotal     = ($svc->service_cost ?? 0) + $partsCostTotal;
                        @endphp
                        <div class="border-t border-zinc-800 bg-zinc-800/20 px-5 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-4 text-xs text-zinc-400">
                                @if ($svc->service_cost)
                                    <span>Labour / Service: <span class="font-semibold text-zinc-200">₹ {{ number_format($svc->service_cost, 2) }}</span></span>
                                @endif
                                @if ($partsCostTotal > 0)
                                    <span>Parts: <span class="font-semibold text-zinc-200">₹ {{ number_format($partsCostTotal, 2) }}</span></span>
                                @endif
                                @if ($grandTotal > 0)
                                    <span class="font-semibold text-zinc-100">Total: ₹ {{ number_format($grandTotal, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</div>
