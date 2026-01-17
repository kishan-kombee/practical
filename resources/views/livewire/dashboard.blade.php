<div class="flex flex-col w-full">
    <!-- Dashboard Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Products -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('messages.dashboard.total_products') }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $totalProducts ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="cube" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        <!-- Total Appointments -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('messages.dashboard.total_appointments') }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $totalAppointments ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="calendar" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments (Next 7 Days) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('messages.dashboard.upcoming_appointments') }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $upcomingAppointments ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('messages.dashboard.next_7_days') }}
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="clock" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </div>

        <!-- User Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                        {{ __('messages.dashboard.logged_in_user') }}
                    </p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        {{ $userName ?? '-' }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('messages.dashboard.role') }}: <span class="font-medium">{{ $userRole ?? '-' }}</span>
                    </p>
                </div>
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                    <flux:icon name="user" class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Menu -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">

        @if (Gate::allows('view-role'))
        <a wire:navigate href="/role" data-testid="role" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.role')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-user'))
        <a wire:navigate href="/user" data-testid="user" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.user')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-category'))
        <a wire:navigate href="/category" data-testid="category" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.category')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-sub_category'))
        <a wire:navigate href="/sub_category" data-testid="sub_category" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.sub_category')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-product'))
        <a wire:navigate href="/product" data-testid="product" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.product')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-appointment'))
        <a wire:navigate href="/appointment" data-testid="appointment" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.appointment')
            </h3>
        </a>
        @endif
        @if (Gate::allows('view-sms-template'))
        <a wire:navigate href="/sms-template" data-testid="sms-template" class="group flex flex-col h-24 lg:h-30 items-center justify-center p-2 bg-white dark:bg-zinc-700 rounded-lg transition-all duration-300 border border-gray-200 dark:border-zinc-600 hover:bg-blue-100 dark:hover:bg-zinc-600 hover:-translate-y-1">
            <div class="mb-2">
                <flux:icon name="users" class="h-8 lg:h-10 w-8 lg:w-10 text-blue-600 dark:text-blue-400" />
            </div>
            <h3 class="text-xs lg:text-base font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors duration-300">
                @lang('messages.side_menu.sms_template')
            </h3>
        </a>
        @endif<!-- Dynamic blocks will be inserted here -->
    </div>
</div>