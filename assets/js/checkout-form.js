// assets/js/checkout-form.js
// ใช้ร่วมกับ FC_Popup และ FastCheckout.OTPCtrl (REST)
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

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
        // ให้ OTP ทำงานก่อนค่อย submit จริง
        e.preventDefault();
        if (submitting) return;
        setSubmitting(true);

        try {
            if (!ensureDeps()) {
                setSubmitting(false);
                return;
            }

            const msisdn = getMsisdn();
            if (!msisdn) {
                alert('กรุณากรอกหมายเลขโทรศัพท์');
                setSubmitting(false);
                return;
            }
            console.log('Request OTP for', msisdn);

            // ถ้าคุณมี client fingerprint ใส่ตรงนี้ได้
            const client_fingerprint = window.fc_fingerprint || undefined;

            // เรียก REST ผ่าน Controller: request -> popup -> verify
            const result = await window.FastCheckout.OTPCtrl.requestAndPrompt(
                msisdn,
                {
                    client_fingerprint,
                }
            );
            console.log('OTP result', result);
            // result = { token, pin, otp_token, expires_in };

            if (!result || !result.status) {
                // ปกติฝั่ง route จะส่ง otp_token มาแล้ว
                alert('ไม่สามารถยืนยัน OTP ได้');
                setSubmitting(false);
                return;
            }

            // setHidden('otp_refno', result.refno || '');
            // setHidden('otp_token', result.otp_token || '');
            // setHidden('otp_code', result.code || '');

            // ===== ไปต่อ ส่งออเดอร์เข้า 1577 =====

            // form.submit();
        } catch (err) {
            console.warn(err);
            setSubmitting(false);
        }
    });
});
