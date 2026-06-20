<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasAuditLog
{
    public ?array $_auditOld = null;
    public ?array $_auditNew = null;

    protected static array $auditExclude = [
        'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected function auditAssetId(): ?int
    {
        return $this->asset_id ?? null;
    }

    public static function bootHasAuditLog(): void
    {
        static::created(function (Model $model) {
            $new = collect($model->getAttributes())
                ->except(static::$auditExclude)
                ->filter(fn($v) => $v !== null)
                ->toArray();

            static::writeAuditLog('created', $model, null, $new);
        });

        static::updating(function (Model $model) {
            $changed = collect($model->getDirty())
                ->except(static::$auditExclude)
                ->keys()
                ->toArray();

            if (empty($changed)) return;

            $model->_auditOld = collect($changed)
                ->mapWithKeys(fn($k) => [$k => $model->getOriginal($k)])
                ->toArray();
            $model->_auditNew = collect($changed)
                ->mapWithKeys(fn($k) => [$k => $model->getDirty()[$k]])
                ->toArray();
        });

        static::updated(function (Model $model) {
            if (empty($model->_auditOld)) return;

            static::writeAuditLog('updated', $model, $model->_auditOld, $model->_auditNew);
            $model->_auditOld = $model->_auditNew = null;
        });

        static::deleted(function (Model $model) {
            static::writeAuditLog('deleted', $model, null, null);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                static::writeAuditLog('restored', $model, null, null);
            });
        }
    }

    private static function writeAuditLog(string $event, Model $model, ?array $old, ?array $new): void
    {
        $assetId = $model->auditAssetId();
        if (! $assetId) return;

        \App\Models\AssetAuditLog::create([
            'asset_id'       => $assetId,
            'auditable_type' => static::class,
            'auditable_id'   => $model->id,
            'event'          => $event,
            'causer_id'      => auth()->id(),
            'ip_address'     => request()?->ip(),
            'old_values'     => $old,
            'new_values'     => $new,
            'description'    => static::buildAuditDescription($event, $model, $old ?? [], $new ?? []),
        ]);
    }

    protected static function buildAuditDescription(string $event, Model $model, array $old, array $new): string
    {
        $label = (new static)->auditModelLabel();

        if ($event !== 'updated') {
            return "{$label} {$event}";
        }

        $parts = [];

        if (isset($new['status'])) {
            $from = ucfirst(str_replace('_', ' ', $old['status'] ?? ''));
            $to   = ucfirst(str_replace('_', ' ', $new['status']));
            if ($from && $from !== $to) {
                $parts[] = "Status changed from {$from} to {$to}";
            }
        }

        $remaining = array_diff(array_keys($new), ['status']);
        $count     = count($remaining);
        $labels    = static::auditFieldLabels();

        if ($count === 1) {
            $key     = $remaining[0];
            $parts[] = ($labels[$key] ?? ucfirst(str_replace('_', ' ', $key))) . ' updated';
        } elseif ($count > 1 && $count <= 3) {
            $names   = array_map(fn($k) => $labels[$k] ?? ucfirst(str_replace('_', ' ', $k)), $remaining);
            $parts[] = implode(', ', $names) . ' updated';
        } elseif ($count > 3) {
            $parts[] = "{$label} details updated ({$count} fields changed)";
        }

        return implode(' · ', array_filter($parts)) ?: "{$label} updated";
    }

    protected function auditModelLabel(): string
    {
        return class_basename(static::class);
    }

    protected static function auditFieldLabels(): array
    {
        return [];
    }
}
