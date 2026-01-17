<div class="flex flex-col gap-6">
    <x-session-message></x-session-message>
    <x-auth-header :title="__('messages.login.title')" :description="__('Enter your email and password below to log in')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('messages.login.label_email')" type="email" required autofocus autocomplete="email" placeholder="email@example.com" onblur="value=value.trim()" data-testid="email" id="email" />

        <!-- Password -->
        <div class="relative">
            <flux:input wire:model="password" :label="__('messages.login.label_password')" type="password" required autocomplete="current-password" :placeholder="__('messages.login.label_password')" viewable data-testid="password" />
        </div>

        @if(config('constants.check_google_recaptcha') && !empty(config('constants.google_recaptcha_key')))
        <input type="hidden" id="recaptcha-token" name="recaptcha_token" wire:model="recaptchaToken">
        @endif

        <div class="flex items-center justify-end">
            <flux:button variant="primary" class="w-full cursor-pointer" type="submit" wire:loading.attr="disabled" data-test="login-button" wire:loading.class="opacity-50" wire:target="login" id="login-button">
                {{ __('messages.submit_button_text') }}
            </flux:button>
        </div>
    </form>

    <div class="flex flex-col gap-2 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
        @if (Route::has('password.request'))
        <div>
            <flux:link data-testid="forgot_password" :href="route('password.request')" wire:navigate>{{ __('messages.login.forgot_password_title') }}?</flux:link>
        </div>
        @endif

        @if (Route::has('register'))
        <div>
            {{ __('messages.register.dont_have_account') }}
            <flux:link data-testid="register_link" :href="route('register')" wire:navigate>{{ __('messages.register.register_link') }}</flux:link>
        </div>
        @endif
    </div>

</div>

@push('scripts')
@if(config('constants.check_google_recaptcha') && !empty(config('constants.google_recaptcha_key')))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.google_recaptcha_key') }}"></script>
<script>
    $("body").delegate("#login-button", "click", function() {
        event.preventDefault(); // Prevent the form from submitting immediately
        grecaptcha.ready(function() {
            grecaptcha.execute("{{ config('constants.google_recaptcha_key') }}", {
                action: 'login'
            }).then(function(token) {
                @this.set('recaptchaToken', token).then(function() {
                    @this.call('login'); // Call the Livewire method to submit the form
                });
            });
        });
    });
</script>
@endif
@endpush