@php
$columns = collect($columns)->map(function ($column) {
return data_forget($column, 'rawQueries');
});
@endphp

<div
    class="flex flex-col"
    @if ($deferLoading) wire:init="fetchDatasource" @endif>
    <div
        id="power-grid-table-container"
        class="{{ theme_style($theme, 'table.layout.container') }}">
        <div
            id="power-grid-table-base"
            class="{{ theme_style($theme, 'table.layout.base') }}">
            @include(theme_style($theme, 'layout.header'), [
            'enabledFilters' => $enabledFilters,
            ])

            @php
            $filtersFromColumns = $columns
            ->filter(fn($column) => filled(data_get($column, 'filters')));
            $filterMode = config('livewire-powergrid.filter');
            @endphp

            {{-- Mobile Filter Container for both inline and outside modes --}}
            @if ($filtersFromColumns->count() > 0)
            <div
                x-data="{ 
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
                class="mobile-filter-wrapper">

                {{-- Mobile Filter Container - Shows filters in mobile-friendly format for inline mode --}}
                @if ($filterMode === 'inline')
                <div
                    x-show="Alpine.store('filterState')?.isMobile && Alpine.store('filterState')?.mobileOpen"
                    x-cloak
                    @click.away="if (Alpine.store('filterState')) { Alpine.store('filterState').mobileOpen = false; }"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="pg-filter-container mobile-inline-filters block md:hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-2 mb-2">
                    @php
                    $componentFilters = collect($this->filters());
                    $filterOrderMap = $componentFilters->pluck('field')->flip();
                    $sortedFilters = $filtersFromColumns->sortBy(function ($column) use ($filterOrderMap) {
                    $fieldName = data_get($column, 'filters.field');
                    return $filterOrderMap->get($fieldName, 999);
                    });
                    @endphp
                    <div class="space-y-2">
                        @foreach ($sortedFilters as $column)
                        @php
                        $filter = data_get($column, 'filters');
                        $title = data_get($column, 'title');
                        $baseClass = data_get($filter, 'baseClass');
                        $className = str(data_get($filter, 'className'));
                        @endphp

                        <div class="{{ $baseClass }} filter-item-wrapper">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-1.5">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex-shrink-0 w-full sm:w-24 sm:pt-2">
                                    {{ ucfirst($title)  }}
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
                @endif
            </div>
            @endif

            {{-- Outside filter mode - original component --}}
            @if ($filterMode === 'outside' && $filtersFromColumns->count() > 0)
            <x-livewire-powergrid::frameworks.tailwind.filter
                :enabled-filters="$enabledFilters"
                :tableName="$tableName"
                :columns="$columns"
                :filtersFromColumns="$filtersFromColumns"
                :theme="$theme" />
            @endif

            <div
                @class([ 'overflow-x-auto'=> $readyToLoad,
                'overflow-hidden' => !$readyToLoad,
                'powergrid-table-scroll-container' => $readyToLoad,
                theme_style($theme, 'table.layout.div'),
                ])
                >
                <div class="powergrid-table-wrapper">
                    @include($table)
                </div>
            </div>

            @include(theme_style($theme, 'footer.view'))
        </div>
    </div>
</div>