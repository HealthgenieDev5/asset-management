<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetServicePart extends Model
{
    protected $fillable = [
        'asset_service_id',
        'asset_id',
        'part_name',
        'part_serial_number',
        'part_cost',
        'purchased_from',
        'bill_no',
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
            'warranty_till' => 'date',
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

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class, 'documentable_id')
            ->where('documentable_type', self::class);
    }
}
