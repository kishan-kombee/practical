@props([
    'name' => '',
    'labeltext' => '',
    'placeholder' => 'Select options...',
    'options' => [],
    'selected' => [],
    'multiple' => true,
    'searchable' => true,
    'maxSelection' => null,
    'description' => '',
    'error' => '',
    'class' => '',
    'disabled' => false,
    'required' => false,
])

@php
    // Transform options to standardized format
    $transformedOptions = [];
    if (!empty($options)) {
        // Handle Laravel Collections
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }
        
        if (is_array($options) && !isset($options[0])) {
            // Associative array (key => value format)
            foreach ($options as $value => $label) {
                $transformedOptions[] = ['value' => $value, 'label' => $label];
            }
        } elseif (is_array($options) && isset($options[0])) {
            // Array of objects/models
            foreach ($options as $option) {
                if (is_object($option)) {
                    $transformedOptions[] = [
                        'value' => $option->id ?? $option->value ?? $option,
                        'label' => $option->name ?? $option->label ?? $option
                    ];
                } elseif (is_array($option)) {
                    $transformedOptions[] = [
                        'value' => $option['value'] ?? $option['id'] ?? $option,
                        'label' => $option['label'] ?? $option['name'] ?? $option
                    ];
                } else {
                    $transformedOptions[] = ['value' => $option, 'label' => $option];
                }
            }
        } else {
            $transformedOptions = $options;
        }
    }
    
    // Transform selected values for multi-select
    $transformedSelected = [];
    if (!empty($selected)) {
        if (is_array($selected) && !isset($selected[0]['value'])) {
            // Array of values, find labels from options
            foreach ($selected as $value) {
                $label = $value;
                // Find matching label from options
                foreach ($transformedOptions as $option) {
                    if ($option['value'] == $value) {
                        $label = $option['label'];
                        break;
                    }
                }
                $transformedSelected[] = ['value' => $value, 'label' => $label];
            }
        } else {
            $transformedSelected = $selected;
        }
    }
@endphp

@php
    // Generate unique ID for this instance
    $uniqueId = $name . '_' . uniqid();
@endphp

<script>
// Global state storage for dropdown states
window.dropdownStates = window.dropdownStates || {};
</script>

<div class="relative {{ $class }}" 
     x-data="searchableDropdown({
        options: @js($transformedOptions),
        selected: @js($transformedSelected),
        multiple: @js($multiple),
        searchable: @js($searchable),
        maxSelection: @js($maxSelection),
        disabled: @js($disabled),
        name: '{{ $name }}',
        placeholder: '{{ $placeholder }}',
        uniqueId: '{{ $uniqueId }}'
     })"
     @click.outside="isOpen = false; window.dropdownStates[name] = false"
     id="{{ $uniqueId }}">
    <flux:field>
        @if($labeltext)
            @if($required)
                <flux:label for="{{ $name }}" required>
                    {{ $labeltext }}
                    <span class="text-red-500">*</span>
                </flux:label>
            @else
                <flux:label for="{{ $name }}">
                    {{ $labeltext }}
                </flux:label>
            @endif
        @endif
        
        <!-- Select Button -->
        <div class="relative">
            <button 
                type="button"
                class="w-full h-9 px-3 text-left text-sm border border-gray-300 rounded-md shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
                @click="!disabled && toggleDropdown()"
            >
                <div class="flex items-center justify-between">
                    <span class="block truncate text-sm" x-text="displayText"></span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                         :class="{ 'rotate-180': isOpen }" 
                         fill="none" 
                         stroke="currentColor" 
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>
        </div>

        <!-- Dropdown Menu -->
        <div 
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
        >
            <!-- Search Input -->
            <div x-show="searchable" class="p-2 border-b border-gray-200">
                <input 
                    type="text"
                    x-model="searchQuery"
                    @input="filterOptions()"
                    @keydown.escape="isOpen = false"
                    @keydown.arrow-down.prevent="highlightNext()"
                    @keydown.arrow-up.prevent="highlightPrevious()"
                    @keydown.enter.prevent="selectHighlighted()"
                    placeholder="Search options..."
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent"
                    :id="'search_' + uniqueId"
                />
            </div>
            
            <!-- Options List -->
            <div x-show="filteredOptions.length > 0">
                <template x-for="(option, index) in filteredOptions" :key="option.value">
                    <div 
                        class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                        :class="getItemClasses(index, option)"
                        @click="selectOption(option)"
                        @mouseenter="highlightedIndex = index"
                    >
                        <div class="flex items-center">
                            <template x-if="multiple">
                                <input 
                                    type="checkbox" 
                                    :checked="isSelected(getOptionValue(option))"
                                    :disabled="maxSelection && selected.length >= maxSelection && !isSelected(getOptionValue(option))"
                                    class="mr-2 h-3 w-3 text-accent focus:ring-accent border-gray-300 rounded"
                                    readonly
                                />
                            </template>
                            <template x-if="!multiple">
                                <div class="flex items-center justify-between w-full">
                                    <span class="flex-1 text-sm" x-text="getOptionLabel(option)"></span>
                                    <div x-show="isSelected(getOptionValue(option))" class="ml-2 text-green-600">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </template>
                            <template x-if="multiple">
                                <span class="flex-1 text-sm" x-text="getOptionLabel(option)"></span>
                            </template>
                            <template x-if="multiple && maxSelection && selected.length >= maxSelection && !isSelected(getOptionValue(option))">
                                <span class="ml-2 text-xs text-gray-500">(Max reached)</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- No Results -->
            <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">
                No options found
            </div>
        </div>

        @if($description)
            <flux:description>{{ $description }}</flux:description>
        @endif
        
        @if($error)
            <flux:error name="{{ $name }}_error"  data-testid="{{ $name }}_error"/>
        @endif
    </flux:field>
