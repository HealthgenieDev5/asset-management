@php
    use Illuminate\Support\Facades\Storage;
    $isEdit    = isset($asset) && $asset !== null;
    $old       = fn(string $field, $default = '') => old($field, $isEdit ? ($asset->$field ?? $default) : $default);
    $isVehicle = $isEdit ? $asset->isVehicle() : false;
@endphp

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assetForm', () => ({
        categoryId: '{{ $old('asset_category_id') }}',
        isVehicle: {{ $isVehicle ? 'true' : 'false' }},
        subcategories: {!! json_encode($subcategories->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()) !!},
        selectedSubcategoryId: null,
        _initialSubcategoryId: {{ old('asset_subcategory_id', $isEdit ? ($asset->asset_subcategory_id ?? 'null') : 'null') }},
        // Location searchable dropdown
        allLocations: {!! json_encode(($locations ?? collect())->map(fn($l) => ['id' => $l->id, 'name' => $l->name])->values()) !!},
        locationSearch: '{{ addslashes($old('location')) }}',
        locationValue: '{{ addslashes($old('location')) }}',
        locationOpen: false,
        locationResults: [],
        showAddLocation: false,
        newLocationName: '',
        locationSaving: false,
        filterLocations() {
            const q = this.locationSearch.toLowerCase();
            this.locationResults = q.length === 0
                ? this.allLocations
                : this.allLocations.filter(l => l.name.toLowerCase().includes(q));
            this.showAddLocation = q.length > 1
                && !this.allLocations.some(l => l.name.toLowerCase() === q.toLowerCase());
            if (q.length > 1 && this.showAddLocation) this.newLocationName = this.locationSearch;
        },
        selectLocation(name) {
            this.locationSearch = name;
            this.locationValue  = name;
            this.locationOpen   = false;
        },
        async saveNewLocation() {
            if (!this.newLocationName.trim()) return;
            this.locationSaving = true;
            try {
                const xsrf = decodeURIComponent(document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
                const res = await fetch('/api/locations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf },
                    body: JSON.stringify({ name: this.newLocationName.trim() })
                });
                const loc = await res.json();
                this.allLocations.push(loc);
                this.allLocations.sort((a, b) => a.name.localeCompare(b.name));
                this.selectLocation(loc.name);
                this.newLocationName  = '';
                this.showAddLocation  = false;
            } finally {
                this.locationSaving = false;
            }
        },

        // Department searchable dropdown
        allDepartments: {!! json_encode(($departments ?? collect())->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->values()) !!},
        deptSearch: '{{ addslashes($old('department')) }}',
        deptValue: '{{ addslashes($old('department')) }}',
        deptOpen: false,
        deptResults: [],
        showAddDept: false,
        newDeptName: '',
        deptSaving: false,
        filterDepts() {
            const q = this.deptSearch.toLowerCase();
            this.deptResults = q.length === 0
                ? this.allDepartments
                : this.allDepartments.filter(d => d.name.toLowerCase().includes(q));
            this.showAddDept = q.length > 1
                && !this.allDepartments.some(d => d.name.toLowerCase() === q.toLowerCase());
            if (q.length > 1 && this.showAddDept) this.newDeptName = this.deptSearch;
        },
        selectDept(name) {
            this.deptSearch = name;
            this.deptValue  = name;
            this.deptOpen   = false;
        },
        async saveNewDept() {
            if (!this.newDeptName.trim()) return;
            this.deptSaving = true;
            try {
                const xsrf = decodeURIComponent(document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
                const res = await fetch('/api/departments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf },
                    body: JSON.stringify({ name: this.newDeptName.trim() })
                });
                const dept = await res.json();
                this.allDepartments.push(dept);
                this.allDepartments.sort((a, b) => a.name.localeCompare(b.name));
                this.selectDept(dept.name);
                this.newDeptName = '';
                this.showAddDept = false;
            } finally {
                this.deptSaving = false;
            }
        },

        init() {
            this.$nextTick(() => {
                // Apply after x-for has rendered the options
                if (this._initialSubcategoryId) {
                    this.selectedSubcategoryId = this._initialSubcategoryId;
                }

                const fp = (name) => flatpickr(this.$el.querySelector(`[name='${name}']`), {
                    dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true
                });
                fp('bill_date'); fp('purchase_date');
                fp('puc_expiry_date'); fp('fitness_expiry_date'); fp('road_tax_expiry_date');

                const billInput = this.$el.querySelector("[name='purchase_bill_file']");
                if (billInput) {
                    const billWrap = billInput.closest('[data-existing-bill]');
                    const existingBillUrl  = billWrap?.dataset.existingBill;
                    const existingBillName = billWrap?.dataset.existingBillName;
                    initUploadPond(billInput, {
                        acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                        files: existingBillUrl ? [{
                            source: existingBillUrl,
                            options: { type: 'local' },
                        }] : undefined,
                        fileMetaBySource: existingBillUrl ? { [existingBillUrl]: { name: existingBillName } } : undefined,
                    });
                }
            });
        },

        loadSubcategories(catId) {
            this.categoryId = catId;
            this.selectedSubcategoryId = null;
            if (!catId) { this.subcategories = []; this.isVehicle = false; return; }
            fetch('/api/subcategories?category_id=' + catId)
                .then(r => r.json())
                .then(data => {
                    this.subcategories = data;
                    const sel = document.getElementById('category_select');
                    const opt = sel.options[sel.selectedIndex];
                    this.isVehicle = opt && opt.dataset.code === 'VE';
                });
        }
    }));
});
</script>

