/**
 * AI Opportunity Assessment Tool
 * Multi-step questionnaire with scoring algorithm and personalized results
 */

class AssessmentTool {
    constructor() {
        this.currentStep = 0;
        this.responses = {
            // Initialize pain scale questions with 0 as default
            Q5: 0,
            Q6: 0,
            Q7: 0,
            Q8: 0,
            Q9: 0,
            Q10: 0
        };
        this.totalSteps = 5; // 4 question sections + 1 contact form
        this.results = null;

        this.init();
    }

    init() {
        this.render();
        this.initializeEventDelegation(); // Use event delegation instead
    }

    render() {
        const container = document.getElementById('assessment-embed');
        if (!container) {
            console.error('Assessment container not found');
            return;
        }

        container.innerHTML = this.getHTML();
    }

    getHTML() {
        return `
            <div class="assessment-container">
                <!-- Progress Bar -->
                <div class="assessment-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${(this.currentStep / this.totalSteps) * 100}%"></div>
                    </div>
                    <div class="progress-info">
                        <div class="progress-text">Step ${this.currentStep + 1} of ${this.totalSteps} • ${Math.round((this.currentStep / this.totalSteps) * 100)}% complete</div>
                        <div class="progress-time">~${(this.totalSteps - this.currentStep) * 2} min remaining</div>
                    </div>
                    ${this.renderStepIndicators()}
                </div>

                <!-- Question Sections -->
                <div class="assessment-sections">
                    ${this.renderCurrentSection()}
                </div>

                <!-- Navigation -->
                <div class="assessment-nav">
                    ${this.currentStep > 0 ? '<button class="btn-secondary" id="btnPrev">← Previous</button>' : ''}
                    <button class="btn-primary" id="btnNext">${this.getNextButtonText()}</button>
                </div>
            </div>
        `;
    }

