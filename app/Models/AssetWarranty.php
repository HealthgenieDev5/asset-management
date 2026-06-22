<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Vendor;

class AssetWarranty extends Model
{
    use HasAuditLog;
    protected static function booted(): void
    {
        static::deleting(function (AssetWarranty $warranty) {
            foreach ($warranty->documents as $doc) {
                Storage::disk('public')->delete($doc->file_path);
                $doc->delete();
            }
        });
    }

    protected $fillable = [
        'asset_id',
        'warranty_type',
        'scope',
        'part_name',
        'part_serial_number',
        'vendor',
        'vendor_id',
        'bill_no',
        'bill_amount',
        'details',
        'terms',
        'tracking_mode',
        'unit',
        'meter_source',
        'date_from',
        'expiry_date',
        'reminder_before_days',
        'counter_limit',
        'reminder_before_units',
        'status',
        'disposed_at',
        'disposed_reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_from'          => 'date',
            'expiry_date'        => 'date',
            'disposed_at'        => 'date',
            'bill_amount'        => 'decimal:2',
            'counter_limit'      => 'integer',
            'reminder_before_units' => 'integer',
            'reminder_before_days'  => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendorRecord(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function auditModelLabel(): string
    {
        return 'Warranty';
    }

    protected static function auditFieldLabels(): array
    {
        return [
            'warranty_type' => 'Warranty Type',
            'scope'         => 'Scope',
            'expiry_date'   => 'Expiry Date',
            'status'        => 'Status',
            'vendor'        => 'Vendor',
            'bill_amount'   => 'Bill Amount',
        ];
    }

    public function smartReminders(): HasMany
    {
        return $this->hasMany(AssetSmartReminder::class, 'remindable_id')
            ->where('remindable_type', self::class);
    }

    public function isTimeBased(): bool
    {
        return ($this->tracking_mode ?? 'time') === 'time';
    }

    public function isMeterBased(): bool
    {
        return $this->tracking_mode === 'meter';
    }

    public function isCountBased(): bool
    {
        return $this->tracking_mode === 'count';
    }

    public function unitLabel(): string
    {
        return $this->unit ?? 'units';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDisposed(): bool
    {
        return $this->status === 'disposed';
    }

    public function linkedReminderThreshold(): ?int
    {
        if ($this->relationLoaded('smartReminders')) {
            $days = $this->smartReminders->first()?->reminder_days ?? [];
        } else {
            $days = AssetSmartReminder::where('remindable_type', self::class)
                ->where('remindable_id', $this->id)
                ->value('reminder_days') ?? [];
        }
        return empty($days) ? null : max($days);
    }

    public function latestCounter(): ?int
    {
        if (! $this->unit) return null;
        return $this->asset?->latestMeterReading($this->unit);
    }

    public function remainingUnits(): ?int
    {
        $current = $this->latestCounter();
        if ($current === null || ! $this->counter_limit) return null;
        return max(0, $this->counter_limit - $current);
    }

    public function isExpired(): bool
    {
        if ($this->tracking_mode !== 'time') {
            $current = $this->latestCounter();
            return $current !== null && $this->counter_limit !== null && $current >= $this->counter_limit;
        }
        return $this->expiry_date && $this->expiry_date->lt(now()->startOfDay());
    }

    public function scopeLabel(): string
    {
        if ($this->scope === 'part') {
            return $this->part_name ?? 'Part';
        }
        return 'Overall';
    }

    public function warrantyTypeLabel(): string
    {
        return match ($this->warranty_type) {
            'original' => 'Original',
            'extended' => 'Extended',
            default    => ucfirst($this->warranty_type),
        };
    }

    public function statusBadge(): string
    {
        if ($this->isDisposed()) {
            return 'disposed';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isTimeBased() && $this->expiry_date) {
            $daysLeft = now()->startOfDay()->diffInDays($this->expiry_date, false);
            $threshold = $this->reminder_before_days ?? 30;
            if ($daysLeft >= 0 && $daysLeft <= $threshold) {
                return 'soon';
            }
        } elseif (! $this->isTimeBased() && $this->counter_limit) {
            $current = $this->latestCounter();
            if ($current !== null) {
                $remaining = $this->counter_limit - $current;
                $threshold = $this->reminder_before_units ?? 0;
                if ($remaining >= 0 && $remaining <= $threshold) {
                    return 'soon';
                }
            }
        }
        return 'active';
    }
}
