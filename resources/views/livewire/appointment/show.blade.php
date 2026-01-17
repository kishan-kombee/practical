<div>
    <x-show-info-modal modalTitle="{{ __('messages.appointment.show.label_appointment') }}" :eventName="$event" :showSaveButton="false" :showCancelButton="false">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.appointment.show.details.patient_name') }}</flux:label>
                    <flux:description>{{ $appointment?->patient_name ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.appointment.show.details.clinic_location') }}</flux:label>
                    <flux:description>{{ $appointment?->clinic_location ?? '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.appointment.show.details.clinician') }}</flux:label>
                    <flux:description>{{ !is_null($appointment) ? ($appointment->clinician_name ?? '-') : '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.appointment.show.details.appointment_date') }}</flux:label>
                    <flux:description>{{ !is_null($appointment) && !is_null($appointment->appointment_date)
            ? Carbon\Carbon::parse($appointment->appointment_date)->format(config('constants.default_date_format'))
            : '-' }}</flux:description>
                </flux:field>
                <flux:field class="border-b border-gray-200 dark:border-gray-700 gap-1!">
                    <flux:label>{{ __('messages.appointment.show.details.status') }}</flux:label>
                    <flux:description>{{ $appointment?->status ?? '-' }}</flux:description>
                </flux:field>
            </div>
        </div>
    </x-show-info-modal>
</div>