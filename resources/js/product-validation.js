/**
 * ========================================
 * PRODUCT FORM VALIDATION HANDLER
 * ========================================
 * Purpose: Real-time JavaScript validation for product create/edit forms
 * Features:
 * - Price validation (numeric/decimal)
 * - Quantity validation (integer, non-negative)
 * - Field length validation
 * - Real-time feedback with custom error messages
 * - Visual error indicators
 * 
 * Usage: Add x-data="productValidation()" to the form element
 */

window.productValidation = function() {
    return {
        // Validation state for each field
        errors: {
            item_code: '',
            name: '',
            price: '',
            description: '',
            category_id: '',
            sub_category_id: '',
            quantity: ''
        },
        
        // Field validation flags
        touched: {
            item_code: false,
            name: false,
            price: false,
            description: false,
            category_id: false,
            sub_category_id: false,
            quantity: false
        },
        
        /**
         * Initialize validation handlers
         */
        init() {
            // Listen for Livewire validation errors to sync with our validation
            if (this.$wire) {
                this.$wire.on('validation-failed', () => {
                    this.syncLivewireErrors();
                });
            }
        },
        
        /**
         * Validate item code field
         * @param {string} value - The item code value
         */
        validateItemCode(value) {
            this.touched.item_code = true;
            this.errors.item_code = '';
            
            if (!value || (typeof value === 'string' && value.trim() === '') || value === null) {
                this.errors.item_code = 'Item code is required.';
                return false;
            }
            
            const stringValue = String(value);
            if (stringValue.length > 191) {
                this.errors.item_code = 'Item code must not exceed 191 characters.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate product name field
         * @param {string} value - The product name value
         */
        validateName(value) {
            this.touched.name = true;
            this.errors.name = '';
            
            if (!value || (typeof value === 'string' && value.trim() === '') || value === null) {
                this.errors.name = 'Product name is required.';
                return false;
            }
            
            const stringValue = String(value);
            if (stringValue.length > 191) {
                this.errors.name = 'Product name must not exceed 191 characters.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate price field (numeric/decimal)
         * @param {string} value - The price value
         */
        validatePrice(value) {
            this.touched.price = true;
            this.errors.price = '';
            
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                this.errors.price = 'Price is required.';
                return false;
            }
            
            // Remove any whitespace
            const cleanValue = String(value).trim();
            
            // Check if it's a valid number (allows decimals)
            const priceRegex = /^-?\d+(\.\d{1,2})?$/;
            
            if (!priceRegex.test(cleanValue)) {
                this.errors.price = 'Please enter a valid price (e.g., 10.99 or 100).';
                return false;
            }
            
            // Convert to number and check if it's positive
            const priceNum = parseFloat(cleanValue);
            if (isNaN(priceNum)) {
                this.errors.price = 'Price must be a valid number.';
                return false;
            }
            
            if (priceNum < 0) {
                this.errors.price = 'Price cannot be negative.';
                return false;
            }
            
            // Check for reasonable maximum value (optional - adjust as needed)
            if (priceNum > 999999999.99) {
                this.errors.price = 'Price is too large. Maximum value is 999,999,999.99.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate description field
         * @param {string} value - The description value
         */
        validateDescription(value) {
            this.touched.description = true;
            this.errors.description = '';
            
            if (!value || (typeof value === 'string' && value.trim() === '') || value === null) {
                this.errors.description = 'Description is required.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate category field
         * @param {string|number} value - The category ID value
         */
        validateCategory(value) {
            this.touched.category_id = true;
            this.errors.category_id = '';
            
            if (!value || value === '' || value === null) {
                this.errors.category_id = 'Category is required.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate subcategory field
         * @param {string|number} value - The subcategory ID value
         */
        validateSubCategory(value) {
            this.touched.sub_category_id = true;
            this.errors.sub_category_id = '';
            
            if (!value || value === '' || value === null) {
                this.errors.sub_category_id = 'Subcategory is required.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate quantity field (integer, non-negative)
         * @param {string} value - The quantity value
         */
        validateQuantity(value) {
            this.touched.quantity = true;
            this.errors.quantity = '';
            
            // Quantity is optional, so if empty, it's valid
            if (!value || (typeof value === 'string' && value.trim() === '') || value === null) {
                return true;
            }
            
            // Remove any whitespace
            const cleanValue = String(value).trim();
            
            // Check if it's a valid integer
            const quantityRegex = /^\d+$/;
            
            if (!quantityRegex.test(cleanValue)) {
                this.errors.quantity = 'Quantity must be a whole number (e.g., 10, 100).';
                return false;
            }
            
            // Convert to number and check if it's non-negative
            const quantityNum = parseInt(cleanValue, 10);
            if (isNaN(quantityNum)) {
                this.errors.quantity = 'Quantity must be a valid number.';
                return false;
            }
            
            if (quantityNum < 0) {
                this.errors.quantity = 'Quantity cannot be negative.';
                return false;
            }
            
            // Check for reasonable maximum value (optional - adjust as needed)
            if (quantityNum > 2147483647) {
                this.errors.quantity = 'Quantity is too large.';
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate all fields before form submission
         * @returns {boolean} - True if all fields are valid
         */
        validateAll() {
            let isValid = true;
            
            // Get current values from Livewire
            if (this.$wire) {
                isValid = this.validateItemCode(this.$wire.item_code) && isValid;
                isValid = this.validateName(this.$wire.name) && isValid;
                isValid = this.validatePrice(this.$wire.price) && isValid;
                isValid = this.validateDescription(this.$wire.description) && isValid;
                isValid = this.validateCategory(this.$wire.category_id) && isValid;
                isValid = this.validateSubCategory(this.$wire.sub_category_id) && isValid;
                isValid = this.validateQuantity(this.$wire.quantity) && isValid;
            }
            
            // If validation fails, scroll to first error
            if (!isValid) {
                this.scrollToFirstError();
            }
            
            return isValid;
        },
        
        /**
         * Clear all validation errors
         */
        clearErrors() {
            Object.keys(this.errors).forEach(key => {
                this.errors[key] = '';
            });
            Object.keys(this.touched).forEach(key => {
                this.touched[key] = false;
            });
        },
        
        /**
         * Get error message for a field
         * @param {string} fieldName - The field name
         * @returns {string} - The error message or empty string
         */
        getError(fieldName) {
            return this.errors[fieldName] || '';
        },
        
        /**
         * Check if a field has an error
         * @param {string} fieldName - The field name
         * @returns {boolean} - True if field has error
         */
        hasError(fieldName) {
            return this.errors[fieldName] !== '';
        },
        
        /**
         * Check if a field has been touched
         * @param {string} fieldName - The field name
         * @returns {boolean} - True if field has been touched
         */
        isTouched(fieldName) {
            return this.touched[fieldName];
        },
        
        /**
         * Get CSS classes for error state
         * @param {string} fieldName - The field name
         * @returns {string} - CSS classes
         */
        getErrorClasses(fieldName) {
            if (this.hasError(fieldName) && this.isTouched(fieldName)) {
                return 'border-red-500 focus:ring-red-500 focus:border-red-500';
            }
            return '';
        },
        
        /**
         * Sync errors from Livewire validation
         */
        syncLivewireErrors() {
            // This will be called when Livewire validation fails
            // The actual error messages will be shown by Livewire's error components
            // This is just for syncing our validation state
            setTimeout(() => {
                // Mark all fields as touched if Livewire validation failed
                Object.keys(this.touched).forEach(key => {
                    this.touched[key] = true;
                });
            }, 100);
        },
        
        /**
         * Scroll to first validation error
         */
        scrollToFirstError() {
            setTimeout(() => {
                // Find the first field with validation error
                const firstErrorField = document.querySelector('[data-product-error]') ||
                                      document.querySelector('.product-error') ||
                                      document.querySelector('[data-flux-error]') ||
                                      document.querySelector('.flux-error');
                
                if (firstErrorField) {
                    const fieldContainer = firstErrorField.closest('.flux-field') ||
                                         firstErrorField.closest('[data-flux-field]') ||
                                         firstErrorField.parentElement;
                    
                    const targetElement = fieldContainer || firstErrorField;
                    
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                        inline: 'nearest'
                    });
                    
                    const input = targetElement.querySelector('input, select, textarea');
                    if (input) {
                        input.focus();
                    }
                }
            }, 200);
        },
        
        /**
         * Handle form submission
         * Validates all fields before allowing Livewire to submit
         */
        handleSubmit(event) {
            // Validate all fields
            const isValid = this.validateAll();
            
            if (!isValid) {
                // Prevent form submission if validation fails
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
            
            // If validation passes, allow Livewire to handle submission
            // Don't prevent default - let Livewire handle it
            return true;
        }
    };
};
