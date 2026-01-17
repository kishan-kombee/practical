<?php

namespace App\Traits;

use App\Helper;
use App\Mail\EmailSendWithBlade;
use App\Mail\EmailSendWithBladeAttach;
use App\Models\EmailFormat;
use App\Models\EmailHistory;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

trait Mailable
{
    public static function sendEmailCommonCode($template, $user, $order = null, $adminEmail = null, $extraLegendValues = null, $voucher = null, $link = null)
    {
        // Get the appropriate email template based on the type
        $emailTemplate = self::getEmailTemplate($template);

        if ($emailTemplate) {
            // Set up modelArray based on the presence of an order
            $modelArray = ['users' => $user];
            // Set default extraLegendValues based on the type
            $defaultExtraLegendValues = [
                config('constants.email_template.common_lagends.admin_login_url') => config('app.url'),
            ];
            // Merge default and provided extraLegendValues
            $extraLegendValues = array_merge($defaultExtraLegendValues, $extraLegendValues ?? []);

            // Get subject and body with dynamic content
            $subject = User::getDynamicContent($emailTemplate->subject, $modelArray, $extraLegendValues);
            $emailBody = User::getDynamicContent($emailTemplate->body, $modelArray, $extraLegendValues);

            // Determine recipient email
            $recipientEmail = $adminEmail ?? $user->email;

            // Send email
            self::sendMail($recipientEmail, $subject, $emailBody, null, $link);
        }
    }

    public static function sendImportEmail($template, $extraLegendValues, $link, $user)
    {
        $emailTemplate = Mailable::getEmailTemplate($template);

        if ($emailTemplate) {
            $modelArray = null;
            $emailSubject = User::getDynamicContent($emailTemplate->subject, $modelArray, $extraLegendValues);
            $emailBody = User::getDynamicContent($emailTemplate->body, $modelArray, $extraLegendValues);
            Mailable::sendMail($user->email, $emailSubject, $emailBody, null, $link, null);
        }
    }

    public static function sendImportSuccess($model, $extraLegendValues = null, $link = null)
    {
        $emailTemplate = Mailable::getEmailTemplate(config('constants.email_template.type.import_success'));

        if ($emailTemplate) {
            $modelArray = null;
            $emailSubject = User::getDynamicContent($emailTemplate->subject, $modelArray, $extraLegendValues);
            $emailBody = User::getDynamicContent($emailTemplate->body, $modelArray, $extraLegendValues);
            Mailable::sendMail(config('constants.import_csv_log.import_email_recipients'), null, $emailSubject, $emailBody, null, $link);
        }
    }

    public static function sendImportFail($model, $extraLegendValues = null, $link = null)
    {
        $emailTemplate = Mailable::getEmailTemplate(config('constants.email_template.type.import_fail'));

        if ($emailTemplate) {
            $modelArray = null;
            $emailSubject = User::getDynamicContent($emailTemplate->subject, $modelArray, $extraLegendValues);
            $emailBody = User::getDynamicContent($emailTemplate->body, $modelArray, $extraLegendValues);
            Mailable::sendMail(config('constants.import_csv_log.import_email_recipients'), null, $emailSubject, $emailBody, null, $link);
        }
    }

    public static function sendMail($toEmails, $ccEmails, $subject, $body, $attachment = null, $link = null, $loginLink = null, $isEmailOtp = false)
    {
        $completeBody = [
            'header' => self::getEmailFormat(config('constants.email_format.type.header')),
            'body' => $body,
            'signature' => self::getEmailFormat(config('constants.email_format.type.signature')),
            'footer' => self::getEmailFormat(config('constants.email_format.type.footer')),
            'link' => $link,
            'login_link' => $loginLink,
        ];

        $html = (new EmailSendWithBlade($subject, $completeBody))->render();

        if (! App::environment(['local'])) {
            if (is_null($attachment)) {
                Mail::to($toEmails)->send(new EmailSendWithBlade($subject, $completeBody));
            } else {
                Mail::to($toEmails)->send(new EmailSendWithBladeAttach($subject, $completeBody, $attachment));
            }
        }

        EmailHistory::storeHistory($toEmails, $ccEmails, $subject, $html);
    }

    public static function getEmailFormat($type)
    {
        $emailFormat = EmailFormat::where('type', $type)->first();
        if (! is_null($emailFormat)) {
            return $emailFormat->body;
        }

        return '';
    }

    public static function getEmailTemplate($type)
    {
        return EmailTemplate::where('type', $type)
            ->first();
    }

    public static function sendUserOtp($user, $userOtp, $extraLegendValues = null): void
    {
        Helper::logSingleInfo(static::class, __FUNCTION__, 'Start', ['user' => $user, 'userOtp' => $userOtp, 'extraLegendValues' => $extraLegendValues]);
        $emailTemplate = self::getEmailTemplate(config('constants.email_template.type.user_login'));

        if ($emailTemplate) {
            $extraLegendValues['{{user_otp}}'] = $userOtp;
            $emailBody = User::getDynamicContent($emailTemplate->body, $modelArray = [], $extraLegendValues);
            self::sendMail($user->email, null, $emailTemplate->subject, $emailBody, null, null, null);
        }
        Helper::logSingleInfo(static::class, __FUNCTION__, 'End');
    }
}
