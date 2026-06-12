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
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'extended_warranty_date_from' => 'date',
            'extended_warranty_date_to'   => 'date',
            'extended_warranty_amount'    => 'decimal:2',
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

    public function isExpired(): bool
    {
        return $this->extended_warranty_date_to && $this->extended_warranty_date_to->lt(now()->startOfDay());
    }
}
