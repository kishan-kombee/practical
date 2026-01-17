<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log a custom activity.
     */
    public function log(
        string $description,
        ?Model $subject = null,
        string $event = 'custom',
        ?array $properties = null,
        ?int $responseType = null
    ): ActivityLog {
        $user = Auth::user();

        $data = [
            'log_name' => $subject ? strtolower(class_basename($subject)) : 'system',
            'description' => $description,
            'event' => $event,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties ?? [],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
            'response_type' => $responseType,
        ];

        if ($subject) {
            $data['subject_type'] = get_class($subject);
            $data['subject_id'] = $subject->getKey();
        }

        return ActivityLog::create($data);
    }

    /**
     * Log a created event.
     */
    public function logCreated(Model $model, ?string $description = null): ActivityLog
    {
        $description = $description ?? "Created " . class_basename($model) . " #{$model->getKey()}";

        return $this->log(
            description: $description,
            subject: $model,
            event: 'created',
            properties: ['attributes' => $model->getAttributes()],
            responseType: 201
        );
    }

    /**
     * Log an updated event.
     */
    public function logUpdated(Model $model, ?string $description = null): ActivityLog
    {
        $description = $description ?? "Updated " . class_basename($model) . " #{$model->getKey()}";

        $properties = [
            'attributes' => $model->getAttributes(),
            'old' => $model->getOriginal(),
            'changes' => $model->getChanges(),
        ];

        return $this->log(
            description: $description,
            subject: $model,
            event: 'updated',
            properties: $properties,
            responseType: 200
        );
    }

    /**
     * Log a deleted event.
     */
    public function logDeleted(Model $model, ?string $description = null): ActivityLog
    {
        $description = $description ?? "Deleted " . class_basename($model) . " #{$model->getKey()}";

        return $this->log(
            description: $description,
            subject: $model,
            event: 'deleted',
            properties: ['attributes' => $model->getAttributes()],
            responseType: 200
        );
    }

    /**
     * Get activity logs for a specific model.
     */
    public function getModelLogs(Model $model, ?int $limit = null)
    {
        $query = ActivityLog::where('subject_type', get_class($model))
            ->where('subject_id', $model->getKey())
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get activity logs for a specific user.
     */
    public function getUserLogs(int $userId, ?int $limit = null)
    {
        $query = ActivityLog::where('causer_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get activity logs by event type.
     */
    public function getLogsByEvent(string $event, ?int $limit = null)
    {
        $query = ActivityLog::where('event', $event)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get activity logs by log name (model type).
     */
    public function getLogsByModel(string $modelName, ?int $limit = null)
    {
        $query = ActivityLog::where('log_name', strtolower($modelName))
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
