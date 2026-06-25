<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetMaintenanceSchedule extends Model
{
    use HasAuditLog;
    protected $fillable = [
        'asset_id',
        'schedule_category',
        'service_type',
        'schedule_name',
        'description',
        'schedule_type',
        'interval_value',
        'interval_unit',
        'last_done_date',
        'next_due_date',
        'interval_km',
        'last_done_km',
        'interval_hours',
        'last_done_hours',
        'reminder_thresholds',
        'reminder_unit',
        'is_active',
        'completed_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reminder_thresholds' => 'array',
        'last_done_date'      => 'date',
        'next_due_date'       => 'date',
        'completed_at'        => 'datetime',
        'is_active'           => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function smartReminders(): HasMany
    {
        return $this->hasMany(AssetSmartReminder::class, 'remindable_id')
            ->where('remindable_type', self::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    private ?AssetService $_latestSvcCache = null;
    private bool $_latestSvcResolved = false;

    public function latestServiceRecord(): ?AssetService
    {
        if (! $this->_latestSvcResolved) {
            $this->_latestSvcResolved = true;
            $services = $this->asset->relationLoaded('services')
                ? $this->asset->services
                : $this->asset->services()->get();

            $this->_latestSvcCache = $services
                ->when($this->service_type, fn ($c) => $c->where('service_type', $this->service_type))
                ->sortByDesc('service_date')
                ->first();
        }

        return $this->_latestSvcCache;
    }

    public function latestMileage(): ?int
    {
        $svc = $this->latestServiceRecord();
        if ($svc && $svc->mileage_reading !== null) {
            return (int) $svc->mileage_reading;
        }
        return null;
    }

    public function latestHours(): ?int
    {
        $svc = $this->latestServiceRecord();
        if ($svc && $svc->meter_reading !== null) {
            return (int) $svc->meter_reading;
        }
        return null;
    }

    public function effectiveLastDoneDate(): ?CarbonInterface
    {
        $svc = $this->latestServiceRecord();
        return $svc?->service_date ?? $this->last_done_date;
    }

    public function effectiveLastDoneKm(): ?int
    {
        $svc = $this->latestServiceRecord();
        if ($svc && $svc->mileage_reading !== null) {
            return (int) $svc->mileage_reading;
        }
        return $this->last_done_km !== null ? (int) $this->last_done_km : null;
    }

    public function effectiveLastDoneHours(): ?int
    {
        $svc = $this->latestServiceRecord();
        if ($svc && $svc->meter_reading !== null) {
            return (int) $svc->meter_reading;
        }
        return $this->last_done_hours !== null ? (int) $this->last_done_hours : null;
    }

    public function remainingKm(): ?int
    {
        if ($this->interval_km === null) {
            return null;
        }
        $lastKm = $this->effectiveLastDoneKm();
        if ($lastKm === null) {
            return null;
        }
        $latest = $this->latestMileage();
        if ($latest === null) {
            return null;
        }
        return $this->interval_km - ($latest - $lastKm);
    }

    public function remainingHours(): ?int
    {
        if ($this->interval_hours === null) {
            return null;
        }
        $lastHrs = $this->effectiveLastDoneHours();
        if ($lastHrs === null) {
            return null;
        }
        $latest = $this->latestHours();
        if ($latest === null) {
            return null;
        }
        return $this->interval_hours - ($latest - $lastHrs);
    }

    public function computeNextDueDate(): ?CarbonInterface
    {
        $base = $this->effectiveLastDoneDate();
        if (! $base || ! $this->interval_value || ! $this->interval_unit) {
            return null;
        }
        $base = $base->copy();
        $n    = (int) $this->interval_value;
        return match ($this->interval_unit) {
            'days'   => $base->addDays($n),
            'weeks'  => $base->addWeeks($n),
            'months' => $base->addMonths($n),
            'years'  => $base->addYears($n),
            default  => null,
        };
    }

    private ?string $_statusLabelCache = null;

    public function statusLabel(): string
    {
        if ($this->_statusLabelCache !== null) {
            return $this->_statusLabelCache;
        }

        if ($this->schedule_type === 'date') {
            if (! $this->next_due_date) {
                return $this->_statusLabelCache = 'active';
            }
            $days = (int) now()->startOfDay()->diffInDays($this->next_due_date->startOfDay(), false);
            if ($days < 0) {
                return $this->_statusLabelCache = 'overdue';
            }
            $threshold = $this->reminder_thresholds ? max($this->reminder_thresholds) : 30;
            return $this->_statusLabelCache = ($days <= $threshold ? 'due-soon' : 'active');
        }

        if ($this->schedule_type === 'mileage') {
            $remaining = $this->remainingKm();
            if ($remaining === null) {
                return $this->_statusLabelCache = 'active';
            }
            if ($remaining <= 0) {
                return $this->_statusLabelCache = 'overdue';
            }
            $threshold = $this->reminder_thresholds ? max($this->reminder_thresholds) : 500;
            return $this->_statusLabelCache = ($remaining <= $threshold ? 'due-soon' : 'active');
        }

        if ($this->schedule_type === 'operating_hours') {
            $remaining = $this->remainingHours();
            if ($remaining === null) {
                return $this->_statusLabelCache = 'active';
            }
            if ($remaining <= 0) {
                return $this->_statusLabelCache = 'overdue';
            }
            $threshold = $this->reminder_thresholds ? max($this->reminder_thresholds) : 50;
            return $this->_statusLabelCache = ($remaining <= $threshold ? 'due-soon' : 'active');
        }

        return $this->_statusLabelCache = 'active';
    }

    public function statusColor(): string
    {
        return match ($this->statusLabel()) {
            'overdue'  => 'bg-red-400/10 text-red-400',
            'due-soon' => 'bg-yellow-400/10 text-yellow-400',
            default    => 'bg-green-400/10 text-green-400',
        };
    }

    public function statusText(): string
    {
        return match ($this->statusLabel()) {
            'overdue'  => 'Overdue',
            'due-soon' => 'Due Soon',
            default    => 'Active',
        };
    }

    public function serviceTypeLabel(): string
    {
        return match ($this->service_type) {
            'preventive_maintenance' => 'Preventive Maintenance',
            'corrective_maintenance' => 'Corrective Maintenance',
            'inspection'             => 'Inspection',
            'repair'                 => 'Repair',
            'calibration'            => 'Calibration',
            'cleaning'               => 'Cleaning',
            'other'                  => 'Other',
            default                  => 'Unclassified',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    protected function auditModelLabel(): string
    {
        return 'Maintenance Schedule';
    }

    protected static function auditFieldLabels(): array
    {
        return [
            'schedule_name' => 'Schedule Name',
            'next_due_date' => 'Next Due Date',
            'is_active'     => 'Active',
            'service_type'  => 'Service Type',
            'last_done_date' => 'Last Done Date',
        ];
    }
}
