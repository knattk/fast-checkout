(() => {
    try {
        const fieldGroup = document.querySelector('#billing_phone_group');
        const phoneField = document.querySelector('#billing_phone');

        if (!fieldGroup || !phoneField) return;

        phoneField.maxLength = 10;

        // Create progress-wrapper if not exists
        let wrapper = fieldGroup.querySelector('.progress-wrapper');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'progress-wrapper';
            fieldGroup.appendChild(wrapper);
        }

        // Helper to render segments
        const renderSegments = (count) => {
            wrapper.innerHTML = ''; // Clear old segments
            for (let i = 0; i < count; i++) {
                const segment = document.createElement('div');
                segment.className = 'progress-segment';
                wrapper.appendChild(segment);
            }
        };

        // Input event
        phoneField.addEventListener('input', () => {
            phoneField.value = phoneField.value.replace(/\D/g, '');
            const value = phoneField.value;

            let expectedLength = 10;

            if (value.match(/^0(2|3|4|5|7)/)) {
                expectedLength = 9;
            } else if (value.match(/^0(6|8|9)/)) {
                expectedLength = 10;
            }

            renderSegments(expectedLength); // Dynamically adjust segment count
            const segments = wrapper.querySelectorAll('.progress-segment');

            segments.forEach((seg, i) => {
                if (i < value.length && i < expectedLength) {
                    seg.classList.add('active');
                    seg.classList.remove('over');
                } else {
                    seg.classList.remove('active');
                    seg.classList.remove('over');
                }
            });

            // If too long, color overflow red
            if (value.length > expectedLength) {
                for (
                    let i = expectedLength;
                    i < value.length && i < segments.length;
                    i++
                ) {
                    segments[i]?.classList.add('over');
                }
            }

            // Activate wrapper
            if (value.length > 0) {
                wrapper.classList.add('active');
            } else {
                wrapper.classList.remove('active');
            }

            // Add valid class
            if (value.match(/^(0(?:2|3|4|5|7)\d{7}|0(?:6|8|9)\d{8})$/)) {
                phoneField.classList.add('edited');
            } else {
                phoneField.classList.remove('edited');
            }
        });

        // Focus behavior
        phoneField.addEventListener('focus', () => {
            wrapper.classList.add('active');
        });
        phoneField.addEventListener('blur', () => {
            if (!phoneField.value) {
                wrapper.classList.remove('active');
            }
        });
    } catch (error) {
        console.error('Phone field interaction error:', error);
    }
})();

/**
 * Fast Checkout Form JavaScript
 * Handles form submission, address autocomplete, and order confirmation
 */

