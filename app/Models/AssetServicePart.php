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
