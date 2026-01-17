<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <form wire:submit="store" class="space-y-3">
        <!-- Basic Information Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl lg:border border-gray-200 dark:border-gray-700 p-2 lg:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2  gap-4 lg:gap-6 mb-0">
                    <div class="flex-1">
        <flux:field>
            <flux:label for="name" required>{{ __('messages.role.create.label_name') }} <span class="text-red-500">*</span></flux:label>
            <flux:input type="text" data-testid="name" id="name" wire:model="name" placeholder="Enter {{ __('messages.role.create.label_name') }}" required/>
            <flux:error name="name" data-testid="name_error"/>
        </flux:field>
    </div>
                             <div class="flex-1" x-data="{ status: @entangle('status') }">
        <flux:field>
            <flux:label for="status_switch">{{ __('messages.role.create.label_status') }}
                <span class="text-red-500">*</span></flux:label>
            <div class="flex items-center gap-3">
                <flux:switch
                    id="status_switch"
                    data-testid="status"
                    x-bind:checked="status === 'Y'"
                    x-on:change="$wire.set('status', $event.target.checked ? 'Y' : 'N')"
                    class="cursor-pointer"
                />
                <label for="status_switch"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer"
                    x-text="status === 'Y' ? 'Active' : 'Inactive'">
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

            <flux:button data-testid="cancel_button" class="cursor-pointer h-8! lg:h-9!" variant="outline" @click="hide()">
                {{ __('messages.cancel_button_text') }}
            </flux:button>
        </div>
    </form>
</div>
