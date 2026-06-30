@php use Illuminate\Support\Facades\Storage; @endphp

{{-- ── Doc Lightbox ── --}}
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

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Insurance Policies</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->insurancePolicies->count() }} {{ Str::plural('policy', $asset->insurancePolicies->count()) }}
            </flux:text>
        </div>
        {{-- <button type="button" x-on:click="$dispatch('open-modal-add-insurance')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Policy
        </button> --}}
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

    {{-- View Modals (one per policy) - inline-edit two-column layout --}}
    @foreach ($asset->insurancePolicies->sortByDesc('created_at') as $policy)
        @php
            $vDays    = $policy->daysUntilExpiry();
            $vExpired = $policy->isExpired();
            $vSoon    = ! $vExpired && $vDays !== null && $vDays <= 30;
            $insPatchUrl  = route('assets.insurance.patch-field', [$asset, $policy]);
            $insDocStore  = route('assets.insurance.documents.store', [$asset, $policy]);
            $insDocRevert = route('assets.insurance.documents.revert', $asset);
            $insFirstDoc  = $policy->documents->first();
            $insExtraDocs = $policy->documents->skip(1);
            $iInp  = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
            $iBtnOk = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
            $iBtnX  = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
            $iDt    = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
            $iDd    = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
            $iPencil = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
            $iCheck  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
            $iX      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
        @endphp

        <x-modal name="view-insurance-{{ $policy->id }}" title="Insurance Policy Details">
            <x-slot:footer>
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:icon.building-library class="size-4 shrink-0 text-zinc-400" />
                    @if ($vExpired)
                        <span class="rounded-full bg-red-400/10 px-2 py-0.5 text-xs font-medium text-red-400">Expired</span>
                    @elseif ($vSoon)
                        <span class="rounded-full bg-yellow-400/10 px-2 py-0.5 text-xs font-medium text-yellow-400">Expiring in {{ $vDays }}d</span>
                    @elseif ($vDays !== null)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Active</span>
                    @endif
                    @if ($policy->policy_number)
                        <span class="font-mono text-xs text-zinc-500">{{ $policy->policy_number }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal-view-insurance-{{ $policy->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                        <flux:icon.x-mark class="size-3.5" />
                        Close
                    </button>
                    <form method="POST" action="{{ route('assets.insurance.destroy', [$asset, $policy]) }}" onsubmit="confirmDelete(this, 'Delete this insurance policy?'); return false;">
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
                iPolicyNo:   {{ json_encode($policy->policy_number ?? '') }},
                iInsurerName:{{ json_encode($policy->insurer_name ?? '') }},
                iPolicyType: {{ json_encode($policy->policy_type ?? '') }},
                iDateFrom:   '{{ $policy->policy_date_from?->format('d M Y') ?? '' }}',
                iDateTo:     '{{ $policy->policy_date_to?->format('d M Y') ?? '' }}',
                iPremium:    '{{ $policy->premium_amount ?? '' }}',
                iSumInsured: '{{ $policy->sum_insured ?? '' }}',
                iBillNo:     {{ json_encode($policy->bill_no ?? '') }},
                iBillDate:   '{{ $policy->bill_date?->format('d M Y') ?? '' }}',
                iContact:    {{ json_encode($policy->insurer_contact_person ?? '') }},
                iPhone:      {{ json_encode($policy->insurer_phone ?? '') }},
                iEmail:      {{ json_encode($policy->insurer_email ?? '') }},
                iRemindDays: '{{ $policy->reminder_before_days ?? '' }}',
                iCovDetails: {{ json_encode($policy->coverage_details ?? '') }},
                iRemarks:    {{ json_encode($policy->remarks ?? '') }},
                async ip(field, value) {
                    const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                    const r = await fetch('{{ $insPatchUrl }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: fd,
                    });
                    if (!r.ok) { toastr.error('Save failed.'); return false; }
                    toastr.success('Updated.');
                    return true;
                }
            }" class="flex min-h-0 gap-5 mt-1">

                {{-- ── Left: editable fields ── --}}
                <div class="flex-1 min-w-0 space-y-5">

                    {{-- ── Policy Details ── --}}
                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Policy Details</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            {{-- Policy Number --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Policy Number</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="font-mono text-sm text-zinc-800 dark:text-zinc-200" x-text="iPolicyNo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpPno" class="{{ $iInp }} w-36" :value="iPolicyNo" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('policy_number',$refs.inpPno.value)){iPolicyNo=$refs.inpPno.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Insurer Name --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Insurer Name</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="iInsurerName||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpIns" class="{{ $iInp }} w-40" :value="iInsurerName" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('insurer_name',$refs.inpIns.value)){iInsurerName=$refs.inpIns.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Policy Type --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Policy Type</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iPolicyType||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpType" class="{{ $iInp }} w-48" :value="iPolicyType" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('policy_type',$refs.inpType.value)){iPolicyType=$refs.inpType.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Policy From --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpIFrom{{ $policy->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $iDt }}">Policy From</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iDateFrom||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpIFrom{{ $policy->id }}" class="{{ $iInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="const v=$refs['fpIFrom{{ $policy->id }}']._flatpickr?.altInput?.value||$refs['fpIFrom{{ $policy->id }}'].value;if(await ip('policy_date_from',v)){iDateFrom=v;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Policy Expiry --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpITo{{ $policy->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $iDt }}">Policy Expiry</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $vExpired ? 'text-red-400 font-semibold text-sm' : ($vSoon ? 'text-yellow-400 text-sm' : $iDd) }}" x-text="iDateTo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpITo{{ $policy->id }}" class="{{ $iInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="const v=$refs['fpITo{{ $policy->id }}']._flatpickr?.altInput?.value||$refs['fpITo{{ $policy->id }}'].value;if(await ip('policy_date_to',v)){iDateTo=v;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Financials ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Financials</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            {{-- Premium --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Premium (₹)</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iPremium ? '₹ ' + parseFloat(iPremium).toLocaleString('en-IN',{minimumFractionDigits:2}) : '--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="number" x-ref="inpPrem" class="{{ $iInp }} w-28" :value="iPremium" min="0" step="0.01" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('premium_amount',$refs.inpPrem.value)){iPremium=$refs.inpPrem.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Sum Insured --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Sum Insured (₹)</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iSumInsured ? '₹ ' + parseFloat(iSumInsured).toLocaleString('en-IN',{minimumFractionDigits:2}) : '--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="number" x-ref="inpSum" class="{{ $iInp }} w-28" :value="iSumInsured" min="0" step="0.01" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('sum_insured',$refs.inpSum.value)){iSumInsured=$refs.inpSum.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Bill Number --}}
                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Bill Number</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iBillNo||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpBill" class="{{ $iInp }} w-32" :value="iBillNo" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('bill_no',$refs.inpBill.value)){iBillNo=$refs.inpBill.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            {{-- Bill Date --}}
                            <div x-data="{ editing: false }"
                                 x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpIBill{{ $policy->id }}, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                                <dt class="{{ $iDt }}">Bill Date</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iBillDate||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="fpIBill{{ $policy->id }}" class="{{ $iInp }} w-32" placeholder="Date" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="const v=$refs['fpIBill{{ $policy->id }}']._flatpickr?.altInput?.value||$refs['fpIBill{{ $policy->id }}'].value;if(await ip('bill_date',v)){iBillDate=v;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- ── Contact ── --}}
                    <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contact</p>
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Contact Person</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iContact||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpCon" class="{{ $iInp }} w-36" :value="iContact" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('insurer_contact_person',$refs.inpCon.value)){iContact=$refs.inpCon.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Phone</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }}" x-text="iPhone||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="text" x-ref="inpPh" class="{{ $iInp }} w-32" :value="iPhone" maxlength="30" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('insurer_phone',$refs.inpPh.value)){iPhone=$refs.inpPh.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Email</dt>
                                <dd class="mt-0.5 flex items-center gap-1.5">
                                    <span x-show="!editing" class="{{ $iDd }} truncate" x-text="iEmail||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }}">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex items-center gap-1">
                                            <input type="email" x-ref="inpEm" class="{{ $iInp }} w-40" :value="iEmail" maxlength="255" />
                                            <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('insurer_email',$refs.inpEm.value)){iEmail=$refs.inpEm.value;editing=false}">{!! $iCheck !!}</button>
                                            <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
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
                                <dt class="{{ $iDt }}">Coverage Details</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5">
                                    <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="iCovDetails||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }} mt-0.5">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex w-full flex-col gap-1">
                                            <textarea x-ref="taCov" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="iCovDetails"></textarea>
                                            <span class="flex gap-1">
                                                <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('coverage_details',$refs.taCov.value)){iCovDetails=$refs.taCov.value;editing=false}">{!! $iCheck !!}</button>
                                                <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
                                            </span>
                                        </span>
                                    </template>
                                </dd>
                            </div>

                            <div x-data="{ editing: false }">
                                <dt class="{{ $iDt }}">Remarks</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5">
                                    <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="iRemarks||'--'"></span>
                                    <button x-show="!editing" type="button" @click="editing=true" class="{{ $iBtnX }} mt-0.5">{!! $iPencil !!}</button>
                                    <template x-if="editing">
                                        <span class="flex w-full flex-col gap-1">
                                            <textarea x-ref="taRem" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="iRemarks"></textarea>
                                            <span class="flex gap-1">
                                                <button type="button" class="{{ $iBtnOk }}" @click="if(await ip('remarks',$refs.taRem.value)){iRemarks=$refs.taRem.value;editing=false}">{!! $iCheck !!}</button>
                                                <button type="button" class="{{ $iBtnX }}" @click="editing=false">{!! $iX !!}</button>
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
                    <div class="ins-doc-upload" x-data x-init="
                        initUploadPond($el.querySelector('input'), {
                            acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                            labelIdle: `<div class='flex flex-col items-center gap-2 py-1'>
                                <div class='w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center'>
                                    <svg class='h-5 w-5 text-accent' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'/></svg>
                                </div>
                                <p class='text-[11px] font-medium text-zinc-300 text-center leading-snug'>Drag &amp; Drop your file<br>or <span class='filepond--label-action text-accent'>Browse</span></p>
                                <p class='text-[9px] uppercase tracking-wider text-zinc-500'>PDF, PNG, JPG · Max 5MB</p>
                            </div>`,
                            files: @js($insFirstDoc ? [['source' => Storage::url($insFirstDoc->file_path), 'options' => ['type' => 'local']]] : []),
                            fileMetaBySource: @js($insFirstDoc ? [Storage::url($insFirstDoc->file_path) => ['name' => $insFirstDoc->file_original_name]] : (object)[]),
                            deleteUrl: @js($insFirstDoc ? route('assets.insurance.documents.destroy', [$asset, $insFirstDoc]) : ''),
                            csrfToken: @js(csrf_token()),
                            revertUrlTemplate: () => @js(route('assets.insurance.documents.revert', $asset)),
                            server: {
                                process: { url: @js($insDocStore), method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' }, onload: (id) => { const n = parseInt(id); if (!n) { toastr.error('Upload failed.'); return null; } toastr.success('Document uploaded.'); return String(n); }, onerror: (e) => toastr.error('Upload failed.') },
                            },
                        })
                    "><input type="file" /></div>

                    @if ($insFirstDoc)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 dark:border-zinc-800 dark:bg-zinc-800/50">
                            @if ($insFirstDoc->isImage())<flux:icon.photo class="size-3.5 shrink-0 text-zinc-400" />@else<flux:icon.document class="size-3.5 shrink-0 text-zinc-400" />@endif
                            <p class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $insFirstDoc->file_original_name }}</p>
                            <button type="button"
                                x-on:click="$dispatch('open-doc-lightbox', { src: '{{ Storage::url($insFirstDoc->file_path) }}', title: '{{ addslashes($insFirstDoc->file_original_name) }}', isPdf: {{ $insFirstDoc->isImage() ? 'false' : 'true' }} })"
                                class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700"
                                title="View">
                                <flux:icon.eye class="size-3" />
                            </button>
                            <a href="{{ Storage::url($insFirstDoc->file_path) }}" download="{{ $insFirstDoc->file_original_name }}"
                                class="inline-flex size-5 shrink-0 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700"
                                title="Download">
                                <flux:icon.arrow-down-tray class="size-3" />
                            </a>
                        </div>
                    @endif

                    @if ($insExtraDocs->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach ($insExtraDocs as $doc)
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
                                    <form method="POST" action="{{ route('assets.insurance.documents.destroy', [$asset, $doc]) }}" onsubmit="confirmDelete(this,'Delete this document?');return false;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-400 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if (!$insFirstDoc && $insExtraDocs->isEmpty())
                        <div class="mt-3 flex flex-col items-center justify-center">
                            <p class="text-[11px] text-zinc-500 italic">No document yet.</p>
                        </div>
                    @endif
                </aside>{{-- end right --}}

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
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'insuranceid' => $policy->id]) }}"
                           title="{{ $policy->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border border-accent text-accent hover:bg-accent/10 transition-colors">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        {{-- <button type="button"
                                x-on:click="$dispatch('open-modal-edit-insurance-{{ $policy->id }}')"
                                aria-label="Edit insurance policy"
                                title="Edit insurance policy"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.insurance.destroy', [$asset, $policy]) }}"
                              onsubmit="confirmDelete(this, 'Delete this insurance policy?'); return false;">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete insurance policy"
                                    title="Delete insurance policy"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form> --}}
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
