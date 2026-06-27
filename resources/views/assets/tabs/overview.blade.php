@php
    $patchUrl  = route('assets.patch-field', $asset);
    $pencilSvg = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>';
    $checkSvg  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
    $xSvg      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';
    $inpInline = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
    $btnCheck  = 'rounded p-0.5 text-green-400 hover:text-green-300 transition-colors';
    $btnX      = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';

    $inlineField = function(string $field, $value, string $display, string $type = 'text', string $refKey = '', string $extraAttrs = '') use ($patchUrl, $inpInline, $btnCheck, $btnX, $pencilSvg, $checkSvg, $xSvg) {
        $ref = $refKey ?: $field;
        return compact('field', 'value', 'display', 'type', 'ref', 'extraAttrs', 'patchUrl', 'inpInline', 'btnCheck', 'btnX', 'pencilSvg', 'checkSvg', 'xSvg');
    };
@endphp

<div class="space-y-6">

    {{-- Core Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Core Details</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Asset Code (read-only) --}}
            <div>
                <dt class="text-xs font-medium text-zinc-500">Asset Code</dt>
                <dd class="mt-0.5 text-sm"><span class="font-mono text-accent">{{ $asset->asset_code }}</span></dd>
            </div>

            {{-- Category (editable select) --}}
            <div x-data="{
                    ...inlineEdit(),
                    async saveCategory(form) {
                        if (this.saving) return;
                        this.saving = true;
                        const data = new FormData(form);
                        const r = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            body: data,
                        });
                        this.saving = false;
                        if (r.ok) {
                            const catId = form.querySelector('[name=value]').value;
                            this.$dispatch('reload-subcats', { catId });
                            window.location.reload();
                        } else {
                            toastr.error('Failed to save. Please try again.');
                        }
                    }
                 }">
                <dt class="text-xs font-medium text-zinc-500">Category</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->category?->name ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0"
                          @submit.prevent="saveCategory($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="asset_category_id">
                        <select name="value" class="{{ $inpInline }}"
                                x-ref="cat"
                                x-init="$watch('editing', v => v && $nextTick(() => $refs.cat.focus()))">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $asset->asset_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="{{ $btnCheck }}" :disabled="saving">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Subcategory (editable select, reloads when category changes) --}}
            <div x-data="{
                    ...inlineEdit(),
                    subcats: {{ Js::from($asset->category ? $asset->category->subcategories()->orderBy('name')->get(['id','name']) : collect()) }},
                    subcatValue: '{{ $asset->asset_subcategory_id }}',
                    loading: false,
                    async loadSubs(catId) {
                        this.loading = true;
                        const r = await fetch('/api/subcategories?category_id=' + catId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        this.subcats = await r.json();
                        this.subcatValue = '';
                        this.loading = false;
                    }
                 }"
                 @reload-subcats.window="loadSubs($event.detail.catId)"
            >
                <dt class="text-xs font-medium text-zinc-500">Subcategory</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->subcategory?->name ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="asset_subcategory_id">
                        <select name="value" x-model="subcatValue" class="{{ $inpInline }}"
                                x-ref="subcat"
                                x-init="$watch('editing', v => v && $nextTick(() => $refs.subcat?.focus()))">
                            <option value="">— None —</option>
                            <template x-for="s in subcats" :key="s.id">
                                <option :value="s.id" :selected="String(s.id) === String(subcatValue)" x-text="s.name"></option>
                            </template>
                        </select>
                        <button type="submit" class="{{ $btnCheck }}" x-bind:disabled="loading">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Manufacturer (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Manufacturer</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->manufacturer ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="manufacturer">
                        <input type="text" name="value" value="{{ $asset->manufacturer }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="manufacturer"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.manufacturer.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Model (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Model</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->model ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="model">
                        <input type="text" name="value" value="{{ $asset->model }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="model"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.model.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Model Year (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Model Year</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->model_year ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="model_year">
                        <input type="number" name="value" value="{{ $asset->model_year }}" class="{{ $inpInline }} w-24"
                               min="1900" max="{{ date('Y') + 1 }}"
                               @keydown.escape="editing = false"
                               x-ref="model_year"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.model_year.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Serial Number (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Serial Number</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->serial_number ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="serial_number">
                        <input type="text" name="value" value="{{ $asset->serial_number }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="serial_number"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.serial_number.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Status (editable select) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Status</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing">
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                            {{ $asset->status_label }}
                        </span>
                    </span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="status">
                        <select name="value" class="{{ $inpInline }}"
                                x-ref="status"
                                x-init="$watch('editing', v => v && $nextTick(() => $refs.status.focus()))">
                            @foreach (['active' => 'Active', 'under_repair' => 'Under Repair', 'disposed' => 'Disposed', 'scrapped' => 'Scrapped', 'inactive' => 'Inactive'] as $val => $label)
                                <option value="{{ $val }}" {{ $asset->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

        </dl>
    </div>

    {{-- Location & Ownership --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Location & Ownership</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Location (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Location</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->location ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="location">
                        <input type="text" name="value" value="{{ $asset->location }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="location"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.location.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Department (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Department</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->department ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="department">
                        <input type="text" name="value" value="{{ $asset->department }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="department"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.department.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Custodian (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Custodian</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->custodian ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="custodian">
                        <input type="text" name="value" value="{{ $asset->custodian }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="custodian"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.custodian.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Vendor/Supplier (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Vendor/Supplier</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->vendor_supplier ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="vendor_supplier">
                        <input type="text" name="value" value="{{ $asset->vendor_supplier }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="vendor_supplier"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.vendor_supplier.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

        </dl>
    </div>

    {{-- Purchase Details --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Purchase Details</flux:heading>
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Bill Number (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Bill Number</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->bill_no ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="bill_no">
                        <input type="text" name="value" value="{{ $asset->bill_no }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="bill_no"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.bill_no.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Bill Amount (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Bill Amount</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->bill_amount ? '₹ ' . number_format($asset->bill_amount, 2) : '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="bill_amount">
                        <input type="number" name="value" value="{{ $asset->bill_amount }}" class="{{ $inpInline }} w-28"
                               step="0.01" min="0"
                               @keydown.escape="editing = false"
                               x-ref="bill_amount"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.bill_amount.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Bill Date (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Bill Date</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->bill_date?->format('d M Y') ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="bill_date">
                        <input type="date" name="value" value="{{ $asset->bill_date?->format('Y-m-d') }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="bill_date"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.bill_date.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

            {{-- Purchase Date (editable) --}}
            <div x-data="inlineEdit()">
                <dt class="text-xs font-medium text-zinc-500">Purchase Date</dt>
                <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                    <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->purchase_date?->format('d M Y') ?: '—' }}</span>
                    <button x-show="!editing" type="button" @click="editing = true"
                            class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                    <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                        @csrf @method('PATCH')
                        <input type="hidden" name="field" value="purchase_date">
                        <input type="date" name="value" value="{{ $asset->purchase_date?->format('Y-m-d') }}" class="{{ $inpInline }}"
                               @keydown.escape="editing = false"
                               x-ref="purchase_date"
                               x-init="$watch('editing', v => v && $nextTick(() => $refs.purchase_date.focus()))">
                        <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                        <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                    </form>
                </dd>
            </div>

        </dl>

        @php
            use Illuminate\Support\Facades\Storage;
            $purchaseBills = $asset->documents->where('document_type', 'purchase_bill')->values();
            $existingBill  = $purchaseBills->first();
            $previewBills  = $purchaseBills->filter(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'))->values();
            $otherBills    = $purchaseBills->diff($previewBills);
        @endphp

        {{-- Purchase Bill -- FilePond upload (same widget as edit page) --}}
        <div class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <p class="mb-3 text-xs font-medium text-zinc-500">Purchase Bill Photo / PDF
                <span class="ml-1 font-normal">(PDF, JPG, PNG, WEBP — max 5 MB)</span>
            </p>
            <form method="POST" action="{{ route('assets.documents.store', $asset) }}"
                  enctype="multipart/form-data"
                  x-data
                  x-init="
                      const billInput = $el.querySelector('[name=\'file\']');
                      if (billInput) {
                          const existingBillUrl  = @js($existingBill ? Storage::url($existingBill->file_path) : '');
                          const existingBillName = @js($existingBill?->file_original_name ?? '');
                          initUploadPond(billInput, {
                              files: existingBillUrl ? [{ source: existingBillUrl, options: { type: 'local' } }] : undefined,
                              fileMetaBySource: existingBillUrl ? { [existingBillUrl]: { name: existingBillName } } : undefined,
                              deleteUrl: @js($existingBill ? route('assets.documents.destroy', [$asset, $existingBill]) : ''),
                              csrfToken: @js(csrf_token()),
                              deleteSuccessMessage: 'Purchase bill deleted.',
                              acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp'],
                              revertUrlTemplate: () => @js(route('assets.documents.revert', $asset)),
                              server: {
                                  process: {
                                      url: @js(route('assets.documents.store', $asset)),
                                      method: 'POST',
                                      headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                                      ondata: (formData) => { formData.append('document_type', 'purchase_bill'); return formData; },
                                      onload: (id) => {
                                          const n = parseInt(id);
                                          if (!n) { toastr.error('Upload failed.'); return null; }
                                          toastr.success('Purchase bill uploaded.');
                                          return String(n);
                                      },
                                      onerror: () => toastr.error('Upload failed.'),
                                  },
                              },
                          });
                      }
                  ">
                @csrf
                <input type="hidden" name="document_type" value="purchase_bill">
                <input type="hidden" name="_tab" value="overview">

                <div class="max-w-md">
                    <input type="file" name="file" accept="application/pdf,image/jpeg,image/png,image/webp" />
                </div>

                @if ($existingBill)
                <div id="overview-bill-actions" class="mt-2 max-w-md">
                    <a href="{{ Storage::url($existingBill->file_path) }}" download="{{ $existingBill->file_original_name }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:border-zinc-400 hover:text-zinc-800 transition-colors dark:border-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
                            <path d="M8.75 2.75a.75.75 0 0 0-1.5 0v5.69L5.03 6.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 0 0-1.06-1.06L8.75 8.44V2.75Z"/>
                            <path d="M3.5 9.75a.75.75 0 0 0-1.5 0v1.5A2.75 2.75 0 0 0 4.75 14h6.5A2.75 2.75 0 0 0 14 11.25v-1.5a.75.75 0 0 0-1.5 0v1.5c0 .69-.56 1.25-1.25 1.25h-6.5c-.69 0-1.25-.56-1.25-1.25v-1.5Z"/>
                        </svg>
                        Download
                    </a>
                </div>
                @endif
            </form>

            {{-- Non-previewable bill files with delete --}}
            @if ($otherBills->isNotEmpty())
                <div class="mt-3 space-y-1.5 max-w-md">
                    @foreach ($otherBills as $doc)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                            <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->file_original_name }}</span>
                            <span class="text-xs text-zinc-500">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                               class="text-xs text-accent hover:underline">View</a>
                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                            <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                               class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                            <form method="POST" action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                  onsubmit="confirmDelete(this, 'Delete this purchase bill?'); return false;">
                                @csrf @method('DELETE')
                                <input type="hidden" name="_tab" value="overview">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition-colors">Delete</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Purchase bill image/PDF lightbox --}}
        @if ($previewBills->isNotEmpty())
            <div x-data="docLightbox()"
                 x-on:bill-lightbox-open.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
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
                            <img :src="src" :alt="title"
                                 class="max-h-full max-w-full object-contain rounded-lg shadow-xl">
                        </div>
                    </template>
                    <template x-if="isPdf">
                        <object :data="src" type="application/pdf"
                                class="w-full bg-white" style="height:82vh;">
                            <p class="text-center p-4">
                                <a :href="src" target="_blank" class="underline text-accent">Open PDF in new tab</a>
                            </p>
                        </object>
                    </template>
                </div>
            </div>
        @endif
    </div>

    {{-- Vehicle Compliance (only for VE category) --}}
    @if ($asset->isVehicle())
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">Vehicle Compliance</flux:heading>
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                {{-- Registration Number (editable) --}}
                <div class="sm:col-span-2 lg:col-span-3" x-data="inlineEdit()">
                    <dt class="text-xs font-medium text-zinc-500">Registration Number</dt>
                    <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                        <span x-show="!editing" data-display class="font-mono text-sm font-semibold uppercase text-zinc-800 dark:text-zinc-200">{{ $asset->registration_number ?: '—' }}</span>
                        <button x-show="!editing" type="button" @click="editing = true"
                                class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                        <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="registration_number">
                            <input type="text" name="value" value="{{ $asset->registration_number }}" class="{{ $inpInline }} uppercase"
                                   @keydown.escape="editing = false"
                                   x-ref="regnumber"
                                   x-init="$watch('editing', v => v && $nextTick(() => $refs.regnumber.focus()))">
                            <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                            <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                        </form>
                    </dd>
                </div>

                {{-- PUC Expiry (editable date + reminder days) --}}
                @foreach ([
                    ['PUC Expiry',      'puc_expiry_date',       'puc_reminder_before_days',       $asset->puc_expiry_date,       $asset->puc_reminder_before_days,       'pucdate',  'pucdays'],
                    ['Fitness Expiry',  'fitness_expiry_date',   'fitness_reminder_before_days',   $asset->fitness_expiry_date,   $asset->fitness_reminder_before_days,   'fitdate',  'fitdays'],
                    ['Road Tax Expiry', 'road_tax_expiry_date',  'road_tax_reminder_before_days',  $asset->road_tax_expiry_date,  $asset->road_tax_reminder_before_days,  'rtaxdate', 'rtaxdays'],
                ] as [$label, $dateField, $daysField, $date, $reminderDays, $dateRef, $daysRef])
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-0.5 space-y-1">
                            {{-- Date row --}}
                            <div x-data="inlineEdit()" class="flex items-center gap-1.5 min-w-0">
                                @if ($date)
                                    @php $expired = $date->isPast(); $daysLeft = (int) now()->diffInDays($date, false); $soon = !$expired && $daysLeft <= ($reminderDays ?? 30); @endphp
                                    <span x-show="!editing" class="{{ $expired ? 'text-red-400' : ($soon ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-200') }} text-sm">
                                        {{ $date->format('d M Y') }}
                                        @if ($expired) <span class="text-xs">(expired)</span>
                                        @elseif ($soon) <span class="text-xs">(expiring soon)</span>
                                        @endif
                                    </span>
                                @else
                                    <span x-show="!editing" class="text-sm text-zinc-500">—</span>
                                @endif
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="{{ $dateField }}">
                                    <input type="date" name="value" value="{{ $date?->format('Y-m-d') }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="{{ $dateRef }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['{{ $dateRef }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </div>
                            {{-- Remind days row --}}
                            <div x-data="inlineEdit()" class="flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-xs text-zinc-500">
                                    Remind: {{ $reminderDays ? $reminderDays . 'd before' : '—' }}
                                </span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="{{ $daysField }}">
                                    <input type="number" name="value" value="{{ $reminderDays }}" class="{{ $inpInline }} w-20"
                                           min="1" max="365" placeholder="days"
                                           @keydown.escape="editing = false"
                                           x-ref="{{ $daysRef }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['{{ $daysRef }}'].focus()))">
                                    <span class="text-xs text-zinc-500 shrink-0">days before</span>
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </div>
                        </dd>
                    </div>
                @endforeach

                {{-- OBV (editable) --}}
                <div x-data="inlineEdit()">
                    <dt class="text-xs font-medium text-zinc-500">OBV</dt>
                    <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                        <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->vehicle_obv ? '₹ ' . number_format($asset->vehicle_obv, 2) : '—' }}</span>
                        <button x-show="!editing" type="button" @click="editing = true"
                                class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                        <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="vehicle_obv">
                            <input type="number" name="value" value="{{ $asset->vehicle_obv }}" class="{{ $inpInline }} w-28"
                                   step="0.01" min="0" @keydown.escape="editing = false"
                                   x-ref="vobv"
                                   x-init="$watch('editing', v => v && $nextTick(() => $refs.vobv.focus()))">
                            <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                            <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                        </form>
                    </dd>
                </div>

                {{-- Depreciation % (editable) --}}
                <div x-data="inlineEdit()">
                    <dt class="text-xs font-medium text-zinc-500">Depreciation %</dt>
                    <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                        <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->vehicle_depreciation_percent ? $asset->vehicle_depreciation_percent . '%' : '—' }}</span>
                        <button x-show="!editing" type="button" @click="editing = true"
                                class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                        <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="vehicle_depreciation_percent">
                            <input type="number" name="value" value="{{ $asset->vehicle_depreciation_percent }}" class="{{ $inpInline }} w-20"
                                   step="0.01" min="0" max="100" @keydown.escape="editing = false"
                                   x-ref="vdepct"
                                   x-init="$watch('editing', v => v && $nextTick(() => $refs.vdepct.focus()))">
                            <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                            <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                        </form>
                    </dd>
                </div>

                {{-- Book Value (editable) --}}
                <div x-data="inlineEdit()">
                    <dt class="text-xs font-medium text-zinc-500">Book Value</dt>
                    <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                        <span x-show="!editing" data-display class="text-sm text-zinc-800 dark:text-zinc-200">{{ $asset->vehicle_depreciation_book_value ? '₹ ' . number_format($asset->vehicle_depreciation_book_value, 2) : '—' }}</span>
                        <button x-show="!editing" type="button" @click="editing = true"
                                class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                        <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0" @submit.prevent="save($el)">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="vehicle_depreciation_book_value">
                            <input type="number" name="value" value="{{ $asset->vehicle_depreciation_book_value }}" class="{{ $inpInline }} w-28"
                                   step="0.01" min="0" @keydown.escape="editing = false"
                                   x-ref="vbookval"
                                   x-init="$watch('editing', v => v && $nextTick(() => $refs.vbookval.focus()))">
                            <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                            <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                        </form>
                    </dd>
                </div>

            </dl>
        </div>
    @endif

    {{-- Remarks (editable) --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
         x-data="inlineEdit()">
        <flux:heading class="font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Remarks</flux:heading>
        <div x-show="!editing" class="flex items-start gap-1.5">
            <p data-display class="text-sm text-zinc-700 whitespace-pre-line dark:text-zinc-300">{{ $asset->remarks ?: '—' }}</p>
            <button type="button" @click="editing = true"
                    class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0 mt-0.5">{!! $pencilSvg !!}</button>
        </div>
        <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="space-y-2" @submit.prevent="save($el)">
            @csrf @method('PATCH')
            <input type="hidden" name="field" value="remarks">
            <textarea name="value" rows="3"
                      class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                      @keydown.escape="editing = false"
                      x-ref="remarks"
                      x-init="$watch('editing', v => v && $nextTick(() => $refs.remarks.focus()))">{{ $asset->remarks }}</textarea>
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-green-400 hover:text-green-300 transition-colors">
                    {!! $checkSvg !!} Save
                </button>
                <button type="button" @click="editing = false"
                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-zinc-400 hover:text-zinc-200 transition-colors">
                    {!! $xSvg !!} Cancel
                </button>
            </div>
        </form>
    </div>

    {{-- Meta --}}
    <div class="text-xs text-zinc-600 space-y-0.5">
        <p>Created: {{ $asset->created_at->format('d M Y, h:i A') }}</p>
        <p>Last updated: {{ $asset->updated_at->format('d M Y, h:i A') }}</p>
    </div>
</div>
