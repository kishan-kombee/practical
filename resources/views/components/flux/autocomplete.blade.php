@props([
'name' => '',
'wireModel' => '',
'label' => '',
'labeltext' => '',
'items' => [], // Array of items to display (simple array or associative)
'options' => [], // Backward compatibility
'selected' => null,
'placeholder' => 'Type to search...',
'description' => '',
'required' => false,
'disabled' => false,
'minSearchLength' => 1,
'maxResults' => 50,
'displayOptions' => 10,
])

@php
// Handle wire:model attribute (Livewire binding)
$wireModelAttribute = $attributes->get('wire:model') ?? $attributes->get('wireModel');

// Handle backward compatibility with old props
$fieldName = $name ?: $wireModel ?: $wireModelAttribute;
$fieldLabel = $labeltext ?: $label;

// Get items - prefer 'items' prop, fallback to 'options'
$itemsData = !empty($items) ? $items : $options;

// Normalize items to a list of [key => ..., label => ...] and a key=>label map
$normalizedItems = [];
$itemsMap = [];
if (!empty($itemsData) && is_array($itemsData)) {
$isList = array_keys($itemsData) === range(0, count($itemsData) - 1);

if (!$isList) {
// Treat as map even if keys are numeric but non-sequential (e.g., ids)
foreach ($itemsData as $k => $v) {
$normalizedItems[] = ['key' => (string) $k, 'label' => (string) $v];
$itemsMap[(string) $k] = (string) $v;
}
} else {
// Simple list: key = label = value
foreach ($itemsData as $v) {
$normalizedItems[] = ['key' => (string) $v, 'label' => (string) $v];
$itemsMap[(string) $v] = (string) $v;
}
}
}

// Handle displayOptions - convert to proper format for JavaScript
$maxResults = $displayOptions;
if ($displayOptions === 'all' || $displayOptions === 'ALL') {
$maxResults = 'all';
}
@endphp

<div class="relative" data-field-name="{{ $fieldName }}"
    x-data="autocomplete({
        items: @js($normalizedItems),
        itemsMap: @js($itemsMap),
        selectedKey: @js($selected ?? ''),
        isOpen: false,
        searchQuery: '',
        highlightedIndex: -1,
        placeholder: @js($placeholder),
        minLength: {{ $minSearchLength }},
        maxResults: @js($maxResults),
        disabled: {{ $disabled ? 'true' : 'false' }}
     })"
    @click.outside="isOpen = false"
    {{ $attributes->except(['wire:model', 'options', 'selected', 'displayOptions']) }}>
    <flux:field>
        @if($fieldLabel)
        <flux:label for="{{ $fieldName }}" :required="$required">
            {{ $fieldLabel }}
            @if($required)
            <span class="text-red-500">*</span>
            @endif
        </flux:label>
        @endif

        <!-- Hidden input for Livewire -->
        <input
            type="hidden"
            name="{{ $fieldName }}"
            wire:model="{{ $fieldName }}" />

        <!-- Search Input -->
        <div class="relative">
            <input
                type="text"
                x-model="searchQuery"
                @click="if (!isOpen) { openDropdown(); }"
                @input="handleInput()"
                @keydown.escape="closeDropdown()"
                @keydown.arrow-down.prevent="if (!isOpen) { openDropdown(); } highlightNext()"
                @keydown.arrow-up.prevent="if (!isOpen) { openDropdown(); } highlightPrevious()"
                @keydown.enter.prevent="selectHighlighted()"
                :placeholder="selectedLabel || placeholder"
                :disabled="disabled"
                class="w-full h-9 px-3 pr-16 text-sm border border-gray-300 rounded-md shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent disabled:opacity-50 disabled:cursor-not-allowed" />

            <!-- Clear Button -->
            <button
                type="button"
                x-show="selectedKey"
                @click="clearSelection()"
                class="absolute right-8 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                <flux:icon name="x-mark" class="w-4 h-4" />
            </button>

            <!-- Dropdown Arrow -->
            <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-bind:class="isOpen ? 'rotate-180' : ''">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
    </flux:field>

    <!-- Dropdown Menu - Outside flux:field to avoid layout issues -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto"
        style="top: 100%;">
        <!-- Items List -->
        <template x-for="(item, index) in filteredItems" :key="index">
            <div
                class="px-4 py-3 text-sm cursor-pointer hover:bg-gray-50 focus:bg-gray-50 focus:outline-none transition-colors duration-150 border-b border-gray-100 last:border-b-0"
                x-bind:class="highlightedIndex === index ? 'bg-accent-50 text-accent-900' : ''"
                @click="selectItem(item)"
                @mouseenter="highlightedIndex = index"
                x-text="item.label"></div>
        </template>

        <!-- No Results -->
        <div x-show="filteredItems.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
            No results found
        </div>
    </div>

    @if($description)
    <flux:description class="mt-2">{{ $description }}</flux:description>
    @endif
</div>

