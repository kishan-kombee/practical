/**
 * Dropzone Component
 * Reusable dropzone functionality for file uploads
 */

class DropzoneComponent {
    /**
     * Constructor - Initializes the DropzoneComponent with configuration
     *
     * @param {Object} config - Configuration object containing dropzone settings
     * @param {string} config.container - CSS selector for dropzone element
     * @param {string} config.dropzoneContainer - CSS selector for container element
     * @param {string} config.url - Upload endpoint URL
     * @param {number} config.maxFiles - Maximum number of files allowed
     * @param {number} config.maxFilesize - Maximum file size in MB
     * @param {string} config.acceptedFiles - Accepted file types
     */
    constructor(config) {
        this.config = {
            container: '#dropzone-instance',
            dropzoneContainer: '#dropzone-container',
            url: '/upload',
            maxFiles: 1,
            maxFilesize: 10,
            acceptedFiles: ".csv, text/csv, application/vnd.ms-excel, application/csv, text/x-csv, application/x-csv, text/comma-separated-values, text/x-comma-separated-values",
            ...config
        };

        this.dropzone = null;
        this.isInitialized = false;
    }

    /**
     * Initialize the dropzone component
     *
     * Main entry point for setting up the dropzone. Checks if Dropzone library is loaded,
     * verifies elements exist, and sets up drag/drop handlers and the dropzone instance.
     * Includes fallback mechanisms for delayed initialization.
     */
    init() {
        if (typeof Dropzone === 'undefined') {
            setTimeout(() => this.init(), 100);
            return;
        }

        // Ensure auto-discovery is disabled
        Dropzone.autoDiscover = false;

        const dropzoneElement = document.querySelector(this.config.container);
        if (dropzoneElement && dropzoneElement.dropzone) {
            return;
        }

        const dropzoneContainer = document.querySelector(this.config.dropzoneContainer);
        if (!dropzoneContainer) {
            return;
        }

        // Check if URL is provided
        if (!this.config.url) {
            return;
        }

        this.setupDragAndDrop(dropzoneContainer);
        this.createDropzoneInstance();

        if (this.dropzone) {
            this.isInitialized = true;
            // Fallback: set up event handlers if not already done
            setTimeout(() => {
                if (this.dropzone && !this.dropzone._eventHandlersSet) {
                    this.setupEventHandlers();
                }
            }, 200);
        }
    }

