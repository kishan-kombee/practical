<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <form wire:submit="store" class="space-y-3">
        <!-- Basic Information Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl lg:border border-gray-200 dark:border-gray-700 p-2 lg:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2  gap-4 lg:gap-6 mb-0">
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="patient_name" required>{{ __('messages.appointment.create.label_patient_name') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" data-testid="patient_name" id="patient_name" wire:model="patient_name" placeholder="Enter {{ __('messages.appointment.create.label_patient_name') }}" required />
                        <flux:error name="patient_name" data-testid="patient_name_error" />
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label for="clinic_location" required>{{ __('messages.appointment.create.label_clinic_location') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" data-testid="clinic_location" id="clinic_location" wire:model="clinic_location" placeholder="Enter {{ __('messages.appointment.create.label_clinic_location') }}" required />
                        <flux:error name="clinic_location" data-testid="clinic_location_error" />
                    </flux:field>
                </div>
                <div class="flex-1">
                    <x-flux.autocomplete
                        name="clinician_id"
                        data-testid="clinician_id"
                        labeltext="{{ __('messages.appointment.create.label_clinician') }}"
                        placeholder="{{ __('messages.appointment.create.label_clinician') }}"
                        :options="$clinicians"
                        displayOptions="10"
                        wire:model="clinician_id"
                        :disabled="!$isAdmin && count($clinicians) === 1"
                        :required="true" />
                    <flux:error name="clinician_id" data-testid="clinician_id_error" />
                    @if(!$isAdmin)
                    <flux:description class="text-sm text-gray-500 mt-1">You can only create appointments for yourself.</flux:description>
                    @endif
                </div>
                <div class="flex-1">
                    <x-flux.date-picker for="appointment_date" wireModel="appointment_date" label="{{ __('messages.appointment.create.label_appointment_date') }}" :required="true" />
                </div>
                <div class="flex-1" x-data="{ status: @entangle('status') }">
                    <flux:field>
                        <flux:label for="status_switch">{{ __('messages.appointment.create.label_status') }}
                            <span class="text-red-500">*</span>
                        </flux:label>
                        <div class="flex items-center gap-3">
                            <flux:switch
                                id="status_switch"
                                data-testid="status"
                                x-bind:checked="status === 'B'"
                                x-on:change="$wire.set('status', $event.target.checked ? 'B' : 'D')"
                                class="cursor-pointer" />
                            <label for="status_switch"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer"
                                x-text="status === 'B' ? 'Booked' : 'Completed'">
                            </label>
                        </div>
                        <flux:error name="status" data-testid="status_error" />
                    </flux:field>
                </div>
            </div>
        </div>



        <!-- Action Buttons -->
        <div class="flex items-center justify-top gap-3 mt-3 lg:mt-3 border-t-2 lg:border-none border-gray-100 py-4 lg:py-0">

            <flux:button type="submit" variant="primary" data-testid="submit_button" class="cursor-pointer h-8! lg:h-9!" wire:loading.attr="disabled" wire:target="store">
                {{ __('messages.submit_button_text') }}
            </flux:button>

            <flux:button type="button" data-testid="cancel_button" class="cursor-pointer h-8! lg:h-9!" variant="outline" href="/appointment" wire:navigate>
                {{ __('messages.cancel_button_text') }}
            </flux:button>
        </div>
    </form>
</div>