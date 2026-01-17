<?php

namespace App\Models;

use App\Helper;
use App\Traits\AuditTrail;
use App\Traits\CommonTrait;
use App\Traits\CreatedbyUpdatedby;
use App\Traits\ImportTrait;
use App\Traits\Legendable;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Represents a user in the system with authentication capabilities, role associations,
 * and various user-related attributes such as name, email, mobile number, and status.
 *
 * @property int $id
 * @property int|null $role_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $mobile_number
 * @property string|null $password
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $locale
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Role|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $appointments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoginHistory> $loginHistories
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use AuditTrail;
    use CommonTrait;
    use CreatedbyUpdatedby;
    use HasApiTokens;
    use HasFactory;
    use ImportTrait;
    use Legendable;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;
    use UploadTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['role_id', 'first_name', 'last_name', 'email', 'mobile_number', 'status', 'last_login_at', 'locale', 'email_verified_at'];

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
     * Legend placeholders for template replacement.
     *
     * @var array<string>
     */
    public $legend = ['{{users_name}}', '{{users_email}}'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
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
            [['key' => 'Y', 'label' => 'Active'], ['key' => 'N', 'label' => 'Inactive']]
        );
    }

    /**
     * Check if the user has a specific permission for a given role.
     *
     * @param string $permission The permission name to check
     * @param int $roleId The role ID to check permissions for
     * @return bool True if the user has the permission, false otherwise
     */
    public function hasPermission(string $permission, int $roleId): bool
    {
        $permissions = Helper::getCachedPermissionsByRole($roleId);

        return in_array($permission, $permissions);
    }

    /**
     * Get the user's initials from their first name or name.
     *
     * Takes up to 2 words from the name and returns the first character of each word.
     *
     * @return string The user's initials (e.g., "JD" for "John Doe")
     */
    public function initials(): string
    {
        $nameField = $this->first_name ?? $this->name ?? '';

        return Str::of($nameField)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the role that owns the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Role, self>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the appointments for the user (as clinician).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Appointment>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'clinician_id');
    }

    /**
     * Get the login histories for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LoginHistory>
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Check if the user is an admin.
     * Uses relationship to avoid repeated queries.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        if (!$this->role_id) {
            return false;
        }

        // Use relationship to avoid repeated queries - Laravel will cache the relationship
        $role = $this->role;
        return $role && strtolower($role->name) === 'admin';
    }
}
