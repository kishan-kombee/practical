<div class="flex h-full w-full flex-1 flex-col gap-0 rounded-xl">
    <x-session-message></x-session-message>
    
    <form wire:submit.prevent="store" class="space-y-6">
        @foreach($emailFormats as $i => $emailFormat)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:field>
                <flux:label for="body_{{ $i }}" required>{{ $emailFormat['label'] }}</flux:label>
                <x-flux.editor wireModel="emailFormats.{{ $i }}.body" id="body_{{ $i }}" height="300px" />
                <flux:error name="emailFormats.{{ $i }}.body" />
            </flux:field>            
            </div>
        @endforeach
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-top gap-3 mt-3 lg:mt-3 border-t-2 lg:border-none border-gray-100 py-4 lg:py-0">
            <flux:button type="submit" variant="primary" data-testid="submit_button" class="cursor-pointer h-8! lg:h-9!" wire:loading.attr="disabled" wire:target="store">
                {{ __('messages.submit_button_text') }}
            </flux:button>
        </div>
    </form>
</div>