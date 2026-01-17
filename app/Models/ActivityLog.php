<?php

namespace App\Models;

use App\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ActivityLog Model
 *
 * Represents an activity log entry that tracks various system activities,
 * including the subject (model being acted upon), causer (user performing action),
 * and event details.
 *
 * @property int $id
 * @property string|null $log_name
 * @property string|null $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $event
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property string|null $method
 * @property int|null $response_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 * @property-read \Illuminate\Database\Eloquent\Model|null $causer
 * @property-read \App\Models\User|null $user
 */
class ActivityLog extends Model
{
    use CommonTrait;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'response_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subject_id' => 'integer',
        'causer_id' => 'integer',
        'properties' => 'array',
        'response_type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the subject of the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, self>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer (user) of the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, self>
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Scope a query to filter by event type.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $event The event type to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to filter by log name.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $logName The log name to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeLogName(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope a query to filter by subject type.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $subjectType The subject type to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeSubjectType(Builder $query, string $subjectType): Builder
    {
        return $query->where('subject_type', $subjectType);
    }

    /**
     * Scope a query to filter by causer (user).
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param int $userId The user ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeCauser(Builder $query, int $userId): Builder
    {
        return $query->where('causer_id', $userId);
    }
}
