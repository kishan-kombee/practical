@props([
    'label' => '',
    'wireModel' => '',
    'error' => '',
    'description' => '',
    'id' => null,
    'accept' => '.pdf,.doc,.docx',
    'maxSize' => '5MB',
])

<flux:field>
    @if($label)
        <flux:label for="{{ $id ?? $wireModel }}">
            {{ $label }}
        </flux:label>
    @endif
    
    <!-- Flux UI Dropbox Container -->
    <div 
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors duration-200 cursor-pointer bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800"
        x-data="dropboxHandler()"
        data-max-size="{{ $maxSize }}"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
        x-bind:class="isDragging ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : ''"
    >
        <!-- Hidden File Input -->
        <input 
            id="{{ $id ?? $wireModel }}"
            type="file" 
            wire:model="{{ $wireModel }}"
            accept="{{ $accept }}"
            x-ref="fileInput"
            class="hidden"
            x-on:change="handleFileSelect($event)"
        />
        
        <!-- Dropbox Content - Show when no file selected -->
        <div x-show="!selectedFile" class="space-y-3">
            <!-- Upload Icon -->
            <div class="mx-auto w-10 h-10 text-gray-400">
                <flux:icon icon="cloud-arrow-up" class="w-full h-full" />
            </div>
            
            <!-- Upload Text -->
            <div class="space-y-1">
                <flux:text class="text-sm font-medium">
                    {{ __('messages.user.create.document_upload.drop_files_text') }}
                </flux:text>
                <flux:text class="text-xs text-gray-500">
                    {{ strtoupper(str_replace('.', '', $accept)) }} up to {{ $maxSize }}
                </flux:text>
            </div>
            
            <!-- Upload Button -->
            <flux:button 
                type="button" 
                variant="outline" 
                size="xs"
                icon="cloud-arrow-up"
            >
                {{ __('messages.user.create.document_upload.choose_files') }}
            </flux:button>
        </div>
        
        <!-- File Preview - Show when file is selected -->
        <div x-show="selectedFile" class="space-y-3" x-transition>
            <!-- File Icon -->
            <div class="mx-auto w-10 h-10 text-blue-500">
                <flux:icon icon="document-text" class="w-full h-full" />
            </div>
            
            <!-- File Information -->
            <div class="space-y-1">
                <flux:text class="text-sm font-medium truncate max-w-xs mx-auto" x-text="selectedFile?.name || '{{ __('messages.user.create.document_upload.selected_file') }}'">
                    {{ __('messages.user.create.document_upload.selected_file') }}
                </flux:text>
                <flux:text class="text-xs text-gray-500" x-text="selectedFile ? (selectedFile.size / 1024 / 1024).toFixed(2) + ' MB' : ''">
                    {{ __('messages.user.create.document_upload.file_size') }}
                </flux:text>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-center gap-2">
                <flux:button 
                    type="button" 
                    variant="outline" 
                    size="xs"
                    icon="arrow-path"
                    @click.stop="$refs.fileInput.click()"
                >
                    {{ __('messages.user.create.document_upload.change') }}
                </flux:button>
                
                <flux:button 
                    type="button" 
                    variant="danger" 
                    size="xs"
                    icon="trash"
                    @click.stop="removeFile()"
                >
                    {{ __('messages.user.create.document_upload.remove') }}
                </flux:button>
            </div>
        </div>
        
        <!-- Toast Notifications -->
        <div x-show="showToast" x-transition class="mt-3">
            <flux:callout 
                x-bind:variant="toastType === 'error' ? 'danger' : 'success'"
                x-bind:icon="toastType === 'error' ? 'exclamation-triangle' : 'check-circle'"
            >
                <div 
                    class="text-sm font-medium"
                    x-bind:class="toastType === 'error' ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300'"
                    x-text="toastMessage || 'No message'"
                ></div>
            </flux:callout>
        </div>
        
    </div>
    
    @if($description)
        <flux:description>{{ $description }}</flux:description>
    @endif
    
    @if($error)
        <flux:error name="{{ $error }}" />
    @endif
</flux:field>
