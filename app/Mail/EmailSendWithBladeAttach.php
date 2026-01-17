<?php

namespace App\Mail;

use App\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EmailSendWithBladeAttach extends Mailable implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $subject;

    public $body;

    public $allAttachments;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body, $allAttachments)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->allAttachments = $allAttachments;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            $email = $this->markdown('emails.welcome')
                ->subject($this->subject)
                ->with('body', $this->body)
                ->with('salutation', 'Regards,');

            // $attachments is an array with file paths of attachments
            foreach ($this->allAttachments as $filePath) {
                $email->attach($filePath);
            }

            return $email;
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__, ['subject' => $this->subject]);

            return $this; // Ensure a return even on failure
        }
    }
}