</div>

<script>
function searchableDropdown(config) {
    return {
        options: config.options || [],
        selected: config.selected || [],
        multiple: config.multiple || false,
        searchable: config.searchable || true,
        maxSelection: config.maxSelection || null,
        disabled: config.disabled || false,
        name: config.name || '',
        placeholder: config.placeholder || 'Select options...',
        uniqueId: config.uniqueId || '',
        searchQuery: '',
        isOpen: false,
        highlightedIndex: -1,
        filteredOptions: [],
        displayText: '',
        
        init() {
            // Check if this dropdown was open before re-initialization
            const wasOpen = window.dropdownStates[this.name] || false;
            
            this.filteredOptions = [...this.options];
            this.$watch('searchQuery', () => this.filterOptions());
            this.updateDisplayText();
            
            // Initialize selected values for multi-select
            if (this.selected && Array.isArray(this.selected) && this.selected.length > 0) {
                this.selected = this.selected.map(item => {
                    if (typeof item === 'object' && item.value && item.label) {
                        return item;
                    } else {
                        const option = this.options.find(opt => opt.value == item);
                        return option || { value: item, label: item };
                    }
                });
            } else {
                this.selected = [];
            }
            
            // Restore dropdown state if it was open before re-initialization
            if (wasOpen) {
                this.isOpen = true;
            }
            
            // Watch for Livewire changes
            this.$watch(`$wire.${this.name}`, (newValue) => {
                // Store current dropdown state in global storage before updating
                window.dropdownStates[this.name] = this.isOpen;
                
                if (this.multiple) {
                    if (newValue && Array.isArray(newValue)) {
                        this.selected = newValue.map(value => {
                            const option = this.options.find(opt => opt.value === value);
                            return option || { value: value, label: value };
                        });
                    } else {
                        this.selected = [];
                    }
                } else {
                    // For single selection
                    if (newValue) {
                        const option = this.options.find(opt => opt.value == newValue);
                        this.selected = option ? [option] : [];
                    } else {
                        this.selected = [];
                    }
                }
                
                // Restore dropdown state after Livewire update
                if (window.dropdownStates[this.name]) {
                    this.isOpen = true;
                }
                
                this.updateDisplayText();
            });
        },
        
        updateDisplayText() {
            if (this.selected.length === 0) {
                this.displayText = this.placeholder;
            } else {
                if (this.multiple) {
                    this.displayText = this.selected.length === 1 
                        ? this.selected[0].label 
                        : `${this.selected.length} items selected`;
                } else {
                    this.displayText = this.selected[0].label;
                }
            }
        },
        
        getItemClasses(index, option) {
            const classes = {};
            if (this.highlightedIndex === index) {
                classes['bg-gray-100'] = true;
                classes['text-gray-900'] = true;
            }
            if (this.maxSelection && this.selected.length >= this.maxSelection && !this.isSelected(this.getOptionValue(option))) {
                classes['opacity-50'] = true;
                classes['cursor-not-allowed'] = true;
            }
            return classes;
        },
        
        toggleDropdown() {
            if (this.disabled) return;
            
            this.isOpen = !this.isOpen;
            
            // Store the new state in global storage
            window.dropdownStates[this.name] = this.isOpen;
            
            if (this.isOpen && this.searchable) {
                this.$nextTick(() => {
                    const searchInput = this.$el.querySelector(`#search_${this.uniqueId}`);
                    searchInput?.focus();
                });
            }
        },
        
        filterOptions() {
            if (!this.searchQuery.trim()) {
                this.filteredOptions = [...this.options];
            } else {
                this.filteredOptions = this.options.filter(option => 
                    option.label.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
            }
            this.highlightedIndex = -1;
        },
        
        selectOption(option) {
            if (this.disabled) return;
            
            if (this.multiple) {
                if (this.isSelected(option.value)) {
                    this.removeItem(option.value);
                } else {
                    if (this.maxSelection && this.selected.length >= this.maxSelection) {
                        return;
                    }
                    this.selected.push(option);
                }
                // Keep dropdown open for multiple selection
            } else {
                // For single selection, replace the current selection
                if (this.isSelected(option.value)) {
                    this.selected = [];
                } else {
                    this.selected = [option];
                }
                // Close dropdown after single selection
                this.isOpen = false;
                window.dropdownStates[this.name] = false;
            }
            
            this.searchQuery = '';
            this.updateDisplayText();
            this.updateLivewire();
        },
        
        removeItem(value) {
            if (this.disabled) return;
            
            this.selected = this.selected.filter(item => item.value !== value);
            this.updateLivewire();
        },
        
        isSelected(value) {
            if (!this.selected || !Array.isArray(this.selected)) {
                return false;
            }
            
            // Handle both string and number comparisons
            return this.selected.some(item => {
                if (!item || typeof item !== 'object') return false;
                return item.value == value || item.value === value;
            });
        },
        
        updateLivewire() {
            if (this.multiple) {
                const values = this.selected.map(item => item.value);
                this.$wire.call('updateSelected', values);
            } else {
                // For single selection, update the Livewire property directly
                const selectedValue = this.selected.length > 0 ? this.selected[0].value : null;
                this.$wire.set(this.name, selectedValue);
            }
        },
        
        highlightNext() {
            if (this.highlightedIndex < this.filteredOptions.length - 1) {
                this.highlightedIndex++;
            }
        },
        
        highlightPrevious() {
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            }
        },
        
        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.filteredOptions[this.highlightedIndex]) {
                this.selectOption(this.filteredOptions[this.highlightedIndex]);
            }
        },
        
        getOptionLabel(option) {
            return option ? option.label : '';
        },
        
        getOptionValue(option) {
            return option ? option.value : '';
        }
    }
}
</script>