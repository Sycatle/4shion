<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records who created, changed or deleted a model from the back-office.
 * Only staff actions are logged: customer and system writes (checkout, webhooks) are not.
 *
 * @mixin Model
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => self::logActivity($model, 'created', $model->getAttributes()));
        static::updated(fn (Model $model) => self::logActivity($model, 'updated', self::changesOf($model)));
        static::deleted(fn (Model $model) => self::logActivity($model, 'deleted', []));
    }

    /** Human label shown in the activity log. */
    public function activityLabel(): string
    {
        foreach (['name', 'number', 'code'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $key = $this->getKey();

        return is_int($key) || is_string($key) ? (string) $key : '';
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private static function logActivity(Model $model, string $event, array $changes): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isStaff() || $changes === [] && $event === 'updated') {
            return;
        }

        $hidden = ['password', 'remember_token', 'updated_at', 'created_at'];

        ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => is_numeric($model->getKey()) ? (int) $model->getKey() : 0,
            'subject_label' => method_exists($model, 'activityLabel') ? $model->activityLabel() : null,
            'event' => $event,
            'changes' => array_diff_key($changes, array_flip($hidden)),
        ]);
    }

    /** @return array<string, array{old: mixed, new: mixed}> */
    private static function changesOf(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $attribute => $new) {
            if ($attribute === 'updated_at') {
                continue;
            }

            $changes[$attribute] = ['old' => $model->getOriginal($attribute), 'new' => $new];
        }

        return $changes;
    }
}
