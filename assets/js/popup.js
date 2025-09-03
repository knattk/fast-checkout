const FC_Popup = {
    el: document.getElementById('fc-popup'),
    contentEl: null,

    init() {
        if (!this.el) return;
        this.contentEl = this.el.querySelector('.fc-popup-content');

        // close events
        this.el
            .querySelector('.fc-popup-close')
            .addEventListener('click', () => this.close());
        this.el
            .querySelector('.fc-popup-overlay')
            .addEventListener('click', () => this.close());
    },

    open(html) {
        if (!this.el) return;
        this.contentEl.innerHTML = html;
        this.el.classList.remove('hidden');
    },

    close() {
        if (!this.el) return;
        this.el.classList.add('hidden');
        this.contentEl.innerHTML = '';
    },
};

// initialize once DOM is ready
document.addEventListener('DOMContentLoaded', () => FC_Popup.init());