@php
// Reusable floating-label classes
$inputCls  = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$selectCls = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$labelCls  = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent dark:text-zinc-400 dark:peer-focus:text-accent';
$labelSelCls = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$textareaCls = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
@endphp

<div class="space-y-6" x-data="assetForm">

    {{-- Section: Basic Info --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Basic Information</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2">
            {{-- Left: 2x2 grid of Name/Status/Category/Subcategory --}}
            <div class="grid gap-4 grid-cols-2">
                {{-- Asset Name --}}
                <div>
                    <div class="relative">
                        <input type="text" name="asset_name" id="asset_name"
                            value="{{ $old('asset_name') }}" placeholder=" " required
                            class="{{ $inputCls }}" />
                        <label for="asset_name" class="{{ $labelCls }}">Asset Name <span class="text-red-400">*</span></label>
                    </div>
                    <flux:error name="asset_name" />
                </div>

                {{-- Status --}}
                <div class="relative">
                    <select name="status" id="status" class="{{ $selectCls }}">
                        <option value="active" @selected($old('status', 'active') === 'active')>Active</option>
                        <option value="under_repair" @selected($old('status') === 'under_repair')>Under Repair</option>
                        <option value="disposed" @selected($old('status') === 'disposed')>Disposed</option>
                        <option value="scrapped" @selected($old('status') === 'scrapped')>Scrapped</option>
                        <option value="inactive" @selected($old('status') === 'inactive')>Inactive</option>
                    </select>
                    <label for="status" class="{{ $labelSelCls }}">Status <span class="text-red-400">*</span></label>
                    <flux:error name="status" />
                </div>

                {{-- Category --}}
                <div class="relative">
                    <select id="category_select" name="asset_category_id"
                        class="{{ $selectCls }}"
                        x-on:change="loadSubcategories($event.target.value)"
                        required>
                        <option value="">Select Category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" data-code="{{ $cat->code }}"
                                @selected($old('asset_category_id') == $cat->id)>
                                {{ $cat->code }} — {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <label for="category_select" class="{{ $labelSelCls }}">Category <span class="text-red-400">*</span></label>
                    <flux:error name="asset_category_id" />
                </div>

                {{-- Subcategory --}}
                <div class="relative">
                    <select name="asset_subcategory_id" id="asset_subcategory_id"
                        x-model="selectedSubcategoryId"
                        class="{{ $selectCls }}">
                        <option value="">— None —</option>
                        <template x-for="sub in subcategories" :key="sub.id">
                            <option :value="sub.id" x-text="sub.name"></option>
                        </template>
                    </select>
                    <label for="asset_subcategory_id" class="{{ $labelSelCls }}">Subcategory</label>
                    <flux:error name="asset_subcategory_id" />
                </div>
            </div>

            {{-- Right: Description spanning both rows --}}
            <div class="relative">
                <textarea name="asset_description" id="asset_description" rows="4"
                    placeholder=" "
                    class="{{ $textareaCls }} h-full">{{ $old('asset_description') }}</textarea>
                <label for="asset_description" class="{{ $labelCls }}">Description</label>
                <flux:error name="asset_description" />
            </div>
        </div>
    </div>

    {{-- Section: Identification --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Identification & Location</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative">
                <input type="text" name="manufacturer" id="manufacturer"
                    value="{{ $old('manufacturer') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="manufacturer" class="{{ $labelCls }}">Manufacturer</label>
                <flux:error name="manufacturer" />
            </div>

            <div class="relative">
                <input type="text" name="model" id="model"
                    value="{{ $old('model') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="model" class="{{ $labelCls }}">Model</label>
                <flux:error name="model" />
            </div>

            <div class="relative">
                <input type="number" name="model_year" id="model_year"
                    value="{{ $old('model_year') }}" placeholder=" "
                    min="1900" max="{{ date('Y') + 1 }}"
                    class="{{ $inputCls }}" />
                <label for="model_year" class="{{ $labelCls }}">Model Year</label>
                <flux:error name="model_year" />
            </div>

            <div class="relative">
                <input type="text" name="serial_number" id="serial_number"
                    value="{{ $old('serial_number') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="serial_number" class="{{ $labelCls }}">Serial Number</label>
                <flux:error name="serial_number" />
            </div>

            {{-- Location searchable dropdown --}}
            <div class="relative" x-on:click.outside="locationOpen = false">
                <input type="hidden" name="location" :value="locationValue">
                <input type="text" placeholder=" " autocomplete="off" id="location"
                       x-model="locationSearch"
                       x-on:focus="locationOpen = true; filterLocations()"
                       x-on:input="locationOpen = true; filterLocations()"
                       x-on:keydown.escape="locationOpen = false"
                       class="{{ $inputCls }}" />
                <label for="location" class="{{ $labelCls }}">Location</label>
                <flux:error name="location" />

                <div x-show="locationOpen" x-cloak
                     class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                     style="max-height: 220px; overflow-y: auto;">

                    {{-- Results --}}
                    <template x-for="loc in locationResults" :key="loc.id">
                        <button type="button" x-on:click="selectLocation(loc.name)"
                                class="flex w-full items-center px-3 py-2 text-sm text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                :class="locationValue === loc.name ? 'text-accent font-semibold bg-accent/5' : 'text-zinc-700 dark:text-zinc-300'"
                                x-text="loc.name">
                        </button>
                    </template>

                    {{-- No results --}}
                    <p x-show="locationResults.length === 0 && !showAddLocation"
                       class="px-3 py-2 text-xs text-zinc-400">No locations found.</p>

                    {{-- Inline Add --}}
                    <div x-show="showAddLocation"
                         class="border-t border-zinc-100 px-3 py-2.5 dark:border-zinc-800">
                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Add New Location</p>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="newLocationName"
                                   placeholder="Location name…"
                                   x-on:keydown.enter.prevent="saveNewLocation()"
                                   class="flex-1 rounded-md border border-zinc-300 bg-white px-2.5 py-1 text-sm focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                            <button type="button" x-on:click="saveNewLocation()"
                                    :disabled="locationSaving"
                                    class="rounded-md bg-accent px-2.5 py-1 text-xs font-semibold text-accent-foreground disabled:opacity-50 transition-opacity">
                                <span x-show="!locationSaving">Add</span>
                                <span x-show="locationSaving" x-cloak>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Department searchable dropdown --}}
            <div class="relative" x-on:click.outside="deptOpen = false">
                <input type="hidden" name="department" :value="deptValue">
                <input type="text" placeholder=" " autocomplete="off" id="department"
                       x-model="deptSearch"
                       x-on:focus="deptOpen = true; filterDepts()"
                       x-on:input="deptOpen = true; filterDepts()"
                       x-on:keydown.escape="deptOpen = false"
                       class="{{ $inputCls }}" />
                <label for="department" class="{{ $labelCls }}">Department</label>
                <flux:error name="department" />

                <div x-show="deptOpen" x-cloak
                     class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                     style="max-height: 220px; overflow-y: auto;">

                    <template x-for="dept in deptResults" :key="dept.id">
                        <button type="button" x-on:click="selectDept(dept.name)"
                                class="flex w-full items-center px-3 py-2 text-sm text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                :class="deptValue === dept.name ? 'text-accent font-semibold bg-accent/5' : 'text-zinc-700 dark:text-zinc-300'"
                                x-text="dept.name">
                        </button>
                    </template>

                    <p x-show="deptResults.length === 0 && !showAddDept"
                       class="px-3 py-2 text-xs text-zinc-400">No departments found.</p>

                    <div x-show="showAddDept"
                         class="border-t border-zinc-100 px-3 py-2.5 dark:border-zinc-800">
                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Add New Department</p>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="newDeptName"
                                   placeholder="Department name…"
                                   x-on:keydown.enter.prevent="saveNewDept()"
                                   class="flex-1 rounded-md border border-zinc-300 bg-white px-2.5 py-1 text-sm focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                            <button type="button" x-on:click="saveNewDept()"
                                    :disabled="deptSaving"
                                    class="rounded-md bg-accent px-2.5 py-1 text-xs font-semibold text-accent-foreground disabled:opacity-50 transition-opacity">
                                <span x-show="!deptSaving">Add</span>
                                <span x-show="deptSaving" x-cloak>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <input type="text" name="custodian" id="custodian"
                    value="{{ $old('custodian') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="custodian" class="{{ $labelCls }}">Custodian</label>
                <flux:error name="custodian" />
            </div>

            <div class="relative">
                <input type="text" name="vendor_supplier" id="vendor_supplier"
                    value="{{ $old('vendor_supplier') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="vendor_supplier" class="{{ $labelCls }}">Vendor / Supplier</label>
                <flux:error name="vendor_supplier" />
            </div>
        </div>
    </div>

    {{-- Section: Purchase / Bill --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Purchase & Bill Details</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative">
                <input type="text" name="bill_no" id="bill_no"
                    value="{{ $old('bill_no') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="bill_no" class="{{ $labelCls }}">Bill Number</label>
                <flux:error name="bill_no" />
            </div>

            <div class="relative">
                <input type="number" name="bill_amount" id="bill_amount"
                    value="{{ $old('bill_amount') }}" placeholder=" "
                    min="0" step="0.01"
                    class="{{ $inputCls }}" />
                <label for="bill_amount" class="{{ $labelCls }}">Bill Amount (₹)</label>
                <flux:error name="bill_amount" />
            </div>

            <div>
                <x-date-picker name="bill_date" label="Bill Date" value="{{ $old('bill_date') }}" />
                <flux:error name="bill_date" />
            </div>

            <div>
                <x-date-picker name="purchase_date" label="Purchase Date" value="{{ $old('purchase_date') }}" />
                <flux:error name="purchase_date" />
            </div>
        </div>

        {{-- Purchase Bill Photo --}}
        @php
            $existingBill = $isEdit ? $asset->documents->where('document_type', 'purchase_bill')->first() : null;
        @endphp
        <div class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <p class="mb-3 text-xs font-medium text-zinc-500">Purchase Bill Photo / PDF
                <span class="ml-1 font-normal">(PDF, JPG, PNG, WEBP — max 5 MB)</span>
            </p>
            <div class="mx-auto max-w-md"
                 data-existing-bill="{{ $existingBill ? Storage::url($existingBill->file_path) : '' }}"
                 data-existing-bill-name="{{ $existingBill?->file_original_name }}">
                <input type="file" name="purchase_bill_file"
                       accept=".pdf,.jpg,.jpeg,.png,.webp" />
                <flux:error name="purchase_bill_file" />
            </div>
        </div>
    </div>

    {{-- Section: Vehicle Fields (shown only when category = VE) --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" x-show="isVehicle" x-transition>
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">
            Vehicle Compliance Dates
            <span class="ml-2 text-xs font-normal text-zinc-500">(Vehicle category only)</span>
        </flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="relative">
                <input type="text" name="registration_number" id="registration_number"
                    value="{{ $old('registration_number') }}" placeholder=" "
                    class="{{ $inputCls }} uppercase" />
                <label for="registration_number" class="{{ $labelCls }}">Registration Number</label>
                <flux:error name="registration_number" />
            </div>

            <div class="lg:col-span-2"></div>{{-- spacer --}}

            <div>
                <x-date-picker name="puc_expiry_date" label="PUC Expiry Date" value="{{ $old('puc_expiry_date') }}" />
                <flux:error name="puc_expiry_date" />
            </div>

            <div></div>{{-- spacer --}}

            <div>
                <x-date-picker name="fitness_expiry_date" label="Fitness Expiry Date" value="{{ $old('fitness_expiry_date') }}" />
                <flux:error name="fitness_expiry_date" />
            </div>

            <div></div>{{-- spacer --}}

            <div>
                <x-date-picker name="road_tax_expiry_date" label="Road Tax Expiry Date" value="{{ $old('road_tax_expiry_date') }}" />
                <flux:error name="road_tax_expiry_date" />
            </div>

            <div></div>{{-- spacer --}}

            <div class="relative">
                <input type="number" name="vehicle_obv" id="vehicle_obv"
                    value="{{ $old('vehicle_obv') }}" placeholder=" "
                    min="0" step="0.01"
                    class="{{ $inputCls }}" />
                <label for="vehicle_obv" class="{{ $labelCls }}">OBV (₹)</label>
                <flux:error name="vehicle_obv" />
            </div>

            <div class="relative">
                <input type="number" name="vehicle_depreciation_percent" id="vehicle_depreciation_percent"
                    value="{{ $old('vehicle_depreciation_percent') }}" placeholder=" "
                    min="0" max="100" step="0.01"
                    class="{{ $inputCls }}" />
                <label for="vehicle_depreciation_percent" class="{{ $labelCls }}">Depreciation %</label>
                <flux:error name="vehicle_depreciation_percent" />
            </div>

            <div class="relative">
                <input type="number" name="vehicle_depreciation_book_value" id="vehicle_depreciation_book_value"
                    value="{{ $old('vehicle_depreciation_book_value') }}" placeholder=" "
                    min="0" step="0.01"
                    class="{{ $inputCls }}" />
                <label for="vehicle_depreciation_book_value" class="{{ $labelCls }}">Depreciation Book Value (₹)</label>
                <flux:error name="vehicle_depreciation_book_value" />
            </div>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="flex items-center gap-3 pt-2">
        <flux:button type="submit" variant="primary" icon="check">
            {{ $isEdit ? 'Update Asset and Continue' : 'Create Asset and Continue' }}
        </flux:button>
        <flux:button href="{{ $isEdit ? route('assets.show', $asset) : route('assets.index') }}" wire:navigate variant="ghost">
            Cancel
        </flux:button>
    </div>
</div>
