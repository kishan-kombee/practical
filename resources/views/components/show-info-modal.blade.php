@props([
    'modalTitle' => '',
    'eventName',
    'showSaveButton' => true,
    'saveButtonText' => 'Save',
    'showCancelButton' => true,
    'cancelButtonText' => 'Cancel',
])

<div x-data wire:ignore.self id="{{ $eventName }}" tabindex="-1" aria-labelledby="{{ $eventName }}"
    aria-hidden="true" class="fixed inset-0 z-50 hidden">
    <!-- Background Overlay -->
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300"
        @click="$dispatch('hide-modal', { id: '#{{ $eventName }}' })">
    </div>

    <!-- Centered Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4"
        @click="$dispatch('hide-modal', { id: '#{{ $eventName }}' })">
        <div class="relative w-full max-w-3xl mx-auto bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl transition-transform duration-300 scale-100"
            role="dialog" aria-modal="true" @click.stop>
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                    {{ $modalTitle }}
                </flux:heading>

                <flux:button variant="ghost" size="sm"
                    class="rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                    @click="$dispatch('hide-modal', { id: '#{{ $eventName }}' })">
                    <flux:icon.x-mark class="w-5 h-5" />
                </flux:button>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 max-h-[80vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                @if ($showCancelButton)
                    <flux:button variant="filled" @click="$dispatch('hide-modal', { id: '#{{ $eventName }}' })">
                        {{ $cancelButtonText }}
                    </flux:button>
                @endif

                @if ($showSaveButton)
                    <flux:button variant="primary">
                        {{ $saveButtonText }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</div>