    /**
     * Setup drag and drop visual feedback handlers
     *
     * Adds event listeners to the container for drag and drop visual feedback.
     * Handles dragover, dragleave, and drop events to provide user feedback
     * during file drag operations.
     *
     * @param {HTMLElement} container - The dropzone container element
     */
    setupDragAndDrop(container) {
        // Drag and drop visual feedback
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
            const title = document.getElementById('dropzone-title');
            if (title) {
                title.classList.add('text-blue-600', 'dark:text-blue-400');
                title.textContent = this.config.dropText || 'Drop files here';
            }
        });

        container.addEventListener('dragleave', (e) => {
            e.preventDefault();
            if (!container.contains(e.relatedTarget)) {
                container.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
                const title = document.getElementById('dropzone-title');
                if (title) {
                    title.classList.remove('text-blue-600', 'dark:text-blue-400');
                    title.textContent = this.config.noteText || 'Drop files here';
                }
            }
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
            const title = document.getElementById('dropzone-title');
            if (title) {
                title.classList.remove('text-blue-600', 'dark:text-blue-400');
                title.textContent = this.config.noteText || 'Drop files here';
            }

            const files = e.dataTransfer.files;
            if (files.length > 0 && this.dropzone) {
                files.forEach(file => this.dropzone.addFile(file));
            }
        });
    }

    /**
     * Create the Dropzone instance with configuration
     *
     * Creates a new Dropzone instance with all the configured settings including
     * CSRF token, upload URL, file limits, accepted file types, and custom preview template.
     * Also sets up event handlers after initialization.
     */
    createDropzoneInstance() {
        try {

            this.dropzone = new Dropzone(this.config.container, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                url: this.config.url,
                paramName: "file",
                maxFiles: this.config.maxFiles,
                maxFilesize: this.config.maxFilesize,
                addRemoveLinks: true,
                removeLinks: true,
                acceptedFiles: this.config.acceptedFiles,
                clickable: this.config.dropzoneContainer,
                previewTemplate: this.getPreviewTemplate(),
                init: () => {
                    // Set up event handlers after dropzone is fully initialized
                    setTimeout(() => {
                        this.setupEventHandlers();
                    }, 100);
                }
            });

        } catch (error) {
            this.dropzone = null;
        }
    }

    /**
     * Get custom preview template HTML
     *
     * Returns a custom HTML template for displaying file upload progress.
     * Shows file name, size, progress text, spinner, error messages, and remove button.
     *
     * @returns {string} HTML template string for dropzone preview
     */
    getPreviewTemplate() {
        return `
            <div class="dz-preview dz-file-preview">
                <div class="dz-processing-content">
                    <div class="dz-details">
                        <div class="dz-filename"><span data-dz-name></span></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                    <div class="dz-progress-text">Uploading...</div>
                    <div class="dz-processing-spinner"></div>
                    <div class="dz-error-message">
                        <span data-dz-errormessage></span>
                    </div>
                    <a class="dz-remove" href="javascript:undefined;" data-dz-remove>×</a>
                </div>
            </div>
        `;
    }

    /**
     * Setup all dropzone event handlers
     *
     * Configures event listeners for dropzone events:
     * - sending: Before file upload starts
     * - uploadprogress: During file upload (updates progress)
     * - error: When upload fails
     * - success: When upload completes
     * - addedfile: When file is added to dropzone
     * - removedfile: When file is removed from dropzone
     *
     * Also handles custom remove button clicks and updates UI accordingly.
     */
    setupEventHandlers() {
        if (!this.dropzone) {
            return;
        }

        this.dropzone.on("sending", (file, xhr, formData) => {
            if (this.config.onSending) {
                this.config.onSending(file, xhr, formData);
            }
        });

        this.dropzone.on("uploadprogress", (file, progress, bytesSent) => {
            if (file.previewElement) {
                // Update progress text
                const progressText = file.previewElement.querySelector('.dz-progress-text');
                if (progressText) {
                    progressText.textContent = `Uploading... ${Math.round(progress)}%`;
                }

                // Show spinner during upload
                const spinner = file.previewElement.querySelector('.dz-processing-spinner');
                if (spinner) {
                    spinner.style.display = 'block';
                }
            }
        });

        this.dropzone.on("error", (file, response, xhr) => {
            if (this.config.onError) {
                this.config.onError(file, response, xhr);
            }
        });

        this.dropzone.on("success", (file, response, xhr) => {
            // Hide spinner on success
            if (file.previewElement) {
                const spinner = file.previewElement.querySelector('.dz-processing-spinner');
                if (spinner) {
                    spinner.style.display = 'none';
                }
                const progressText = file.previewElement.querySelector('.dz-progress-text');
                if (progressText) {
                    progressText.textContent = 'Upload Complete!';
                }
            }

            this.dropzone.removeAllFiles();
            if (this.config.onSuccess) {
                this.config.onSuccess(file, response, xhr);
            }
        });

        this.dropzone.on("addedfile", (file) => {
            const messageElement = document.querySelector('.dz-message');
            if (messageElement) {
                messageElement.style.display = 'none';
            }
            if (file.previewElement) {
                // Show spinner initially
                const spinner = file.previewElement.querySelector('.dz-processing-spinner');
                if (spinner) {
                    spinner.style.display = 'block';
                }
                // Custom remove button handler
                const removeButton = file.previewElement.querySelector('.dz-remove');
                if (removeButton) {
                    removeButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.dropzone.removeFile(file);
                    });
                }
            }
        });

        this.dropzone.on("removedfile", (file) => {
            if (this.dropzone && this.dropzone.files.length === 0) {
                const messageElement = document.querySelector('.dz-message');
                if (messageElement) {
                    messageElement.style.display = 'block';
                }
            }
        });

        // Mark that event handlers have been set
        this.dropzone._eventHandlersSet = true;
    }

    /**
     * Destroy the dropzone instance
     *
     * Cleanly destroys the dropzone instance, removes all files, and resets
     * initialization state. Used when re-initializing or cleaning up.
     */
    destroy() {
        if (this.dropzone) {
            this.dropzone.destroy();
            this.dropzone = null;
            this.isInitialized = false;
        }
    }
}

/**
 * Global initialization function for creating dropzone instances
 *
 * This is the main entry point for initializing dropzone components.
 * It safely destroys any existing instance on the target container before
 * creating a new one to prevent conflicts.
 *
 * @param {Object} config - Configuration object for the dropzone instance
 * @returns {DropzoneComponent} The initialized dropzone component instance
 */
window.initDropzone = function(config = {}) {
    // Use the container from config, or fallback to default
    const containerSelector = config.container || '#dropzone-instance';

    // Destroy existing dropzone if it exists
    const existingElement = document.querySelector(containerSelector);
    if (existingElement && existingElement.dropzone) {
        console.log('Destroying existing dropzone instance before re-init');
        try {
            existingElement.dropzone.destroy();
        } catch (e) {
            console.log('Error destroying dropzone:', e);
        }
        delete existingElement.dropzone;
    }

    const dropzone = new DropzoneComponent(config);
    dropzone.init();
    return dropzone;
};