    renderStepIndicators() {
        const stepNames = [
            'Business Context',
            'Pain Points',
            'Strategic Priorities',
            'Readiness',
            'Contact Info'
        ];

        return `
            <div class="step-indicators">
                ${stepNames.map((name, index) => {
                    let statusClass = '';
                    let icon = '';
                    if (index < this.currentStep) {
                        statusClass = 'completed';
                        icon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    } else if (index === this.currentStep) {
                        statusClass = 'current';
                        icon = `<span class="step-number">${index + 1}</span>`;
                    } else {
                        statusClass = 'pending';
                        icon = `<span class="step-number">${index + 1}</span>`;
                    }

                    return `
                        <div class="step-indicator ${statusClass}">
                            <div class="step-icon">${icon}</div>
                            <div class="step-name">${name}</div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    renderCurrentSection() {
        switch(this.currentStep) {
            case 0:
                return this.renderSection1(); // Business Context
            case 1:
                return this.renderSection2(); // Pain Points
            case 2:
                return this.renderSection3(); // Strategic Priorities
            case 3:
                return this.renderSection4(); // Readiness
            case 4:
                return this.renderContactForm(); // Contact Info
            default:
                return '';
        }
    }

    renderSection1() {
        return `
            <div class="section-wrapper">
                <h2 class="section-title">Section 1: Business Context</h2>
                <p class="section-subtitle">Help us understand your business</p>

                <!-- Q1: Company Size -->
                <div class="question-block">
                    <label class="question-label">1. What is your company size?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q1', '1-10 employees', '1-10 employees (Micro)')}
                        ${this.renderRadio('Q1', '11-50 employees', '11-50 employees (Small)')}
                        ${this.renderRadio('Q1', '51-200 employees', '51-200 employees (Medium)')}
                        ${this.renderRadio('Q1', '201-1,000 employees', '201-1,000 employees (Large)')}
                        ${this.renderRadio('Q1', '1,000+ employees', '1,000+ employees (Enterprise)')}
                    </div>
                </div>

                <!-- Q2: Industry -->
                <div class="question-block">
                    <label class="question-label">2. What is your industry?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q2', 'Professional Services', 'Professional Services (Consulting, Legal, Accounting)')}
                        ${this.renderRadio('Q2', 'E-commerce / Retail', 'E-commerce / Retail')}
                        ${this.renderRadio('Q2', 'SaaS / Technology', 'SaaS / Technology')}
                        ${this.renderRadio('Q2', 'Healthcare / Medical', 'Healthcare / Medical')}
                        ${this.renderRadio('Q2', 'Manufacturing / Distribution', 'Manufacturing / Distribution')}
                        ${this.renderRadio('Q2', 'Real Estate', 'Real Estate')}
                        ${this.renderRadio('Q2', 'Financial Services', 'Financial Services')}
                        ${this.renderRadio('Q2', 'Marketing / Agency', 'Marketing / Agency')}
                        ${this.renderRadio('Q2', 'Education / Training', 'Education / Training')}
                        ${this.renderRadio('Q2', 'Other', 'Other')}
                    </div>
                </div>

                <!-- Q3: Annual Revenue -->
                <div class="question-block">
                    <label class="question-label">3. What is your annual revenue range?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q3', 'Under $500K', 'Under $500K')}
                        ${this.renderRadio('Q3', '$500K - $2M', '$500K - $2M')}
                        ${this.renderRadio('Q3', '$2M - $10M', '$2M - $10M')}
                        ${this.renderRadio('Q3', '$10M - $50M', '$10M - $50M')}
                        ${this.renderRadio('Q3', '$50M+', '$50M+')}
                    </div>
                </div>

                <!-- Q4: Tech Comfort -->
                <div class="question-block">
                    <label class="question-label">4. How would you describe your team's comfort with technology?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q4', 'Very comfortable', 'Very comfortable - We already use automation tools and modern software')}
                        ${this.renderRadio('Q4', 'Moderately comfortable', 'Moderately comfortable - We use standard business software (CRM, accounting, etc.)')}
                        ${this.renderRadio('Q4', 'Somewhat comfortable', 'Somewhat comfortable - We\'re open to new technology but need guidance')}
                        ${this.renderRadio('Q4', 'Not very comfortable', 'Not very comfortable - We prefer proven, simple solutions')}
                    </div>
                </div>
            </div>
        `;
    }

    renderSection2() {
        return `
            <div class="section-wrapper">
                <h2 class="section-title">Section 2: Business Pain Points</h2>
                <p class="section-subtitle">Rate the intensity of each challenge (0 = Not a problem, 5 = Critical problem)</p>

                ${this.renderScaleQuestion('Q5', 'Customer Support / Service Delivery', 'How much time does your team spend answering repetitive customer questions or searching for information to help customers?')}
                ${this.renderScaleQuestion('Q6', 'Content Creation & Marketing', 'How much time does your team spend creating content (blogs, social media, emails, proposals, reports)?')}
                ${this.renderScaleQuestion('Q7', 'Sales & Lead Management', 'How much manual work is involved in qualifying leads, following up, or preparing sales materials?')}
                ${this.renderScaleQuestion('Q8', 'Document Processing', 'How much time does your team spend manually processing documents (invoices, contracts, forms, emails)?')}
                ${this.renderScaleQuestion('Q9', 'Internal Knowledge Management', 'How difficult is it for your team to find information (policies, procedures, past work, documentation)?')}
                ${this.renderScaleQuestion('Q10', 'Repetitive Data Tasks', 'How much time does your team spend on repetitive data entry, copying information between systems, or generating reports?')}
            </div>
        `;
    }

    renderSection3() {
        return `
            <div class="section-wrapper">
                <h2 class="section-title">Section 3: Strategic Priorities</h2>
                <p class="section-subtitle">Help us understand your goals</p>

                <!-- Q11: Primary Goal -->
                <div class="question-block">
                    <label class="question-label">11. What is your PRIMARY business goal for the next 12 months? (Select ONE)</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q11', 'Increase revenue / sales', 'Increase revenue / sales')}
                        ${this.renderRadio('Q11', 'Improve customer satisfaction / retention', 'Improve customer satisfaction / retention')}
                        ${this.renderRadio('Q11', 'Reduce operational costs', 'Reduce operational costs')}
                        ${this.renderRadio('Q11', 'Scale operations without adding headcount', 'Scale operations without adding headcount')}
                        ${this.renderRadio('Q11', 'Improve team productivity', 'Improve team productivity')}
                        ${this.renderRadio('Q11', 'Enter new markets / launch new products', 'Enter new markets / launch new products')}
                    </div>
                </div>

                <!-- Q12: Value Priority -->
                <div class="question-block">
                    <label class="question-label">12. What would provide the MOST value to your business? (Select ONE)</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q12', 'Freeing up employee time for higher-value work', 'Freeing up employee time for higher-value work')}
                        ${this.renderRadio('Q12', 'Faster response times to customers', 'Faster response times to customers')}
                        ${this.renderRadio('Q12', 'Higher quality / consistency in deliverables', 'Higher quality / consistency in deliverables')}
                        ${this.renderRadio('Q12', 'Better data and insights for decisions', 'Better data and insights for decisions')}
                        ${this.renderRadio('Q12', 'Competitive advantage / innovation', 'Competitive advantage / innovation')}
                    </div>
                </div>

                <!-- Q13: Budget -->
                <div class="question-block">
                    <label class="question-label">13. What is your realistic budget range for an AI pilot project?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q13', 'Under $10K', 'Under $10K (Proof of concept)')}
                        ${this.renderRadio('Q13', '$10K - $25K', '$10K - $25K (Small pilot)')}
                        ${this.renderRadio('Q13', '$25K - $50K', '$25K - $50K (Moderate project)')}
                        ${this.renderRadio('Q13', '$50K - $100K', '$50K - $100K (Significant investment)')}
                        ${this.renderRadio('Q13', '$100K+', '$100K+ (Enterprise initiative)')}
                        ${this.renderRadio('Q13', 'Not sure yet / Depends on ROI', 'Not sure yet / Depends on ROI')}
                    </div>
                </div>

                <!-- Q14: Timeline -->
                <div class="question-block">
                    <label class="question-label">14. What is your timeline expectation for seeing results?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q14', '1-2 months', '1-2 months (Quick win needed)')}
                        ${this.renderRadio('Q14', '3-6 months', '3-6 months (Standard project timeline)')}
                        ${this.renderRadio('Q14', '6-12 months', '6-12 months (Strategic initiative, phased rollout)')}
                        ${this.renderRadio('Q14', '12+ months', '12+ months (Long-term transformation)')}
                        ${this.renderRadio('Q14', 'Flexible / Depends on the opportunity', 'Flexible / Depends on the opportunity')}
                    </div>
                </div>
            </div>
        `;
    }

    renderSection4() {
        return `
            <div class="section-wrapper">
                <h2 class="section-title">Section 4: Readiness Assessment</h2>
                <p class="section-subtitle">Understanding your organization's readiness</p>

                <!-- Q15: Executive Sponsor -->
                <div class="question-block">
                    <label class="question-label">15. Do you have an executive sponsor who would champion an AI initiative?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q15', 'Yes - C-level executive committed', 'Yes - C-level executive committed')}
                        ${this.renderRadio('Q15', 'Yes - VP/Director level committed', 'Yes - VP/Director level committed')}
                        ${this.renderRadio('Q15', 'Maybe - Need to build the business case first', 'Maybe - Need to build the business case first')}
                        ${this.renderRadio('Q15', 'No - Would need to convince leadership', 'No - Would need to convince leadership')}
                    </div>
                </div>

                <!-- Q16: Data Organization -->
                <div class="question-block">
                    <label class="question-label">16. How is your data currently organized?</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q16', 'Well-organized in centralized systems', 'Well-organized in centralized systems (CRM, databases, cloud storage)')}
                        ${this.renderRadio('Q16', 'Somewhat organized but spread across multiple tools', 'Somewhat organized but spread across multiple tools')}
                        ${this.renderRadio('Q16', 'Not very organized', 'Not very organized - lots of scattered documents and emails')}
                        ${this.renderRadio('Q16', 'Mostly on paper or not digitized', 'Mostly on paper or not digitized')}
                    </div>
                </div>

                <!-- Q17: Biggest Concern -->
                <div class="question-block">
                    <label class="question-label">17. What is your biggest concern about adopting AI? (Select ONE)</label>
                    <div class="radio-group">
                        ${this.renderRadio('Q17', 'Cost / ROI uncertainty', 'Cost / ROI uncertainty')}
                        ${this.renderRadio('Q17', 'Technical complexity / our team\'s ability to use it', 'Technical complexity / our team\'s ability to use it')}
                        ${this.renderRadio('Q17', 'Data security / privacy', 'Data security / privacy')}
                        ${this.renderRadio('Q17', 'Accuracy / reliability concerns', 'Accuracy / reliability concerns')}
                        ${this.renderRadio('Q17', 'Change management / employee adoption', 'Change management / employee adoption')}
                        ${this.renderRadio('Q17', 'Not sure where to start', 'Not sure where to start')}
                    </div>
                </div>
            </div>
        `;
    }

    renderContactForm() {
        return `
            <div class="section-wrapper">
                <h2 class="section-title">Almost Done! Get Your Personalized Results</h2>
                <p class="section-subtitle">Enter your information to receive your AI opportunity assessment</p>

                <div class="contact-form-grid">
                    <div class="form-group">
                        <label for="contactName">Your Name *</label>
                        <input type="text" id="contactName" name="contactName" required
                               value="${this.responses.contactName || ''}"
                               placeholder="John Smith">
                    </div>

                    <div class="form-group">
                        <label for="contactEmail">Email Address *</label>
                        <input type="email" id="contactEmail" name="contactEmail" required
                               value="${this.responses.contactEmail || ''}"
                               placeholder="john@company.com">
                    </div>

                    <div class="form-group">
                        <label for="contactPhone">Phone Number *</label>
                        <input type="tel" id="contactPhone" name="contactPhone" required
                               value="${this.responses.contactPhone || ''}"
                               placeholder="(555) 123-4567">
                    </div>

                    <div class="form-group">
                        <label for="contactCompany">Company Name *</label>
                        <input type="text" id="contactCompany" name="contactCompany" required
                               value="${this.responses.contactCompany || ''}"
                               placeholder="Acme Inc">
                    </div>
                </div>

                <div class="consent-box">
                    <label class="checkbox-label">
                        <input type="checkbox" id="contactConsent"
                               ${this.responses.contactConsent ? 'checked' : ''}>
                        <span>I agree to receive my assessment results and occasional updates about AI opportunities. You can unsubscribe anytime.</span>
                    </label>
                </div>
            </div>
        `;
    }

    renderRadio(name, value, label) {
        const checked = this.responses[name] === value ? 'checked' : '';
        const id = `${name}-${value.replace(/[^a-zA-Z0-9]/g, '_')}`;
        return `
            <label class="radio-label">
                <input type="radio" name="${name}" value="${value}" id="${id}" ${checked}>
                <span class="radio-text">${label}</span>
            </label>
        `;
    }

    renderScaleQuestion(name, title, description) {
        const value = this.responses[name] !== undefined ? this.responses[name] : '';
        return `
            <div class="question-block scale-question">
                <label class="question-label">${name.replace('Q', '')}. ${title}</label>
                <p class="question-description">${description}</p>
                <div class="scale-group">
                    ${[0,1,2,3,4,5].map(n => `
                        <label class="scale-option">
                            <input type="radio" name="${name}" value="${n}" ${value == n ? 'checked' : ''}>
                            <span class="scale-number">${n}</span>
                            <span class="scale-label">${this.getScaleLabel(n)}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    }

    getScaleLabel(num) {
        const labels = {
            0: 'Not a problem',
            1: 'Minor',
            2: 'Noticeable',
            3: 'Moderate',
            4: 'Major',
            5: 'Critical'
        };
        return labels[num] || '';
    }

    getNextButtonText() {
        if (this.currentStep === this.totalSteps - 1) {
            return 'View My Results →';
        }
        return 'Next →';
    }

    /**
     * Initialize event delegation (called once)
     * Uses event delegation to avoid duplicate listeners
     */
    initializeEventDelegation() {
        const container = document.getElementById('assessmentContainer');

        // Single listener for all changes (delegated to container)
        container.addEventListener('change', (e) => {
            if (e.target.type === 'radio') {
                const value = e.target.value;
                this.responses[e.target.name] = value;
            }

            if (e.target.type === 'checkbox') {
                this.responses[e.target.id] = e.target.checked;
            }
        });

        // Single listener for all inputs (delegated to container)
        container.addEventListener('input', (e) => {
            if (e.target.type === 'text' || e.target.type === 'email' || e.target.type === 'tel') {
                this.responses[e.target.id] = e.target.value;
            }
        });

        // Navigation buttons (delegated to container)
        container.addEventListener('click', (e) => {
            if (e.target.id === 'btnPrev' || e.target.closest('#btnPrev')) {
                e.preventDefault();
                this.previousStep();
            }

            if (e.target.id === 'btnNext' || e.target.closest('#btnNext')) {
                e.preventDefault();
                this.nextStep();
            }
        });
    }

    validateCurrentStep() {
        // Get required fields for current step
        const requiredFields = this.getRequiredFieldsForStep(this.currentStep);
        const missingFields = [];
        const fieldLabels = this.getFieldLabels();

        for (let field of requiredFields) {
            // Check if field is undefined, null, or empty string (but allow 0 for numeric scales)
            if (this.responses[field] === undefined || this.responses[field] === null || this.responses[field] === '') {
                missingFields.push(fieldLabels[field] || field);
            }
        }

        if (missingFields.length > 0) {
            const errorMessage = missingFields.length === 1
                ? `Please answer: ${missingFields[0]}`
                : `Please answer the following ${missingFields.length} questions:<br>• ${missingFields.join('<br>• ')}`;
            this.showError(errorMessage);
            return false;
        }

        // Validate contact form
        if (this.currentStep === 4) {
            if (!this.responses.contactName || !this.responses.contactEmail || !this.responses.contactPhone || !this.responses.contactCompany) {
                const missing = [];
                if (!this.responses.contactName) missing.push('Your Name');
                if (!this.responses.contactEmail) missing.push('Email Address');
                if (!this.responses.contactPhone) missing.push('Phone Number');
                if (!this.responses.contactCompany) missing.push('Company Name');
                this.showError(`Please fill in: ${missing.join(', ')}`);
                return false;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.responses.contactEmail)) {
                this.showError('Please enter a valid email address');
                return false;
            }
        }

        return true;
    }

