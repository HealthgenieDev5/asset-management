@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp


<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">AMC Contracts</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->amcContracts->count() }} {{ Str::plural('contract', $asset->amcContracts->count()) }}
            </flux:text>
        </div>
        {{-- <button type="button" x-on:click="$dispatch('open-modal-add-amc')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add AMC
        </button> --}}
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

    {{-- View Modals (one per contract) - inline-edit two-column layout --}}
    @foreach ($asset->amcContracts->sortByDesc('created_at') as $amc)
        @php
            $vDays    = $amc->daysUntilExpiry();
            $vExpired = $amc->isExpired();
            $vSoon    = ! $vExpired && $vDays !== null && $vDays <= 30;
            $amcPatchUrl  = route('assets.amc.patch-field', [$asset, $amc]);
            $amcDocStore  = route('assets.amc.documents.store', [$asset, $amc]);
            $amcDocRevert = route('assets.amc.documents.revert', $asset);
            $amcFirstDoc  = $amc->documents->first();
            $amcExtraDocs = $amc->documents->skip(1);
            $aInp  = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
            $aBtnOk = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
            $aBtnX  = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
            $aDt    = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
            $aDd    = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
            $aPencil = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
            $aCheck  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
            $aX      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
        @endphp

        <x-modal name="view-amc-{{ $amc->id }}" title="AMC Contract Details">
            <x-slot:footer>
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:icon.wrench-screwdriver class="size-4 shrink-0 text-zinc-400" />
                    @if ($vExpired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($vSoon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $vDays }}d</span>
                    @elseif ($vDays !== null)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                    @if ($amc->contract_number)
                        <span class="font-mono text-xs text-zinc-500">{{ $amc->contract_number }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal-view-amc-{{ $amc->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                        <flux:icon.x-mark class="size-3.5" />
                        Close
                    </button>
                    <form method="POST" action="{{ route('assets.amc.destroy', [$asset, $amc]) }}" onsubmit="confirmDelete(this, 'Delete this AMC contract?'); return false;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300/60 px-3 py-1.5 text-xs font-medium text-red-500 transition-colors hover:border-red-500/60 hover:bg-red-500/5 dark:border-red-700/50 dark:text-red-400 dark:hover:border-red-500/60">
                            <flux:icon.trash class="size-3.5" />
                            Delete
                        </button>
                    </form>
                </div>
            </x-slot:footer>

            <div x-data="{
                aVendorLabel: {{ json_encode($amc->vendor?->name ?? '') }},
                aContractNo:  {{ json_encode($amc->contract_number ?? '') }},
                aCoverage:    '{{ $amc->coverage_type }}',
                aDateFrom:    '{{ $amc->amc_date_from?->format('d M Y') ?? '' }}',
                aDateTo:      '{{ $amc->amc_date_to?->format('d M Y') ?? '' }}',
                aAmount:      '{{ $amc->amc_amount ?? '' }}',
                aBillNo:      {{ json_encode($amc->amc_bill_no ?? '') }},
                aBillDate:    '{{ $amc->amc_bill_date?->format('d M Y') ?? '' }}',
                aRemindDays:  '{{ $amc->reminder_before_days ?? '' }}',
                aCovDetails:  {{ json_encode($amc->coverage_details ?? '') }},
                aTerms:       {{ json_encode($amc->amc_terms ?? '') }},
                aRemarks:     {{ json_encode($amc->remarks ?? '') }},
                async ap(field, value) {
                    const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                    const r = await fetch('{{ $amcPatchUrl }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: fd,
                    });
                    if (!r.ok) { toastr.error('Save failed.'); return false; }
                    const d = await r.json();
                    toastr.success('Updated.');
                    if (field === 'vendor_id') { this.aVendorLabel = d.label || ''; }
                    return true;
                }
            }" class="flex min-h-0 gap-5 mt-1">

                {{-- ── Left: editable fields ── --}}
                <div class="flex-1 min-w-0 space-y-5">

                    {{-- ── Contract ── --}}
                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contract</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            {{-- Contract Number --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Contract Number</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="font-mono text-sm text-zinc-800 dark:text-zinc-200" x-text="aContractNo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpCno" class="{{ $aInp }} w-36" :value="aContractNo" maxlength="255" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('contract_number',$refs.inpCno.value)){aContractNo=$refs.inpCno.value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Vendor --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Vendor</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aVendorLabel||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <select x-ref="selVnd" class="{{ $aInp }} max-w-44">
                                                <option value="">— None —</option>
                                                @foreach ($vendors ?? [] as $vnd)
                                                    <option value="{{ $vnd->id }}" {{ $amc->vendor_id == $vnd->id ? 'selected' : '' }}>{{ $vnd->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="{{ $aBtnOk }}"
                                                @click="if(await ap('vendor_id',$refs.selVnd.value)){aVendorLabel=$refs.selVnd.options[$refs.selVnd.selectedIndex].text==='— None —'?'':$refs.selVnd.options[$refs.selVnd.selectedIndex].text;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Coverage Type --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Coverage Type</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="{'comprehensive':'Comprehensive','non_comprehensive':'Non-Comprehensive','parts_only':'Parts Only','labour_only':'Labour Only'}[aCoverage]||aCoverage"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <select x-ref="selCov" class="{{ $aInp }}" :value="aCoverage">
                                                <option value="comprehensive">Comprehensive</option>
                                                <option value="non_comprehensive">Non-Comprehensive</option>
                                                <option value="parts_only">Parts Only</option>
                                                <option value="labour_only">Labour Only</option>
                                            </select>
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('coverage_type',$refs.selCov.value)){aCoverage=$refs.selCov.value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- AMC From --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpFrom{{ $amc->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $aDt }}">AMC From</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aDateFrom||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpFrom{{ $amc->id }}" class="{{ $aInp }} w-28" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_date_from',$refs['fpFrom{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpFrom{{ $amc->id }}'].value)){aDateFrom=$refs['fpFrom{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpFrom{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- AMC Lapse Date --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpTo{{ $amc->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $aDt }}">AMC Lapse Date</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $vExpired ? 'text-red-400 font-semibold text-sm' : ($vSoon ? 'text-yellow-400 text-sm' : $aDd) }}" x-text="aDateTo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpTo{{ $amc->id }}" class="{{ $aInp }} w-28" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_date_to',$refs['fpTo{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpTo{{ $amc->id }}'].value)){aDateTo=$refs['fpTo{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpTo{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Billing ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Billing</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            {{-- Amount --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">AMC Amount (₹)</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aAmount ? '₹ ' + parseFloat(aAmount).toLocaleString('en-IN',{minimumFractionDigits:2}) : '--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="number" x-ref="inpAmt" class="{{ $aInp }} w-28" :value="aAmount" min="0" step="0.01" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_amount',$refs.inpAmt.value)){aAmount=$refs.inpAmt.value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Bill Number --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Bill Number</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aBillNo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpBill" class="{{ $aInp }} w-32" :value="aBillNo" maxlength="255" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_bill_no',$refs.inpBill.value)){aBillNo=$refs.inpBill.value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Bill Date --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpBill{{ $amc->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $aDt }}">Bill Date</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aBillDate||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpBill{{ $amc->id }}" class="{{ $aInp }} w-28" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_bill_date',$refs['fpBill{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpBill{{ $amc->id }}'].value)){aBillDate=$refs['fpBill{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpBill{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Notes ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Notes</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Coverage Details</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5">
                                    <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="aCovDetails||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }} mt-0.5">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex w-full flex-col gap-1">
                                            <textarea x-ref="taCov" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="aCovDetails"></textarea>
                                            <span class="flex gap-1">
                                                <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('coverage_details',$refs.taCov.value)){aCovDetails=$refs.taCov.value;editing=false}">{!! $aCheck !!}</button>
                                                <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                            </span>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">AMC Terms</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5">
                                    <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="aTerms||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }} mt-0.5">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex w-full flex-col gap-1">
                                            <textarea x-ref="taTerms" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="aTerms"></textarea>
                                            <span class="flex gap-1">
                                                <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_terms',$refs.taTerms.value)){aTerms=$refs.taTerms.value;editing=false}">{!! $aCheck !!}</button>
                                                <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                            </span>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Remarks</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5">
                                    <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="aRemarks||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }} mt-0.5">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex w-full flex-col gap-1">
                                            <textarea x-ref="taRem" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="aRemarks"></textarea>
                                            <span class="flex gap-1">
                                                <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('remarks',$refs.taRem.value)){aRemarks=$refs.taRem.value;editing=false}">{!! $aCheck !!}</button>
                                                <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                            </span>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                </div>{{-- end left --}}

                {{-- ── Right: Document panel ── --}}
                <aside class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700 flex flex-col">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>
                    <div class="amc-doc-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                    <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                </div>
                                <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                            </div>`,
                            files: @js($amcFirstDoc ? [['source' => Storage::url($amcFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                            fileMetaBySource: @js($amcFirstDoc ? [Storage::url($amcFirstDoc->file_path) => ['name' => $amcFirstDoc->file_original_name]] : (object)[]),
                            deleteUrl: @js($amcFirstDoc ? route('assets.amc.documents.destroy', [$asset, $amcFirstDoc]) : ''),
                            csrfToken: @js(csrf_token()),
                            revertUrlTemplate: () => @js(route('assets.amc.documents.revert', $asset)),
                            server: {
                                process: { url: @js($amcDocStore), method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' }, onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); }, onerror: (e) => toastr.error('Upload failed.') },
                            },
                        })
                    "><input type="file" /></div>

                    @if ($amcFirstDoc)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                            @if ($amcFirstDoc->isImage())
                                <flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />
                            @else
                                <flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />
                            @endif
                            <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $amcFirstDoc->file_original_name }}</p>
                            <button type="button"
                                x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($amcFirstDoc->file_path) }}', title: '{{ addslashes($amcFirstDoc->file_original_name) }}', isPdf: {{ $amcFirstDoc->isImage() ? 'false' : 'true' }} })"
                                title="View"
                                class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                <flux:icon.eye class="size-3" />
                            </button>
                            <a href="{{ Storage::url($amcFirstDoc->file_path) }}" download="{{ $amcFirstDoc->file_original_name }}"
                                title="Download"
                                class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                <flux:icon.arrow-down-tray class="size-3" />
                            </a>
                        </div>
                    @endif

                    @if ($amcExtraDocs->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach ($amcExtraDocs as $doc)
                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                    <button type="button"
                                        x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.eye class="size-3" />
                                    </button>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                        class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                    </a>
                                    <form method="POST" action="{{ route('assets.amc.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this,'Delete this document?');return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if (!$amcFirstDoc && $amcExtraDocs->isEmpty())
                        <div class="mt-3 flex flex-col items-center justify-center">
                            <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                        </div>
                    @endif
                </aside>{{-- end right --}}

            </div>
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
                            {{ $amc->vendor?->name ?? $amc->vendor_name ?: 'AMC Contract' }}
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
                                x-on:click="$dispatch('open-modal-view-amc-{{ $amc->id }}')"
                                aria-label="View AMC contract"
                                title="View AMC contract"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'amcid' => $amc->id]) }}"
                           title="{{ $amc->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border border-accent text-accent hover:bg-accent/10 transition-colors">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        {{-- <button type="button"
                                x-on:click="$dispatch('open-modal-edit-amc-{{ $amc->id }}')"
                                aria-label="Edit AMC contract"
                                title="Edit AMC contract"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button> --}}
                        {{-- <form method="POST" action="{{ route('assets.amc.destroy', [$asset, $amc]) }}"
                              onsubmit="confirmDelete(this, 'Delete this AMC contract?'); return false;">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete AMC contract"
                                    title="Delete AMC contract"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form> --}}
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
                        @if ($amc->vendor || $amc->vendor_phone || $amc->vendor_email)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="text-xs font-medium text-zinc-500">Vendor Contact</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">
                                    @if ($amc->vendor)
                                        <a href="{{ route('vendors.show', $amc->vendor) }}" wire:navigate class="text-accent hover:underline">{{ $amc->vendor->name }}</a>
                                        @if ($amc->vendor->phone)
                                            <span class="ml-1 text-zinc-500">— {{ $amc->vendor->phone }}</span>
                                        @endif
                                    @else
                                        {{ implode(' · ', array_filter([$amc->vendor_phone, $amc->vendor_email])) }}
                                    @endif
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
                        <div class="mt-4 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>
                            <div class="space-y-1.5">
                                @foreach ($amc->documents as $doc)
                                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                        @if ($doc->isImage())
                                            <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                                        @else
                                            <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                        @endif
                                        <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                        <span class="text-xs text-zinc-600">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
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
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
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

    {{-- Shared lightbox for AMC document previews --}}
    <div x-data="docLightbox()"
         x-on:keydown.escape.window="close()"
         x-on:open-doc-lightbox.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
         x-show="open" style="display:none"
         class="fixed inset-0 z-200 flex flex-col bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-2.5">
            <p class="truncate text-sm font-medium text-white" x-text="title"></p>
            <button type="button" @click="close()"
                    class="shrink-0 rounded-md p-1 text-white/60 hover:bg-white/10 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>
        <div class="flex flex-1 cursor-zoom-out items-center justify-center overflow-hidden p-4" @click.self="close()">
            <template x-if="isPdf">
                <iframe :src="src" class="h-full w-full max-w-4xl rounded-lg border-0 bg-white" style="min-height:70vh"></iframe>
            </template>
            <template x-if="!isPdf">
                <img :src="src" :alt="title" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
            </template>
        </div>
    </div>

</div>
