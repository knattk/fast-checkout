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

    /* Submit button animation */
    document
        .querySelector('#fast-checkout-submit')
        .addEventListener('click', function () {
            const button = this;
            button.classList.add('loading');

            // Optionally disable the form temporarily
            setTimeout(() => {
                button.classList.remove('loading');
            }, 3000);
        });
})();

/**
 * Fast Checkout Form JavaScript
 * Handles form submission, address autocomplete, and order confirmation
 */

(function () {
    'use strict';

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
        if (!form) return;

        form.addEventListener('submit', handleFormSubmission);
    }

    /**
     * Handle form submission
     */
    // async function handleFormSubmission(e) {
    //     e.preventDefault();

    //     const form = e.target;
    //     const formData = buildFormData(form);

    //     try {
    //         const response = await fetch(window.fastCheckoutConfig.webhookUrl, {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //             },
    //             body: JSON.stringify(formData),
    //         });

    //         const result = await response.json();

    //         // console.log(result);

    //         if (result && !result.error) {
    //             showOrderConfirmation(result);
    //         } else {
    //             console.warn('Line API error:', result.error);
    //         }
    //     } catch (error) {
    //         console.error('Form submission error:', error);
    //         showErrorMessage(
    //             'เกิดข้อผิดพลาดในการส่งข้อมูล กรุณาลองใหม่อีกครั้ง'
    //         );
    //     }
    // }

    async function handleFormSubmission(e) {
        e.preventDefault();

        const form = e.target;
        const formData = buildFormData(form);

        // Add essential security data
        const securityData = await collectEssentialSecurityData();
        Object.assign(formData, securityData);

        try {
            const response = await fetch(window.fastCheckoutConfig.webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (result && !result.error) {
                showOrderConfirmation(result);
            } else {
                console.warn('API error:', result.error);
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showErrorMessage(
                'เกิดข้อผิดพลาดในการส่งข้อมูล กรุณาลองใหม่อีกครั้ง'
            );
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
     * Show order confirmation modal
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

        const confirmationHTML = `
            <div class="fast-checkout-order-confirmation">
                <div class="confirm-heading">
                    ${getSuccessIcon()}
                    <h3>สั่งซื้อสำเร็จ</h3>
                </div>
                <div class="confirm-email">
                    รายละเอียดคำสั่งซื้อจะถูกส่งไปที่อีเมล <span>${email}</span>
                </div>
                <div class="confirm-details">
                    <h4>รายละเอียดคำสั่งซื้อ</h4>
                    <ul>
                        <li><strong>หมายเลขออเดอร์:</strong> ${id}</li>
                        <li><strong>ยอดหลังหักส่วนลด:</strong> ${total} ${currency_symbol}</li>
                        <li><strong>ช่องทางชำระเงิน:</strong> ${payment_method_title}</li>
                    </ul>
                </div>
                <div class="confirm-details">
                    <h4>ที่อยู่จัดส่งสินค้า</h4>
                    <ul>
                        <li><strong>ชื่อ-สกุล:</strong> ${first_name} ${last_name}</li>
                        <li><strong>เบอร์ติดต่อ:</strong> ${phone}</li>
                        <li><strong>อีเมล:</strong> ${email}</li>
                        <li><strong>ที่อยู่:</strong> ${address_1}, ${address_2}, ${city}, ${postcode}</li>
                    </ul>
                </div>
                <button class="close-button" aria-label="ปิด">×</button>
            </div>

           
        `;

        const orderConfirmationBox = document.createElement('div');
        orderConfirmationBox.innerHTML = confirmationHTML;
        orderConfirmationBox.className = 'fast-checkout-modal-overlay';
        document.body.appendChild(orderConfirmationBox);

        // Close button handler
        const closeBtn = orderConfirmationBox.querySelector('.close-button');
        closeBtn.addEventListener('click', () => {
            orderConfirmationBox.remove();
        });

        // Close on overlay click
        orderConfirmationBox.addEventListener('click', (e) => {
            if (e.target === orderConfirmationBox) {
                orderConfirmationBox.remove();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function escapeHandler(e) {
            if (e.key === 'Escape') {
                orderConfirmationBox.remove();
                document.removeEventListener('keydown', escapeHandler);
            }
        });
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const errorHTML = `
            <div class="fast-checkout-error-message">
                <div class="error-content">
                    <h3>เกิดข้อผิดพลาด</h3>
                    <p>${message}</p>
                    <button class="close-button">ปิด</button>
                </div>
            </div>
        `;

        const errorBox = document.createElement('div');
        errorBox.innerHTML = errorHTML;
        errorBox.className = 'fast-checkout-modal-overlay';
        document.body.appendChild(errorBox);

        // Close button handler
        errorBox
            .querySelector('.close-button')
            .addEventListener('click', () => {
                errorBox.remove();
            });
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
})();
