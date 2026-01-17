<div>
    <x-show-info-modal
        modalTitle="{{ __('messages.import_error_title') }}"
        :eventName="$event"
        :showSaveButton="false"
        cancelButtonText="{{ __('messages.cancel_button_text') }}"
    >
        <div class="space-y-6">
            @if($errorLogs)
                <!-- Error Details Section -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('messages.import_error.header_one')
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('messages.import_error.header_two')
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($errorLogs as $index => $eLog)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                             {{ $eLog['row'] ?? '' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="space-y-1">
                                                @if(is_array($eLog['error'] ?? $eLog))
                                                    @foreach($eLog['error'] ?? $eLog as $err)
                                                        <div class="flex items-start space-x-2">
                                                            <flux:icon icon="x-circle" class="w-4 h-4 text-red-500 dark:text-red-400 mt-0.5 flex-shrink-0" />
                                                            <span class="text-red-600 dark:text-red-400">{{ $err }}</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="flex items-start space-x-2">
                                                        <flux:icon icon="x-circle" class="w-4 h-4 text-red-500 dark:text-red-400 mt-0.5 flex-shrink-0" />
                                                        <span class="text-red-600 dark:text-red-400">{{ $eLog['error'] ?? $eLog }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Error Summary Section -->
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
                    <div class="flex items-center">
                        <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" />
                        <div class="flex-1">
                            <flux:heading size="sm" class="font-medium text-red-800 dark:text-red-200">
                                @lang('messages.import_error.errors_found_title')
                            </flux:heading>
                            <flux:text class="text-sm text-red-600 dark:text-red-300 mt-1">
                                @lang('messages.import_error.errors_found_count', ['count' => count($errorLogs)])
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-show-info-modal>
</div>
