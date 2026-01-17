<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CommonTrait;
use App\Traits\CreatedbyUpdatedby;
use App\Traits\ImportTrait;
use App\Traits\Legendable;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * SmsTemplate Model
 *
 * Represents an SMS template in the system with attributes such as type, label,
 * message, DLT message ID, and status.
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $label
 * @property string|null $message
 * @property string|null $dlt_message_id
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class SmsTemplate extends Model
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
    protected $fillable = ['type', 'label', 'message', 'dlt_message_id', 'status'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'label' => 'string',
        'message' => 'string',
        'dlt_message_id' => 'string',
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
            [['key' => 'Y', 'label' => 'Active'], ['key' => 'N', 'label' => 'InActive']]
        );
    }
}
