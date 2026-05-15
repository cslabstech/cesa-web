<?php

namespace Webkul\Security\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Models\User;

trait HasNullableCreator
{
    use HasPermissionScope {
        scopeApplyPermissionScope as protected scopeApplyBasePermissionScope;
    }

    /**
     * @var array<string, bool>
     */
    protected static array $creatorColumnExistsCache = [];

    public function initializeHasNullableCreator(): void
    {
        $creatorColumn = $this->getCreatorColumn();

        $this->setOwnerColumn($creatorColumn);
        $this->mergeFillable([$creatorColumn]);
        $this->mergeCasts([$creatorColumn => 'integer']);
    }

    protected static function bootHasNullableCreator(): void
    {
        static::creating(function (Model $model): void {
            if (! method_exists($model, 'getCreatorColumn')) {
                return;
            }

            $creatorColumn = $model->getCreatorColumn();

            if (! method_exists($model, 'hasCreatorColumn') || ! $model->hasCreatorColumn($creatorColumn)) {
                return;
            }

            $model->{$creatorColumn} ??= Auth::id();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getCreatorColumn())->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getCreatorColumn())->withTrashed();
    }

    public function scopeApplyPermissionScope(Builder $query): Builder
    {
        if (! $this->hasCreatorColumn($this->getCreatorColumn())) {
            return $query;
        }

        return $this->scopeApplyBasePermissionScope($query);
    }

    protected function getCreatorColumn(): string
    {
        return property_exists($this, 'creatorColumn')
            ? $this->creatorColumn
            : 'creator_id';
    }

    protected function hasCreatorColumn(string $creatorColumn): bool
    {
        $cacheKey = implode('|', [
            static::class,
            $this->getConnectionName() ?? $this->getConnection()->getName(),
            $this->getTable(),
            $creatorColumn,
        ]);

        if (array_key_exists($cacheKey, static::$creatorColumnExistsCache)) {
            return static::$creatorColumnExistsCache[$cacheKey];
        }

        $schema = $this->getConnection()->getSchemaBuilder();

        return static::$creatorColumnExistsCache[$cacheKey] = $schema->hasTable($this->getTable())
            && $schema->hasColumn($this->getTable(), $creatorColumn);
    }
}
