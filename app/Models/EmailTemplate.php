<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CreatedbyUpdatedby;
use App\Traits\Mailable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * EmailTemplate Model
 *
 * Represents an email template in the system with attributes such as type, label,
 * subject, body, and status for email communication.
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $label
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class EmailTemplate extends Model
{
    use AuditTrail;
    use CreatedbyUpdatedby;
    use HasFactory;
    use Mailable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['id', 'type', 'label', 'subject', 'body', 'status', 'created_by', 'updated_by'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'subject' => 'string',
        'body' => 'string',
        'status' => 'string',
        'created_by' => 'integer',
        'updated_by' => 'integer',
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
            [['key' => 'N', 'label' => 'Inactive'], ['key' => 'Y', 'label' => 'Active']]
        );
    }

    /**
     * Get email template types as an array.
     *
     * Returns an array of template types with key-value pairs from configuration.
     *
     * @return array<int, array<string, string>>
     */
    public static function types(): array
    {
        return collect(
            config('constants.email_template.type_values')
        )->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->toArray();
    }
}
