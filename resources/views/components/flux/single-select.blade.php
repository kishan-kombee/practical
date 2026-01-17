@props([
    'id',
    'label',
    'required' => false,
    'disabled' => false,
    'testid' => null,
])

@php
    // Detect whether the parent passed wire:model or wire:model.live
    $wireAttr = $attributes->whereStartsWith('wire:model')->first();
@endphp

<div class="flex-1">
    <flux:field>
        <flux:label for="{{ $id }}">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </flux:label>

        @if ($required)
            <flux:select id="{{ $id }}" data-testid="{{ $testid ?? $id }}"
                class="{{ $disabled ? 'cursor-not-allowed bg-gray-100' : 'cursor-pointer' }}" required
                {{ $attributes->whereStartsWith('wire:model') }}>
                {{-- Parent will inject options --}}
                {{ $slot }}
            </flux:select>
        @else
            <flux:select id="{{ $id }}" data-testid="{{ $testid ?? $id }}"
                class="{{ $disabled ? 'cursor-not-allowed bg-gray-100' : 'cursor-pointer' }}"
                {{ $attributes->whereStartsWith('wire:model') }}>
                {{-- Parent will inject options --}}
                {{ $slot }}
            </flux:select>
        @endif

        <flux:error
            name="{{ $wireAttr }}"
            data-testid="{{ $testid ? $testid.'_error' : $id.'_error' }}"
        />
    </flux:field>
</div>
