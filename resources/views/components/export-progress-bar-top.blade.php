<div id="progressBarDiv" class="hidden fixed top-0 left-0 right-0 z-[100] transform transition-all duration-300 ease-in-out" style="transform: translateY(-100%);">
    <!-- Enhanced gradient background with better depth -->
    <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-950/40 dark:via-teal-950/40 dark:to-cyan-950/40 border-b border-emerald-200/60 dark:border-emerald-700/30 shadow-xl backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
            <div class="flex items-center justify-between gap-4">
                <!-- Left: Enhanced Icon and Message -->
                <div class="flex items-center gap-3.5 flex-1 min-w-0">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p id="waitingMessage" class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate leading-tight">
                                Preparing export...
                            </p>
                            <!-- Queue badge indicator with dropdown -->
                            <div class="relative" id="queueBadgeContainer">
                                <button
                                    id="queueBadge"
                                    class="hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-700 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-1"
                                    onclick="toggleQueueDropdown(event)"
                                    aria-label="View queue details"
                                    aria-expanded="false">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span id="queueCount">0</span> in queue
                                    <svg id="queueBadgeArrow" class="w-3 h-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <!-- Queue dropdown -->
                                <div
                                    id="queueDropdown"
                                    class="hidden absolute top-full left-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
                                    style="min-width: 200px; max-width: 90vw;">
                                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Queued Exports</h3>
                                            <span id="queueDropdownCount" class="text-xs font-medium text-blue-600 dark:text-blue-400">0</span>
                                        </div>
                                    </div>
                                    <div id="queueList" class="max-h-48 overflow-y-auto">
                                        <!-- Queue items will be populated here -->
                                        <div class="p-3 text-center">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">No exports in queue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Subtle status indicator -->
                        <p id="queueStatus" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate hidden sm:block">
                            Please wait while we process your request
                        </p>
                    </div>
                </div>

                <!-- Center: Enhanced Progress Bar with better design -->
                <div class="hidden md:flex items-center gap-4 flex-1 max-w-md">
                    <div class="flex-1 bg-gray-200/80 dark:bg-gray-700/80 rounded-full h-3 overflow-hidden shadow-inner border border-gray-300/50 dark:border-gray-600/50">
                        <div id="progressBar" class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 h-full rounded-full transition-all duration-500 ease-out relative overflow-hidden shadow-lg shadow-emerald-500/30" style="width: 0%">
                            <!-- Enhanced shimmer effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer"></div>
                            <!-- Glow effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/50 via-teal-400/50 to-cyan-400/50 blur-sm"></div>
                        </div>
                    </div>
                    <!-- Enhanced percentage display -->
                    <div class="flex items-center gap-1.5">
                        <span id="progressText" class="text-sm font-bold text-emerald-700 dark:text-emerald-300 min-w-[3.5rem] text-right tabular-nums drop-shadow-sm">0%</span>
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></div>
                    </div>
                </div>

                <!-- Right: Enhanced Percentage (Mobile) and Close Button -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span id="progressTextMobile" class="md:hidden text-sm font-bold text-emerald-700 dark:text-emerald-300 tabular-nums drop-shadow-sm">0%</span>
                    <!-- Enhanced close button with professional hover effects and smooth animation -->
                    <button
                        id="progressBarClose"
                        onclick="stopExportProgress()"
                        class="group flex-shrink-0 p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all duration-300 ease-in-out hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-2 relative overflow-hidden"
                        aria-label="Hide progress bar"
                        title="Hide progress bar">
                        <!-- Ripple effect background -->
                        <span class="absolute inset-0 bg-gray-200 dark:bg-gray-700 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 scale-0 group-hover:scale-100 transform origin-center"></span>
                        <!-- Icon with rotation effect -->
                        <svg class="relative h-4 w-4 transform transition-transform duration-300 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Enhanced Mobile Progress Bar -->
            <div class="md:hidden mt-3">
                <div class="bg-gray-200/80 dark:bg-gray-700/80 rounded-full h-2.5 overflow-hidden shadow-inner border border-gray-300/50 dark:border-gray-600/50">
                    <div id="progressBarMobile" class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 h-full rounded-full transition-all duration-500 ease-out relative overflow-hidden shadow-md shadow-emerald-500/30" style="width: 0%">
                        <!-- Enhanced shimmer effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer"></div>
                        <!-- Glow effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/50 via-teal-400/50 to-cyan-400/50 blur-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>