@verbatim
<script>
    /**
     * Alpine.js component for autocomplete functionality
     * Provides searchable dropdown with keyboard navigation
     */
    function autocomplete(config) {
        const items = config.items || [];
        const itemsMap = config.itemsMap || {};
        const placeholder = config.placeholder;
        const minLength = config.minLength;
        const maxResults = config.maxResults;
        const disabled = config.disabled;
        const initialSelectedKey = config.selectedKey || '';

        return {
            items: items, // [{ key, label }]
            itemsMap: itemsMap, // { key: label }
            selectedKey: String(initialSelectedKey || ''),
            fieldName: '', // Will be set in init from data attribute
            isOpen: false,
            searchQuery: '',
            highlightedIndex: -1,
            placeholder: placeholder,
            minLength: minLength,
            maxResults: maxResults,
            disabled: disabled,

            get selectedLabel() {
                if (!this.selectedKey) return '';
                const key = String(this.selectedKey);
                return this.itemsMap[key] || key;
            },

            init() {
                // Get field name from data attribute
                this.fieldName = this.$el.getAttribute('data-field-name');

                // Watch for Livewire changes
                if (this.$wire && this.fieldName) {
                    // Check if property exists on $wire and set initial value
                    if (this.$wire[this.fieldName] !== undefined) {
                        this.selectedKey = String(this.$wire[this.fieldName] ?? '');
                    }

                    // Watch for Livewire property changes (only update if value actually changed)
                    this.$watch('$wire.' + this.fieldName, (value) => {
                        const newValue = String(value ?? '');
                        if (this.selectedKey !== newValue) {
                            this.selectedKey = newValue;
                        }
                    });
                }
            },

            /**
             * Returns filtered items based on search query
             * Shows all items when no search query or query is too short
             */
            get filteredItems() {
                const query = this.searchQuery ? this.searchQuery.toLowerCase() : '';
                const showAll = this.maxResults === 'all' || this.maxResults === -1;

                let filtered = this.items;

                // Filter by search query if there is one
                if (query && query.length >= this.minLength) {
                    filtered = this.items.filter(item => {
                        const label = (item.label || '').toLowerCase();
                        const key = (item.key || '').toLowerCase();
                        return label.includes(query) || key.includes(query);
                    });
                }

                // Return all items or slice based on maxResults
                if (showAll) {
                    return filtered;
                } else {
                    return filtered.slice(0, this.maxResults);
                }
            },

            /**
             * Opens the dropdown menu
             */
            openDropdown() {
                if (!this.disabled) {
                    this.isOpen = true;
                }
            },

            /**
             * Closes the dropdown menu
             */
            closeDropdown() {
                this.isOpen = false;
                this.highlightedIndex = -1;
            },

            /**
             * Selects an item from the dropdown
             */
            selectItem(item) {
                if (this.disabled) return;

                const newKey = String(item.key);

                // Only update if value has changed
                if (this.selectedKey === newKey) {
                    this.searchQuery = '';
                    this.closeDropdown();
                    return;
                }

                this.selectedKey = newKey;
                this.searchQuery = '';
                this.closeDropdown();

                // Update hidden input value and sync with Livewire
                const hiddenInput = this.$el.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = newKey;
                    // Trigger both input and change events for Livewire wire:model
                    hiddenInput.dispatchEvent(new Event('input', {
                        bubbles: true,
                        cancelable: true
                    }));
                    hiddenInput.dispatchEvent(new Event('change', {
                        bubbles: true,
                        cancelable: true
                    }));
                }

                // Also use Livewire's $wire.set() if available for direct property update
                if (this.$wire && this.fieldName) {
                    this.$wire.set(this.fieldName, newKey);
                }
            },

            /**
             * Clears the selected item
             */
            clearSelection() {
                // Only update if value has changed
                if (!this.selectedKey) {
                    return;
                }

                this.selectedKey = '';
                this.searchQuery = '';

                // Update hidden input value and sync with Livewire
                const hiddenInput = this.$el.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = '';
                    // Trigger both input and change events for Livewire wire:model
                    hiddenInput.dispatchEvent(new Event('input', {
                        bubbles: true,
                        cancelable: true
                    }));
                    hiddenInput.dispatchEvent(new Event('change', {
                        bubbles: true,
                        cancelable: true
                    }));
                }

                // Also use Livewire's $wire.set() if available for direct property update
                if (this.$wire && this.fieldName) {
                    this.$wire.set(this.fieldName, '');
                }
            },

            /**
             * Highlights the next item in the filtered list
             */
            highlightNext() {
                if (this.highlightedIndex < this.filteredItems.length - 1) {
                    this.highlightedIndex++;
                }
            },

            /**
             * Highlights the previous item in the filtered list
             */
            highlightPrevious() {
                if (this.highlightedIndex > 0) {
                    this.highlightedIndex--;
                }
            },

            /**
             * Selects the currently highlighted item
             */
            selectHighlighted() {
                if (this.highlightedIndex >= 0 && this.filteredItems[this.highlightedIndex]) {
                    this.selectItem(this.filteredItems[this.highlightedIndex]);
                }
            },

            /**
             * Resets highlighted index when filtering
             */
            filterItems() {
                this.highlightedIndex = -1;
            },

            /**
             * Handles input event - filters items and opens dropdown if search has enough characters
             */
            handleInput() {
                this.filterItems();
                // Open dropdown if user has typed enough characters
                if (!this.isOpen && this.searchQuery.length >= this.minLength) {
                    this.isOpen = true;
                }
            }
        };
    }
</script>
@endverbatim