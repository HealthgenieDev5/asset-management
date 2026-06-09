<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class AssetService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_id',
        'service_type',
        'service_date',
        'service_agency',
        'technician_name',
        'work_done',
        'service_cost',
        'bill_no',
        'bill_date',
        'next_service_date',
        'service_interval_value',
        'service_interval_unit',
        'meter_reading',
        'mileage_reading',
        'downtime_hours',
        'condition_rating',
        'certification_expiry',
        'certification_reminder_before_days',
        'next_service_reminder_before_days',
        'safety_notes',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date'        => 'date',
            'bill_date'           => 'date',
            'next_service_date'   => 'date',
            'certification_expiry'=> 'date',
            'service_cost'        => 'decimal:2',
            'downtime_hours'      => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(AssetServicePart::class, 'asset_service_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class, 'documentable_id')
            ->where('documentable_type', self::class);
    }

    public function totalPartsCost(): float
    {
        return (float) $this->parts->sum(fn($p) => ($p->part_cost ?? 0) * $p->quantity);
    }

    public function grandTotalCost(): float
    {
        return (float) ($this->service_cost ?? 0) + $this->totalPartsCost();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return match ($this->service_type) {
            'preventive_maintenance' => 'Preventive Maintenance',
            'corrective_maintenance' => 'Corrective Maintenance',
            'inspection'             => 'Inspection',
            'repair'                 => 'Repair',
            'calibration'            => 'Calibration',
            'cleaning'               => 'Cleaning',
            'other'                  => 'Other',
            default                  => ucfirst($this->service_type ?? ''),
        };
    }

    public function getServiceTypeColorAttribute(): string
    {
        return match ($this->service_type) {
            'preventive_maintenance' => 'text-blue-400 bg-blue-400/10',
            'corrective_maintenance' => 'text-orange-400 bg-orange-400/10',
            'inspection'             => 'text-purple-400 bg-purple-400/10',
            'repair'                 => 'text-yellow-400 bg-yellow-400/10',
            'calibration'            => 'text-cyan-400 bg-cyan-400/10',
            'cleaning'               => 'text-green-400 bg-green-400/10',
            default                  => 'text-zinc-400 bg-zinc-400/10',
        };
    }

    public function getConditionRatingLabelAttribute(): string
    {
        return match ($this->condition_rating) {
            'excellent' => 'Excellent',
            'good'      => 'Good',
            'fair'      => 'Fair',
            'poor'      => 'Poor',
            'critical'  => 'Critical',
            default     => '—',
        };
    }

    public function getConditionRatingColorAttribute(): string
    {
        return match ($this->condition_rating) {
            'excellent' => 'text-green-400',
            'good'      => 'text-emerald-400',
            'fair'      => 'text-yellow-400',
            'poor'      => 'text-orange-400',
            'critical'  => 'text-red-400',
            default     => 'text-zinc-400',
        };
    }

    public function isNextServiceOverdue(): bool
    {
        return $this->next_service_date && $this->next_service_date->isPast();
    }

    public function daysUntilNextService(): ?int
    {
        if (! $this->next_service_date) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->next_service_date->startOfDay(), false);
    }

    public function isCertificationExpired(): bool
    {
        return $this->certification_expiry && $this->certification_expiry->isPast();
    }

    public function daysUntilCertificationExpiry(): ?int
    {
        if (! $this->certification_expiry) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->certification_expiry->startOfDay(), false);
    }
}
