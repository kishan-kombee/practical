<div class="flex flex-col gap-6">
    <x-session-message></x-session-message>
    <x-auth-header :title="__('messages.login.reset_passowrd')" :description="__('Please enter your new password below')" />

    <form method="POST" wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" disabled :label="__('messages.login.label_email')" type="email" required autocomplete="email" onblur="value=value.trim()" data-testid="email" />

        <!-- Password -->
        <flux:input wire:model="password" :label="__('messages.login.label_new_password')" type="password" required autocomplete="new-password" :placeholder="__('messages.login.label_new_password')" viewable data-testid="password" />

        <!-- Confirm Password -->
        <flux:input wire:model="password_confirmation" :label="__('messages.login.label_confirm_password')" type="password" required autocomplete="new-password" :placeholder="__('messages.login.label_confirm_password')" viewable data-testid="password_confirmation" />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full cursor-pointer" data-testid="submit_button">
                {{ __('messages.reset_password_button_text') }}
            </flux:button>
        </div>
    </form>
</div>
