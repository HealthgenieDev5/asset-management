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
                fp('bill_date'); fp('purchase_date'); fp('warranty_lapse_date');
                fp('ew_date_from'); fp('ew_date_to');
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

<div class="space-y-6" x-data="assetForm">

    {{-- Section: Basic Info --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Basic Information</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Asset Name --}}
            <div class="lg:col-span-2">
                <flux:field>
                    <flux:label>Asset Name <span class="text-red-400">*</span></flux:label>
                    <flux:input name="asset_name" value="{{ $old('asset_name') }}" placeholder="e.g. Honda City Car" required />
                    <flux:error name="asset_name" />
                </flux:field>
            </div>

            {{-- Status --}}
            <flux:field>
                <flux:label>Status <span class="text-red-400">*</span></flux:label>
                <flux:select name="status">
                    <option value="active" @selected($old('status', 'active') === 'active')>Active</option>
                    <option value="under_repair" @selected($old('status') === 'under_repair')>Under Repair</option>
                    <option value="disposed" @selected($old('status') === 'disposed')>Disposed</option>
                    <option value="scrapped" @selected($old('status') === 'scrapped')>Scrapped</option>
                    <option value="inactive" @selected($old('status') === 'inactive')>Inactive</option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            {{-- Category --}}
            <flux:field>
                <flux:label>Category <span class="text-red-400">*</span></flux:label>
                <select id="category_select" name="asset_category_id"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
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
                <flux:error name="asset_category_id" />
            </flux:field>

            {{-- Subcategory --}}
            <flux:field>
                <flux:label>Subcategory</flux:label>
                <select name="asset_subcategory_id" x-model="selectedSubcategoryId"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">— None —</option>
                    <template x-for="sub in subcategories" :key="sub.id">
                        <option :value="sub.id" x-text="sub.name"></option>
                    </template>
                </select>
                <flux:error name="asset_subcategory_id" />
            </flux:field>

            {{-- Description --}}
            <div class="lg:col-span-3">
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea name="asset_description" rows="2" placeholder="Brief description of the asset">{{ $old('asset_description') }}</flux:textarea>
                    <flux:error name="asset_description" />
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Section: Identification --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Identification & Location</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:field>
                <flux:label>Manufacturer</flux:label>
                <flux:input name="manufacturer" value="{{ $old('manufacturer') }}" placeholder="e.g. Honda" />
                <flux:error name="manufacturer" />
            </flux:field>

            <flux:field>
                <flux:label>Model</flux:label>
                <flux:input name="model" value="{{ $old('model') }}" placeholder="e.g. City 1.5 VX" />
                <flux:error name="model" />
            </flux:field>

            <flux:field>
                <flux:label>Model Year</flux:label>
                <flux:input type="number" name="model_year" value="{{ $old('model_year') }}" placeholder="e.g. 2022" min="1900" max="{{ date('Y') + 1 }}" />
                <flux:error name="model_year" />
            </flux:field>

            <flux:field>
                <flux:label>Serial Number</flux:label>
                <flux:input name="serial_number" value="{{ $old('serial_number') }}" placeholder="Manufacturer serial" />
                <flux:error name="serial_number" />
            </flux:field>

            <flux:field>
                <flux:label>Location</flux:label>
                <flux:input name="location" value="{{ $old('location') }}" placeholder="e.g. Head Office, Warehouse B" />
                <flux:error name="location" />
            </flux:field>

            <flux:field>
                <flux:label>Department</flux:label>
                <flux:input name="department" value="{{ $old('department') }}" placeholder="e.g. Operations" />
                <flux:error name="department" />
            </flux:field>

            <flux:field>
                <flux:label>Custodian</flux:label>
                <flux:input name="custodian" value="{{ $old('custodian') }}" placeholder="Person responsible" />
                <flux:error name="custodian" />
            </flux:field>

            <flux:field>
                <flux:label>Vendor / Supplier</flux:label>
                <flux:input name="vendor_supplier" value="{{ $old('vendor_supplier') }}" placeholder="Supplier name" />
                <flux:error name="vendor_supplier" />
            </flux:field>
        </div>
    </div>

    {{-- Section: Purchase / Bill --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Purchase & Bill Details</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:field>
                <flux:label>Bill Number</flux:label>
                <flux:input name="bill_no" value="{{ $old('bill_no') }}" placeholder="Invoice / bill no." />
                <flux:error name="bill_no" />
            </flux:field>

            <flux:field>
                <flux:label>Bill Amount (₹)</flux:label>
                <flux:input type="number" name="bill_amount" value="{{ $old('bill_amount') }}" placeholder="0.00" min="0" step="0.01" />
                <flux:error name="bill_amount" />
            </flux:field>

            <flux:field>
                <flux:label>Bill Date</flux:label>
                <x-date-picker name="bill_date" value="{{ $old('bill_date') }}" />
                <flux:error name="bill_date" />
            </flux:field>

            <flux:field>
                <flux:label>Purchase Date</flux:label>
                <x-date-picker name="purchase_date" value="{{ $old('purchase_date') }}" />
                <flux:error name="purchase_date" />
            </flux:field>
        </div>
    </div>

    {{-- Section: Warranty --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Original Warranty</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <flux:field>
                    <flux:label>Warranty Details</flux:label>
                    <flux:textarea name="warranty_details" rows="2" placeholder="e.g. 2 year on-site warranty from Honda">{{ $old('warranty_details') }}</flux:textarea>
                    <flux:error name="warranty_details" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Warranty Lapse Date</flux:label>
                <x-date-picker name="warranty_lapse_date" value="{{ $old('warranty_lapse_date') }}" />
                <flux:error name="warranty_lapse_date" />
            </flux:field>

            <flux:field>
                <flux:label>Remind Before (days)</flux:label>
                <flux:input type="number" name="warranty_reminder_before_days" value="{{ $old('warranty_reminder_before_days', 30) }}" min="1" max="365" />
                <flux:description>Days before warranty expiry to send reminder</flux:description>
                <flux:error name="warranty_reminder_before_days" />
            </flux:field>
        </div>

        {{-- Warranty document uploads --}}
        <div class="mt-5 grid gap-4 border-t border-zinc-200 pt-5 sm:grid-cols-2 dark:border-zinc-800">
            {{-- Warranty Card --}}
            <div>
                <flux:label class="mb-1.5 block">Warranty Card
                    <span class="ml-1 text-xs font-normal text-zinc-500">(PDF / image, max 5 MB)</span>
                </flux:label>
                @if ($isEdit && ($warrantyCard = $asset->documents->where('document_type', 'warranty_card')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-4 text-zinc-400 shrink-0" />
                        <a href="{{ Storage::url($warrantyCard->file_path) }}" target="_blank"
                           class="flex-1 truncate text-accent hover:underline">
                            {{ $warrantyCard->file_original_name }}
                        </a>
                        <span class="shrink-0 text-xs text-zinc-500">
                            {{ number_format($warrantyCard->file_size / 1024, 0) }} KB
                        </span>
                    </div>
                    <p class="mb-1.5 text-xs text-zinc-500">Upload a new file to replace the existing one.</p>
                @endif
                <input type="file" name="warranty_card" accept=".pdf,.jpg,.jpeg,.png,.webp"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                           file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                           focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                @error('warranty_card')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Warranty Activation Image --}}
            <div>
                <flux:label class="mb-1.5 block">Warranty Activation Image
                    <span class="ml-1 text-xs font-normal text-zinc-500">(PDF / image, max 5 MB)</span>
                </flux:label>
                @if ($isEdit && ($activationImg = $asset->documents->where('document_type', 'warranty_activation_image')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-4 text-zinc-400 shrink-0" />
                        <a href="{{ Storage::url($activationImg->file_path) }}" target="_blank"
                           class="flex-1 truncate text-accent hover:underline">
                            {{ $activationImg->file_original_name }}
                        </a>
                        <span class="shrink-0 text-xs text-zinc-500">
                            {{ number_format($activationImg->file_size / 1024, 0) }} KB
                        </span>
                    </div>
                    <p class="mb-1.5 text-xs text-zinc-500">Upload a new file to replace the existing one.</p>
                @endif
                <input type="file" name="warranty_activation_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                           file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                           focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                @error('warranty_activation_image')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section: Extended Warranty --}}
    @php
        $ew = $isEdit ? $asset->extendedWarranties->first() : null;
        $ewOld = fn(string $field, $default = '') => old($field, $ew?->$field ?? $default);
    @endphp
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-1 font-semibold text-zinc-800 dark:text-zinc-200">Extended Warranty</flux:heading>
        <flux:text class="mb-5 text-xs text-zinc-500">Optional — fill only if an extended warranty was purchased separately from the original.</flux:text>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Vendor --}}
            <flux:field>
                <flux:label>Vendor / Provider</flux:label>
                <flux:input name="ew_vendor" value="{{ $ewOld('extended_warranty_vendor') }}" placeholder="e.g. Honda Extended Care" />
                <flux:error name="ew_vendor" />
            </flux:field>

            {{-- Date From --}}
            <flux:field>
                <flux:label>Warranty From</flux:label>
                <x-date-picker name="ew_date_from" value="{{ $ew?->extended_warranty_date_from ? old('ew_date_from', $ew->extended_warranty_date_from->format('Y-m-d')) : old('ew_date_from') }}" />
                <flux:error name="ew_date_from" />
            </flux:field>

            {{-- Date To --}}
            <flux:field>
                <flux:label>Warranty Lapse Date</flux:label>
                <x-date-picker name="ew_date_to" value="{{ $ew?->extended_warranty_date_to ? old('ew_date_to', $ew->extended_warranty_date_to->format('Y-m-d')) : old('ew_date_to') }}" />
                <flux:error name="ew_date_to" />
            </flux:field>

            {{-- Bill No --}}
            <flux:field>
                <flux:label>Bill Number</flux:label>
                <flux:input name="ew_bill_no" value="{{ $ewOld('extended_warranty_bill_no') }}" placeholder="Invoice / bill no." />
                <flux:error name="ew_bill_no" />
            </flux:field>

            {{-- Bill Amount --}}
            <flux:field>
                <flux:label>Bill Amount (₹)</flux:label>
                <flux:input type="number" name="ew_amount" value="{{ $ewOld('extended_warranty_amount') }}" placeholder="0.00" min="0" step="0.01" />
                <flux:error name="ew_amount" />
            </flux:field>

            {{-- Reminder Before Days --}}
            <flux:field>
                <flux:label>Remind Before (days)</flux:label>
                <flux:input type="number" name="ew_reminder_days" value="{{ $ewOld('reminder_before_days', 30) }}" min="1" max="365" />
                <flux:description>Days before extended warranty expiry to send reminder</flux:description>
                <flux:error name="ew_reminder_days" />
            </flux:field>

            {{-- Terms --}}
            <div class="sm:col-span-2 lg:col-span-2">
                <flux:field>
                    <flux:label>Warranty Terms</flux:label>
                    <flux:textarea name="ew_terms" rows="2" placeholder="Coverage details, exclusions, etc.">{{ $ewOld('extended_warranty_terms') }}</flux:textarea>
                    <flux:error name="ew_terms" />
                </flux:field>
            </div>

            {{-- Remarks --}}
            <flux:field>
                <flux:label>Remarks</flux:label>
                <flux:textarea name="ew_remarks" rows="2" placeholder="Any additional notes">{{ $ewOld('remarks') }}</flux:textarea>
                <flux:error name="ew_remarks" />
            </flux:field>
        </div>

        {{-- Extended Warranty Document Uploads --}}
        <div class="mt-5 grid gap-4 border-t border-zinc-200 pt-5 sm:grid-cols-2 dark:border-zinc-800">
            {{-- Bill Image --}}
            <div>
                <flux:label class="mb-1.5 block">Extended Warranty Bill
                    <span class="ml-1 text-xs font-normal text-zinc-500">(PDF / image, max 5 MB)</span>
                </flux:label>
                @if ($ew && ($ewBill = $ew->documents->where('document_type', 'extended_warranty_bill')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-4 shrink-0 text-zinc-400" />
                        <a href="{{ Storage::url($ewBill->file_path) }}" target="_blank"
                           class="flex-1 truncate text-accent hover:underline">{{ $ewBill->file_original_name }}</a>
                        <span class="shrink-0 text-xs text-zinc-500">{{ number_format($ewBill->file_size / 1024, 0) }} KB</span>
                    </div>
                    <p class="mb-1.5 text-xs text-zinc-500">Upload a new file to replace.</p>
                @endif
                <input type="file" name="ew_bill_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                           file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                           focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                @error('ew_bill_image')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Activation Image --}}
            <div>
                <flux:label class="mb-1.5 block">Warranty Activation Image
                    <span class="ml-1 text-xs font-normal text-zinc-500">(PDF / image, max 5 MB)</span>
                </flux:label>
                @if ($ew && ($ewActivation = $ew->documents->where('document_type', 'extended_warranty_image')->last()))
                    <div class="mb-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.paper-clip class="size-4 shrink-0 text-zinc-400" />
                        <a href="{{ Storage::url($ewActivation->file_path) }}" target="_blank"
                           class="flex-1 truncate text-accent hover:underline">{{ $ewActivation->file_original_name }}</a>
                        <span class="shrink-0 text-xs text-zinc-500">{{ number_format($ewActivation->file_size / 1024, 0) }} KB</span>
                    </div>
                    <p class="mb-1.5 text-xs text-zinc-500">Upload a new file to replace.</p>
                @endif
                <input type="file" name="ew_activation_image" accept=".pdf,.jpg,.jpeg,.png,.webp"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                           file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                           focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                @error('ew_activation_image')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section: Maintenance Schedule --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="mb-5 font-semibold text-zinc-800 dark:text-zinc-200">Maintenance Schedule</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:field>
                <flux:label>Schedule Type</flux:label>
                <flux:select name="maintenance_schedule_type" x-model="maintenanceType">
                    <option value="none" @selected($old('maintenance_schedule_type', 'none') === 'none')>None</option>
                    <option value="date_based" @selected($old('maintenance_schedule_type') === 'date_based')>Date Based</option>
                    <option value="hours_based" @selected($old('maintenance_schedule_type') === 'hours_based')>Hours Based</option>
                    <option value="mileage_based" @selected($old('maintenance_schedule_type') === 'mileage_based')>Mileage Based</option>
                    <option value="custom" @selected($old('maintenance_schedule_type') === 'custom')>Custom</option>
                </flux:select>
                <flux:error name="maintenance_schedule_type" />
            </flux:field>

            <flux:field x-show="maintenanceType !== 'none'">
                <flux:label>Interval Value</flux:label>
                <flux:input type="number" name="maintenance_interval_value" value="{{ $old('maintenance_interval_value') }}" min="1" placeholder="e.g. 3" />
                <flux:error name="maintenance_interval_value" />
            </flux:field>

            <flux:field x-show="maintenanceType !== 'none'">
                <flux:label>Interval Unit</flux:label>
                <flux:select name="maintenance_interval_unit">
                    <option value="">Select Unit</option>
                    <option value="days" @selected($old('maintenance_interval_unit') === 'days')>Days</option>
                    <option value="weeks" @selected($old('maintenance_interval_unit') === 'weeks')>Weeks</option>
                    <option value="months" @selected($old('maintenance_interval_unit') === 'months')>Months</option>
                    <option value="years" @selected($old('maintenance_interval_unit') === 'years')>Years</option>
                    <option value="operating_hours" @selected($old('maintenance_interval_unit') === 'operating_hours')>Operating Hours</option>
                    <option value="miles" @selected($old('maintenance_interval_unit') === 'miles')>Miles</option>
                    <option value="kilometers" @selected($old('maintenance_interval_unit') === 'kilometers')>Kilometers</option>
                </flux:select>
                <flux:error name="maintenance_interval_unit" />
            </flux:field>
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
                <flux:field>
                    <flux:label>Inspection Frequency</flux:label>
                    <flux:input type="number" name="inspection_frequency_value" value="{{ $old('inspection_frequency_value') }}" min="1" placeholder="e.g. 6" />
                    <flux:error name="inspection_frequency_value" />
                </flux:field>

                <flux:field>
                    <flux:label>Frequency Unit</flux:label>
                    <flux:select name="inspection_frequency_unit">
                        <option value="">Select Unit</option>
                        <option value="days" @selected($old('inspection_frequency_unit') === 'days')>Days</option>
                        <option value="weeks" @selected($old('inspection_frequency_unit') === 'weeks')>Weeks</option>
                        <option value="months" @selected($old('inspection_frequency_unit') === 'months')>Months</option>
                        <option value="years" @selected($old('inspection_frequency_unit') === 'years')>Years</option>
                    </flux:select>
                    <flux:error name="inspection_frequency_unit" />
                </flux:field>
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
            <flux:field>
                <flux:label>Registration Number</flux:label>
                <flux:input name="registration_number" value="{{ $old('registration_number') }}" placeholder="e.g. MH12AB1234" class="uppercase" />
                <flux:error name="registration_number" />
            </flux:field>

            <div class="lg:col-span-2"></div>{{-- spacer --}}

            <flux:field>
                <flux:label>PUC Expiry Date</flux:label>
                <x-date-picker name="puc_expiry_date" value="{{ $old('puc_expiry_date') }}" />
                <flux:error name="puc_expiry_date" />
            </flux:field>

            <flux:field>
                <flux:label>PUC Reminder (days before)</flux:label>
                <flux:input type="number" name="puc_reminder_before_days" value="{{ $old('puc_reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
                <flux:error name="puc_reminder_before_days" />
            </flux:field>

            <div></div>{{-- spacer --}}

            <flux:field>
                <flux:label>Fitness Expiry Date</flux:label>
                <x-date-picker name="fitness_expiry_date" value="{{ $old('fitness_expiry_date') }}" />
                <flux:error name="fitness_expiry_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fitness Reminder (days before)</flux:label>
                <flux:input type="number" name="fitness_reminder_before_days" value="{{ $old('fitness_reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
                <flux:error name="fitness_reminder_before_days" />
            </flux:field>

            <div></div>{{-- spacer --}}

            <flux:field>
                <flux:label>Road Tax Expiry Date</flux:label>
                <x-date-picker name="road_tax_expiry_date" value="{{ $old('road_tax_expiry_date') }}" />
                <flux:error name="road_tax_expiry_date" />
            </flux:field>

            <flux:field>
                <flux:label>Road Tax Reminder (days before)</flux:label>
                <flux:input type="number" name="road_tax_reminder_before_days" value="{{ $old('road_tax_reminder_before_days') }}" min="1" max="365" placeholder="e.g. 30" />
                <flux:error name="road_tax_reminder_before_days" />
            </flux:field>

            <div></div>{{-- spacer --}}

            <flux:field>
                <flux:label>OBV (₹)</flux:label>
                <flux:input type="number" name="vehicle_obv" value="{{ $old('vehicle_obv') }}" min="0" step="0.01" placeholder="Original Book Value" />
                <flux:error name="vehicle_obv" />
            </flux:field>

            <flux:field>
                <flux:label>Depreciation %</flux:label>
                <flux:input type="number" name="vehicle_depreciation_percent" value="{{ $old('vehicle_depreciation_percent') }}" min="0" max="100" step="0.01" placeholder="e.g. 15.00" />
                <flux:error name="vehicle_depreciation_percent" />
            </flux:field>

            <flux:field>
                <flux:label>Depreciation Book Value (₹)</flux:label>
                <flux:input type="number" name="vehicle_depreciation_book_value" value="{{ $old('vehicle_depreciation_book_value') }}" min="0" step="0.01" />
                <flux:error name="vehicle_depreciation_book_value" />
            </flux:field>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="flex items-center gap-3 pt-2">
        <flux:button type="submit" variant="primary" icon="check">
            {{ $isEdit ? 'Update Asset' : 'Create Asset' }}
        </flux:button>
        <flux:button href="{{ $isEdit ? route('assets.show', $asset) : route('assets.index') }}" wire:navigate variant="ghost">
            Cancel
        </flux:button>
    </div>
</div>

