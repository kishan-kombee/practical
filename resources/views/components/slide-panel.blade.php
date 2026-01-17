<div x-data="slidePanel" x-show="open" x-cloak class="fixed inset-0 z-50 flex justify-end rounded-0"
    @open-slide.window="show($event.detail.title, $event.detail.component, $event.detail.params)"
    @close-slide.window="hide()">
    <!-- Blurred Transparent Backdrop -->
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity duration-300" @click="hide()"></div>

    <!-- Slide Panel -->
    <div x-show="open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        class="relative w-full sm:w-2/3 md:w-1/2 lg:w-1/3 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-2xl border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg" class="text-gray-900 dark:text-gray-100" x-text="title"></flux:heading>

            <flux:button variant="ghost" size="sm" class="rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                @click="hide()">
                <flux:icon.x-mark class="w-5 h-5" />
            </flux:button>
        </div>

        <!-- Divider -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-4"></div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto">
            @livewire('slide-panel-loader', key(time()))
        </div>
    </div>
</div>