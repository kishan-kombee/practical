@props([
    'modalName' => 'confirmation-modal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'confirmEvent' => 'confirmed',
    'cancelEvent' => 'cancelled',
    'params' => [],
    'variant' => 'danger',
])

<flux:modal name="{{ $modalName }}" class="max-w-md w-[calc(100%-2rem)] sm:w-full mx-auto" wire:model="showModal">
    <div class="space-y-4 sm:space-y-6">
        <div class="flex items-start sm:items-center space-x-3">
            @if ($variant === 'danger')
                <div class="flex-shrink-0">
                    <flux:icon.exclamation-triangle class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" />
                </div>
            @elseif($variant === 'warning')
                <div class="flex-shrink-0">
                    <flux:icon.exclamation-triangle class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-600" />
                </div>
            @else
                <div class="flex-shrink-0">
                    <flux:icon.information-circle class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600" />
                </div>
            @endif

            <div class="flex-1">
                <flux:heading size="lg" class="text-base sm:text-lg">{{ $title }}</flux:heading>
            </div>
        </div>

        <flux:text variant="subtle" class="text-sm sm:text-base">
            {{ $message }}
        </flux:text>

        <div class="flex flex-col-reverse sm:flex-row justify-end space-y-reverse space-y-2 sm:space-y-0 sm:space-x-3">
            <flux:button class="cursor-pointer w-full sm:w-auto" data-testid="cancel-button" variant="outline" wire:click="hideModal">
                {{ $cancelText }}
            </flux:button>

            <flux:button data-testid="delete-button" variant="{{ $variant }}" wire:click="{{ $confirmEvent }}" class="cursor-pointer w-full sm:w-auto">
                {{ $confirmText }}
            </flux:button>
        </div>
    </div>
</flux:modal>
