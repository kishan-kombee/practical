/**
 * ========================================
 * COMMON FORM HANDLERS
 * ========================================
 * Purpose: Reusable JavaScript functions for form validation and file handling
 * Used by: Livewire components that need form validation and file upload functionality
 * 
 * USAGE EXAMPLES:
 * 
 * 1. Form Validation Handler:
 *    <form x-data="formHandler()" x-init="init()">
 * 
 * 2. Document Upload Handler:
 *    <div x-data="dropboxHandler()">
 * 
 * 3. Color Picker Enhancement:
 *    Add @push('scripts') with initColorPickerEnhancement() in your component
 * 
 * These functions are automatically available globally when the app.js is loaded.
 */

// Ensure functions are available immediately
console.log('Loading form handlers...');


/**
 * Form validation error handler
 * Automatically scrolls to and highlights the first validation error
 * when form submission fails. Provides better UX by guiding users to errors.
 * 
 * Usage: x-data="formHandler()" on the main form element
 */
window.formHandler = function() {
    return {
        /**
         * Initialize error handling listeners
         * Listens for both custom and built-in Livewire validation events
         */
        init() {
            // Listen for Livewire validation errors
            this.$wire.on('validation-failed', () => {
                this.scrollToFirstError();
            });
            
            // Also listen for Livewire's built-in validation events
            document.addEventListener('livewire:validation-failed', () => {
                this.scrollToFirstError();
            });
        },
        
        /**
         * Find and scroll to the first validation error field
         * Searches for error elements using multiple selectors for Flux UI compatibility
         * Adds visual highlighting and focuses the input field
         */
        scrollToFirstError() {
            // Wait a bit for the DOM to update with error messages
            setTimeout(() => {
                // Find the first field with validation error - multiple selectors for Flux UI
                const firstErrorField = document.querySelector('[data-flux-error]') || 
                                      document.querySelector('.flux-error') ||
                                      document.querySelector('[wire\\:error]') ||
                                      document.querySelector('.error') ||
                                      document.querySelector('[data-slot="error"]') ||
                                      document.querySelector('.text-red-500') ||
                                      document.querySelector('.text-red-600');
                
                if (firstErrorField) {
                    // Find the parent field container (flux:field)
                    const fieldContainer = firstErrorField.closest('.flux-field') || 
                                         firstErrorField.closest('[data-flux-field]') ||
                                         firstErrorField.closest('.space-y-2') ||
                                         firstErrorField.parentElement;
                    
                    const targetElement = fieldContainer || firstErrorField;
                    
                    // Scroll to the error field with smooth behavior
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest'
                    });
                    
                    // Just scroll to error, no visual highlighting
                    // No borders, rings, or visual effects added
                    
                    // Focus the input field if it's focusable
                    const input = targetElement.querySelector('input, select, textarea');
                    if (input) {
                        input.focus();
                        // No visual effects added to input
                    }
                }
            }, 200);
        }
    }
};

/**
 * Document upload dropbox handler
 * Handles drag-and-drop file upload functionality for document upload section
 * Features: File validation, drag states, toast notifications, file preview
 * 
 * Usage: x-data="dropboxHandler()" on the document upload component
 */
window.dropboxHandler = function() {
    console.log('dropboxHandler function called');
    return {
        // State management for drag-and-drop functionality
        isDragging: false,        // Tracks if user is dragging files over drop area
        selectedFile: null,       // Currently selected file object
        showToast: false,         // Controls toast notification visibility
        toastMessage: '',         // Toast notification message
        toastType: 'success',     // Toast notification type (success/error)
        
        /**
         * Handle file drop event
         * Called when user drops files onto the drop area
         * @param {DragEvent} event - The drag and drop event
         */
        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.handleFileSelect({ target: { files: files } });
            }
        },
        
        /**
         * Handle file selection (both drag-drop and click-to-browse)
         * Validates file type and size, shows appropriate error messages
         * @param {Event} event - The file input change event
         */
        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                const file = files[0];
                
                // Get maxSize from the component's data attribute or default to 5MB
                const maxSizeText = event.target.closest('[x-data]').getAttribute('data-max-size') || '5MB';
                const maxSize = this.parseMaxSize(maxSizeText);
                const allowedExtensions = ['pdf', 'doc', 'docx'];
                
                // Get file extension
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                // Check file type
                if (!allowedExtensions.includes(fileExtension)) {
                    this.selectedFile = null;
                    event.target.value = '';
                    this.showToastMessage('Invalid file type. Please select only PDF, DOC, or DOCX files.', 'error');
                    return;
                }
                
                // Check file size
                if (file.size > maxSize) {
                    this.selectedFile = null;
                    event.target.value = '';
                    this.showToastMessage(`File size too large. Please select a file smaller than ${maxSizeText}.`, 'error');
                    return;
                }
                
                // File is valid
                this.selectedFile = file;
                // Trigger Livewire update
                event.target.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
        
        parseMaxSize(sizeText) {
            const size = parseFloat(sizeText);
            const unit = sizeText.toUpperCase().replace(/[0-9.]/g, '');
            
            switch(unit) {
                case 'KB':
                    return size * 1024;
                case 'MB':
                    return size * 1024 * 1024;
                case 'GB':
                    return size * 1024 * 1024 * 1024;
                default:
                    return 5 * 1024 * 1024; // Default to 5MB
            }
        },
        
        /**
         * Remove currently selected file
         * Clears the file input and resets the selected file state
         */
        removeFile() {
            this.selectedFile = null;
            const input = document.getElementById('document_image');
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },
        
        /**
         * Show toast notification message
         * Displays success or error messages to the user
         * @param {string} message - The message to display
         * @param {string} type - The type of message (success/error)
         */
        showToastMessage(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            
            // Auto-hide toast after 5 seconds
            setTimeout(() => {
                this.showToast = false;
            }, 5000);
        }
    }
};