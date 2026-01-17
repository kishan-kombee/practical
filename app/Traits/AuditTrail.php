<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait AuditTrail
{
    /**
     * Boot the trait and register model events.
     */
    public static function bootAuditTrail(): void
    {
        static::created(function (Model $model) {
            static::logActivity($model, 'created');
        });

        static::updated(function (Model $model) {
            static::logActivity($model, 'updated');
        });

        static::deleted(function (Model $model) {
            static::logActivity($model, 'deleted');
        });
    }

    /**
     * Log the activity for the model.
     */
    protected static function logActivity(Model $model, string $event): void
    {
        // Check if activity logging is enabled for this model
        if (property_exists($model, 'enableActivityLog') && ! $model->enableActivityLog) {
            return;
        }

        $user = Auth::user();
        $modelName = class_basename($model);
        $logName = Str::lower($modelName);

        // Get changed attributes
        $properties = [
            'attributes' => $model->getAttributes(),
        ];

        if ($event === 'updated' && method_exists($model, 'getOriginal')) {
            $properties['old'] = $model->getOriginal();
            $properties['changes'] = $model->getChanges();
        }

        $description = static::getActivityDescription($model, $event);

        ActivityLog::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'event' => $event,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ]);
    }

    /**
     * Get the activity description.
     */
    protected static function getActivityDescription(Model $model, string $event): string
    {
        $modelName = class_basename($model);
        $identifier = static::getModelIdentifier($model);

        return match ($event) {
            'created' => "Created {$modelName} ({$identifier})",
            'updated' => "Updated {$modelName} ({$identifier})",
            'deleted' => "Deleted {$modelName} ({$identifier})",
            default => "{$event} {$modelName} ({$identifier})",
        };
    }

    /**
     * Get a human-readable identifier for the model.
     */
    protected static function getModelIdentifier(Model $model): string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'title', 'email', 'first_name', 'code', 'id'];

        foreach ($identifierFields as $field) {
            if (isset($model->{$field})) {
                $value = $model->{$field};
                if (is_string($value) || is_numeric($value)) {
                    return "#{$model->getKey()}: {$value}";
                }
            }
        }

        return "#{$model->getKey()}";
    }

    /**
     * Get activity logs for this model.
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    /**
     * Get the latest activity log.
     */
    public function latestActivityLog()
    {
        return $this->morphOne(ActivityLog::class, 'subject')->latestOfMany();
    }
}
