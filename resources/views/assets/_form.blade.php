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
        maintenanceType: '{{ $old('maintenance_schedule_type', 'none') }}',
        inspectionRequired: {{ $old('inspection_required', false) ? 'true' : 'false' }},

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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Asset Name --}}
            <div class="lg:col-span-2">
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

            {{-- Description --}}
            <div class="lg:col-span-3">
                <div class="relative">
                    <textarea name="asset_description" id="asset_description" rows="2"
                        placeholder=" "
                        class="{{ $textareaCls }}">{{ $old('asset_description') }}</textarea>
                    <label for="asset_description" class="{{ $labelCls }}">Description</label>
                </div>
                <flux:error name="asset_description" />
            </div>
        </div>
    </div>

    {{-- Section: Identification --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Identification & Location</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

            <div class="relative">
                <input type="text" name="location" id="location"
                    value="{{ $old('location') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="location" class="{{ $labelCls }}">Location</label>
                <flux:error name="location" />
            </div>

            <div class="relative">
                <input type="text" name="department" id="department"
                    value="{{ $old('department') }}" placeholder=" "
                    class="{{ $inputCls }}" />
                <label for="department" class="{{ $labelCls }}">Department</label>
                <flux:error name="department" />
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
        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <p class="mb-1.5 text-xs font-medium text-zinc-500">Purchase Bill Photo / PDF
                <span class="ml-1 font-normal">(PDF, JPG, PNG, WEBP — max 5 MB)</span>
            </p>
            @if ($isEdit && $asset->documents->where('document_type', 'purchase_bill')->isNotEmpty())
                @php $existingBill = $asset->documents->where('document_type', 'purchase_bill')->first(); @endphp
                <div class="mb-2 flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($existingBill->isImage())
                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                    @else
                        <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
                    @endif
                    <span class="flex-1 truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $existingBill->file_original_name }}</span>
                    <a href="{{ Storage::url($existingBill->file_path) }}" target="_blank"
                       class="text-xs text-accent hover:underline">View</a>
                </div>
                <p class="mb-1.5 text-xs text-zinc-500">Upload a new file to replace the existing one.</p>
            @endif
            <input type="file" name="purchase_bill_file"
                   accept=".pdf,.jpg,.jpeg,.png,.webp"
                   class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                          file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                          focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                          dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
            <flux:error name="purchase_bill_file" />
        </div>
    </div>

    {{-- Section: Maintenance Schedule --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Maintenance Schedule</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative">
                <select name="maintenance_schedule_type" id="maintenance_schedule_type"
                    class="{{ $selectCls }}"
                    x-model="maintenanceType">
                    <option value="none" @selected($old('maintenance_schedule_type', 'none') === 'none')>None</option>
                    <option value="date_based" @selected($old('maintenance_schedule_type') === 'date_based')>Date Based</option>
                    <option value="hours_based" @selected($old('maintenance_schedule_type') === 'hours_based')>Hours Based</option>
                    <option value="mileage_based" @selected($old('maintenance_schedule_type') === 'mileage_based')>Mileage Based</option>
                    <option value="custom" @selected($old('maintenance_schedule_type') === 'custom')>Custom</option>
                </select>
                <label for="maintenance_schedule_type" class="{{ $labelSelCls }}">Schedule Type</label>
                <flux:error name="maintenance_schedule_type" />
            </div>

            <div class="relative" x-show="maintenanceType !== 'none'">
                <input type="number" name="maintenance_interval_value" id="maintenance_interval_value"
                    value="{{ $old('maintenance_interval_value') }}" placeholder=" "
                    min="1"
                    class="{{ $inputCls }}" />
                <label for="maintenance_interval_value" class="{{ $labelCls }}">Interval Value</label>
                <flux:error name="maintenance_interval_value" />
            </div>

            <div class="relative" x-show="maintenanceType !== 'none'">
                <select name="maintenance_interval_unit" id="maintenance_interval_unit"
                    class="{{ $selectCls }}">
                    <option value="">Select Unit</option>
                    <option value="days" @selected($old('maintenance_interval_unit') === 'days')>Days</option>
                    <option value="weeks" @selected($old('maintenance_interval_unit') === 'weeks')>Weeks</option>
                    <option value="months" @selected($old('maintenance_interval_unit') === 'months')>Months</option>
                    <option value="years" @selected($old('maintenance_interval_unit') === 'years')>Years</option>
                    <option value="operating_hours" @selected($old('maintenance_interval_unit') === 'operating_hours')>Operating Hours</option>
                    <option value="miles" @selected($old('maintenance_interval_unit') === 'miles')>Miles</option>
                    <option value="kilometers" @selected($old('maintenance_interval_unit') === 'kilometers')>Kilometers</option>
                </select>
                <label for="maintenance_interval_unit" class="{{ $labelSelCls }}">Interval Unit</label>
                <flux:error name="maintenance_interval_unit" />
            </div>
        </div>

        {{-- Inspection --}}
        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <div class="flex items-center gap-3 mb-4">
                <input type="checkbox" name="inspection_required" id="inspection_required" value="1"
                    x-model="inspectionRequired"
                    @checked($old('inspection_required', false))
                    class="size-4 rounded border-zinc-400 bg-white text-accent focus:ring-accent dark:border-zinc-600 dark:bg-zinc-700" />
                <label for="inspection_required" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Inspection Required</label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2" x-show="inspectionRequired">
                <div class="relative">
                    <input type="number" name="inspection_frequency_value" id="inspection_frequency_value"
                        value="{{ $old('inspection_frequency_value') }}" placeholder=" "
                        min="1"
                        class="{{ $inputCls }}" />
                    <label for="inspection_frequency_value" class="{{ $labelCls }}">Inspection Frequency</label>
                    <flux:error name="inspection_frequency_value" />
                </div>

                <div class="relative">
                    <select name="inspection_frequency_unit" id="inspection_frequency_unit"
                        class="{{ $selectCls }}">
                        <option value="">Select Unit</option>
                        <option value="days" @selected($old('inspection_frequency_unit') === 'days')>Days</option>
                        <option value="weeks" @selected($old('inspection_frequency_unit') === 'weeks')>Weeks</option>
                        <option value="months" @selected($old('inspection_frequency_unit') === 'months')>Months</option>
                        <option value="years" @selected($old('inspection_frequency_unit') === 'years')>Years</option>
                    </select>
                    <label for="inspection_frequency_unit" class="{{ $labelSelCls }}">Frequency Unit</label>
                    <flux:error name="inspection_frequency_unit" />
                </div>
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

            <div class="relative">
                <input type="number" name="puc_reminder_before_days" id="puc_reminder_before_days"
                    value="{{ $old('puc_reminder_before_days') }}" placeholder=" "
                    min="1" max="365"
                    class="{{ $inputCls }}" />
                <label for="puc_reminder_before_days" class="{{ $labelCls }}">PUC Reminder (days before)</label>
                <flux:error name="puc_reminder_before_days" />
            </div>

            <div></div>{{-- spacer --}}

            <div>
                <x-date-picker name="fitness_expiry_date" label="Fitness Expiry Date" value="{{ $old('fitness_expiry_date') }}" />
                <flux:error name="fitness_expiry_date" />
            </div>

            <div class="relative">
                <input type="number" name="fitness_reminder_before_days" id="fitness_reminder_before_days"
                    value="{{ $old('fitness_reminder_before_days') }}" placeholder=" "
                    min="1" max="365"
                    class="{{ $inputCls }}" />
                <label for="fitness_reminder_before_days" class="{{ $labelCls }}">Fitness Reminder (days before)</label>
                <flux:error name="fitness_reminder_before_days" />
            </div>

            <div></div>{{-- spacer --}}

            <div>
                <x-date-picker name="road_tax_expiry_date" label="Road Tax Expiry Date" value="{{ $old('road_tax_expiry_date') }}" />
                <flux:error name="road_tax_expiry_date" />
            </div>

            <div class="relative">
                <input type="number" name="road_tax_reminder_before_days" id="road_tax_reminder_before_days"
                    value="{{ $old('road_tax_reminder_before_days') }}" placeholder=" "
                    min="1" max="365"
                    class="{{ $inputCls }}" />
                <label for="road_tax_reminder_before_days" class="{{ $labelCls }}">Road Tax Reminder (days before)</label>
                <flux:error name="road_tax_reminder_before_days" />
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
