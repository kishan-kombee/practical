<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CreatedbyUpdatedby;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * ImportLog Model
 *
 * Represents a CSV import log entry in the system with attributes such as file information,
 * model name, user ID, status, number of rows, error logs, and import flags.
 *
 * @property int $id
 * @property string|null $file_name
 * @property string|null $file_path
 * @property string|null $model_name
 * @property int|null $user_id
 * @property string|null $status
 * @property int|null $no_of_rows
 * @property string|null $error_log
 * @property string|null $import_flag
 * @property string|null $voucher_email
 * @property string|null $redirect_link
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ImportLog extends Model
{
    use AuditTrail;
    use CreatedbyUpdatedby;
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'import_csv_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['id', 'file_name', 'file_path', 'model_name', 'user_id', 'status', 'no_of_rows', 'error_log', 'import_flag', 'voucher_email', 'redirect_link'];

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
        'user_id' => 'integer',
        'no_of_rows' => 'integer',
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
            [
                ['status' => config('constants.import_csv_log.status.key.success'),  'label' => config('constants.import_csv_log.status.value.success')],
                ['status' => config('constants.import_csv_log.status.key.fail'),  'label' => config('constants.import_csv_log.status.value.fail')],
                ['status' => config('constants.import_csv_log.status.key.pending'),  'label' => config('constants.import_csv_log.status.value.pending')],
                ['status' => config('constants.import_csv_log.status.key.processing'),  'label' => config('constants.import_csv_log.status.value.processing')],
            ]
        );
    }
}
