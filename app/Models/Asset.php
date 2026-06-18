<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Asset $asset) {
            $asset->complaints()->each(fn ($c) => $c->delete());
            $asset->services()->each(fn ($s) => $s->delete());
            $asset->amcContracts()->delete();
            $asset->insurancePolicies()->delete();
            $asset->extendedWarranties()->delete();
            $asset->warranties()->delete();
            $asset->documents()->delete();
            $asset->smartReminders()->delete();
            $asset->maintenanceSchedules()->delete();
        });
    }

    protected $fillable = [
        'asset_code',
        'asset_name',
        'asset_description',
        'asset_category_id',
        'asset_subcategory_id',
        'serial_number',
        'registration_number',
        'manufacturer',
        'model',
        'model_year',
        'location',
        'department',
        'custodian',
        'vendor_supplier',
        'bill_no',
        'bill_amount',
        'bill_date',
        'purchase_date',
        'warranty_details',
        'warranty_lapse_date',
        'warranty_reminder_before_days',
        'warranty_tracking_mode',
        'warranty_unit',
        'warranty_meter_source',
        'warranty_counter_limit',
        'warranty_reminder_before_units',
        'maintenance_schedule_type',
        'maintenance_interval_value',
        'maintenance_interval_unit',
        'inspection_required',
        'inspection_frequency_value',
        'inspection_frequency_unit',
        'puc_expiry_date',
        'puc_reminder_before_days',
        'fitness_expiry_date',
        'fitness_reminder_before_days',
        'road_tax_expiry_date',
        'road_tax_reminder_before_days',
        'vehicle_obv',
        'vehicle_depreciation_percent',
        'vehicle_depreciation_book_value',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date'           => 'date',
            'purchase_date'       => 'date',
            'warranty_lapse_date' => 'date',
            'puc_expiry_date'     => 'date',
            'fitness_expiry_date' => 'date',
            'road_tax_expiry_date' => 'date',
            'bill_amount'         => 'decimal:2',
            'vehicle_obv'         => 'decimal:2',
            'vehicle_depreciation_percent' => 'decimal:2',
            'vehicle_depreciation_book_value' => 'decimal:2',
            'inspection_required'           => 'boolean',
            'warranty_counter_limit'        => 'integer',
            'warranty_reminder_before_units' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(AssetSubcategory::class, 'asset_subcategory_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function extendedWarranties(): HasMany
    {
        return $this->hasMany(AssetExtendedWarranty::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(AssetWarranty::class);
    }

    public function activeWarranties(): HasMany
    {
        return $this->hasMany(AssetWarranty::class)->where('status', 'active');
    }

    public function amcContracts(): HasMany
    {
        return $this->hasMany(AssetAmcContract::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(AssetInsurancePolicy::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(AssetService::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(AssetComplaint::class);
    }

    public function smartReminders(): HasMany
    {
        return $this->hasMany(AssetSmartReminder::class);
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(AssetMaintenanceSchedule::class);
    }

    public function meterLogs(): HasMany
    {
        return $this->hasMany(AssetMeterLog::class)->orderByDesc('logged_at');
    }

    public function latestMeterReading(string $unit): ?int
    {
        return $this->meterLogs()->where('unit', $unit)->value('reading_value');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('asset_category_id', $categoryId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isVehicle(): bool
    {
        return $this->category?->code === 'VE';
    }

    public function isTimeBasedWarranty(): bool
    {
        return ($this->warranty_tracking_mode ?? 'time') === 'time';
    }

    public function isMeterWarranty(): bool
    {
        return $this->warranty_tracking_mode === 'meter';
    }

    public function isCountWarranty(): bool
    {
        return $this->warranty_tracking_mode === 'count';
    }

    public function warrantyUnitLabel(): string
    {
        return $this->warranty_unit ?? 'units';
    }

    public function latestWarrantyCounter(): ?int
    {
        $field = $this->warranty_meter_source === 'mileage' ? 'mileage_reading' : 'meter_reading';
        return $this->services()->orderByDesc('service_date')->value($field);
    }

    public static function generateAssetCode(int $categoryId): string
    {
        $category = AssetCategory::findOrFail($categoryId);

        // Include soft-deleted rows to prevent sequence reuse
        $max = static::withTrashed()
            ->where('asset_category_id', $categoryId)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(asset_code, '-', -1) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $next = ($max ?? 0) + 1;

        return $category->code . '-' . $next;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'       => 'Active',
            'under_repair' => 'Under Repair',
            'disposed'     => 'Disposed',
            'scrapped'     => 'Scrapped',
            'inactive'     => 'Inactive',
            default        => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'       => 'text-green-400 bg-green-400/10 border-green-400/30',
            'under_repair' => 'text-yellow-400 bg-yellow-400/10 border-yellow-400/30',
            'disposed'     => 'text-red-400 bg-red-400/10 border-red-400/30',
            'scrapped'     => 'text-orange-400 bg-orange-400/10 border-orange-400/30',
            'inactive'     => 'text-zinc-400 bg-zinc-400/10 border-zinc-400/30',
            default        => 'text-zinc-400 bg-zinc-400/10 border-zinc-400/30',
        };
    }
}
