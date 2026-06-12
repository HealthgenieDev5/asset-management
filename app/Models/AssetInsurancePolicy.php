<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetInsurancePolicy extends Model
{
    protected $fillable = [
        'asset_id',
        'policy_number',
        'insurer_name',
        'insurer_contact_person',
        'insurer_phone',
        'insurer_email',
        'policy_type',
        'policy_date_from',
        'policy_date_to',
        'premium_amount',
        'sum_insured',
        'bill_no',
        'bill_date',
        'coverage_details',
        'reminder_before_days',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'policy_date_from' => 'date',
            'policy_date_to'   => 'date',
            'bill_date'        => 'date',
            'premium_amount'   => 'decimal:2',
            'sum_insured'      => 'decimal:2',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->policy_date_to && $this->policy_date_to->lt(now()->startOfDay());
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->policy_date_to) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->policy_date_to->startOfDay(), false);
    }
}
