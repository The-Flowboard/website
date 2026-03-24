/**
 * Contact Form Initialization with Validation Framework
 *
 * @package JMC Website
 * @version 1.0
 * @created January 31, 2026
 */

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');

    // Fetch CSRF token on page load
    fetch('/php/get_csrf_token.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.token) {
                document.getElementById('csrfToken').value = data.token;
                console.log('CSRF token loaded');
            }
        })
        .catch(error => {
            console.error('Failed to load CSRF token:', error);
        });

    // Initialize Form Validator
    const validator = new FormValidator(contactForm, {
        firstName: 'required|min:2|max:100|alpha_dash',
        lastName: 'required|min:2|max:100|alpha_dash',
        email: 'required|email|max:255',
        phone: 'required|phone',
        company: 'required|min:2|max:255',
        referralSource: 'required|in:Search Engine,YouTube,Instagram,LinkedIn,Referral,Other',
        message: 'required|min:10|max:5000',
        consent: 'required'
    }, {
        realTime: true,
        showErrors: true,
        focusFirstError: true,
        scrollToError: true,
        errorClass: 'error',
        validClass: 'valid',
        customMessages: {
            firstName: {
                required: 'Please enter your first name',
                min: 'First name must be at least 2 characters',
                alpha_dash: 'First name can only contain letters, numbers, dashes, and underscores'
            },
            lastName: {
                required: 'Please enter your last name',
                min: 'Last name must be at least 2 characters',
                alpha_dash: 'Last name can only contain letters, numbers, dashes, and underscores'
            },
            email: {
                required: 'Please enter your email address',
                email: 'Please enter a valid email address'
            },
            phone: {
                required: 'Please enter your phone number',
                phone: 'Please enter a valid phone number'
            },
            company: {
                required: 'Please enter your company name',
                min: 'Company name must be at least 2 characters'
            },
            referralSource: {
                required: 'Please select how you heard about us'
            },
            message: {
                required: 'Please enter your message',
                min: 'Message must be at least 10 characters'
            },
            consent: {
                required: 'You must agree to the privacy policy to continue'
            }
        }
    });

    // Handle form submission
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('Form submitted - validating...');

        // Validator automatically prevents submission if validation fails
        if (validator.hasErrors()) {
            console.log('Validation failed:', validator.getErrors());
            return;
        }

        console.log('Validation passed. Submitting to server...');

        // Get submit button
        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        // Create FormData object
        const formData = new FormData(contactForm);

        // Submit via fetch with retry logic
        fetchWithRetry('/php/contact_handler.php', {
            method: 'POST',
            body: formData
        }, {
            maxRetries: 3,
            initialDelay: 1000,
            onRetry: (attempt, delay, error) => {
                console.log(`Retry attempt ${attempt} after ${delay}ms due to:`, error);
                showRetryNotification(attempt, delay);
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);

            // Try to parse as JSON
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);

                if (data.success) {
                    console.log('Success!');

                    // Show success message
                    successMessage.classList.add('show');

                    // Reset form and validator
                    contactForm.reset();
                    validator.reset();

                    // Scroll to success message
                    successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Hide success message after 10 seconds
                    setTimeout(() => {
                        successMessage.classList.remove('show');
                    }, 10000);
                } else {
                    console.error('Server returned error:', data.message, data.errors);
                    const errorMsg = data.message || 'Unknown error';
                    alert('Error: ' + errorMsg);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was not valid JSON:', text);
                alert('Server error: Invalid response format');
            }

            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        })
        .catch(error => {
            console.error('Form submission error:', error);
            alert('Error: ' + error.message);

            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Retry notification helper
    function showRetryNotification(attempt, delay) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 165, 0, 0.9);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-family: var(--font-body);
        `;
        notification.textContent = `Connection issue. Retrying (${attempt}/3)...`;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, delay + 1000);
    }
});
