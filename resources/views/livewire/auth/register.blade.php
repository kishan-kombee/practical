<div class="flex flex-col gap-6">
    <x-session-message></x-session-message>

    @if($showSuccessMessage)
    <div
        x-data="{ show: true }"
        x-init="
            show = true;
            setTimeout(() => {
                $wire.call('redirectToLogin');
            }, 3000);
        "
        x-show="show"
        class="mb-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow"
        role="alert">
        <span class="text-sm font-medium">{{ $successMessage }}</span>
        <button type="button"
            class="ml-3 inline-flex h-6 w-6 items-center justify-center rounded-md text-green-700 hover:bg-green-100 focus:outline-none"
            x-on:click="show = false; $wire.call('redirectToLogin');">
            <x-flux::icon name="x-mark" class="h-4 w-4" />
        </button>
    </div>
    @endif

    <x-auth-header :title="__('messages.register.title')" :description="__('messages.register.description')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <!-- First Name -->
        <flux:input wire:model="first_name" :label="__('messages.register.label_first_name')" type="text" required autofocus autocomplete="given-name" placeholder="John" onblur="value=value.trim()" data-testid="first_name" id="first_name" />

        <!-- Last Name -->
        <flux:input wire:model="last_name" :label="__('messages.register.label_last_name')" type="text" required autocomplete="family-name" placeholder="Doe" onblur="value=value.trim()" data-testid="last_name" id="last_name" />

        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('messages.register.label_email')" type="email" required autocomplete="email" placeholder="email@example.com" onblur="value=value.trim()" data-testid="email" id="email" />

        <!-- Mobile Number -->
        <flux:input wire:model="mobile_number" :label="__('messages.register.label_mobile_number')" type="tel" required autocomplete="tel" placeholder="6123456789" data-testid="mobile_number" id="mobile_number" />

        <!-- Password -->
        <div class="relative">
            <flux:input wire:model="password" :label="__('messages.register.label_password')" type="password" required autocomplete="new-password" :placeholder="__('messages.register.label_password')" viewable data-testid="password" id="password" />
        </div>

        <!-- Confirm Password -->
        <div class="relative">
            <flux:input wire:model="password_confirmation" :label="__('messages.register.label_confirm_password')" type="password" required autocomplete="new-password" :placeholder="__('messages.register.label_confirm_password')" viewable data-testid="password_confirmation" id="password_confirmation" />
        </div>

        @if(config('constants.check_google_recaptcha') && !empty(config('constants.google_recaptcha_key')))
        <input type="hidden" id="recaptcha-token" name="recaptcha_token" wire:model="recaptchaToken">
        @endif

        <div class="flex items-center justify-end">
            <flux:button variant="primary" class="w-full cursor-pointer" type="submit" wire:loading.attr="disabled" data-test="register-button" wire:loading.class="opacity-50" wire:target="register" id="register-button">
                {{ __('messages.submit_button_text') }}
            </flux:button>
        </div>
    </form>

    @if (Route::has('login'))
    <div class="text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
        {{ __('messages.register.already_have_account') }}
        <flux:link data-testid="login_link" :href="route('login')" wire:navigate>{{ __('messages.register.login_link') }}</flux:link>
    </div>
    @endif

</div>

@push('scripts')
@if(config('constants.check_google_recaptcha') && !empty(config('constants.google_recaptcha_key')))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.google_recaptcha_key') }}"></script>
<script>
    $("body").delegate("#register-button", "click", function() {
        event.preventDefault(); // Prevent the form from submitting immediately
        grecaptcha.ready(function() {
            grecaptcha.execute("{{ config('constants.google_recaptcha_key') }}", {
                action: 'register'
            }).then(function(token) {
                @this.set('recaptchaToken', token).then(function() {
                    @this.call('register'); // Call the Livewire method to submit the form
                });
            });
        });
    });
</script>
@endif
@endpush