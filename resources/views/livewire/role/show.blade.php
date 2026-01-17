<div class="col-lg-12">
    <div class="card-xl-stretch-1 mb-4">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.role.show.details.name') }}</flux:label>
        <flux:description>{{ $role?->name ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.role.show.details.status') }}</flux:label>
        <flux:description>{{ $role?->status ?? '-' }}</flux:description>
    </flux:field>
            </div>
        </div>
    </div>
</div>