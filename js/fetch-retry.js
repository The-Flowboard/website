/**
 * Fetch with Retry Logic
 * Provides robust network request handling with exponential backoff
 */

/**
 * Fetch with automatic retry on network failures
 * @param {string} url - The URL to fetch
 * @param {object} options - Fetch options (method, headers, body, etc.)
 * @param {object} retryConfig - Retry configuration
 * @param {number} retryConfig.maxRetries - Maximum number of retry attempts (default: 3)
 * @param {number} retryConfig.initialDelay - Initial delay in ms (default: 1000)
 * @param {number} retryConfig.maxDelay - Maximum delay in ms (default: 10000)
 * @param {function} retryConfig.onRetry - Callback when retry occurs (attempt, delay, error)
 * @returns {Promise} - Fetch response
 */
async function fetchWithRetry(url, options = {}, retryConfig = {}) {
    const {
        maxRetries = 3,
        initialDelay = 1000,
        maxDelay = 10000,
        onRetry = null
    } = retryConfig;

    let lastError;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch(url, options);

            // Check for HTTP errors
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response;

        } catch (error) {
            lastError = error;

            // Don't retry on last attempt
            if (attempt === maxRetries) {
                break;
            }

            // Calculate exponential backoff delay
            const delay = Math.min(initialDelay * Math.pow(2, attempt), maxDelay);

            // Call retry callback if provided
            if (onRetry) {
                onRetry(attempt + 1, delay, error);
            }

            // Wait before retrying
            await new Promise(resolve => setTimeout(resolve, delay));
        }
    }

    // All retries failed
    throw new Error(`Request failed after ${maxRetries + 1} attempts: ${lastError.message}`);
}

/**
 * Check if user is online
 * @returns {boolean} - Whether user has network connection
 */
function isOnline() {
    return navigator.onLine;
}

/**
 * Wait for network to be available
 * @param {number} timeout - Maximum wait time in ms (default: 30000)
 * @returns {Promise<boolean>} - True if network available, false if timeout
 */
function waitForNetwork(timeout = 30000) {
    return new Promise((resolve) => {
        if (isOnline()) {
            resolve(true);
            return;
        }

        const startTime = Date.now();

        const checkConnection = () => {
            if (isOnline()) {
                window.removeEventListener('online', checkConnection);
                resolve(true);
            } else if (Date.now() - startTime >= timeout) {
                window.removeEventListener('online', checkConnection);
                resolve(false);
            }
        };

        window.addEventListener('online', checkConnection);

        // Also check periodically
        const interval = setInterval(() => {
            if (isOnline() || Date.now() - startTime >= timeout) {
                clearInterval(interval);
                checkConnection();
            }
        }, 1000);
    });
}

/**
 * Show user-friendly error message
 * @param {Error} error - The error object
 * @returns {string} - User-friendly error message
 */
function getUserFriendlyError(error) {
    if (!isOnline()) {
        return 'No internet connection. Please check your network and try again.';
    }

    if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
        return 'Network error. Please check your connection and try again.';
    }

    if (error.message.includes('HTTP 500') || error.message.includes('HTTP 502') || error.message.includes('HTTP 503')) {
        return 'Server error. Please try again in a few moments.';
    }

    if (error.message.includes('HTTP 429')) {
        return 'Too many requests. Please wait a moment and try again.';
    }

    if (error.message.includes('HTTP 400')) {
        return 'Invalid request. Please check your input and try again.';
    }

    if (error.message.includes('HTTP 401') || error.message.includes('HTTP 403')) {
        return 'Authentication error. Please refresh the page and try again.';
    }

    if (error.message.includes('timeout')) {
        return 'Request timed out. Please try again.';
    }

    return 'An error occurred. Please try again.';
}

/**
 * Create a loading indicator
 * @param {string} message - Loading message
 * @returns {HTMLElement} - Loading indicator element
 */
function createLoadingIndicator(message = 'Loading...') {
    const loader = document.createElement('div');
    loader.className = 'fetch-retry-loader';
    loader.innerHTML = `
        <div class="loader-spinner"></div>
        <div class="loader-message">${message}</div>
    `;
    return loader;
}

/**
 * Show retry notification
 * @param {number} attempt - Current retry attempt
 * @param {number} delay - Delay until next retry in ms
 */
function showRetryNotification(attempt, delay) {
    const notification = document.createElement('div');
    notification.className = 'retry-notification';
    notification.textContent = `Connection issue. Retrying in ${Math.ceil(delay / 1000)}s... (Attempt ${attempt})`;

    document.body.appendChild(notification);

    // Fade in
    setTimeout(() => notification.classList.add('show'), 10);

    // Remove after delay
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, delay);
}

// Add default styles if not already present
if (typeof document !== 'undefined') {
    const styleId = 'fetch-retry-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            .fetch-retry-loader {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
                padding: 2rem;
            }

            .loader-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid rgba(255, 255, 255, 0.1);
                border-top-color: var(--accent-cyan, #06b6d4);
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            .loader-message {
                color: var(--text-secondary, rgba(255, 255, 255, 0.8));
                font-size: 0.9rem;
            }

            .retry-notification {
                position: fixed;
                bottom: 2rem;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: rgba(239, 68, 68, 0.95);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                font-size: 0.9rem;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 10000;
                max-width: 90%;
            }

            .retry-notification.show {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    }
}

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        fetchWithRetry,
        isOnline,
        waitForNetwork,
        getUserFriendlyError,
        createLoadingIndicator,
        showRetryNotification
    };
}
