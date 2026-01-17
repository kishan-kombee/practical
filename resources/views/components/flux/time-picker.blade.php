@props([
    'wireModel' => '',
    'label' => '',
    'required' => false,
    'for' => '',
])
<flux:field>
    <flux:label for="{{ $for }}" :required="$required">{{ $label }}@if ($required)
            <span class="text-red-500">*</span>
        @endif
    </flux:label>
    <div class="relative cursor-pointer" onclick="document.getElementById('{{ $wireModel }}').showPicker()">
        @if ($required)
            <flux:input id="{{ $for }}" data-testid="{{ $wireModel }}" type="time"
                wire:model="{{ $wireModel }}" class="cursor-pointer" style="cursor: pointer !important;" required />
        @else
            <flux:input id="{{ $for }}" data-testid="{{ $wireModel }}" type="time"
                wire:model="{{ $wireModel }}" class="cursor-pointer" style="cursor: pointer !important;" />
        @endif
        <style>
            input[type="time"]::-webkit-calendar-picker-indicator {
                cursor: pointer !important;
            }

            input[type="time"]::-webkit-inner-spin-button,
            input[type="time"]::-webkit-outer-spin-button {
                cursor: pointer !important;
            }
        </style>
    </div>
    <flux:error name="{{ $wireModel }}" data-testid="{{ $wireModel }}_error" />
</flux:field>
