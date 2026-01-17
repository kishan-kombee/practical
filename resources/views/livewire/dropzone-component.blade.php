@props([
    'importData' => null,
    'userID' => null,
    'class' => '',
    'instanceId' => 'dropzone-instance',
])

<div class="{{ $class }}">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Header Section -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <flux:heading size="lg" class="font-bold text-gray-900 dark:text-white">
                @lang('messages.dropzone.title')
            </flux:heading>
        </div>

        <!-- Dropzone Section -->
        <div class="p-6">
            <div class="w-full">
                <div class="relative">
                    <div class="border-2 border-dashed rounded-lg transition-all duration-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10 hover:shadow-lg hover:shadow-blue-100 dark:hover:shadow-blue-900/20 bg-gray-50 dark:bg-gray-800/30 cursor-pointer h-40 overflow-hidden"
                         id="dropzone-container-{{ $instanceId }}">
                        <div class="dropzone" id="{{ $instanceId }}">
                            <div class="dz-message needsclick">
                                <div class="inline-flex items-center justify-center w-8 h-8 mb-2 bg-gray-100 dark:bg-gray-700 rounded-lg transition-all duration-300 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 group-hover:scale-110"
                                     id="dropzone-icon">
                                    <flux:icon icon="arrow-up-tray" class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-all duration-300 group-hover:text-blue-500 dark:group-hover:text-blue-400"
                                              id="dropzone-icon-svg" />
                                </div>

                                <div class="space-y-1">
                                    <flux:heading size="sm" class="font-bold text-gray-900 dark:text-white"
                                                 id="dropzone-title">
                                        @lang('messages.dropzone.note')
                                    </flux:heading>
                                    <flux:text class="text-xs font-bold text-gray-400 dark:text-gray-500">
                                        @lang('messages.dropzone.file_type_text')
                                    </flux:text>
                                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                        @lang('messages.dropzone.browse_text')
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <flux:text class="mt-2 text-xs font-bold text-gray-500 dark:text-gray-400">
                @lang('messages.dropzone.upload_record_limit_text')
            </flux:text>

            <div id="flash-message-{{ $instanceId }}" class="mt-4"></div>
        </div>
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
        @endpush
    @endonce

    @once
        @push('scripts')
            <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
        @endpush
    @endonce

    @push('scripts')
        <script>
            (function() {
                // Set configuration for this instance
                const config = {
                    container: '#{{ $instanceId }}',
                    dropzoneContainer: '#dropzone-container-{{ $instanceId }}',
                    url: "{{ route('uploadFile') }}",
                    dropText: '{{ __("messages.dropzone.drop_files_text") }}',
                    noteText: '{{ __("messages.dropzone.note") }}',
                    onSending: function(file, xhr, formData) {
                        formData.append("folderName", '{{ $importData["folderName"] }}');
                        formData.append("modelName", '{{ $importData["modelName"] }}');
                        formData.append("userId", '{{ $userID }}');
                    },
                    onError: function(file, response, xhr) {
                        Livewire.dispatch('alert', { type: 'error', message: response.error });
                    },
                    onSuccess: function(file, response, xhr) {
                        $('#flash-message-{{ $instanceId }}').html(`
                            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 my-4 text-sm">
                                ${response.success}
                            </div>
                        `);
                        setTimeout(() => $('#flash-message-{{ $instanceId }}').empty(), 10000);
                        setTimeout(() => Livewire.dispatch('refreshTable'), 1000);
                    }
                };

                // Create a unique function name for this instance
                const eventName = 'initializeDropzone{{ $instanceId }}';

                // Remove existing listener if any
                document.removeEventListener('livewire:navigated', window[eventName]);

                // Initialize function
                window[eventName] = function() {
                    // Check if DOM element exists
                    const containerExists = document.querySelector(config.dropzoneContainer);
                    if (!containerExists) {
                        setTimeout(window[eventName], 100);
                        return;
                    }

                    if (typeof Dropzone !== 'undefined' && typeof window.initDropzone === 'function') {
                        console.log('Initializing dropzone {{ $instanceId }}');
                        window.initDropzone(config);
                    } else {
                        setTimeout(window[eventName], 100);
                    }
                };

                // Initialize when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', window[eventName]);
                } else {
                    // DOM already loaded
                    window[eventName]();
                }

                // Re-initialize on Livewire navigation
                document.addEventListener('livewire:navigated', window[eventName]);
            })();
        </script>
    @endpush
</div>