    getFieldLabels() {
        return {
            'Q1': 'Company Size',
            'Q2': 'Industry',
            'Q3': 'Annual Revenue',
            'Q4': 'Tech Comfort Level',
            'Q5': 'Customer Support / Service Delivery',
            'Q6': 'Content Creation & Marketing',
            'Q7': 'Sales & Lead Management',
            'Q8': 'Document Processing',
            'Q9': 'Internal Knowledge Management',
            'Q10': 'Repetitive Data Tasks',
            'Q11': 'Primary Business Goal',
            'Q12': 'Value Priority',
            'Q13': 'Budget Range',
            'Q14': 'Timeline Expectation',
            'Q15': 'Executive Sponsor',
            'Q16': 'Data Organization',
            'Q17': 'Biggest Concern',
            'contactName': 'Your Name',
            'contactEmail': 'Email Address',
            'contactPhone': 'Phone Number',
            'contactCompany': 'Company Name'
        };
    }

    getRequiredFieldsForStep(step) {
        switch(step) {
            case 0: return ['Q1', 'Q2', 'Q3', 'Q4'];
            case 1: return ['Q5', 'Q6', 'Q7', 'Q8', 'Q9', 'Q10'];
            case 2: return ['Q11', 'Q12', 'Q13', 'Q14'];
            case 3: return ['Q15', 'Q16', 'Q17'];
            case 4: return ['contactName', 'contactEmail', 'contactPhone', 'contactCompany'];
            default: return [];
        }
    }

    previousStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this.render();
            this.scrollToTop();
        }
    }

    async nextStep() {
        if (!this.validateCurrentStep()) {
            return;
        }

        if (this.currentStep < this.totalSteps - 1) {
            this.currentStep++;
            this.render();
            this.scrollToTop();
        } else {
            // Final step - submit and show results
            await this.submitAssessment();
        }
    }

    async submitAssessment() {
        try {
            this.showLoading();

            const csrfRes = await fetch('/php/get_csrf_token.php');
            const csrfData = await csrfRes.json();

            const response = await fetchWithRetry('/php/process_assessment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfData.token
                },
                body: JSON.stringify(this.responses)
            }, {
                maxRetries: 3,
                initialDelay: 1500,
                onRetry: (attempt, delay, error) => {
                    console.log(`Assessment submission retry ${attempt} after ${delay}ms:`, error);
                    showRetryNotification(attempt, delay);
                }
            });

            const result = await response.json();

            if (result.success) {
                this.results = result;
                this.showResults();
            } else {
                throw new Error(result.message || 'Assessment processing failed');
            }

        } catch (error) {
            console.error('Assessment submission error:', error);
            const friendlyError = getUserFriendlyError(error);
            this.showError(friendlyError + ' If the problem persists, please contact us directly.');
        }
    }

    showResults() {
        const container = document.getElementById('assessment-embed');
        container.innerHTML = `
            <div class="results-container">
                <h2 class="results-title">Your AI Opportunity Assessment</h2>
                <div class="results-loading">
                    <div class="spinner"></div>
                    <p>Analyzing your responses and generating personalized recommendations...</p>
                </div>
            </div>
        `;

        // Redirect to results page
        setTimeout(() => {
            window.location.href = `/assessment-results.php?id=${this.results.submission_id}`;
        }, 2000);
    }

    showLoading() {
        const btnNext = document.getElementById('btnNext');
        if (btnNext) {
            btnNext.disabled = true;
            btnNext.innerHTML = '<span class="spinner-small"></span> Processing...';
        }
    }

    showError(message) {
        const existing = document.querySelector('.error-message');
        if (existing) {
            existing.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = message; // Use innerHTML to support HTML formatting

        const container = document.querySelector('.assessment-nav');
        container.parentNode.insertBefore(errorDiv, container);

        // Scroll to error message
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Remove after 8 seconds (longer for multiple field errors)
        setTimeout(() => errorDiv.remove(), 8000);
    }

    scrollToTop() {
        const container = document.getElementById('assessment-embed');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

// Initialize assessment when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const assessmentContainer = document.getElementById('assessment-embed');
    if (assessmentContainer) {
        new AssessmentTool();
    }
});