(function () {
    'use strict';

    // Submission Protection Class
    class FastCheckoutProtection {
        constructor() {
            this.isSubmitting = false;
            this.lastSubmissionTime = 0;
            this.minSubmissionInterval = 3000; // 3 seconds minimum between submissions
            this.submitButton = null;
            this.form = null;
            this.messageContainer = null;
        }

        init(form, submitButton) {
            this.form = form;
            this.submitButton = submitButton;
            this.createMessageContainer();
        }

        createMessageContainer() {
            // Create message container at the top of the form
            this.messageContainer = document.createElement('div');
            this.messageContainer.className = 'fast-checkout-messages';
            this.messageContainer.style.cssText = `
                margin-bottom: 15px;
                position: relative;
                z-index: 1000;
            `;

            if (this.form) {
                this.form.insertBefore(
                    this.messageContainer,
                    this.form.firstChild
                );
            }
        }

        canSubmit() {
            const currentTime = Date.now();

            // Check if already submitting
            if (this.isSubmitting) {
                this.showMessage(
                    'กำลังประมวลผลคำสั่งซื้อของคุณ กรุณารอสักครู่...',
                    'warning'
                );
                return false;
            }

            // Check minimum time interval
            if (
                currentTime - this.lastSubmissionTime <
                this.minSubmissionInterval
            ) {
                this.showMessage(
                    'กรุณารอสักครู่ก่อนส่งข้อมูลอีกครั้ง',
                    'warning'
                );
                return false;
            }

            // Check for duplicate submission
            if (this.checkDuplicateSubmission()) {
                this.showMessage(
                    'ข้อมูลนี้เพิ่งถูกส่งไปแล้ว กรุณารอสักครู่',
                    'warning'
                );
                return false;
            }

            return true;
        }

        startSubmission() {
            this.isSubmitting = true;
            this.lastSubmissionTime = Date.now();
            this.disableSubmitButton();
            this.storeFormSignature();
            this.showMessage('กำลังประมวลผลคำสั่งซื้อ...', 'info');
        }

        onSuccess() {
            this.showMessage('คำสั่งซื้อสำเร็จ!', 'success');
            // Don't reset immediately on success - let the confirmation modal handle it
        }

        onError(errorMessage = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง') {
            this.showMessage(errorMessage, 'error');
            this.resetSubmissionState();
        }

        resetSubmissionState() {
            this.isSubmitting = false;
            this.enableSubmitButton();
        }

        disableSubmitButton() {
            if (!this.submitButton) return;

            this.submitButton.disabled = true;
            this.submitButton.style.opacity = '0.6';
            this.submitButton.style.cursor = 'not-allowed';
            this.submitButton.classList.add('loading');

            // Store original text
            if (!this.submitButton.dataset.originalText) {
                this.submitButton.dataset.originalText =
                    this.submitButton.value || this.submitButton.textContent;
            }

            // Update button text
            if (this.submitButton.tagName === 'INPUT') {
                this.submitButton.value = 'กำลังประมวลผล...';
            } else {
                this.submitButton.textContent = 'กำลังประมวลผล...';
            }
        }

        enableSubmitButton() {
            if (!this.submitButton) return;

            this.submitButton.disabled = false;
            this.submitButton.style.opacity = '1';
            this.submitButton.style.cursor = 'pointer';
            this.submitButton.classList.remove('loading');

            // Restore original text
            if (this.submitButton.dataset.originalText) {
                if (this.submitButton.tagName === 'INPUT') {
                    this.submitButton.value =
                        this.submitButton.dataset.originalText;
                } else {
                    this.submitButton.textContent =
                        this.submitButton.dataset.originalText;
                }
            }
        }

        storeFormSignature() {
            if (!this.form) return;

            const formData = new FormData(this.form);
            const signature = Array.from(formData.entries())
                .filter(([key, value]) => key !== 'fast_checkout_nonce') // Exclude nonce from signature
                .map(([key, value]) => `${key}=${value}`)
                .join('&');

            const signatureHash = this.hashString(signature);

            try {
                localStorage.setItem(
                    'fast_checkout_last_submission',
                    JSON.stringify({
                        signature: signatureHash,
                        timestamp: Date.now(),
                    })
                );
            } catch (e) {
                console.warn(
                    'Could not store form signature in localStorage:',
                    e
                );
            }
        }

        checkDuplicateSubmission() {
            if (!this.form) return false;

            try {
                const stored = localStorage.getItem(
                    'fast_checkout_last_submission'
                );
                if (!stored) return false;

                const data = JSON.parse(stored);
                const currentSignature = this.getFormSignature();

                // Check if same form submitted within last 60 seconds
                if (
                    data.signature === currentSignature &&
                    Date.now() - data.timestamp < 60000
                ) {
                    return true;
                }
            } catch (e) {
                // Invalid stored data, clear it
                localStorage.removeItem('fast_checkout_last_submission');
            }

            return false;
        }

        getFormSignature() {
            if (!this.form) return '';

            const formData = new FormData(this.form);
            const signature = Array.from(formData.entries())
                .filter(([key, value]) => key !== 'fast_checkout_nonce')
                .map(([key, value]) => `${key}=${value}`)
                .join('&');

            return this.hashString(signature);
        }

        hashString(str) {
            let hash = 0;
            if (str.length === 0) return hash;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = (hash << 5) - hash + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash).toString(36);
        }

        showMessage(message, type = 'info') {
            if (!this.messageContainer) return;

            // Remove existing messages of the same type
            const existingMessages = this.messageContainer.querySelectorAll(
                `.fast-checkout-message.${type}`
            );
            existingMessages.forEach((msg) => msg.remove());

            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `fast-checkout-message fast-checkout-${type}`;
            messageDiv.textContent = message;

            // Style the message
            messageDiv.style.cssText = `
                padding: 12px 16px;
                margin-bottom: 10px;
                border-radius: 6px;
                font-weight: 500;
                font-size: 14px;
                line-height: 1.4;
                position: relative;
                animation: slideIn 0.3s ease-out;
                ${this.getMessageStyles(type)}
            `;

            // Add close button for persistent messages
            if (type === 'error' || type === 'warning') {
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '×';
                closeBtn.style.cssText = `
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: inherit;
                    opacity: 0.7;
                `;
                closeBtn.addEventListener('click', () => messageDiv.remove());
                messageDiv.appendChild(closeBtn);
            }

            // Insert message
            this.messageContainer.appendChild(messageDiv);

            // Auto-remove after delay for non-error messages
            if (type !== 'error') {
                setTimeout(
                    () => {
                        if (messageDiv.parentNode) {
                            messageDiv.style.animation =
                                'slideOut 0.3s ease-in';
                            setTimeout(() => messageDiv.remove(), 300);
                        }
                    },
                    type === 'success' ? 3000 : 5000
                );
            }

            // Add animation styles if not already present
            this.addAnimationStyles();
        }

        getMessageStyles(type) {
            const styles = {
                info: 'background-color: #e3f2fd; color: #1565c0; border-left: 4px solid #2196f3;',
                warning:
                    'background-color: #fff8e1; color: #f57c00; border-left: 4px solid #ff9800;',
                error: 'background-color: #ffebee; color: #c62828; border-left: 4px solid #f44336;',
                success:
                    'background-color: #e8f5e8; color: #2e7d32; border-left: 4px solid #4caf50;',
            };

            return styles[type] || styles['info'];
        }

        addAnimationStyles() {
            if (document.getElementById('fast-checkout-animations')) return;

            const style = document.createElement('style');
            style.id = 'fast-checkout-animations';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes slideOut {
                    from {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Auto-reset after timeout (fallback)
        setAutoReset() {
            setTimeout(() => {
                if (this.isSubmitting) {
                    this.onError(
                        'การประมวลผลใช้เวลานานเกินไป กรุณาลองใหม่อีกครั้ง'
                    );
                }
            }, 30000); // 30 seconds timeout
        }
    }

    // Create global protection instance
    const protection = new FastCheckoutProtection();

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        initializeCheckoutForm();
        initializeThailandAddress();
        initializeAddressToggle();
    });

    /**
     * Initialize the checkout form
     */
    function initializeCheckoutForm() {
        const form = document.getElementById('fast-checkout_form');
        const submitButton = document.getElementById('fast-checkout-submit');

        if (!form || !submitButton) return;

        // Initialize protection
        protection.init(form, submitButton);

        // Enhanced submit button click handler
        submitButton.addEventListener('click', function (e) {
            // Check if we can submit
            if (!protection.canSubmit()) {
                e.preventDefault();
                return false;
            }
        });

        form.addEventListener('submit', handleFormSubmission);
    }

    /**
     * Handle form submission with protection
     */
    async function handleFormSubmission(e) {
        e.preventDefault();

        // Final protection check
        if (!protection.canSubmit()) {
            return false;
        }

        // Start submission protection
        protection.startSubmission();
        protection.setAutoReset();

        const form = e.target;
        const formData = buildFormData(form);

        // Add essential security data
        const securityData = await collectEssentialSecurityData();
        Object.assign(formData, securityData);

        try {
            console.log('Submitting form data:', formData); // Debug log

            const response = await fetch(window.fastCheckoutConfig.webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData),
            });

            console.log('Response status:', response.status); // Debug log
            console.log(
                'Response headers:',
                Object.fromEntries(response.headers.entries())
            ); // Debug log

            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Get response text first to check if it's empty
            const responseText = await response.text();
            console.log('Response text:', responseText); // Debug log

            if (!responseText) {
                throw new Error('Empty response from server');
            }

            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON parsing error:', jsonError);
                console.error(
                    'Response text that failed to parse:',
                    responseText
                );
                throw new Error('Invalid JSON response from server');
            }

            console.log('Parsed result:', result); // Debug log

            if (result && !result.error) {
                protection.onSuccess();
                showOrderConfirmation(result);
                // Form will be reset when modal is closed
            } else {
                const errorMsg = result?.error || 'เกิดข้อผิดพลาดในการประมวลผล';
                protection.onError(errorMsg);
                console.warn('API error:', result?.error);
            }
        } catch (error) {
            console.error('Form submission error:', error);

            // Provide more specific error messages
            let errorMessage = 'เกิดข้อผิดพลาดในการส่งข้อมูล';

            if (error.message.includes('HTTP error')) {
                errorMessage =
                    'เซิร์ฟเวอร์ตอบกลับด้วยข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
            } else if (error.message.includes('Empty response')) {
                errorMessage =
                    'ไม่ได้รับการตอบกลับจากเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง';
            } else if (error.message.includes('Invalid JSON')) {
                errorMessage =
                    'ข้อมูลที่ได้รับจากเซิร์ฟเวอร์ไม่ถูกต้อง กรุณาติดต่อผู้ดูแลระบบ';
            } else if (
                error.name === 'TypeError' &&
                error.message.includes('fetch')
            ) {
                errorMessage =
                    'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต';
            }

            protection.onError(errorMessage);
        }
    }

    // Collect only the most important security data
    async function collectEssentialSecurityData() {
        return {
            // Critical tracking data
            user_ip: (await getClientIP()) || '0.0.0.0',
            user_agent: navigator.userAgent,
            timestamp: new Date().toISOString(),

            // Device fingerprinting (most effective)
            screen_resolution: `${screen.width}x${screen.height}`,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,

            // Session tracking
            // session_id: getOrCreateSessionId(),
            referrer: document.referrer,

            // Bot detection
            touch_support:
                'ontouchstart' in window || navigator.maxTouchPoints > 0,
        };
    }

    // Get client IP
    async function getClientIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch (error) {
            console.error('Failed to get client IP:', error);
            return null;
        }
    }

    // Session ID management
    function getOrCreateSessionId() {
        let sessionId = sessionStorage.getItem('form_session_id');
        if (!sessionId) {
            sessionId =
                'sess_' +
                Math.random().toString(36).substr(2, 9) +
                Date.now().toString(36);
            sessionStorage.setItem('form_session_id', sessionId);
        }
        return sessionId;
    }

    /**
     * Build form data object
     */
    function buildFormData(form) {
        return {
            fast_checkout_nonce: form.fast_checkout_nonce.value,
            limit_timeout_hours: form.limit_timeout_hours.value,
            fields: {
                payment: { value: form.checkout_payment.value },
                product_id: { value: form.product_id.value },
                billing_full_name: { value: form.billing_first_name.value },
                billing_address_1: { value: form.billing_address_1.value },
                billing_address_2: { value: form.billing_address_2.value },
                billing_city: { value: form.billing_city.value },
                billing_state: { value: form.billing_state.value },
                billing_postcode: { value: form.billing_postcode.value },
                billing_phone: { value: form.billing_phone.value },
                billing_email: { value: form.billing_email.value },
                policy_consent_privacy: {
                    value: form.policy_consent_privacy.value,
                },
                policy_consent_ad: { value: form.policy_consent_ad.value },
            },
        };
    }

    /**
     * Show order confirmation by replacing form content
     */
    function showOrderConfirmation(result) {
        const {
            billing: {
                first_name,
                last_name,
                phone,
                email,
                address_1,
                address_2,
                city,
                postcode,
            },
            id,
            total,
            discount_total,
            currency_symbol,
            payment_method_title,
        } = result;

        const form = document.getElementById('fast-checkout_form');
        if (!form) return;

        // Store original form content for potential restoration
        const originalFormContent = form.innerHTML;

        const confirmationHTML = `
            <div class="fast-checkout-order-confirmation">
                <div class="confirm-heading">
                    ${getSuccessIcon()}
                    <h3>สั่งซื้อสำเร็จ</h3>
                </div>
                <div class="confirm-email">
                    รายละเอียดคำสั่งซื้อจะถูกส่งไปที่อีเมล <strong>${email}</strong>
                </div>
                <div class="confirm-details">
                    <h4>รายละเอียดคำสั่งซื้อ</h4>
                    <div class="details">
                        <p><strong>หมายเลขออเดอร์:</strong> #${id}</p>
                        <p><strong>ยอดรวม:</strong> ${total} ${currency_symbol}</p>
                        <p><strong>ช่องทางชำระเงิน:</strong> ${payment_method_title}</p>
                    </div>
                </div>
                <div class="confirm-details">
                    <h4>ที่อยู่จัดส่งสินค้า</h4>
                    <div class="address-info">
                        <p><strong>ชื่อ-สกุล:</strong> ${first_name} ${last_name}</p>
                        <p><strong>เบอร์ติดต่อ:</strong> ${phone}</p>
                        <p><strong>อีเมล:</strong> ${email}</p>
                        <p><strong>ที่อยู่:</strong> ${address_1}${
            address_2 ? ', ' + address_2 : ''
        }, ${city}, ${postcode}</p>
                    </div>
                </div>
                <div class="confirmation-actions">
                    <button type="button" class="btn-save-image">บันทึกคำสั่งซื้อ</button>
                    <button type="button" class="btn-new-order">สั่งซื้อใหม่</button>
                </div>
            </div>
        `;

        // Replace form content with confirmation
        form.innerHTML = confirmationHTML;
        form.classList.add('confirmation-mode');

        // Scroll to top of form smoothly
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Handle save as image button
        const saveImageBtn = form.querySelector('.btn-save-image');
        if (saveImageBtn) {
            saveImageBtn.addEventListener('click', () => {
                saveConfirmationAsImage(
                    form.querySelector('.fast-checkout-order-confirmation')
                );
            });
        }
        const newOrderBtn = form.querySelector('.btn-new-order');
        if (newOrderBtn) {
            newOrderBtn.addEventListener('click', () => {
                // Restore original form content
                // form.innerHTML = originalFormContent;
                // form.classList.remove('confirmation-mode');

                // Reset protection state
                protection.resetSubmissionState();

                // Re-initialize form (since we replaced the HTML)
                // setTimeout(() => {
                //     initializeCheckoutForm();
                //     initializeThailandAddress();
                //     initializeAddressToggle();
                // }, 100);
                window.location.reload();
                // Scroll back to form
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    /**
     * Save confirmation as image using html2canvas
     */
    async function saveConfirmationAsImage(confirmationElement) {
        try {
            // Load html2canvas library if not already loaded
            if (typeof html2canvas === 'undefined') {
                await loadHtml2Canvas();
            }

            // Hide buttons temporarily for clean screenshot
            const actionsDiv = confirmationElement.querySelector(
                '.confirmation-actions'
            );
            const originalDisplay = actionsDiv.style.display;
            actionsDiv.style.display = 'none';

            // Generate canvas from the confirmation element
            const canvas = await html2canvas(confirmationElement, {
                backgroundColor: '#ffffff',
                scale: 2, // Higher quality
                useCORS: true,
                allowTaint: true,
                height: confirmationElement.scrollHeight,
                width: confirmationElement.scrollWidth,
                scrollX: 0,
                scrollY: 0,
            });

            // Restore buttons
            actionsDiv.style.display = originalDisplay;

            // Convert canvas to blob
            canvas.toBlob((blob) => {
                // Create download link
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;

                // Get order ID for filename
                const orderIdElement = confirmationElement.querySelector(
                    '.details p:first-child'
                );
                const orderId = orderIdElement
                    ? orderIdElement.textContent.match(/#(\d+)/)?.[1] || 'order'
                    : 'order';

                link.download = `order-confirmation-${orderId}-${new Date().getTime()}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                // Show success message
                protection.showMessage(
                    'บันทึกคำสั่งซื้อเรียบร้อยแล้ว',
                    'success'
                );
            }, 'image/png');
        } catch (error) {
            console.error('Error saving image:', error);
            protection.showMessage(
                'ไม่สามารถบันทึกภาพได้ กรุณาลองใหม่อีกครั้ง',
                'error'
            );
        }
    }

    /**
     * Load html2canvas library dynamically
     */
    function loadHtml2Canvas() {
        return new Promise((resolve, reject) => {
            if (typeof html2canvas !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src =
                'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        protection.onError(message);
    }

    /**
     * Get success icon SVG
     */
    function getSuccessIcon() {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" fill="#439d55" viewBox="0 0 256 256">
                <path d="M225.86,102.82c-3.77-3.94-7.67-8-9.14-11.57-1.36-3.27-1.44-8.69-1.52-13.94-.15-9.76-.31-20.82-8-28.51s-18.75-7.85-28.51-8c-5.25-.08-10.67-.16-13.94-1.52-3.56-1.47-7.63-5.37-11.57-9.14C146.28,23.51,138.44,16,128,16s-18.27,7.51-25.18,14.14c-3.94,3.77-8,7.67-11.57,9.14C88,40.64,82.56,40.72,77.31,40.8c-9.76.15-20.82.31-28.51,8S41,67.55,40.8,77.31c-.08,5.25-.16,10.67-1.52,13.94-1.47,3.56-5.37,7.63-9.14,11.57C23.51,109.72,16,117.56,16,128s7.51,18.27,14.14,25.18c3.77,3.94,7.67,8,9.14,11.57,1.36,3.27,1.44,8.69,1.52,13.94.15,9.76.31,20.82,8,28.51s18.75,7.85,28.51,8c5.25.08,10.67.16,13.94,1.52,3.56,1.47,7.63,5.37,11.57,9.14C109.72,232.49,117.56,240,128,240s18.27-7.51,25.18-14.14c3.94-3.77,8-7.67,11.57-9.14,3.27-1.36,8.69-1.44,13.94-1.52,9.76-.15,20.82-.31,28.51-8s7.85-18.75,8-28.51c.08-5.25.16-10.67,1.52-13.94,1.47-3.56,5.37-7.63,9.14-11.57C232.49,146.28,240,138.44,240,128S232.49,109.73,225.86,102.82Zm-11.55,39.29c-4.79,5-9.75,10.17-12.38,16.52-2.52,6.1-2.63,13.07-2.73,19.82-.1,7-.21,14.33-3.32,17.43s-10.39,3.22-17.43,3.32c-6.75.1-13.72.21-19.82,2.73-6.35,2.63-11.52,7.59-16.52,12.38S132,224,128,224s-9.15-4.92-14.11-9.69-10.17-9.75-16.52-12.38c-6.1-2.52-13.07-2.63-19.82-2.73-7-.1-14.33-.21-17.43-3.32s-3.22-10.39-3.32-17.43c-.1-6.75-.21-13.72-2.73-19.82-2.63-6.35-7.59-11.52-12.38-16.52S32,132,32,128s4.92-9.15,9.69-14.11,9.75-10.17,12.38-16.52c2.52-6.1,2.63-13.07,2.73-19.82.1-7,.21-14.33,3.32-17.43S70.51,56.9,77.55,56.8c6.75-.1,13.72-.21,19.82-2.73,6.35-2.63,11.52-7.59,16.52-12.38S124,32,128,32s9.15,4.92,14.11,9.69,10.17,9.75,16.52,12.38c6.1,2.52,13.07,2.63,19.82,2.73,7,.1,14.33.21,17.43,3.32s3.22,10.39,3.32,17.43c.1,6.75.21,13.72,2.73,19.82,2.63,6.35,7.59,11.52,12.38,16.52S224,124,224,128,219.08,137.15,214.31,142.11ZM173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34Z"></path>
            </svg>
        `;
    }

    /**
     * Initialize Thailand address autocomplete
     */
    function initializeThailandAddress() {
        // Check if jQuery and Thailand.js are loaded
        if (typeof $ !== 'undefined' && typeof $.Thailand !== 'undefined') {
            $.Thailand({
                $district: $('#billing_address_2'),
                $amphoe: $('#billing_city'),
                $province: $('#billing_state'),
                $zipcode: $('#billing_postcode'),
            });
        }
    }

    /**
     * Initialize address field toggle functionality
     */
    function initializeAddressToggle() {
        const zipcode = document.getElementById('billing_postcode');
        const addressFields = [
            document.getElementById('billing_city'),
            document.getElementById('billing_state'),
            document.getElementById('billing_address_2'),
        ];

        if (!zipcode) return;

        function toggleAddressFields() {
            const hasValue = zipcode.value.trim() !== '';

            addressFields.forEach((field) => {
                if (field) {
                    const wrapper = field.closest('div');
                    if (wrapper) {
                        wrapper.style.display = hasValue ? 'block' : 'none';
                    }
                }
            });
        }

        // Initial state
        toggleAddressFields();

        // Listen for input changes
        zipcode.addEventListener('input', toggleAddressFields);
    }

    // Expose protection instance globally for debugging
    window.fastCheckoutProtection = protection;
})();
