@props([
'wireModel' => '',
'label' => '',
'min' => '',
'max' => '',
'required' => false,
'for' => '',
'dateFormat' => 'Y-m-d H:i',
])
@php
$inputId = $for ?: 'datetimepicker_' . uniqid();
@endphp
<flux:field>
    <flux:label for="{{ $inputId }}" :required="$required">{{ $label }}@if ($required)
        <span class="text-red-500">*</span>
        @endif
    </flux:label>
    <div class="relative"
        x-data="{ 
             flatpickrInstance: null,
             init() {
                 this.$nextTick(() => {
                     const input = this.$el.querySelector('#{{ $inputId }}');
                     if (input && typeof flatpickr !== 'undefined' && !input._flatpickr) {
                         const config = {
                             dateFormat: '{{ $dateFormat }}',
                             enableTime: true,
                             time_24hr: true,
                             allowInput: true,
                             clickOpens: true,
                             @if($min) minDate: '{{ $min }}', @endif
                             @if($max) maxDate: '{{ $max }}', @endif
                             onChange: (selectedDates, dateStr) => {
                                 @if($wireModel)
                                     @this.set('{{ $wireModel }}', dateStr);
                                 @endif
                             }
                         };
                         this.flatpickrInstance = flatpickr(input, config);
                         
                         // Initialize with existing value if present
                         @if($wireModel)
                         const initialValue = @this.get('{{ $wireModel }}');
                         if (initialValue) {
                             this.flatpickrInstance.setDate(initialValue, false);
                         }
                         @endif
                     }
                 });
                 
                 // Watch for Livewire updates and sync Flatpickr
                 @if($wireModel)
                 this.$watch('$wire.{{ $wireModel }}', (value) => {
                     if (this.flatpickrInstance) {
                         if (value && this.flatpickrInstance.input.value !== value) {
                             this.flatpickrInstance.setDate(value, false);
                         } else if (!value) {
                             this.flatpickrInstance.clear();
                         }
                     }
                 });
                 @endif
             }
         }">
        @if ($required)
        <flux:input
            id="{{ $inputId }}"
            data-testid="{{ $wireModel }}"
            type="text"
            class="flatpickr-datetime-input cursor-pointer"
            placeholder="Select date and time"
            wire:ignore
            required />
        @else
        <flux:input
            id="{{ $inputId }}"
            data-testid="{{ $wireModel }}"
            type="text"
            class="flatpickr-datetime-input cursor-pointer"
            placeholder="Select date and time"
            wire:ignore />
        @endif
        @if($wireModel)
        <input type="hidden" wire:model="{{ $wireModel }}" />
        @endif
    </div>
    <flux:error name="{{ $wireModel }}" data-testid="{{ $wireModel }}_error" />
</flux:field>

<style>
    /* Match PowerGrid Flatpickr styling */
    .flatpickr-datetime-input {
        cursor: pointer !important;
    }

    /* Ensure Flatpickr calendar matches PowerGrid style */
    .flatpickr-calendar {
        @apply shadow-lg border border-gray-200 dark:border-gray-700 rounded-lg;
        @apply bg-white dark:bg-gray-800;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        @apply bg-black border-black;
    }

    .flatpickr-day.selected:hover,
    .flatpickr-day.startRange:hover,
    .flatpickr-day.endRange:hover {
        @apply bg-gray-800 border-gray-800;
    }

    .flatpickr-day:hover {
        @apply bg-gray-100 dark:bg-gray-700;
    }

    .flatpickr-day.today {
        @apply border-gray-400;
    }

    .flatpickr-day.today:hover {
        @apply border-gray-600;
    }

    .flatpickr-months {
        @apply bg-white dark:bg-gray-800;
    }

    .flatpickr-month {
        @apply text-gray-900 dark:text-gray-100;
    }

    .flatpickr-weekdays {
        @apply bg-white dark:bg-gray-800;
    }

    .flatpickr-weekday {
        @apply text-gray-600 dark:text-gray-400;
    }

    .flatpickr-day {
        @apply text-gray-700 dark:text-gray-300;
    }

    .flatpickr-day.flatpickr-disabled {
        @apply text-gray-300 dark:text-gray-600;
    }
</style>