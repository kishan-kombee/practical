<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CommonTrait;
use App\Traits\CreatedbyUpdatedby;
use App\Traits\ImportTrait;
use App\Traits\Legendable;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Appointment Model
 *
 * Represents an appointment in the system with attributes such as patient name,
 * clinic location, clinician association, appointment date, and status.
 *
 * @property int $id
 * @property string|null $patient_name
 * @property string|null $clinic_location
 * @property int|null $clinician_id
 * @property \Illuminate\Support\Carbon|null $appointment_date
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $clinician
 */
class Appointment extends Model
{
    use AuditTrail;
    use CommonTrait;
    use CreatedbyUpdatedby;
    use HasFactory;
    use ImportTrait;
    use Legendable;
    use SoftDeletes;
    use UploadTrait;

    /**
     * Lightweight fields for minimal data retrieval.
     *
     * @var array<string>
     */
    public $light = [];

    /**
     * Fields to track in activity logs.
     *
     * @var array<string>
     */
    public $activity_log = [];

    /**
     * Related models to include in log tracking.
     *
     * @var array<string>
     */
    public $log_relations = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['patient_name', 'clinic_location', 'clinician_id', 'appointment_date', 'status'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'patient_name' => 'string',
        'clinic_location' => 'string',
        'clinician_id' => 'integer',
        'appointment_date' => 'date',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get status options as a collection.
     *
     * Returns a collection of status options with key-value pairs
     * for use in dropdowns, filters, and form selections.
     *
     * @return \Illuminate\Support\Collection<int, array<string, string>>
     */
    public static function status(): Collection
    {
        return collect(
            [['key' => 'B', 'label' => 'Booked'], ['key' => 'D', 'label' => 'Completed'], ['key' => 'N', 'label' => 'Cancelled']]
        );
    }

    /**
     * Get the clinician (user) that owns the appointment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function clinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinician_id');
    }

    /**
     * Scope a query to only include appointments for a specific clinician.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param int $clinicianId The clinician ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForClinician(Builder $query, int $clinicianId): Builder
    {
        return $query->where('clinician_id', $clinicianId);
    }

    /**
     * Scope a query to only include appointments with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $status The status to filter by
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include booked appointments.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', 'B');
    }

    /**
     * Scope a query to only include completed appointments.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'D');
    }

    /**
     * Scope a query to only include cancelled appointments.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'N');
    }

    /**
     * Scope a query to only include appointments on or after a specific date.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $date The start date (Y-m-d format)
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeFromDate(Builder $query, string $date): Builder
    {
        return $query->where('appointment_date', '>=', $date);
    }

    /**
     * Scope a query to only include appointments on or before a specific date.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $date The end date (Y-m-d format)
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeToDate(Builder $query, string $date): Builder
    {
        return $query->where('appointment_date', '<=', $date);
    }

    /**
     * Scope a query to only include appointments between two dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $startDate The start date (Y-m-d format)
     * @param string $endDate The end date (Y-m-d format)
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }
}
