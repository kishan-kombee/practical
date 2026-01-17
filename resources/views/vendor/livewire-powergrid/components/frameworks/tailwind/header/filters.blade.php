<button
    wire:key="toggle-filters-{{ $tableName }}"
    id="toggle-filters"
    wire:click="toggleFilters"
    type="button"
    title="Filter"
    class="flex rounded-md ring-1 transition focus:ring-2 dark:ring-gray-600 ring-gray-300 dark:bg-gray-800 bg-white dark:text-gray-300 text-gray-600 border-0 py-1.5 px-2 sm:py-2 sm:px-3 focus:outline-none text-xs sm:text-sm leading-5 sm:leading-6 w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 inline-flex items-center justify-center focus:ring-blue-600 focus:ring-offset-1 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
>
    <x-livewire-powergrid::icons.filter class="h-4 w-4 sm:h-5 sm:w-5 transition-transform duration-150" />
</button>
