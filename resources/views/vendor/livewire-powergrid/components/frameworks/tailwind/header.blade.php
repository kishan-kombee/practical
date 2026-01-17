<div>
    @includeIf(data_get($setUp, 'header.includeViewOnTop'))

    <div class="mb-2 md:mb-3 flex flex-row w-full justify-end items-center gap-1.5 md:gap-2 pt-2">
        @include(data_get($theme, 'root') . '.header.search')
        @includeWhen(boolval(data_get($setUp, 'header.wireLoading')),
            data_get($theme, 'root') . '.header.loading')
        <div x-data="pgRenderActions">
            <span class="pg-actions" x-html="toHtml"></span>
        </div>
        @if (data_get($setUp, 'exportable'))
            <div id="pg-header-export">
                @include(data_get($theme, 'root') . '.header.export')
            </div>
        @endif
        @if (count($this->filters()) > 0)
            {{-- Mobile Filter Button - Icon only, similar to Add/Export buttons --}}
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
                            if (Alpine.store('filterState')) {
                                Alpine.store('filterState').isMobile = window.innerWidth < 768;
                            }
                        });
                    }
                }"
                class="mobile-filter-button-container inline-flex md:hidden items-center"
            >
                <button
                    @click.stop="if (Alpine.store('filterState')) { Alpine.store('filterState').mobileOpen = !Alpine.store('filterState').mobileOpen; }"
                    type="button"
                    title="Filter"
                    class="mobile-filter-toggle-btn flex rounded-md ring-1 transition focus:ring-2 dark:ring-gray-600 ring-gray-300 bg-blue-600 dark:bg-blue-600 border-0 py-1.5 focus:outline-none text-xs leading-5 w-8 h-8 lg:w-9 lg:h-9 inline-flex items-center justify-center focus:ring-blue-600 focus:ring-offset-1 cursor-pointer hover:bg-blue-700 dark:hover:bg-blue-700"
                >
                    <svg 
                        class="h-4 w-4 transition-transform duration-150 text-white"
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                </button>
            </div>
        @endif
        @includeIf(data_get($theme, 'root') . '.header.toggle-columns')
        @includeIf(data_get($theme, 'root') . '.header.soft-deletes')
        @if (config('livewire-powergrid.filter') == 'outside' && count($this->filters()) > 0)
            {{-- Desktop filter toggle button - hidden on mobile --}}
            <div class="desktop-filter-toggle hidden md:block">
                @includeIf(data_get($theme, 'root') . '.header.filters')
            </div>
        @endif
    </div>

    @includeIf(data_get($theme, 'root') . '.header.enabled-filters')

    @includeWhen(data_get($setUp, 'exportable.batchExport.queues', 0), data_get($theme, 'root') . '.header.batch-exporting')
    @includeWhen($multiSort, data_get($theme, 'root') . '.header.multi-sort')
    @includeIf(data_get($setUp, 'header.includeViewOnBottom'))
    @includeIf(data_get($theme, 'root') . '.header.message-soft-deletes')
</div>

