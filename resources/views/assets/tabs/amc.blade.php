@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp
<style>.amc-doc-upload .filepond--panel-root { border: 1px dashed #4b4b4c; border-radius: 10px; }</style>


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

                    {{-- ── Contract Info ── --}}
                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contract Info</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

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

                        </dl>
                    </div>

                    {{-- ── Period ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Period</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

                            {{-- From Date --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpFrom{{ $amc->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $aDt }}">From</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aDateFrom||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpFrom{{ $amc->id }}" class="{{ $aInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_date_from',$refs['fpFrom{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpFrom{{ $amc->id }}'].value)){aDateFrom=$refs['fpFrom{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpFrom{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Lapse Date --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpTo{{ $amc->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $aDt }}">Lapse Date</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $vExpired ? 'text-red-400 font-semibold text-sm' : ($vSoon ? 'text-yellow-400 text-sm' : $aDd) }}" x-text="aDateTo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpTo{{ $amc->id }}" class="{{ $aInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_date_to',$refs['fpTo{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpTo{{ $amc->id }}'].value)){aDateTo=$refs['fpTo{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpTo{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Reminder Before Days --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Reminder Before (days)</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $aDd }}" x-text="aRemindDays ? aRemindDays + ' days' : '--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $aBtnX }}">{!! $aPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="number" x-ref="inpRem" class="{{ $aInp }} w-20" :value="aRemindDays" min="1" max="365" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('reminder_before_days',$refs.inpRem.value)){aRemindDays=$refs.inpRem.value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Vendor / Billing ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Vendor / Billing</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

                            {{-- Vendor (select) --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Vendor / Provider</dt>
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

                            {{-- Amount --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Amount (₹)</dt>
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

                            {{-- Bill No --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $aDt }}">Bill No</dt>
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
                                            <input type="text" x-ref="fpBill{{ $amc->id }}" class="{{ $aInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $aBtnOk }}" @click="if(await ap('amc_bill_date',$refs['fpBill{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpBill{{ $amc->id }}'].value)){aBillDate=$refs['fpBill{{ $amc->id }}']._flatpickr?.altInput?.value||$refs['fpBill{{ $amc->id }}'].value;editing=false}">{!! $aCheck !!}</button>
                                            <button type="button" class="{{ $aBtnX }}" @click="editing=false">{!! $aX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Coverage Details ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Details</p>
                        <dl class="space-y-3">

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
                <div class="w-56 shrink-0 border-l border-zinc-200 pl-4 dark:border-zinc-700">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Document</p>
                    <div class="amc-doc-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            @if ($amcFirstDoc)
                            files: [{
                                source: '{{ (string) $amcFirstDoc->id }}',
                                options: { type: 'limbo', file: { name: '{{ addslashes($amcFirstDoc->file_original_name) }}', size: {{ $amcFirstDoc->file_size }}, type: '{{ $amcFirstDoc->file_mime_type }}' } }
                            }],
                            @endif
                            server: {
                                process: { url: @js($amcDocStore), method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()) }, onload: (id) => { toastr.success('Document uploaded.'); return id; } },
                                revert:  { url: @js($amcDocRevert), method: 'DELETE', headers: { 'X-CSRF-TOKEN': @js(csrf_token()) } },
                                load:    (source, load, error, progress, abort) => { fetch(source).then(r => r.blob()).then(load).catch(error); return { abort }; },
                            },
                        })
                    "><input type="file" /></div>

                    @if ($amcExtraDocs->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach ($amcExtraDocs as $doc)
                                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                                    @if ($doc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                                    <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</p>
                                    <button type="button"
                                        x-on:click="$dispatch('amc-lightbox-open', { src: '{{ Storage::url($doc->file_path) }}', title: '{{ addslashes($doc->file_original_name) }}', isPdf: {{ $doc->isImage() ? 'false' : 'true' }} })"
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
                        <p class="text-xs text-zinc-500">No document yet.</p>
                    @endif
                </div>{{-- end right --}}

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
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'amcid' => $amc->id]) }}"
                           title="{{ $amc->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border border-accent text-accent hover:bg-accent/10 transition-colors">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-view-amc-{{ $amc->id }}')"
                                aria-label="View AMC contract"
                                title="View AMC contract"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-edit-amc-{{ $amc->id }}')"
                                aria-label="Edit AMC contract"
                                title="Edit AMC contract"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.amc.destroy', [$asset, $amc]) }}"
                              onsubmit="confirmDelete(this, 'Delete this AMC contract?'); return false;">
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
                        @php
                            $amcPreviewDocs = $amc->documents->filter(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'))->values();
                            $amcOtherDocs   = $amc->documents->reject(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'));
                            $amcPondId      = 'filepond-amc-' . $amc->id;
                        @endphp
                        <div class="mt-4 border-t border-zinc-800 pt-4">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Documents</p>

                            {{-- FilePond strip for images + PDFs --}}
                            @if ($amcPreviewDocs->isNotEmpty())
                                <div id="pond-wrap-{{ $amcPondId }}" class="mb-2"
                                     x-data
                                     x-init="
                                         const wrap  = document.getElementById('pond-wrap-{{ $amcPondId }}');
                                         const mount = document.getElementById('{{ $amcPondId }}-mount');
                                         const files    = {{ Js::from($amcPreviewDocs->map(fn($d) => ['source' => Storage::url($d->file_path), 'options' => ['type' => 'local']])) }};
                                         const fileMeta = {{ Js::from($amcPreviewDocs->map(fn($d) => ['src' => Storage::url($d->file_path), 'title' => $d->document_title ?: $d->file_original_name, 'isPdf' => str_contains($d->file_mime_type ?? '', 'pdf')])) }};
                                         let pond = null;
                                         const initPond = () => {
                                             if (pond) { try { destroyDocImageViewer(pond); } catch(e) {} pond = null; }
                                             if (!mount.isConnected) return;
                                             mount.innerHTML = '';
                                             const input = document.createElement('input');
                                             input.type = 'file';
                                             mount.appendChild(input);
                                             pond = initDocImageViewer(input, files);
                                         };
                                         wrap.addEventListener('click', (e) => {
                                             if (wrap.offsetParent === null) return;
                                             const item = e.target.closest('.filepond--item');
                                             if (!item) return;
                                             const idx = Array.from(wrap.querySelectorAll('.filepond--item')).indexOf(item);
                                             if (fileMeta[idx]) $dispatch('amc-lightbox-open', fileMeta[idx]);
                                         });
                                         window.addEventListener('tab-visible', (e) => {
                                             if (e.detail === 'amc') setTimeout(initPond, 50);
                                         });
                                         if (document.readyState === 'complete') { setTimeout(initPond, 50); }
                                         else { window.addEventListener('load', () => setTimeout(initPond, 50), { once: true }); }
                                     ">
                                    <div id="{{ $amcPondId }}-mount"></div>
                                </div>
                                {{-- Download rows --}}
                                <div class="space-y-1 mb-2">
                                    @foreach ($amcPreviewDocs as $doc)
                                        <div class="flex items-center gap-2 px-1 py-1">
                                            <span class="min-w-0 flex-1 truncate text-xs text-zinc-500">{{ $doc->document_title ?: $doc->file_original_name }}</span>
                                            <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                               class="shrink-0 text-xs text-zinc-500 hover:text-zinc-300">Download</a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Non-previewable docs (DOC, XLS, etc.) --}}
                            @if ($amcOtherDocs->isNotEmpty())
                                <div class="space-y-1.5">
                                    @foreach ($amcOtherDocs as $doc)
                                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                                            <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                                            <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                                            <span class="text-xs text-zinc-600">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                               class="text-xs text-accent hover:underline">View</a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
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
         x-on:amc-lightbox-open.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
         x-show="open"
         x-cloak
         x-on:keydown.escape.window="if (open) close()"
         class="fixed inset-0 z-60 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/85" x-on:click="close()"></div>
        <div class="relative z-10 flex max-w-5xl w-full flex-col rounded-xl overflow-hidden shadow-2xl" x-on:click.stop>
            <div class="flex items-center justify-between bg-zinc-900 px-4 py-2 shrink-0">
                <span x-text="title" class="truncate text-sm text-zinc-300"></span>
                <button type="button" x-on:click="close()"
                    class="ml-4 flex shrink-0 items-center gap-1 text-sm text-zinc-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                    Close
                </button>
            </div>
            <template x-if="!isPdf">
                <div class="flex items-center justify-center bg-zinc-950 w-full" style="height:82vh;">
                    <img :src="src" :alt="title" class="max-h-full max-w-full object-contain rounded-lg shadow-xl">
                </div>
            </template>
            <template x-if="isPdf">
                <div class="w-full bg-zinc-950 flex items-center justify-center p-4" style="height:82vh;">
                    <object :data="src" type="application/pdf" class="w-full h-full rounded-lg shadow-inner">
                        <p class="text-center p-4 text-zinc-400">
                            <a :href="src" target="_blank" class="underline text-accent">Open PDF in new tab</a>
                        </p>
                    </object>
                </div>
            </template>
        </div>
    </div>

</div>
