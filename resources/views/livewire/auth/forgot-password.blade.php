 <div class="flex flex-col gap-6">
    <x-session-message></x-session-message>
     <x-auth-header :title="__('messages.login.forgot_password_title')" :description="__('Enter your email to receive a password reset link')" />

     <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
         <!-- Email Address -->
         <flux:input wire:model="email" :label="__('messages.login.label_email')" type="email" required autofocus
             placeholder="email@example.com" data-testid="email" onblur="value=value.trim()" id="email"/>

         <flux:button variant="primary" data-testid="submit_button" type="submit" class="w-full cursor-pointer">{{ __('messages.submit_button_text') }}</flux:button>
     </form>

     <div class="space-x-1 rtl:space-x-reverse text-left text-sm text-zinc-400">
         <span><</span>
         <flux:link href="/" data-testid="login" wire:navigate>{{ __('messages.login.label_back_to_login') }}</flux:link>
     </div>
 </div>
