<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Parts Replacement History</flux:heading>
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
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">No Servicing Records Yet</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Add a servicing record first, then log parts replaced during that service.</flux:text>
                <div class="mt-4">
                    <flux:button href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}" wire:navigate variant="ghost" size="sm">
                        Go to Servicing Tab
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        {{-- Add Part Modals (one per service) --}}
        @foreach ($asset->services->sortByDesc('service_date') as $svc)
            <x-modal name="add-part-{{ $svc->id }}" title="Add Part — {{ $svc->service_date->format('d M Y') }}" :dismissible="false"
                :auto-open="$errors->any() && old('_form') === 'part' && (int) old('_service_id') === $svc->id && !old('_part_id')">
                <form method="POST" action="{{ route('assets.services.parts.store', [$asset, $svc]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_form" value="part">
                    <input type="hidden" name="_service_id" value="{{ $svc->id }}">

                    @include('assets.tabs._part-form', ['part' => null])

                    <div class="flex items-center gap-3 pt-1">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">Save Part</flux:button>
                        <button type="button" x-on:click="$dispatch('close-modal-add-part-{{ $svc->id }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </x-modal>

            {{-- Edit Part Modals --}}
            @foreach ($svc->parts as $part)
                <x-modal name="edit-part-{{ $part->id }}" title="Edit Part" :dismissible="false"
                    :auto-open="$errors->any() && old('_form') === 'part' && (int) old('_part_id') === $part->id">
                    <form method="POST" action="{{ route('assets.services.parts.update', [$asset, $svc, $part]) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <input type="hidden" name="_form" value="part">
                        <input type="hidden" name="_service_id" value="{{ $svc->id }}">
                        <input type="hidden" name="_part_id" value="{{ $part->id }}">

                        @include('assets.tabs._part-form', ['part' => $part])

                        <div class="flex items-center gap-3 pt-1">
                            <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                            <button type="button" x-on:click="$dispatch('close-modal-edit-part-{{ $part->id }}')"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="view-part-{{ $part->id }}" title="Part Replacement Details">
                    @php $lineTotal = $part->part_cost !== null ? (float) $part->part_cost * $part->quantity : null; @endphp
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <flux:icon.puzzle-piece class="size-4 shrink-0 text-zinc-400" />
                                    <h3 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $part->part_name }}</h3>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $svc->service_type_label }} - {{ $svc->service_date->format('d M Y') }}
                                    @if ($svc->service_agency) - {{ $svc->service_agency }} @endif
                                </p>
                            </div>
                            @if ($part->warranty_till)
                                @php $partWarrantyExpired = $part->warranty_till->lt(now()->startOfDay()); @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $partWarrantyExpired ? 'bg-red-400/10 text-red-400' : 'bg-green-400/10 text-green-400' }}">
                                    {{ $partWarrantyExpired ? 'Warranty Expired' : 'Under Warranty' }}
                                </span>
                            @endif
                        </div>

                        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Quantity</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $part->quantity }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Unit Cost</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $part->part_cost !== null ? 'Rs. ' . number_format($part->part_cost, 2) : '--' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Line Total</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $lineTotal !== null ? 'Rs. ' . number_format($lineTotal, 2) : '--' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Purchased From</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $part->purchased_from ?: '--' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Warranty Till</dt>
                                <dd class="mt-0.5 text-sm {{ $part->warranty_till && $part->warranty_till->lt(now()->startOfDay()) ? 'text-red-400 font-semibold' : 'text-zinc-800 dark:text-zinc-100' }}">
                                    {{ $part->warranty_till?->format('d M Y') ?: '--' }}
                                    @if ($part->warranty_till && $part->warranty_till->lt(now()->startOfDay())) <span class="text-xs font-normal">(Expired)</span> @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Service Cost</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $svc->service_cost ? 'Rs. ' . number_format($svc->service_cost, 2) : '--' }}</dd>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $part->remarks ?: '--' }}</dd>
                            </div>
                        </dl>
                    </div>
                </x-modal>
            @endforeach
        @endforeach

        {{-- Service cards grid --}}
        <div class="grid grid-cols-3 gap-4">
            @foreach ($asset->services->sortByDesc('service_date') as $svc)
                @php
                    $partsCostTotal = $svc->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
                    $grandTotal     = ($svc->service_cost ?? 0) + $partsCostTotal;
                @endphp

                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                    {{-- Card header --}}
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="flex items-center gap-2 min-w-0 flex-wrap">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $svc->service_type_color }}">
                                {{ $svc->service_type_label }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $svc->service_date->format('d M Y') }}</span>
                            @if ($svc->service_agency)
                                <span class="truncate text-xs text-zinc-500">{{ $svc->service_agency }}</span>
                            @endif
                        </div>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-add-part-{{ $svc->id }}')"
                                class="shrink-0 inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Add
                        </button>
                    </div>

                    {{-- Parts list --}}
                    @if ($svc->parts->isEmpty())
                        <div class="px-4 py-6 text-center">
                            <flux:text class="text-xs text-zinc-500">No parts recorded.</flux:text>
                            <button type="button"
                                    x-on:click="$dispatch('open-modal-add-part-{{ $svc->id }}')"
                                    class="mt-2 inline-flex items-center gap-1 text-xs text-accent hover:underline">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add Part
                            </button>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($svc->parts as $part)
                                @php $lineTotal = $part->part_cost !== null ? (float) $part->part_cost * $part->quantity : null; @endphp
                                <div class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $part->part_name }}</p>
                                            <p class="mt-0.5 text-[11px] text-zinc-500">
                                                Qty: {{ $part->quantity }}
                                                @if ($part->part_cost !== null)
                                                    · ₹ {{ number_format($part->part_cost, 2) }}
                                                    @if ($part->quantity > 1) = ₹ {{ number_format($lineTotal, 2) }} @endif
                                                @endif
                                            </p>
                                            @if ($part->purchased_from)
                                                <p class="text-[11px] text-zinc-500">{{ $part->purchased_from }}</p>
                                            @endif
                                            @if ($part->warranty_till)
                                                <p class="text-[11px] {{ $part->warranty_till->lt(now()->startOfDay()) ? 'text-red-400' : 'text-zinc-500' }}">
                                                    Warranty: {{ $part->warranty_till->format('d M Y') }}
                                                    @if ($part->warranty_till->lt(now()->startOfDay())) (Expired) @endif
                                                </p>
                                            @endif
                                            @if ($part->remarks)
                                                <p class="mt-0.5 text-[11px] text-zinc-500 italic">{{ $part->remarks }}</p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <button type="button"
                                                    x-on:click="$dispatch('open-modal-view-part-{{ $part->id }}')"
                                                    aria-label="View part record"
                                                    title="View part record"
                                                    class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                                <flux:icon.eye class="size-3" />
                                            </button>
                                            <button type="button"
                                                    x-on:click="$dispatch('open-modal-edit-part-{{ $part->id }}')"
                                                    aria-label="Edit part record"
                                                    title="Edit part record"
                                                    class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                                <flux:icon.pencil class="size-3" />
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('assets.services.parts.destroy', [$asset, $svc, $part]) }}"
                                                  onsubmit="return confirm('Delete this part record?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        aria-label="Delete part record"
                                                        title="Delete part record"
                                                        class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Cost footer --}}
                        @if ($grandTotal > 0)
                            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-800 dark:bg-zinc-800/20">
                                <div class="flex flex-wrap items-center justify-end gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
                                    @if ($svc->service_cost)
                                        <span>Labour: <span class="font-semibold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($svc->service_cost, 2) }}</span></span>
                                    @endif
                                    @if ($partsCostTotal > 0)
                                        <span>Parts: <span class="font-semibold text-zinc-700 dark:text-zinc-200">₹ {{ number_format($partsCostTotal, 2) }}</span></span>
                                    @endif
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-100">Total: ₹ {{ number_format($grandTotal, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

         
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">Add Another Service</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Log a new servicing record to track more replaced parts.</flux:text>
                <div class="mt-4">
                    <a href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Go to Servicing Tab
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
