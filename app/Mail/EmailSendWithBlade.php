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

class EmailSendWithBlade extends Mailable implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $subject;

    public $body;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            return $this->markdown('emails.welcome')
                ->subject($this->subject)
                ->with('body', $this->body)
                ->with('salutation', 'Regards,');
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__, ['subject' => $this->subject]);

            return $this; // Ensure a return even on failure
        }
    }
}
