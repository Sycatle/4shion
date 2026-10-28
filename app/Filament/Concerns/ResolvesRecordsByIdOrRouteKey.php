<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Filament builds admin URLs from the model's route key (slug, order number) but these resources
 * address records by id. Accept both so every generated link opens the record.
 */
trait ResolvesRecordsByIdOrRouteKey
{
    #[Override]
    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        /** @var Model $model */
        $model = app(static::getModel());
        /** @var Builder<Model> $query */
        $query = static::getRecordRouteBindingEloquentQuery();

        if ($modifyQuery instanceof Closure) {
            /** @var Builder<Model> $query */
            $query = $modifyQuery($query) ?? $query;
        }

        $record = $query
            ->where(function (Builder $where) use ($model, $key): void {
                $where->where($model->getRouteKeyName(), $key);

                // A bigint column rejects non-numeric input, so the id is only compared when it can match.
                if (is_numeric($key)) {
                    $where->orWhere($model->getKeyName(), $key);
                }
            })
            ->first();

        return $record instanceof Model ? $record : null;
    }
}
