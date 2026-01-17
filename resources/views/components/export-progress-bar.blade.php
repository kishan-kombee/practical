<div id="progressBarDiv" class="hidden" style="margin-bottom: 5px;">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-3">
        <!-- Waiting Message -->
        <p id="waitingMessage" class="text-sm font-medium text-gray-700 dark:text-gray-200"></p>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div id="progressBar" class="bg-emerald-500 h-2 rounded-full transition-all duration-300 ease-in-out" style="width: 0%"></div>
        </div>

        <!-- Progress Text and Spinner -->
        <div class="flex items-center justify-between text-sm" id="progressTextContainer">
            <span id="progressText" class="font-medium text-gray-700 dark:text-gray-200">0%</span>
            <div class="flex items-center">
                <svg id="spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-500 dark:text-gray-400">Processing...</span>
            </div>
        </div>
    </div>
</div>