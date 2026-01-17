<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CreatedbyUpdatedby;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EmailHistory Model
 *
 * Represents a record of sent emails in the system with attributes such as recipient email,
 * subject, body, and tracking information.
 *
 * @property int $id
 * @property string|null $to_email
 * @property string|null $subject
 * @property string|null $body
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class EmailHistory extends Model
{
    use AuditTrail;
    use CreatedbyUpdatedby;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['id', 'to_email', 'subject', 'body', 'created_by', 'updated_by'];

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
        'to_email' => 'string',
        'body' => 'string',
        'subject' => 'string',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Store email history record.
     *
     * Creates a new email history record with the provided email details.
     *
     * @param string|array<string> $toEmails Recipient email(s)
     * @param string|array<string> $ccEmails CC email(s) (not currently stored)
     * @param string $subject Email subject
     * @param string $body Email body
     * @return void
     */
    public static function storeHistory(string|array $toEmails, string|array $ccEmails, string $subject, string $body): void
    {
        if (is_array($toEmails)) {
            $toEmails = implode(',', $toEmails);
        }

        $data['to_email'] = $toEmails;
        $data['subject'] = $subject;
        $data['body'] = $body;

        self::create($data);
    }
}
