// assets/js/checkout-form.js
// ใช้ร่วมกับ FC_Popup และ FastCheckout.OTPCtrl (REST)
(function (w, d) {
    'use strict';

    function restNonce() {
        return (
            (w.fc_rest && w.fc_rest.nonce) ||
            (w.wpApiSettings && w.wpApiSettings.nonce) ||
            ''
        );
    }

    const form = document.getElementById('fast-checkout_form');
    if (!form) return;

    // ป้องกัน double-bind
    if (form.__fcBound) return;
    form.__fcBound = true;

    // หาเบอร์โทรจากฟอร์ม (ปรับ selector ได้ตามจริง)
    const getMsisdn = () => {
        const el = form.querySelector(
            '#billing_phone, [name="billing_phone"], .billing_phone'
        );
        return (el && el.value ? String(el.value) : '').trim();
    };

    // เพิ่ม hidden input ลงฟอร์มเพื่อส่งต่อไป backend
    // const setHidden = (name, value) => {
    //     let el = form.querySelector(`input[name="${name}"]`);
    //     if (!el) {
    //         el = document.createElement('input');
    //         el.type = 'hidden';
    //         el.name = name;
    //         form.appendChild(el);
    //     }
    //     el.value = value;
    // };

    // check dependencies
    // โหลดมาจากไฟล์ popup.js กับ otp-controller.js
    const ensureDeps = () => {
        if (
            typeof FC_Popup !== 'object' ||
            typeof FC_Popup.open !== 'function'
        ) {
            alert('ไม่มีป๊อปอัป (FC_Popup)');
            return false;
        }
        if (!window.FastCheckout || !window.FastCheckout.OTPCtrl) {
            alert('ไม่มี FastCheckout.OTPCtrl');
            return false;
        }
        return true;
    };

    // กันกดซ้ำตอน submit
    let submitting = false;

    // ถ้ามีปุ่ม submit
    const submitBtn =
        form.querySelector('.elm-fastcheckout-submit, [type="submit"]') || null;

    const setSubmitting = (state) => {
        submitting = !!state;
        if (submitBtn) {
            submitBtn.disabled = submitting;
            submitBtn.setAttribute('aria-busy', submitting ? 'true' : 'false');
        }
    };

    // ===== main submit flow =====
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (submitting) return;

        try {
            if (!ensureDeps()) {
                return;
            }

            const msisdn = getMsisdn();
            if (!msisdn) {
                alert('กรุณากรอกหมายเลขโทรศัพท์');
                return;
            }

            // Validate phone number format
            if (!msisdn.match(/^(0(?:2|3|4|5|7)\d{7}|0(?:6|8|9)\d{8})$/)) {
                alert('กรุณากรอกหมายเลขโทรศัพท์ให้ถูกต้อง');
                return;
            }

            setSubmitting(true);
            console.log('Request OTP for', msisdn);

            const client_fingerprint = window.fc_fingerprint || undefined;

            // ----------------------------------
            //
            // OTP VERIFY
            //
            // ----------------------------------
            const result = await window.FastCheckout.OTPCtrl.requestAndPrompt(
                msisdn,
                {
                    client_fingerprint,
                }
            );

            if (!result || !result.status) {
                alert('ไม่สามารถยืนยัน OTP ได้');
                setSubmitting(false);
                return;
            }

            // ----------------------------------
            //
            // CREATE ORDER
            //
            // ----------------------------------
            const formData = new FormData(form);
            console.log('Form data:', Array.from(formData.entries()));
            const orderData = {
                otp_token: result.otp_token,
                payment_method: formData.get('checkout_payment') || 'cod',
                customer: {
                    first_name: (() => {
                        const fullName =
                            formData.get('billing_first_name') || '';
                        return fullName.split(' ')[0] || '';
                    })(),
                    last_name: (() => {
                        const fullName =
                            formData.get('billing_first_name') || '';
                        const parts = fullName.split(' ');
                        return parts.slice(1).join(' ') || '';
                    })(),
                    full_name: formData.get('billing_first_name') || '',
                    email: formData.get('billing_email'),
                    phone: msisdn,
                },
                shipping_address: {
                    address_1: formData.get('billing_address_1'),
                    address_2: formData.get('billing_address_2') || '',
                    city: formData.get('billing_city'),
                    state: formData.get('billing_state'),
                    postcode: formData.get('billing_postcode'),
                    country: 'TH',
                },
                items: [
                    {
                        product_id: parseInt(formData.get('product_id')),
                        quantity: 1,
                        variation_id: formData.get('variation_id')
                            ? parseInt(formData.get('variation_id'))
                            : undefined,
                    },
                ],
                policy_consents: {
                    privacy: formData.get('policy_consent_privacy') === 'on',
                    advertising: formData.get('policy_consent_ad') === 'on',
                },
                limit_timeout_hours: parseInt(
                    formData.get('limit_timeout_hours') || '1'
                ),
            };

            console.log('Submitting order data:', orderData);

            // Submit order to backend
            const response = await fetch(
                FC_Config.rest_base + FC_Config.endpoints.order_create,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        ...(restNonce() ? { 'X-WP-Nonce': restNonce() } : {}),
                        ...(w.FC_Config?.csrf
                            ? { 'x-fc-csrf': w.FC_Config.csrf }
                            : {}),
                    },
                    body: JSON.stringify(orderData),
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            if (!responseText) {
                throw new Error('Empty response from server');
            }

            const orderResult = JSON.parse(responseText);
            if (orderResult && !orderResult.error) {
                // Show success popup with order summary
                console.log('Order successful:', orderResult);
                showOrderSuccessPopup(orderResult);
            } else {
                const errorMsg =
                    orderResult?.error || 'เกิดข้อผิดพลาดในการสั่งซื้อ';
                alert('ข้อผิดพลาด: ' + errorMsg);
                setSubmitting(false);
            }
        } catch (err) {
            console.error('Form submission error:', err);

            let errorMessage = 'เกิดข้อผิดพลาดในการส่งข้อมูล';
            if (err.message.includes('HTTP error')) {
                errorMessage =
                    'เซิร์ฟเวอร์ตอบกลับด้วยข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
            } else if (err.message.includes('Empty response')) {
                errorMessage =
                    'ไม่ได้รับการตอบกลับจากเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง';
            } else if (err.message.includes('Invalid JSON')) {
                errorMessage = 'ข้อมูลที่ได้รับจากเซิร์ฟเวอร์ไม่ถูกต้อง';
            }

            alert('ข้อผิดพลาด: ' + errorMessage);
            setSubmitting(false);
        }
    });

    // Function to show order success popup
    const showOrderSuccessPopup = (orderData) => {
        const {
            order_id: id,
            summary: {
                billing: {
                    first_name,
                    last_name,
                    phone,
                    email,
                    address_1,
                    address_2,
                    city,
                    state,
                    postcode,
                },
                total,
                currency,
                payment_method_title,
            },
        } = orderData;
        // console.log(
        //     id,
        //     first_name,
        //     last_name,
        //     phone,
        //     email,
        //     address_1,
        //     address_2,
        //     city,
        //     postcode,
        //     total,
        //     currency,
        //     payment_method_title
        // );

        // Use currency as currency_symbol since that's what the template expects
        const currency_symbol = currency;

        const popupContent = `
            <div class="order-success-content">
               
                <div class="success-header">
                 <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" fill="#439d55" viewBox="0 0 256 256">
                        <path d="M225.86,102.82c-3.77-3.94-7.67-8-9.14-11.57-1.36-3.27-1.44-8.69-1.52-13.94-.15-9.76-.31-20.82-8-28.51s-18.75-7.85-28.51-8c-5.25-.08-10.67-.16-13.94-1.52-3.56-1.47-7.63-5.37-11.57-9.14C146.28,23.51,138.44,16,128,16s-18.27,7.51-25.18,14.14c-3.94,3.77-8,7.67-11.57,9.14C88,40.64,82.56,40.72,77.31,40.8c-9.76.15-20.82.31-28.51,8S41,67.55,40.8,77.31c-.08,5.25-.16,10.67-1.52,13.94-1.47,3.56-5.37,7.63-9.14,11.57C23.51,109.72,16,117.56,16,128s7.51,18.27,14.14,25.18c3.77,3.94,7.67,8,9.14,11.57,1.36,3.27,1.44,8.69,1.52,13.94.15,9.76.31,20.82,8,28.51s18.75,7.85,28.51,8c5.25.08,10.67.16,13.94,1.52,3.56,1.47,7.63,5.37,11.57,9.14C109.72,232.49,117.56,240,128,240s18.27-7.51,25.18-14.14c3.94-3.77,8-7.67,11.57-9.14,3.27-1.36,8.69-1.44,13.94-1.52,9.76-.15,20.82-.31,28.51-8s7.85-18.75,8-28.51c.08-5.25.16-10.67,1.52-13.94,1.47-3.56,5.37-7.63,9.14-11.57C232.49,146.28,240,138.44,240,128S232.49,109.73,225.86,102.82Zm-11.55,39.29c-4.79,5-9.75,10.17-12.38,16.52-2.52,6.1-2.63,13.07-2.73,19.82-.1,7-.21,14.33-3.32,17.43s-10.39,3.22-17.43,3.32c-6.75.1-13.72.21-19.82,2.73-6.35,2.63-11.52,7.59-16.52,12.38S132,224,128,224s-9.15-4.92-14.11-9.69-10.17-9.75-16.52-12.38c-6.1-2.52-13.07-2.63-19.82-2.73-7-.1-14.33-.21-17.43-3.32s-3.22-10.39-3.32-17.43c-.1-6.75-.21-13.72-2.73-19.82-2.63-6.35-7.59-11.52-12.38-16.52S32,132,32,128s4.92-9.15,9.69-14.11,9.75-10.17,12.38-16.52c2.52-6.1,2.63-13.07,2.73-19.82.1-7,.21-14.33,3.32-17.43S70.51,56.9,77.55,56.8c6.75-.1,13.72-.21,19.82-2.73,6.35-2.63,11.52-7.59,16.52-12.38S124,32,128,32s9.15,4.92,14.11,9.69,10.17,9.75,16.52,12.38c6.1,2.52,13.07,2.63,19.82,2.73,7,.1,14.33.21,17.43,3.32s3.22,10.39,3.32,17.43c.1,6.75.21,13.72,2.73,19.82,2.63,6.35,7.59,11.52,12.38,16.52S224,124,224,128,219.08,137.15,214.31,142.11ZM173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34Z"></path>
                    </svg>
                </div>
                    <h3>สั่งซื้อสำเร็จ</h3>
                    <p>รายละเอียดคำสั่งซื้อจะถูกส่งไปที่อีเมล <strong>${email}</strong></p>
                </div>
                
                
                <div class="order-summary">
                    <div class="order-info">
                        <h4>รายละเอียดคำสั่งซื้อ</h4>
                        <div class="order-details">
                            <p><strong>หมายเลขออเดอร์:</strong> #${id}</p>
                            <p><strong>ยอดรวม:</strong> ${total} ${currency_symbol}</p>
                            <p><strong>ช่องทางชำระเงิน:</strong> ${payment_method_title}</p>
                        </div>
                    </div>
                    <div class="order-address">
                        <h4>ที่อยู่จัดส่งสินค้า</h4>
                        <div class="shipping-details">
                            <p><strong>ชื่อ-สกุล:</strong> ${first_name} ${last_name}</p>
                            <p><strong>เบอร์ติดต่อ:</strong> ${phone}</p>
                            <p><strong>ที่อยู่:</strong> ${address_1}${
            address_2 ? ', ' + address_2 : ''
        }, ${city}, ${state}, ${postcode}</p>
                        </div>
                    </div>
                </div>
                
                <div class="popup-actions">
                    <button type="button" class="btn-new-order">สั่งซื้อใหม่</button>
                    <button type="button" class="btn-save-order">บันทึกคำสั่งซื้อ</button>
                </div>
            </div>
        `;

        // Use FC_Popup to show success message
        FC_Popup.open(popupContent);

        // Handle popup actions
        const saveOrderBtn = document.querySelector('.btn-save-order');
        const newOrderBtn = document.querySelector('.btn-new-order');

        if (saveOrderBtn) {
            saveOrderBtn.addEventListener('click', () => {
                FC_Popup.save();
                setSubmitting(false);
            });
        }

        if (newOrderBtn) {
            newOrderBtn.addEventListener('click', () => {
                FC_Popup.close();
                window.location.reload();
            });
        }

        // if order success and user close popup
        // reload page to clear form
        const popupEl = document.getElementById('fc-popup');
        if (popupEl) {
            const observer = new MutationObserver((mutationsList) => {
                for (const mutation of mutationsList) {
                    if (
                        mutation.type === 'attributes' &&
                        mutation.attributeName === 'class'
                    ) {
                        if (popupEl.classList.contains('hidden')) {
                            // popup is closed
                            window.location.reload();
                        }
                    }
                }
            });

            observer.observe(popupEl, { attributes: true });
        }
    };

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

    initializeThailandAddress();
    initializeAddressToggle();
})(window, document);
