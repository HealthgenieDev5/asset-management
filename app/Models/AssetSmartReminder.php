<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssetSmartReminder extends Model
{
    use HasAuditLog;
    protected $fillable = [
        'asset_id',
        'remindable_type',
        'remindable_id',
        'reminder_name',
        'reminder_type',
        'reminder_mode',
        'counter_limit',
        'threshold_unit',
        'reminder_days',
        'expiry_date',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reminder_days' => 'array',
        'expiry_date'   => 'date',
        'is_active'     => 'boolean',
        'counter_limit' => 'integer',
    ];

    public static array $typeLabels = [
        'warranty'           => 'Original Warranty',
        'extended_warranty'  => 'Extended Warranty',
        'amc'                => 'AMC Contract',
        'insurance'          => 'Insurance Policy',
        'puc'                => 'PUC Expiry',
        'fitness'            => 'Fitness Certificate',
        'road_tax'           => 'Road Tax',
        'service_due'        => 'Service Due',
        'certification'      => 'Certification Expiry',
        'part_warranty'        => 'Part Warranty',
        'maintenance_schedule' => 'Maintenance Schedule',
        'custom'               => 'Custom',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isTimeBased(): bool
    {
        return ($this->reminder_mode ?? 'time') === 'time';
    }

    public function daysUntilExpiry(): int
    {
        if (! $this->expiry_date) return 0;
        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    public function remainingUnits(): ?int
    {
        if (! $this->threshold_unit || ! $this->counter_limit) return null;
        $current = $this->asset?->latestMeterReading($this->threshold_unit);
        return $current !== null ? max(0, $this->counter_limit - $current) : null;
    }

    public function isExpired(): bool
    {
        if ($this->isTimeBased()) {
            return $this->expiry_date && $this->expiry_date->startOfDay()->lt(now()->startOfDay());
        }
        $remaining = $this->remainingUnits();
        return $remaining !== null && $remaining === 0;
    }

    public function typeLabelAttribute(): string
    {
        return static::$typeLabels[$this->reminder_type] ?? 'Custom';
    }

    public function statusBadge(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isTimeBased()) {
            $days = $this->daysUntilExpiry();
            $minThreshold = count($this->reminder_days ?? []) ? max($this->reminder_days) : 30;
            return $days <= $minThreshold ? 'soon' : 'active';
        }

        // meter/count
        $remaining = $this->remainingUnits();
        if ($remaining !== null && count($this->reminder_days ?? [])) {
            $maxThreshold = max($this->reminder_days);
            return $remaining <= $maxThreshold ? 'soon' : 'active';
        }

        return 'active';
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }

    protected function auditModelLabel(): string
    {
        return 'Smart Reminder';
    }

    protected static function auditFieldLabels(): array
    {
        return [
            'reminder_name' => 'Name',
            'is_active'     => 'Active',
            'expiry_date'   => 'Expiry Date',
            'reminder_days' => 'Reminder Days',
        ];
    }
}
