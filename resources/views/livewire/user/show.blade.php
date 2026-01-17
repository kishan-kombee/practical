<div>
    <x-show-info-modal modalTitle="{{ __('messages.user.show.label_user') }}" :eventName="$event" :showSaveButton="false" :showCancelButton="false">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.role_name') }}</flux:label>
        <flux:description>{{ !is_null($user) ? $user->role_name : '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.first_name') }}</flux:label>
        <flux:description>{{ $user?->first_name ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.last_name') }}</flux:label>
        <flux:description>{{ $user?->last_name ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.email') }}</flux:label>
        <flux:description>{{ $user?->email ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.mobile_number') }}</flux:label>
        <flux:description>{{ $user?->mobile_number ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.password') }}</flux:label>
        <flux:description>{{ $user?->password ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.user.show.details.status') }}</flux:label>
        <flux:description>{{ $user?->status ?? '-' }}</flux:description>
    </flux:field>
            </div>
        </div>
    </x-show-info-modal>
</div>
