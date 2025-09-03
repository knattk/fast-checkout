// assets/js/otp-controller.js
// REST version matching routes that return:
// - request: { token, refno, status }
// - verify : { message, status }
// UI: FC_Popup must be available.
// Export: window.FastCheckout.OTPCtrl

(function (w, d) {
    'use strict';

    // ===== Utilities =====
    const toStr = (v) => (v == null ? '' : String(v));
    const isSuccessStatus = (s) => {
        const v = toStr(s).toLowerCase();
        return (
            v.startsWith('success') ||
            v === 'ok' ||
            v === 'verified' ||
            v === 'true'
        );
    };

    function restRoot() {
        const r =
            (w.fc_rest && w.fc_rest.root) ||
            (w.wpApiSettings && w.wpApiSettings.root) ||
            '/wp-json/';
        return r.endsWith('/') ? r : r + '/';
    }
    function restNonce() {
        return (
            (w.fc_rest && w.fc_rest.nonce) ||
            (w.wpApiSettings && w.wpApiSettings.nonce) ||
            ''
        );
    }

    function maskPhone(msisdn) {
        if (!msisdn) return '';
        const s = String(msisdn).replace(/\D/g, '');
        if (s.length < 7) return s;
        return s.slice(0, 3) + '-xxx-xxx' + s.slice(-1);
    }

    function createOtpHtml(phoneMasked, cooldownSec) {
        const showTimer = Number(cooldownSec) > 0;
        return `
    <div class="fc-otp fc-otp-box">
      <h2 class="fc-otp-title">ยืนยันรหัส OTP</h2>
      <p class="fc-otp-desc">
        เราได้ส่งรหัส OTP ไปยังเบอร์ <strong>${phoneMasked}</strong><br>
        กรุณากรอกรหัส 4 หลักเพื่อดำเนินการต่อ
      </p>

      <div class="fc-otp-inputs" role="group" aria-label="OTP inputs">
        <input type="text" class="fc-otp-digit" maxlength="1" inputmode="numeric" aria-label="OTP digit 1" />
        <input type="text" class="fc-otp-digit" maxlength="1" inputmode="numeric" aria-label="OTP digit 2" />
        <input type="text" class="fc-otp-digit" maxlength="1" inputmode="numeric" aria-label="OTP digit 3" />
        <input type="text" class="fc-otp-digit" maxlength="1" inputmode="numeric" aria-label="OTP digit 4" />
      </div>

      <p class="fc-otp-noti" style="display:none;">รหัสไม่ถูกต้อง กรุณาลองใหม่</p>

     <button class="fc-otp-resend" ${showTimer ? 'disabled' : ''}>
  ${
      showTimer
          ? `ส่งใหม่ใน <span class="fc-otp-timer">${Number(
                cooldownSec
            )}</span>s`
          : 'ส่งใหม่'
  }
</button>

    </div>
  `;
    }

    const qDigits = (container) =>
        Array.from(container.querySelectorAll('.fc-otp-digit'));

    // ===== Controller =====
    const OTPCtrl = {
        _timerIv: null,
        _lastFocusBackEl: null,
        _onClose: null,

        endpoints: {
            request: () => restRoot() + 'fc/v1/otp/request',
            verify: () => restRoot() + 'fc/v1/otp/verify',
        },

        // --- REST CALLS ---

        /**
         * Request OTP
         * @param {string} msisdn
         * @param {object} extra  // (optional) เผื่อขยายในอนาคต
         * @returns {Promise<{ok:boolean, token?:string, refno?:string, status?:string, message?:string}>}
         */
        async requestOTP(msisdn, extra = {}) {
            const payload = { msisdn: toStr(msisdn) };
            const resp = await fetch(this.endpoints.request(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(restNonce() ? { 'X-WP-Nonce': restNonce() } : {}),
                },
                body: JSON.stringify(payload),
            });

            let json = {};
            try {
                json = await resp.json();
            } catch {}
            const data = json?.data ?? json;
            const status = data?.status;
            if (
                resp.ok &&
                isSuccessStatus(status) &&
                (data?.token || data?.refno)
            ) {
                return {
                    ok: true,
                    token: data.token || '',
                    refno: data.refno || '',
                    status,
                };
            }

            return {
                ok: false,
                status,
                message:
                    data?.message ||
                    json?.message ||
                    data?.error ||
                    'OTP request failed',
            };
        },

        /**
         * Verify OTP
         * @param {string} token
         * @param {string} pin
         * @returns {Promise<{ok:boolean, status?:string, message?:string}>}
         */
        async verifyOTP(token, pin) {
            const payload = { token: toStr(token), pin: toStr(pin) };

            const resp = await fetch(this.endpoints.verify(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(restNonce() ? { 'X-WP-Nonce': restNonce() } : {}),
                },
                body: JSON.stringify(payload),
            });

            let json = {};
            try {
                json = await resp.json();
            } catch {}
            const data = json?.data ?? json;

            const status = data?.status;
            if (resp.ok && isSuccessStatus(status)) {
                return { ok: true, status, message: data?.message || '' };
            }

            return {
                ok: false,
                status,
                message:
                    data?.message ||
                    json?.message ||
                    data?.error ||
                    'OTP verify failed',
            };
        },

        // --- UI FLOW ---

        /**
         * เปิด popup เพื่อกรอก PIN แล้ว verify โดยใช้ token/refno จาก request ที่เพิ่งได้
         * @param {{msisdn:string, token:string, refno:string, cooldown?:number}} opts
         * @returns {Promise<{token:string, refno:string, pin:string, status:string, message?:string}>}
         */
        promptAndWaitVerify(opts) {
            return new Promise((resolve, reject) => {
                if (
                    typeof FC_Popup !== 'object' ||
                    typeof FC_Popup.open !== 'function'
                ) {
                    return reject(
                        new Error('Popup component is not available')
                    );
                }

                const phoneMasked = maskPhone(opts.msisdn);
                const cooldown = Number(opts.cooldown || 0);

                this._lastFocusBackEl = d.activeElement;

                FC_Popup.open(createOtpHtml(phoneMasked, cooldown));
                const popupEl = FC_Popup.el || d.getElementById('fc-popup');
                const content = popupEl?.querySelector('.fc-otp');
                if (!content)
                    return reject(new Error('Popup content not found'));

                const digits = qDigits(content);
                const btnResend = content.querySelector('.fc-otp-resend');
                const notificationEl = content.querySelector('.fc-otp-noti');
                let timerEl = content.querySelector('.fc-otp-timer');

                // inputs
                digits.forEach((inp, idx) => {
                    inp.addEventListener('input', () => {
                        inp.value = inp.value.replace(/\D/g, '').slice(0, 1);
                        if (inp.value && idx < digits.length - 1)
                            digits[idx + 1].focus();
                    });
                    inp.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !inp.value && idx > 0)
                            digits[idx - 1].focus();
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            setTimeout(doVerify, 200);
                        }
                    });
                    inp.addEventListener('paste', (e) => {
                        const clip =
                            (e.clipboardData || w.clipboardData)?.getData(
                                'text'
                            ) || '';
                        const numbers = clip
                            .replace(/\D/g, '')
                            .slice(0, digits.length)
                            .split('');
                        if (numbers.length) {
                            e.preventDefault();
                            digits.forEach(
                                (x, i) => (x.value = numbers[i] || '')
                            );
                            digits[
                                Math.min(numbers.length, digits.length) - 1
                            ]?.focus();
                        }
                    });
                });
                digits[0].focus();

                // timer (optional)
                let remain = cooldown;
                if (timerEl) timerEl.textContent = String(remain);
                if (remain > 0) {
                    btnResend.disabled = true;
                    const tick = () => {
                        remain -= 1;
                        if (timerEl)
                            timerEl.textContent = String(Math.max(0, remain));
                        if (remain <= 0) {
                            clearInterval(this._timerIv);
                            this._timerIv = null;
                            btnResend.disabled = false;
                            btnResend.textContent = 'ส่งใหม่';
                        }
                    };
                    this._timerIv = setInterval(tick, 1000);
                } else {
                    btnResend.disabled = false; // no cooldown => allow immediate resend
                }

                const readPin = () => digits.map((i) => i.value).join('');

                const doVerify = async () => {
                    const pin = readPin();
                    if (pin.length !== 4) {
                        notificationEl.style.display = 'block';
                        notificationEl.textContent = 'กรุณากรอกรหัส 4 หลัก';
                        return;
                    }
                    notificationEl.style.display = 'none';

                    const vr = await this.verifyOTP(opts.token, pin);
                    if (vr.ok) {
                        console.log('OTP verified!', vr);

                        this._showNotification(
                            notificationEl,
                            'ยืนยัน OTP สำเร็จ',
                            false
                        );
                        resolve({
                            token: opts.token,
                            pin,
                            status: vr.status,
                            message: vr.message,
                        });
                    } else {
                        notificationEl.style.display = 'block';
                        notificationEl.textContent =
                            vr.message || 'รหัสไม่ถูกต้อง ลองใหม่';
                        digits.forEach((i) => (i.value = ''));
                        digits[0].focus();
                    }
                };
                // If user complete all digits, auto-submit after slight delay
                digits.forEach((inp) => {
                    inp.addEventListener('input', () => {
                        if (readPin().length === digits.length) {
                            setTimeout(doVerify, 200);
                        }
                    });
                });

                btnResend.addEventListener('click', async () => {
                    if (btnResend.disabled) return;
                    btnResend.disabled = true;
                    btnResend.textContent = 'กำลังส่ง...';

                    const rq = await this.requestOTP(opts.msisdn);
                    console.log('OTP resend result', rq);
                    if (rq.ok) {
                        // อัปเดต token/refno ใหม่
                        opts.token = rq.token || opts.token;
                        opts.refno = rq.refno || opts.refno;

                        // Reset countdown
                        remain = cooldown;
                        if (timerEl) timerEl.textContent = String(remain);

                        // Update button text to show countdown
                        btnResend.innerHTML = `ส่งใหม่ใน <span class="fc-otp-timer">${remain}</span>s`;
                        timerEl = btnResend.querySelector('.fc-otp-timer');

                        // Clear existing timer
                        if (this._timerIv) {
                            clearInterval(this._timerIv);
                            this._timerIv = null;
                        }

                        // Start new countdown
                        if (remain > 0) {
                            const tick = () => {
                                remain -= 1;
                                if (timerEl)
                                    timerEl.textContent = String(
                                        Math.max(0, remain)
                                    );
                                if (remain <= 0) {
                                    clearInterval(this._timerIv);
                                    this._timerIv = null;
                                    btnResend.disabled = false;
                                    btnResend.textContent = 'ส่งใหม่';
                                }
                            };
                            this._timerIv = setInterval(tick, 1000);
                        } else {
                            btnResend.disabled = false;
                            btnResend.textContent = 'ส่งใหม่';
                        }
                    } else {
                        notificationEl.style.display = 'block';
                        notificationEl.textContent =
                            rq.message || 'ส่งใหม่ไม่สำเร็จ';
                        btnResend.disabled = false;
                        btnResend.textContent = 'ส่งใหม่';
                    }
                });

                const onCancel = () => {
                    this._cleanupPopup();
                    reject(new Error('User cancelled'));
                };

                // watch popupEl if contain class hidden (closed) => cancel
                const obs = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (
                            mutation.type === 'attributes' &&
                            mutation.attributeName === 'class'
                        ) {
                            const cls = mutation.target.className || '';
                            if (cls.includes('hidden')) {
                                obs.disconnect();
                                onCancel();
                            }
                        }
                    });
                });
                obs.observe(popupEl, { attributes: true });

                const onKey = (e) => {
                    if (e.key === 'Escape') onCancel();
                };
                d.addEventListener('keydown', onKey, { once: true });

                this._onClose = () => {
                    d.removeEventListener('keydown', onKey);
                    if (this._timerIv) {
                        clearInterval(this._timerIv);
                        this._timerIv = null;
                    }
                };
            });
        },

        _cleanupPopup() {
            try {
                this._onClose && this._onClose();
            } catch {}
            this._onClose = null;
            if (
                typeof FC_Popup === 'object' &&
                typeof FC_Popup.close === 'function'
            ) {
                FC_Popup.close();
            }
            try {
                this._lastFocusBackEl?.focus?.();
            } catch {}
            this._lastFocusBackEl = null;
        },

        _showNotification(target, msg, isError = true) {
            if (isError) {
                target.style.display = 'block';
                target.style.color = 'red';
                target.textContent = msg || 'เกิดข้อผิดพลาด';
            }

            target.style.display = 'block';
            target.style.color = 'green';
            target.textContent = msg || 'สำเร็จ!';
        },
        /**
         * High-level: request -> popup -> verify
         * @param {string} msisdn
         * @param {object} extra // { cooldown?: number } (optional)
         * @returns {Promise<{token:string, refno:string, pin:string, status:string, message?:string}>}
         */
        async requestAndPrompt(msisdn, extra = {}) {
            const rq = await this.requestOTP(msisdn);
            if (!rq.ok) {
                throw new Error(rq.message || 'ส่งรหัส OTP ไม่สำเร็จ');
            }
            return await this.promptAndWaitVerify({
                msisdn,
                token: rq.token,
                refno: rq.refno,
                cooldown: Number(extra.cooldown || 60),
            });
        },
    };

    // export
    w.FastCheckout = w.FastCheckout || {};
    w.FastCheckout.OTPCtrl = OTPCtrl;
})(window, document);
