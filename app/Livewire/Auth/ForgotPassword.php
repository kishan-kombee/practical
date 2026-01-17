<?php

namespace App\Livewire\Auth;

use App\Helper;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    public function mount()
    {
        $this->dispatch('autoFocusElement', elId: 'email');
    }

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink()
    {
        $this->email = str_replace(' ', '', $this->email);

        $this->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
        ]);

        // Rate Limitor For Resend Otp 5 times PerMinute
        $ipPerDay = RateLimiter::tooManyAttempts('ip_restrication' . request()->ip(), config('constants.rate_limiting.limit.ip_attempt_limit'));
        if ($ipPerDay == true) {
            session()->flash('error', __('messages.login.ratelimit_ip_restrication'));

            return;
        }

        $mailPerDay = RateLimiter::tooManyAttempts('email_restrication' . $this->email, config('constants.rate_limiting.limit.email_attempt_limit'));
        if ($mailPerDay == true) {
            session()->flash('error', __('messages.login.ratelimit_email_restrication'));

            return;
        }

        $executed = RateLimiter::attempt(
            'FGT' . $this->email,
            $perMinute = 1,
            function () {
                // Send message...
            },
            config('constants.rate_limiting.limit.forgot_password')
        );

        if (! $executed) {
            $this->clearForm();
            Helper::logError(static::class, __FUNCTION__, __('messages.login.ratelimit_forgot_password'), ['email' => $this->email]);
            session()->flash('error', __('messages.login.ratelimit_forgot_password'));

            return;
        }

        $status = Password::broker('users')->sendResetLink(
            ['email' => $this->email]
        );

        if ($status == Password::RESET_LINK_SENT) {
            // Increment the rate limiter attempts
            RateLimiter::hit('ip_restrication' . request()->ip(), config('constants.rate_limiting.limit.one_day'));
            RateLimiter::hit('email_restrication' . $this->email, config('constants.rate_limiting.limit.one_day'));

            session()->flash('success', __('messages.login.forgot_password_success'));

            return $this->redirect('/', navigate: true); // redirect to user listing
        } else {
            Helper::logError(static::class, __FUNCTION__, __('messages.login.invalid_email_error'), ['email' => $this->email]);
            session()->flash('error', __('messages.login.invalid_email_error'));
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->title(__('messages.meta_titles.forgot_passowrd'));
    }

    public function clearForm()
    {
        $this->email = '';
    }
}
