<?php

namespace App\Livewire\Auth;

use App\Helper;
use App\Models\Role;
use App\Models\User;
use App\Rules\ReCaptcha;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    #[Validate('required|string|max:50|regex:/^[a-zA-Z\s]+$/')]
    public string $first_name = '';

    #[Validate('required|string|max:50|regex:/^[a-zA-Z\s]+$/')]
    public string $last_name = '';

    #[Validate('required|string|email|max:320|unique:users,email')]
    public string $email = '';

    #[Validate('required|digits:10|regex:/^[6-9]\d{9}$/')]
    public string $mobile_number = '';

    #[Validate('required|string|min:8|max:50')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $password_confirmation = '';

    public $recaptchaToken;

    public bool $showSuccessMessage = false;

    public string $successMessage = '';

    public function mount()
    {
        // Rate limiting for registration page - 5 times in 60 seconds - Start
        $request = request();
        $visitorId = $request->cookie('visitor_id');

        if (! $visitorId) {
            $visitorId = bin2hex(random_bytes(16));
            // Set the cookie for 30 days
            cookie()->queue(cookie('visitor_id', $visitorId, 60 * 24 * 30));
        }
        $key = md5(($visitorId ?: $request->ip()) . '|' . $request->header('User-Agent') . '|register');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429);
        }

        RateLimiter::hit($key, 60);
        // Rate limiting for registration page - 5 times in 60 seconds - End

        $this->dispatch('autoFocusElement', elId: 'first_name');
    }

    /**
     * Handle user registration.
     */
    public function register()
    {
        $this->email = str_replace(' ', '', $this->email);
        $this->mobile_number = str_replace(' ', '', $this->mobile_number);

        $this->validate();

        if (App::environment(['production', 'uat'])) {
            $recaptchaResponse = ReCaptcha::verify($this->recaptchaToken);
            if (! $recaptchaResponse['success']) {
                $this->clearForm();
                Helper::logInfo(static::class, __FUNCTION__, __('messages.register.recaptchaError'), ['email' => $this->email]);
                session()->flash('error', __('messages.register.recaptchaError'));

                return;
            }
        }

        // Get default "User" role
        $userRole = Role::where('name', 'Clinician')->where('status', config('constants.role.status.key.active'))->first();

        if (! $userRole) {
            Helper::logInfo(static::class, __FUNCTION__, __('messages.register.role_not_found'), ['email' => $this->email]);
            session()->flash('error', __('messages.register.role_not_found'));

            return;
        }

        $email = Str::lower($this->email);

        // Check if user already exists
        $existingUser = User::where('email', $email)->orWhere('mobile_number', $this->mobile_number)->first();

        if ($existingUser) {
            Helper::logInfo(static::class, __FUNCTION__, __('messages.register.user_already_exists'), ['email' => $email]);
            session()->flash('error', __('messages.register.user_already_exists'));

            return;
        }

        try {
            $data = [
                'role_id' => $userRole->id,
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
                'email' => $email,
                'mobile_number' => $this->mobile_number,
                'status' => config('constants.user.status.key.active'), // Set to active by default, or use 'inactive' if admin approval needed
                'locale' => app()->getLocale(),
            ];

            $user = User::create($data);

            // Set password separately since it's not in fillable array
            $user->password = Hash::make($this->password);
            $user->save();

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            Helper::logInfo(static::class, __FUNCTION__, __('messages.register.success'), ['email' => $email, 'user_id' => $user->id]);

            $this->clearForm();

            // Show success message on registration page first, then redirect after 3 seconds
            $this->successMessage = __('messages.register.verification_email_sent');
            $this->showSuccessMessage = true;
        } catch (\Throwable $e) {
            Helper::logCatchError($e, static::class, __FUNCTION__, ['email' => $email]);
            session()->flash('error', __('messages.register.error'));

            return;
        }
    }

    public function render()
    {
        return view('livewire.auth.register')->title(__('messages.meta_titles.register'));
    }

    public function clearForm()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->mobile_number = '';
        $this->password = '';
        $this->password_confirmation = '';
    }

    /**
     * Redirect to login page after showing success message
     */
    public function redirectToLogin()
    {
        return $this->redirect(route('login'), navigate: true);
    }
}
