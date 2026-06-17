<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetServicePart extends Model
{
    protected $fillable = [
        'asset_service_id',
        'asset_id',
        'part_name',
        'quantity',
        'part_cost',
        'purchased_from',
        'warranty_till',
        'warranty_tracking_mode',
        'warranty_unit',
        'warranty_meter_source',
        'warranty_counter_limit',
        'warranty_reminder_before_days',
        'warranty_reminder_before_units',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'part_cost'    => 'decimal:2',
            'warranty_till'=> 'date',
        ];
    }

    public function isWarrantyTimeBased(): bool
    {
        return ($this->warranty_tracking_mode ?? 'time') === 'time';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AssetService::class, 'asset_service_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function getTotalCostAttribute(): ?string
    {
        if ($this->part_cost === null) {
            return null;
        }
        return number_format((float) $this->part_cost * $this->quantity, 2);
    }
}
