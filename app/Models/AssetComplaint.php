<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AssetComplaint extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (AssetComplaint $complaint) {
            $complaint->comments()->delete();
            $complaint->documents->each(function ($doc) {
                Storage::disk('public')->delete($doc->file_path);
                $doc->delete();
            });
        });
    }

    protected $fillable = [
        'asset_id',
        'location',
        'department',
        'asset_category_id',
        'asset_subcategory_id',
        'title',
        'description',
        'reported_by_name',
        'reported_by_email',
        'reported_by_phone',
        'priority',
        'status',
        'resolution_summary',
        'resolved_at',
        'asset_service_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(AssetSubcategory::class, 'asset_subcategory_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AssetService::class, 'asset_service_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AssetComplaintComment::class, 'complaint_id')->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class, 'documentable_id')
            ->where('documentable_type', self::class);
    }

    public function videosBefore(): HasMany
    {
        return $this->documents()->where('document_type', 'complaint_video_before');
    }

    public function videosAfter(): HasMany
    {
        return $this->documents()->where('document_type', 'complaint_video_after');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open'         => 'Open',
            'acknowledged' => 'Acknowledged',
            'in_progress'  => 'In Progress',
            'resolved'     => 'Resolved',
            'closed'       => 'Closed',
            'rejected'     => 'Rejected',
            default        => ucfirst($this->status ?? ''),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open'         => 'text-blue-400 bg-blue-400/10 border-blue-400/30',
            'acknowledged' => 'text-yellow-400 bg-yellow-400/10 border-yellow-400/30',
            'in_progress'  => 'text-orange-400 bg-orange-400/10 border-orange-400/30',
            'resolved'     => 'text-green-400 bg-green-400/10 border-green-400/30',
            'closed'       => 'text-zinc-400 bg-zinc-400/10 border-zinc-400/30',
            'rejected'     => 'text-red-400 bg-red-400/10 border-red-400/30',
            default        => 'text-zinc-400 bg-zinc-400/10 border-zinc-400/30',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low'      => 'Low',
            'medium'   => 'Medium',
            'high'     => 'High',
            'critical' => 'Critical',
            default    => ucfirst($this->priority ?? ''),
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low'      => 'text-zinc-400 bg-zinc-400/10',
            'medium'   => 'text-blue-400 bg-blue-400/10',
            'high'     => 'text-orange-400 bg-orange-400/10',
            'critical' => 'text-red-400 bg-red-400/10',
            default    => 'text-zinc-400 bg-zinc-400/10',
        };
    }
}
