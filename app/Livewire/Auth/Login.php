<?php

namespace App\Livewire\Auth;

use App\Helper;
use App\Models\LoginHistory;
use App\Rules\ReCaptcha;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email|max:191')]
    public string $email = '';

    #[Validate('required|string|min:6|max:191')]
    public string $password = '';

    public $recaptchaToken;

    public function mount()
    {
        // Rate limiting for login page - 10 times in 60 seconds - Start
        $request = request();
        $visitorId = $request->cookie('visitor_id');

        if (! $visitorId) {
            $visitorId = bin2hex(random_bytes(16));
            // Set the cookie for 30 days
            cookie()->queue(cookie('visitor_id', $visitorId, 60 * 24 * 30));
        }
        $key = md5(($visitorId ?: $request->ip()) . '|' . $request->header('User-Agent'));

        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429);
        }

        RateLimiter::hit($key, 60);
        // Rate limiting for login page - 10 times in 60 seconds - End

        $this->dispatch('autoFocusElement', elId: 'email');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login()
    {
        $this->email = str_replace(' ', '', $this->email);

        $this->validate();

        if (config('constants.check_google_recaptcha')) {
            $recaptchaResponse = ReCaptcha::verify($this->recaptchaToken);
            if (! $recaptchaResponse['success']) {
                $this->clearForm(); // clear all form data
                Helper::logInfo(static::class, __FUNCTION__, __('messages.login.recaptchaError'), ['email' => $this->email]);
                session()->flash('error', __('messages.login.recaptchaError'));

                return;
            }
        }

        $email = Str::lower($this->email);

        $credentials = [
            'email' => $email,
            'password' => $this->password,
        ];

        if (Auth::attempt($credentials)) { // User Found
            $user = Auth::user();

            // Check if user status is active
            if ($user->status != config('constants.user.status.key.active')) {
                Auth::logout();
                session()->flash('error', __('messages.login.unverified_account'));

                return;
            }

            // Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                session()->flash('error', __('messages.login.email_not_verified'));
                Helper::logInfo(static::class, __FUNCTION__, __('messages.login.email_not_verified'), ['email' => $email]);

                return;
            }

            // Use Laravel session to avoid native PHP session locking
            session(['user_id' => $user->id]);

            if (App::environment(['production', 'uat'])) {
                Auth::logoutOtherDevices($email); // Logout all other sessions
            }

            // Record login history
            try {
                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => request()->ip(),
                ]);
            } catch (Throwable $th) {
                // Log error but don't fail the login if history recording fails
                Helper::logCatchError($th, static::class, __FUNCTION__, [
                    'message' => 'Failed to record login history',
                    'user_id' => $user->id,
                ]);
            }

            $this->clearForm(); // clear all form data

            session()->flash('success', __('messages.login.success'));
            $this->redirectIntended(default: route('dashboard'), navigate: true);
        } else {
            Helper::logInfo(static::class, __FUNCTION__, __('messages.login.invalid_credentials_error'), ['email' => $email]);
            session()->flash('error', __('messages.login.invalid_credentials_error'));
        }
    }

    public function render()
    {
        return view('livewire.auth.login')->title(__('messages.meta_titles.login'));
    }

    public function clearForm()
    {
        $this->email = '';
        $this->password = '';
    }
}
