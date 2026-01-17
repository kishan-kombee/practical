<div>
    <x-show-info-modal modalTitle="{{ __('messages.sms_template.show.label_sms_template') }}" :eventName="$event" :showSaveButton="false" :showCancelButton="false">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sms_template.show.details.type') }}</flux:label>
        <flux:description>{{ $smstemplate?->type ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sms_template.show.details.label') }}</flux:label>
        <flux:description>{{ $smstemplate?->label ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sms_template.show.details.message') }}</flux:label>
        <flux:description>{{ $smstemplate?->message ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sms_template.show.details.dlt_message_id') }}</flux:label>
        <flux:description>{{ $smstemplate?->dlt_message_id ?? '-' }}</flux:description>
    </flux:field>
                             <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
        <flux:label>{{ __('messages.sms_template.show.details.status') }}</flux:label>
        <flux:description>{{ $smstemplate?->status ?? '-' }}</flux:description>
    </flux:field>
            </div>
        </div>
    </x-show-info-modal>
</div>
