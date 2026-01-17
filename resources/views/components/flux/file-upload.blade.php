@props([
    'model',
    'label' => null,
    'note' => null,
    'disabled' => false,
    'value' => null,
    'existingValue' => null,
    'existingDocuments' => null,
    'multiple' => false,
    'accept' => null,
    'required' => false,
    'maxFiles' => 1,
    'class' => '',
])

@php
    $uploadId = md5($model);
@endphp

<flux:field class="{{ $class }}">
    @if($label)
        <flux:label for="{{ $model }}" :required="$required">
            {{ $label }}@if($required) <span class="text-red-500">*</span>@endif
        </flux:label>
    @endif

    @if($maxFiles > 1)
        <!-- File Count Display -->
        <div class="mb-2">
            <flux:description>
                @php
                    // Count new uploads
                    $newFileCount = 0;
                    if (isset($this->$model)) {
                        $propertyValue = $this->$model;
                        if (is_array($propertyValue)) {
                            $newFileCount = count($propertyValue);
                        } elseif (is_object($propertyValue) && method_exists($propertyValue, 'getClientOriginalName')) {
                            $newFileCount = 1;
                        }
                    }

                    // Count existing documents from database
                    $existingCount = 0;
                    if ($existingDocuments && is_array($existingDocuments)) {
                        $existingCount = count($existingDocuments);
                    } elseif ($existingValue) {
                        $existingCount = 1;
                    }

                    // Total count
                    $fileCount = $newFileCount + $existingCount;
                    $isMaxFilesReached = $fileCount >= $maxFiles;
                    $remainingFiles = $maxFiles - $fileCount;
                @endphp
                {{ $fileCount }} of {{ $maxFiles }} documents uploaded
                @if($isMaxFilesReached)
                    <span class="text-amber-600 dark:text-amber-400">(Maximum files reached)</span>
                @else
                    <span class="text-zinc-600 dark:text-zinc-400">({{ $remainingFiles }} {{ $remainingFiles == 1 ? 'file' : 'files' }} remaining)</span>
                @endif
            </flux:description>
        </div>
    @endif

    @if($maxFiles > 1 && isset($this->$model) && is_array($this->$model) && count($this->$model) > 0)
        <!-- Multiple Files Preview -->
        <div class="mt-3 space-y-2">
            @foreach($this->$model as $index => $file)
                <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-8 h-8 bg-zinc-200 dark:bg-zinc-700 rounded flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate" title="{{ is_object($file) ? $file->getClientOriginalName() : basename($file) }}">
                                {{ is_object($file) ? $file->getClientOriginalName() : basename($file) }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ is_object($file) ? number_format($file->getSize() / 1024, 1) . ' KB' : '' }}
                            </p>
                        </div>
                    </div>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="removeFile('{{ $model }}', {{ $index }})"
                        class="flex-shrink-0 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 cursor-pointer"
                        title="{{ __('messages.file_upload.remove_file') }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </flux:button>
                </div>
            @endforeach
        </div>
    @endif

    @php
        // Calculate counts for Alpine.js initialization
        $newFileCount = 0;
        if (isset($this->$model)) {
            $propertyValue = $this->$model;
            if (is_array($propertyValue)) {
                $newFileCount = count($propertyValue);
            } elseif (is_object($propertyValue) && method_exists($propertyValue, 'getClientOriginalName')) {
                $newFileCount = 1;
            }
        }

        $existingCount = 0;
        if ($existingDocuments && is_array($existingDocuments)) {
            $existingCount = count($existingDocuments);
        } elseif ($existingValue) {
            $existingCount = 1;
        }

        $fileCount = $newFileCount + $existingCount;
        $isMaxFilesReached = $maxFiles > 1 && $fileCount >= $maxFiles;
    @endphp
    <div
        x-data="{
            uploading: false,
            progress: 0,
            uploadId: @js($uploadId ?? ''),
            fileName: @js($value ? basename($value) : ''),
            maxFilesReached: @js($isMaxFilesReached ?? false),
            currentFileCount: @js($fileCount ?? 0),
            existingFileCount: @js($existingCount ?? 0),
            maxFiles: @js($maxFiles ?? 1),
            disabled: @js($disabled ?? false),
            errorMessage: '',
            showError: false,
            modelName: @js($model),
            noFileChosenText: @js(__('messages.file_upload.no_file_chosen')),
            validateFileSelection(files) {
                if (!files || files.length === 0 || this.maxFiles <= 1) return true;

                const currentNewCount = $wire.get(this.modelName)?.length || 0;
                const existingCount = this.existingFileCount || 0;
                const currentTotal = existingCount + currentNewCount;
                const newFilesCount = files.length;
                const remainingSlots = this.maxFiles - currentTotal;
                const totalAfterAdd = currentTotal + newFilesCount;

                if (remainingSlots > 0 && newFilesCount > remainingSlots) {
                    this.errorMessage = 'You can only upload ' + remainingSlots + ' more file(s). Currently you have ' + currentTotal + ' file(s). Please select only ' + remainingSlots + ' file(s).';
                    this.showError = true;
                    setTimeout(() => { this.showError = false; }, 5000);
                    return false;
                }

                if (totalAfterAdd > this.maxFiles) {
                    if (remainingSlots > 0) {
                        this.errorMessage = 'You can only upload ' + remainingSlots + ' more file(s). Currently you have ' + currentTotal + ' file(s). Please select only ' + remainingSlots + ' file(s).';
                    } else {
                        this.errorMessage = 'You can only upload a maximum of ' + this.maxFiles + ' files. Currently you have ' + currentTotal + ' file(s). Please remove some files first.';
                    }
                    this.showError = true;
                    this.maxFilesReached = currentTotal >= this.maxFiles;
                    setTimeout(() => { this.showError = false; }, 5000);
                    return false;
                }

                this.showError = false;
                this.errorMessage = '';
                return true;
            },
            init() {
                this.$watch('uploading', (value) => {
                    if (value) {
                        console.log('Upload started');
                    } else {
                        console.log('Upload finished');
                    }
                });

                if (this.maxFiles > 1) {
                    this.$watch('$wire.' + this.modelName, (value) => {
                        const newFileCount = Array.isArray(value) ? value.length : 0;
                        const existingCount = this.existingFileCount || 0;
                        const totalCount = newFileCount + existingCount;
                        this.currentFileCount = totalCount;
                        this.maxFilesReached = totalCount >= this.maxFiles;
                    });
                }
            }
        }"
        class="space-y-2"
        wire:ignore.self
    >
        <div class="relative">
            <!-- File Input Area -->
            <div
                class="w-full flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 relative min-h-[2.5rem]"
                x-on:change="fileName = $event.target.files[0]?.name || noFileChosenText"
            >
                <input
                    x-ref="input"
                    x-on:click.stop
                    x-init="Object.defineProperty($el, 'value', {
                        ...Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value'),
                        set(value) {
                            Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(this, value);
                            if(! value) this.dispatchEvent(new Event('change', { bubbles: true }))
                        }
                    })"
                    type="file"
                    class="sr-only"
                    tabindex="-1"
                    @if($multiple)
                        wire:model="{{ $model }}"
                        x-on:change.capture="
                            if ($event.target.files && $event.target.files.length > 0) {
                                if (!validateFileSelection($event.target.files)) {
                                    $event.target.value = '';
                                    $refs.input.value = '';
                                    $event.stopImmediatePropagation();
                                    $event.stopPropagation();
                                    $event.preventDefault();
                                    setTimeout(() => {
                                        $refs.input.value = '';
                                        const currentFiles = $wire.get(modelName) || [];
                                        $wire.set(modelName, currentFiles);
                                    }, 0);
                                    return false;
                                }
                            }
                        "
                    @else
                        wire:model="{{ $model }}"
                    @endif
                    @if($accept) accept="{{ $accept }}" @endif
                    @if($multiple) multiple @endif
                    @if($disabled) disabled @endif
                    x-bind:disabled="maxFilesReached || disabled"
                    x-on:livewire-upload-start="
                        console.log('Upload start event');
                        uploading = true;
                        progress = 0;
                    "
                    x-on:livewire-upload-finish="
                        console.log('Upload finish event');
                        uploading = false;
                        progress = 100;
                        @if($multiple)
                            // Update file count from Livewire
                            setTimeout(() => {
                                const fileCount = $wire.get(modelName)?.length || 0;
                                currentFileCount = fileCount;
                                maxFilesReached = fileCount >= maxFiles;
                                // Reset input after upload finishes to allow selecting more files
                                $refs.input.value = '';
                                progress = 0;
                            }, 300);
                        @else
                            setTimeout(() => {
                                progress = 0;
                            }, 300);
                        @endif
                    "
                    x-on:livewire-upload-error="
                        console.log('Upload error event');
                        uploading = false;
                        progress = 0;
                    "
                    x-on:livewire-upload-progress="console.log('Upload progress:', $event.detail.progress); progress = $event.detail.progress"
                    wire:loading.attr="disabled"
                    @if($required) required @endif
                >

                <flux:button
                    type="button"
                    variant="outline"
                    x-bind:disabled="maxFilesReached || disabled"
                    aria-hidden="true"
                    x-bind:class="maxFilesReached || disabled ? 'cursor-not-allowed' : 'cursor-pointer'"
                    x-on:click.prevent.stop="!maxFilesReached && !disabled && $refs.input.click()"
                    x-on:keydown.enter.prevent.stop="!maxFilesReached && !disabled && $refs.input.click()"
                    x-on:keydown.space.prevent.stop="!maxFilesReached && !disabled"
                    x-on:keyup.space.prevent.stop="!maxFilesReached && !disabled && $refs.input.click()"
                >
                    @if($multiple)
                        {{ __('messages.file_upload.choose_files') }}
                    @else
                        {{ __('messages.file_upload.choose_file') }}
                    @endif
                </flux:button>

                <div
                    x-ref="name"
                    class="cursor-default select-none text-sm text-zinc-500 dark:text-zinc-400 font-medium flex-1 min-w-0 w-full sm:w-auto"
                    aria-hidden="true"
                    x-text="fileName || noFileChosenText"
                    style="word-break: break-all; overflow-wrap: break-word; line-height: 1.4;"
                    title=""
                    x-bind:title="fileName || noFileChosenText"
                >
                    {{ $value ? basename($value) : __('messages.file_upload.no_file_chosen') }}
                </div>

                @if($value && \Illuminate\Support\Str::startsWith($value, ['http://', 'https://']))
                    <flux:button
                        variant="ghost"
                        size="sm"
                        href="{{ $value }}"
                        target="_blank"
                        title="{{ __('messages.file_upload.preview') }}"
                        class="flex-shrink-0 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </flux:button>
                @endif
            </div>

            <!-- Progress Bar -->
            <div x-show="uploading" x-transition:leave.duration.200ms class="mt-3 space-y-2" x-cloak>
                <div class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400">
                    <span class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('messages.file_upload.uploading') }}
                    </span>
                    <span x-text="`${progress}%`" class="font-medium"></span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                    <div
                        class="h-full bg-blue-500 transition-all duration-300 ease-out"
                        x-bind:style="`width: ${progress}%`"
                        x-bind:aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>
        </div>

        @php
            // Get existing documents dynamically from Livewire property
            $existingDocsToDisplay = [];
            if (isset($existingDocuments) && is_array($existingDocuments) && count($existingDocuments) > 0) {
                $existingDocsToDisplay = $existingDocuments;
            } elseif (isset($existingValue) && $existingValue) {
                $existingDocsToDisplay = [$existingValue];
            }
        @endphp

        @if(isset($existingDocsToDisplay) && count($existingDocsToDisplay) > 0)
            <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:description class="mb-2">
                    @if(count($existingDocsToDisplay) > 1)
                        {{ __('messages.file_upload.current_files') }}:
                    @else
                        {{ __('messages.file_upload.current_file') }}:
                    @endif
                </flux:description>
                <div class="space-y-2">
                    @foreach($existingDocsToDisplay as $index => $existingDoc)
                        <div wire:key="existing-doc-{{ $index }}-{{ basename($existingDoc) }}" class="flex items-center justify-between gap-3 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                @if(\Illuminate\Support\Str::startsWith($existingDoc, ['http://', 'https://']) || \Illuminate\Support\Str::startsWith($existingDoc, ['/']))
                                    @if(in_array(strtolower(pathinfo($existingDoc, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']))
                                        <img src="{{ $existingDoc }}" alt="{{ __('messages.file_upload.current_file') }}" class="w-12 h-12 rounded-lg object-cover border border-zinc-200 dark:border-zinc-600">
                                    @else
                                        <div class="w-12 h-12 bg-zinc-200 dark:bg-zinc-700 rounded-lg flex items-center justify-center">
                                            <svg class="h-6 w-6 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-medium text-zinc-900 dark:text-zinc-100 break-words"
                                        style="word-break: break-all; overflow-wrap: break-word;"
                                        title="{{ basename($existingDoc) }}"
                                    >
                                    </p>
                                    <a
                                        href="{{ $existingDoc }}"
                                        target="_blank"
                                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                                    >
                                        {{ __('messages.file_upload.view_file') }}
                                    </a>
                                </div>
                            </div>
                            @if($maxFiles > 1 && isset($existingDocuments) && is_array($existingDocuments))
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="removeExistingDocument({{ $index }})"
                                    class="flex-shrink-0 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 cursor-pointer"
                                    title="{{ __('messages.file_upload.remove_file') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($note)
            <flux:description>
                {{ $note }}
            </flux:description>
        @endif

        <!-- Error Message Display -->
        <div
            x-show="showError && errorMessage"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200"
            role="alert"
            x-cloak
        >
            <div class="flex items-center">
                <svg class="mr-2 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span x-text="errorMessage"></span>
            </div>
        </div>
    </div>
</flux:field>
