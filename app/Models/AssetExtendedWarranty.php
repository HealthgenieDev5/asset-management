<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetExtendedWarranty extends Model
{
    protected $fillable = [
        'asset_id',
        'extended_warranty_vendor',
        'extended_warranty_date_from',
        'extended_warranty_date_to',
        'extended_warranty_bill_no',
        'extended_warranty_amount',
        'extended_warranty_terms',
        'reminder_before_days',
        'extended_warranty_counter_limit',
        'extended_warranty_reminder_before_units',
        'ew_tracking_mode',
        'ew_unit',
        'ew_meter_source',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'extended_warranty_date_from' => 'date',
            'extended_warranty_date_to'   => 'date',
            'extended_warranty_amount'                 => 'decimal:2',
            'extended_warranty_counter_limit'          => 'integer',
            'extended_warranty_reminder_before_units'  => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class, 'documentable_id')
            ->where('documentable_type', self::class);
    }

    public function ewTrackingMode(): string
    {
        return $this->ew_tracking_mode ?? 'time';
    }

    public function ewUnitLabel(): string
    {
        return $this->ew_unit ?? 'units';
    }

    public function latestCounter(): ?int
    {
        $field = $this->ew_meter_source === 'mileage' ? 'mileage_reading' : 'meter_reading';
        return $this->asset?->services()->orderByDesc('service_date')->value($field);
    }

    public function isExpired(): bool
    {
        if ($this->ewTrackingMode() !== 'time') {
            $current = $this->latestCounter();
            return $current !== null && $this->extended_warranty_counter_limit !== null
                && $current >= $this->extended_warranty_counter_limit;
        }

        return $this->extended_warranty_date_to && $this->extended_warranty_date_to->lt(now()->startOfDay());
    }
}
