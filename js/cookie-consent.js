/**
 * Cookie Consent Banner for GDPR Compliance
 * Joshi Management Consultancy
 */

(function() {
    'use strict';
    
    // Check if user has already given consent
    const consentCookie = getCookie('cookie_consent');
    
    if (!consentCookie) {
        // Show banner if no consent has been given
        showConsentBanner();
    } else {
        // Load appropriate cookies based on consent
        if (consentCookie === 'all') {
            enableAllCookies();
        }
    }
    
    function showConsentBanner() {
        // Create banner HTML
        const banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.innerHTML = `
            <div class="cookie-consent-content">
                <div class="cookie-consent-text">
                    <h3>🍪 Cookie Consent</h3>
                    <p>We use cookies to improve your experience and analyze site traffic. You can choose to accept all cookies or only essential ones. 
                    <a href="/privacy-policy.html" target="_blank">Learn more in our Privacy Policy</a>.</p>
                </div>
                <div class="cookie-consent-buttons">
                    <button id="accept-all-cookies" class="cookie-btn cookie-btn-primary">Accept All</button>
                    <button id="essential-only-cookies" class="cookie-btn cookie-btn-secondary">Essential Only</button>
                </div>
            </div>
        `;
        
        // Add CSS styles
        const style = document.createElement('style');
        style.textContent = `
            #cookie-consent-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(var(--primary-deep-rgb), 0.98);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                border-top: 1px solid rgba(var(--accent-purple-dark-rgb), 0.3);
                padding: 1.5rem 2rem;
                z-index: 10000;
                box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }

            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .cookie-consent-content {
                max-width: 1400px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 2rem;
                flex-wrap: wrap;
            }

            .cookie-consent-text {
                flex: 1;
                min-width: 300px;
            }

            .cookie-consent-text h3 {
                color: white;
                font-family: var(--font-display);
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .cookie-consent-text p {
                color: rgba(255, 255, 255, 0.8);
                font-family: var(--font-body);
                font-size: 1rem;
                line-height: 1.6;
                margin: 0;
            }

            .cookie-consent-text a {
                color: var(--accent-cyan);
                text-decoration: underline;
                transition: var(--transition);
            }

            .cookie-consent-text a:hover {
                color: var(--accent-blue);
            }

            .cookie-consent-buttons {
                display: flex;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .cookie-btn {
                padding: 0.875rem 2rem;
                font-family: var(--font-display);
                font-size: 1rem;
                font-weight: 600;
                border: none;
                border-radius: 50px;
                cursor: pointer;
                transition: var(--transition);
                white-space: nowrap;
            }

            .cookie-btn-primary {
                background: linear-gradient(135deg, var(--accent-purple-dark) 0%, var(--accent-cyan) 100%);
                color: white;
                box-shadow: 0 4px 16px rgba(var(--accent-purple-dark-rgb), 0.4);
            }

            .cookie-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(var(--accent-cyan-rgb), 0.5);
            }

            .cookie-btn-secondary {
                background: rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(16px);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.18);
            }

            .cookie-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.12);
                border-color: rgba(255, 255, 255, 0.25);
            }

            @media (max-width: 768px) {
                #cookie-consent-banner {
                    padding: 1rem 1.25rem;
                }

                .cookie-consent-content {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 1rem;
                }

                .cookie-consent-text h3 {
                    font-size: 1rem;
                    margin-bottom: 0.25rem;
                }

                .cookie-consent-text p {
                    font-size: 0.875rem;
                    line-height: 1.5;
                }

                .cookie-consent-buttons {
                    flex-direction: row;
                }

                .cookie-btn {
                    flex: 1;
                    padding: 0.625rem 1rem;
                    font-size: 0.875rem;
                    text-align: center;
                }
            }

            @media (max-width: 480px) {
                .cookie-consent-text h3 {
                    display: none;
                }
            }
        `;
        
        // Append style and banner to document
        document.head.appendChild(style);
        document.body.appendChild(banner);
        
        // Add event listeners
        document.getElementById('accept-all-cookies').addEventListener('click', function() {
            setCookie('cookie_consent', 'all', 365);
            enableAllCookies();
            hideBanner();
        });
        
        document.getElementById('essential-only-cookies').addEventListener('click', function() {
            setCookie('cookie_consent', 'essential', 365);
            hideBanner();
        });
    }
    
    function hideBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.style.animation = 'slideDown 0.5s ease-out';
            setTimeout(() => {
                banner.remove();
            }, 500);
        }
    }
    
    function enableAllCookies() {
        // Enable analytics and marketing cookies here
        // Example: Google Analytics
        // if (typeof gtag !== 'undefined') {
        //     gtag('consent', 'update', {
        //         'analytics_storage': 'granted',
        //         'ad_storage': 'granted'
        //     });
        // }
        
        console.log('All cookies enabled');
    }
    
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Strict;Secure';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Add CSS for slideDown animation
    const slideDownStyle = document.createElement('style');
    slideDownStyle.textContent = `
        @keyframes slideDown {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(slideDownStyle);
    
})();
