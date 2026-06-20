<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAmcContract extends Model
{
    use HasAuditLog;
    protected $fillable = [
        'asset_id',
        'contract_number',
        'vendor_name',
        'vendor_contact_person',
        'vendor_phone',
        'vendor_email',
        'amc_date_from',
        'amc_date_to',
        'amc_amount',
        'amc_bill_no',
        'amc_bill_date',
        'coverage_type',
        'coverage_details',
        'amc_terms',
        'reminder_before_days',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amc_date_from' => 'date',
            'amc_date_to'   => 'date',
            'amc_bill_date' => 'date',
            'amc_amount'    => 'decimal:2',
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
        return $this->amc_date_to && $this->amc_date_to->lt(now()->startOfDay());
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->amc_date_to) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->amc_date_to->startOfDay(), false);
    }

    public function getCoverageTypeLabelAttribute(): string
    {
        return match ($this->coverage_type) {
            'comprehensive'     => 'Comprehensive',
            'non_comprehensive' => 'Non-Comprehensive',
            'parts_only'        => 'Parts Only',
            'labour_only'       => 'Labour Only',
            default             => ucfirst($this->coverage_type ?? ''),
        };
    }

    protected function auditModelLabel(): string
    {
        return 'AMC Contract';
    }

    protected static function auditFieldLabels(): array
    {
        return [
            'contract_number' => 'Contract No.',
            'vendor_name'     => 'Vendor',
            'amc_date_from'   => 'Start Date',
            'amc_date_to'     => 'End Date',
            'amc_amount'      => 'Amount',
            'coverage_type'   => 'Coverage Type',
        ];
    }
}
