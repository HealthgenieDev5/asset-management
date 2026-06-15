<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintEscalationRule extends Model
{
    protected $fillable = [
        'location',
        'asset_category_id',
        'notify_emails',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'notify_emails' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function findForComplaint(string $location, int $categoryId): ?static
    {
        return static::where('location', $location)
            ->where('asset_category_id', $categoryId)
            ->first();
    }
}
