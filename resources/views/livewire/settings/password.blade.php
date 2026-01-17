<section class="w-full">
    <x-session-message></x-session-message>
    <div class="bg-white dark:bg-gray-800 rounded-xl lg:border border-gray-200 dark:border-gray-700 p-2 lg:p-6">
        <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
            <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
                <flux:input data-testid="current_password" wire:model="current_password" :label="__('messages.login.label_old_password')" type="password" required autocomplete="current-password" />
                <flux:input data-testid="password" wire:model="password" :label="__('messages.login.label_new_password')" type="password" required autocomplete="new-password" />
                <flux:input data-testid="password_confirmation" wire:model="password_confirmation" :label="__('messages.login.label_confirm_password')" type="password" required autocomplete="new-password" />

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full cursor-pointer h-8! lg:h-9!" data-testid="submit_button">{{ __('messages.save_button_text') }}</flux:button>
                    </div>
                </div>
            </form>
        </x-settings.layout>
    </div>
</section>
