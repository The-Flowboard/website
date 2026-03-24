/**
 * Form Validation Framework - Client-Side
 *
 * Centralized JavaScript validation utility for all forms
 * Provides real-time validation, consistent error messages, and ARIA support
 *
 * @package JMC Website
 * @version 1.0
 * @created January 31, 2026
 */

class FormValidator {
    /**
     * Constructor
     *
     * @param {HTMLFormElement} form Form element to validate
     * @param {Object} rules Validation rules
     * @param {Object} options Configuration options
     */
    constructor(form, rules = {}, options = {}) {
        this.form = form;
        this.rules = rules;
        this.errors = {};
        this.validated = {};

        // Default options
        this.options = {
            realTime: true,           // Enable real-time validation
            showErrors: true,          // Show error messages
            focusFirstError: true,     // Focus first error field on submit
            scrollToError: true,       // Scroll to first error
            errorClass: 'error',       // CSS class for error state
            errorMessageClass: 'error-message', // CSS class for error messages
            validClass: 'valid',       // CSS class for valid state
            customMessages: {},        // Custom error messages
            ...options
        };

        // Initialize
        this.init();
    }

    /**
     * Initialize validator
     */
    init() {
        // Add form submit listener
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
                this.handleValidationFailure();
            }
        });

        // Add real-time validation
        if (this.options.realTime) {
            this.addRealTimeValidation();
        }
    }

    /**
     * Add real-time validation listeners
     */
    addRealTimeValidation() {
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.elements[fieldName];

            if (field) {
                // Validate on blur (when user leaves field)
                field.addEventListener('blur', () => {
                    this.validateField(fieldName);
                });

                // Clear error on input (when user types)
                field.addEventListener('input', () => {
                    this.clearFieldError(fieldName);
                });

                // Validate on change (for select/checkbox/radio)
                if (field.type === 'select-one' || field.type === 'checkbox' || field.type === 'radio') {
                    field.addEventListener('change', () => {
                        this.validateField(fieldName);
                    });
                }
            }
        });
    }

    /**
     * Validate entire form
     *
     * @returns {boolean} True if valid, false otherwise
     */
    validate() {
        this.errors = {};
        this.validated = {};

        // Clear all existing errors
        Object.keys(this.rules).forEach(fieldName => {
            this.clearFieldError(fieldName);
        });

        // Validate each field
        Object.keys(this.rules).forEach(fieldName => {
            this.validateField(fieldName, false); // false = don't show errors yet
        });

        // Show all errors if validation failed
        if (this.hasErrors() && this.options.showErrors) {
            Object.keys(this.errors).forEach(fieldName => {
                this.showFieldError(fieldName, this.errors[fieldName]);
            });
        }

        return !this.hasErrors();
    }

    /**
     * Validate a single field
     *
     * @param {string} fieldName Field name
     * @param {boolean} showError Show error message immediately
     * @returns {boolean} True if valid, false otherwise
     */
    validateField(fieldName, showError = true) {
        const field = this.form.elements[fieldName];
        if (!field) return true;

        const fieldRules = this.rules[fieldName];
        if (!fieldRules) return true;

        const value = this.getFieldValue(field);
        const label = this.getFieldLabel(field);
        const rules = Array.isArray(fieldRules) ? fieldRules : fieldRules.split('|');

        // Validate against each rule
        for (let rule of rules) {
            let ruleName = rule;
            let ruleParams = [];

            // Parse rule and parameters (e.g., "min:5")
            if (rule.includes(':')) {
                [ruleName, ...ruleParams] = rule.split(':');
                ruleParams = ruleParams.join(':').split(',');
            }

            // Execute validation rule
            const result = this.executeRule(fieldName, value, ruleName, ruleParams, label);

            if (result !== true) {
                this.errors[fieldName] = result;

                if (showError && this.options.showErrors) {
                    this.showFieldError(fieldName, result);
                }

                return false;
            }
        }

        // Field is valid
        delete this.errors[fieldName];
        this.validated[fieldName] = value;

        if (showError) {
            this.clearFieldError(fieldName);
            this.markFieldValid(fieldName);
        }

        return true;
    }

    /**
     * Execute a validation rule
     *
     * @param {string} fieldName Field name
     * @param {*} value Field value
     * @param {string} rule Rule name
     * @param {Array} params Rule parameters
     * @param {string} label Field label
     * @returns {true|string} True if valid, error message if invalid
     */
    executeRule(fieldName, value, rule, params, label) {
        // Check for custom error message
        const customMessage = this.options.customMessages[fieldName]?.[rule];

        switch (rule) {
            case 'required':
                if (!value || (typeof value === 'string' && value.trim() === '')) {
                    return customMessage || `${label} is required.`;
                }
                return true;

            case 'email':
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return customMessage || `${label} must be a valid email address.`;
                }
                return true;

            case 'min':
                const min = parseInt(params[0]) || 0;
                if (value && value.length < min) {
                    return customMessage || `${label} must be at least ${min} characters.`;
                }
                return true;

            case 'max':
                const max = parseInt(params[0]) || 0;
                if (value && value.length > max) {
                    return customMessage || `${label} must not exceed ${max} characters.`;
                }
                return true;

            case 'min_value':
                const minValue = parseFloat(params[0]) || 0;
                if (value && parseFloat(value) < minValue) {
                    return customMessage || `${label} must be at least ${minValue}.`;
                }
                return true;

            case 'max_value':
                const maxValue = parseFloat(params[0]) || 0;
                if (value && parseFloat(value) > maxValue) {
                    return customMessage || `${label} must not exceed ${maxValue}.`;
                }
                return true;

            case 'numeric':
                if (value && !/^-?\d*\.?\d+$/.test(value)) {
                    return customMessage || `${label} must be a number.`;
                }
                return true;

            case 'integer':
                if (value && !/^-?\d+$/.test(value)) {
                    return customMessage || `${label} must be an integer.`;
                }
                return true;

            case 'alpha':
                if (value && !/^[a-zA-Z]+$/.test(value)) {
                    return customMessage || `${label} must contain only letters.`;
                }
                return true;

            case 'alpha_numeric':
                if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
                    return customMessage || `${label} must contain only letters and numbers.`;
                }
                return true;

            case 'alpha_dash':
                if (value && !/^[a-zA-Z0-9_-]+$/.test(value)) {
                    return customMessage || `${label} must contain only letters, numbers, dashes, and underscores.`;
                }
                return true;

            case 'phone':
                if (value && !/^[\d\s\+\(\)\-\.]+$/.test(value)) {
                    return customMessage || `${label} must be a valid phone number.`;
                }
                return true;

            case 'url':
                if (value && !/^https?:\/\/.+\..+/.test(value)) {
                    return customMessage || `${label} must be a valid URL.`;
                }
                return true;

            case 'in':
                if (value && !params.includes(value)) {
                    const options = params.join(', ');
                    return customMessage || `${label} must be one of: ${options}.`;
                }
                return true;

            case 'regex':
                const pattern = new RegExp(params[0]);
                if (value && !pattern.test(value)) {
                    return customMessage || `${label} format is invalid.`;
                }
                return true;

            case 'matches':
                const matchFieldName = params[0];
                const matchField = this.form.elements[matchFieldName];
                const matchValue = this.getFieldValue(matchField);

                if (value && value !== matchValue) {
                    const matchLabel = this.getFieldLabel(matchField);
                    return customMessage || `${label} must match ${matchLabel}.`;
                }
                return true;

            case 'date':
                if (value && isNaN(Date.parse(value))) {
                    return customMessage || `${label} must be a valid date.`;
                }
                return true;

            case 'before':
                const beforeDate = params[0] === 'today' ? new Date() : new Date(params[0]);
                if (value && new Date(value) >= beforeDate) {
                    return customMessage || `${label} must be before ${params[0]}.`;
                }
                return true;

            case 'after':
                const afterDate = params[0] === 'today' ? new Date() : new Date(params[0]);
                if (value && new Date(value) <= afterDate) {
                    return customMessage || `${label} must be after ${params[0]}.`;
                }
                return true;

            default:
                // Unknown rule - skip
                return true;
        }
    }

    /**
     * Get field value
     *
     * @param {HTMLElement} field Form field element
     * @returns {*} Field value
     */
    getFieldValue(field) {
        if (!field) return '';

        if (field.type === 'checkbox') {
            return field.checked ? field.value : '';
        }

        if (field.type === 'radio') {
            const checked = this.form.querySelector(`input[name="${field.name}"]:checked`);
            return checked ? checked.value : '';
        }

        return field.value;
    }

    /**
     * Get field label
     *
     * @param {HTMLElement} field Form field element
     * @returns {string} Field label
     */
    getFieldLabel(field) {
        if (!field) return '';

        // Look for associated label
        const label = this.form.querySelector(`label[for="${field.id}"]`);
        if (label) {
            return label.textContent.replace('*', '').trim();
        }

        // Fallback to field name
        return field.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Show error message for a field
     *
     * @param {string} fieldName Field name
     * @param {string} errorMessage Error message
     */
    showFieldError(fieldName, errorMessage) {
        const field = this.form.elements[fieldName];
        if (!field) return;

        // Add error class to field
        field.classList.add(this.options.errorClass);
        field.classList.remove(this.options.validClass);

        // Set ARIA attributes
        field.setAttribute('aria-invalid', 'true');

        // Get or create error message element
        const errorId = `${fieldName}Error`;
        let errorElement = document.getElementById(errorId);

        if (!errorElement) {
            // Create error message element if it doesn't exist
            errorElement = document.createElement('div');
            errorElement.id = errorId;
            errorElement.className = this.options.errorMessageClass;
            errorElement.setAttribute('role', 'alert');
            errorElement.setAttribute('aria-live', 'polite');

            // Insert after field or after field's parent
            const insertAfter = field.parentElement || field;
            insertAfter.insertAdjacentElement('afterend', errorElement);
        }

        // Set error message
        errorElement.textContent = errorMessage;
        errorElement.style.display = 'block';

        // Associate error with field
        field.setAttribute('aria-describedby', errorId);
    }

    /**
     * Clear error for a field
     *
     * @param {string} fieldName Field name
     */
    clearFieldError(fieldName) {
        const field = this.form.elements[fieldName];
        if (!field) return;

        // Remove error class
        field.classList.remove(this.options.errorClass);

        // Update ARIA
        field.setAttribute('aria-invalid', 'false');

        // Hide error message
        const errorId = `${fieldName}Error`;
        const errorElement = document.getElementById(errorId);

        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    /**
     * Mark field as valid
     *
     * @param {string} fieldName Field name
     */
    markFieldValid(fieldName) {
        const field = this.form.elements[fieldName];
        if (!field) return;

        field.classList.add(this.options.validClass);
        field.classList.remove(this.options.errorClass);
    }

    /**
     * Handle validation failure
     */
    handleValidationFailure() {
        if (this.options.focusFirstError || this.options.scrollToError) {
            const firstErrorField = Object.keys(this.errors)[0];
            if (firstErrorField) {
                const field = this.form.elements[firstErrorField];

                if (this.options.scrollToError) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                if (this.options.focusFirstError) {
                    setTimeout(() => field.focus(), 500);
                }
            }
        }
    }

    /**
     * Check if validation has errors
     *
     * @returns {boolean}
     */
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    /**
     * Get all errors
     *
     * @returns {Object}
     */
    getErrors() {
        return this.errors;
    }

    /**
     * Get error for a specific field
     *
     * @param {string} fieldName Field name
     * @returns {string|null}
     */
    getError(fieldName) {
        return this.errors[fieldName] || null;
    }

    /**
     * Get validated data
     *
     * @returns {Object}
     */
    getValidated() {
        return this.validated;
    }

    /**
     * Reset validator
     */
    reset() {
        this.errors = {};
        this.validated = {};

        Object.keys(this.rules).forEach(fieldName => {
            this.clearFieldError(fieldName);
        });

        this.form.reset();
    }
}

/**
 * Quick validation helper
 *
 * @param {HTMLFormElement} form Form element
 * @param {Object} rules Validation rules
 * @param {Object} options Configuration options
 * @returns {FormValidator} Validator instance
 */
FormValidator.init = function(form, rules, options = {}) {
    return new FormValidator(form, rules, options);
};
