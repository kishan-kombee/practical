@props([
    'wireModel' => '',
    'label' => '',
    'description' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'maxChips' => null,
    'class' => '',
    'chipClass' => '',
    'removeIcon' => true,
    'for' => '',
])

@php
    $selectedValues = $this->$wireModel ?? [];
    if (!is_array($selectedValues)) {
        $selectedValues = [];
    }
@endphp

<flux:field class="{{ $class }}">
    @if ($label)
        <flux:label for="{{ $wireModel }}" :required="$required">
            {{ $label }}@if ($required)
                <span class="text-red-500">*</span>
            @endif
        </flux:label>
    @endif

    <div x-data="{
        chipInput: '',
        tags: @entangle($wireModel),
        maxChips: {{ $maxChips ?? 'null' }},
        disabled: {{ $disabled ? 'true' : 'false' }},

        addChip() {
            const trimmedChip = this.chipInput.trim();
            if (trimmedChip !== '' && !this.tags.includes(trimmedChip)) {
                if (this.maxChips && this.tags.length >= this.maxChips) {
                    return; // Don't add if max chips reached
                }
                this.tags.push(trimmedChip);
                this.chipInput = '';
            }
        },

        removeChip(index) {
            this.tags.splice(index, 1);
        }
    }" class="space-y-3">
        <!-- Input Section -->
        <div class="flex gap-2">
            @if ($required)
                <flux:input type="text" x-model="chipInput"
                    :placeholder="$placeholder ? : 'Enter '.ucwords(str_replace('_', ' ', $wireModel)).
                    '...'"
                    @keydown.enter.prevent="addChip" :disabled="$disabled" class="flex-1" required />
            @else
                <flux:input type="text" x-model="chipInput"
                    :placeholder="$placeholder ? : 'Enter '.ucwords(str_replace('_', ' ', $wireModel)).
                    '...'"
                    @keydown.enter.prevent="addChip" :disabled="$disabled" class="flex-1" />
            @endif
            <flux:button class="cursor-pointer" type="button" @click.prevent="addChip" :disabled="$disabled"
                size="sm">
                Add
            </flux:button>
        </div>

        <!-- Chips Display -->
        <div
            class="min-h-[3rem] p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <template x-if="tags.length === 0">
                <div class="flex items-center">
                    <span class="text-zinc-500 dark:text-zinc-400 text-sm">
                        {{ $placeholder ?: 'No items added yet' }}
                    </span>
                </div>
            </template>

            <div class="flex flex-wrap gap-2" x-show="tags.length > 0">
                <template x-for="(tag, index) in tags" :key="index">
                    <div
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm font-medium rounded-full border border-blue-200 dark:border-blue-700 {{ $chipClass }}">
                        <span class="truncate max-w-[200px]" :title="tag" x-text="tag"></span>
                        @if ($removeIcon && !$disabled)
                            <button type="button" @click.prevent="removeChip(index)"
                                class="ml-1 inline-flex items-center cursor-pointer justify-center w-4 h-4 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                :title="'Remove ' + tag">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </template>
            </div>
        </div>

        <!-- Max chips warning -->
        <template x-if="maxChips && tags.length >= maxChips">
            <div class="text-sm text-amber-600 dark:text-amber-400">
                Maximum {{ $maxChips }} items allowed
            </div>
        </template>
    </div>

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <flux:error name="{{ $wireModel }}" data-testid="{{ $wireModel }}_error" />
</flux:field>
