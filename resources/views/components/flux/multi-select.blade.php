@props([
    'id' => null,
    'label' => null,
    'model' => null, // e.g. "city_ids"
    'required' => false,
    'disabled' => false,
    'placeholder' => __('Select one or more'),
])

<div class="flex-1 cursor-pointer" x-data="{
    open: false,
    selected: @entangle($model),
    searchTerm: '',
    filterOptions() {
        const search = this.searchTerm.toLowerCase().trim();
        const labels = this.$refs.optionsContainer?.querySelectorAll('label') || [];
        labels.forEach(label => {
            if (!search) {
                label.style.display = '';
                return;
            }
            const text = label.textContent.toLowerCase();
            const shouldShow = text.includes(search);
            label.style.display = shouldShow ? '' : 'none';
        });
    }
}"
    x-effect="
        if (searchTerm !== undefined) filterOptions();
        if (!open && searchTerm) searchTerm = '';
    "
    data-testid="{{ $model }}">
    <flux:field>
        {{-- Label --}}
        @if ($label)
            <flux:label for="{{ $id }}" :required="$required">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @endif
            </flux:label>
        @endif

        {{-- Button --}}
        <div class="relative" @click.outside="open = false; searchTerm = '';">
            <button type="button" id="{{ $id }}"
                @click.stop="open = !open; if (open) { setTimeout(() => filterOptions(), 10); } else { searchTerm = ''; }"
                @disabled($disabled)
                class="flex w-full justify-between items-center
                       border border-gray-300 bg-white
                       rounded-xl px-3 py-2 text-left
                       shadow-sm hover:border-black-400
                       focus:outline-none focus:ring-2 focus:ring-black-500 focus:border-black-500
                       disabled:bg-gray-100 disabled:cursor-not-allowed
                       transition duration-150 ease-in-out">
                <span class="truncate text-gray-700 text-sm"
                    x-text="(selected && selected.length)
                          ? `${selected.length} selected`
                          : '{{ $placeholder }}'">
                </span>
                <flux:icon.chevron-down class="w-4 h-4 ml-2 text-gray-500 shrink-0" />
            </button>

            {{-- Dropdown --}}
            <div x-show="open" x-transition.origin.top @click.stop
                class="absolute z-50 mt-1 w-full rounded-xl border border-gray-200
                       bg-white shadow-lg max-h-60 overflow-auto focus:outline-none">
                {{-- Search Input --}}
                <div class="sticky top-0 z-10 bg-white border-b border-gray-200 p-2">
                    <input type="text" x-model="searchTerm" @input="filterOptions()" @click.stop
                        placeholder="{{ __('Search...') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-black-500 focus:border-black-500
                               text-sm" />
                </div>

                {{-- Options Container --}}
                <div class="py-1 [&_span]:text-sm [&_label]:text-sm" x-ref="optionsContainer">
                    {{ $slot }}
                </div>
            </div>
        </div>

        {{-- Error --}}
        @if ($model)
            <flux:error name="{{ $model }}" data-testid="{{ $model }}_error" />
        @endif
    </flux:field>
</div>
