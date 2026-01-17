@props([
'columns' => null,
'theme' => null,
'tableName' => null,
'filtersFromColumns' => null,
'showFilters' => false,
])
<div
    x-data="{ 
        open: @entangle('showFilters').live,
        init() {
            if (!Alpine.store('filterState')) {
                Alpine.store('filterState', {
                    mobileOpen: false,
                    isMobile: window.innerWidth < 768
                });
            }
            window.addEventListener('resize', () => {
                Alpine.store('filterState').isMobile = window.innerWidth < 768;
            });
        }
    }"
    class="mt-2 md:mt-0">
    {{-- Filter Container --}}
    {{-- On mobile: show when mobileOpen is true (controlled by mobile button) --}}
    {{-- On desktop: show when open is true (controlled by header filter button) --}}
    <div
        x-show="Alpine.store('filterState')?.isMobile ? Alpine.store('filterState')?.mobileOpen : open"
        x-cloak
        @click.away="
            if (Alpine.store('filterState')?.isMobile) {
                Alpine.store('filterState').mobileOpen = false;
            } else {
                open = false;
            }
        "
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="pg-filter-container"
        :class="Alpine.store('filterState')?.isMobile ? 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 mb-3' : ''">
        @php
        $customConfig = [];

        $componentFilters = collect($this->filters());
        $filterOrderMap = $componentFilters->pluck('field')->flip();

        // Sort filters based on the order they appear in filters() method
        $sortedFilters = $filtersFromColumns->sortBy(function ($column) use ($filterOrderMap) {
        $fieldName = data_get($column, 'filters.field');
        return $filterOrderMap->get($fieldName, 999); // 999 for fields not found in filters()
        });
        @endphp
        <div :class="Alpine.store('filterState')?.isMobile ? 'space-y-3' : 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6 gap-3'">
            @foreach ($sortedFilters as $column)
            @php
            $filter = data_get($column, 'filters');
            $title = data_get($column, 'title');
            $baseClass = data_get($filter, 'baseClass');
            $className = str(data_get($filter, 'className'));
            @endphp

            <div class="{{ $baseClass }} filter-item-wrapper">
                <div x-show="Alpine.store('filterState')?.isMobile" class="flex flex-col sm:flex-row sm:items-start gap-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex-shrink-0 w-full sm:w-28 sm:pt-2">
                        {{ $title }}
                    </label>
                    <div class="flex-1 min-w-0 w-full sm:w-auto">
                        @if ($className->contains('FilterMultiSelect'))
                        <x-livewire-powergrid::inputs.select
                            :inline="true"
                            :theme="$theme"
                            :table-name="$tableName"
                            :filter="$filter"
                            :title="$title"
                            :initial-values="data_get(data_get($filter, 'multi_select'), data_get($filter, 'field'), [])" />
                        @elseif ($className->contains(['FilterDateTimePicker', 'FilterDatePicker']))
                        @includeIf(theme_style($theme, 'filterDatePicker.view'), [
                        'filter' => $filter,
                        'tableName' => $tableName,
                        'classAttr' => 'w-full',
                        'type' => $className->contains('FilterDateTimePicker') ? 'datetime' : 'date',
                        'inline' => true,
                        ])
                        @elseif ($className->contains(['FilterSelect', 'FilterEnumSelect']))
                        @includeIf(theme_style($theme, 'filterSelect.view'), [
                        'filter' => $filter,
                        'inline' => true,
                        ])
                        @elseif ($className->contains('FilterNumber'))
                        @includeIf(theme_style($theme, 'filterNumber.view'), [
                        'filter' => $filter,
                        'inline' => true,
                        ])
                        @elseif ($className->contains('FilterInputText'))
                        @includeIf(theme_style($theme, 'filterInputText.view'), [
                        'filter' => $filter,
                        'inline' => true,
                        ])
                        @elseif ($className->contains('FilterBoolean'))
                        @includeIf(theme_style($theme, 'filterBoolean.view'), [
                        'filter' => $filter,
                        'inline' => true,
                        ])
                        @elseif ($className->contains('FilterDynamic'))
                        <x-dynamic-component
                            :component="data_get($filter, 'component', '')"
                            :attributes="new \Illuminate\View\ComponentAttributeBag(data_get($filter, 'attributes', []))" />
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>