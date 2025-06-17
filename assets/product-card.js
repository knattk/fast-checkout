document.addEventListener('DOMContentLoaded', function () {
    const CART_KEY = 'fast-checkout-cart';
    const FORM_SELECTOR = '#fast_checkout';

    // Utility: Extract numeric value from a price string
    const extractNumber = (str) => (str ? str.replace(/[^0-9.]/g, '') : null);

    // Utility: Get product details from a product card element
    const getProductDetails = (card) => {
        const id = card.getAttribute('data-product_id');
        const title = card.querySelector('h3')?.textContent.trim();
        const image = card.querySelector('img')?.getAttribute('src');
        const regularPrice = extractNumber(
            card
                .querySelector('del .woocommerce-Price-amount')
                ?.textContent.trim()
        );
        const salePrice = extractNumber(
            card
                .querySelector('ins .woocommerce-Price-amount')
                ?.textContent.trim()
        );

        return {
            id,
            title,
            image,
            price: { regular: regularPrice, sale: salePrice },
        };
    };

    // Utility: Load cart from sessionStorage
    const loadCart = () => {
        try {
            return JSON.parse(sessionStorage.getItem(CART_KEY)) || {};
        } catch {
            return {};
        }
    };

    // Utility: Save cart to sessionStorage
    const saveCart = (cart) => {
        sessionStorage.setItem(CART_KEY, JSON.stringify(cart));
    };

    // Clear sessionStorage
    sessionStorage.removeItem(CART_KEY);

    // Set first product card to sessionStorage
    const firstCard = document.querySelector('.fast-product-card');
    if (firstCard) {
        const product = getProductDetails(firstCard);
        const cart = { default: product };
        saveCart(cart);
        updateForm();
    }

    // Button click event: update sessionStorage and form
    document.querySelectorAll('.add-to-cart-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const card = this.closest('.fast-product-card');
            if (!card) return;

            const product = getProductDetails(card);
            const fieldId = this.getAttribute('data-field_id');

            const cart = loadCart();
            cart[fieldId] = product;
            saveCart(cart);

            const form = document.querySelector(this.getAttribute('href'));
            const cartContainer = document.querySelector(
                '.fast-checkout-cart-container'
            );

            if (cartContainer) {
                cartContainer.classList.add('active');
                cartContainer.scrollIntoView({ behavior: 'smooth' });
            } else if (form) {
                form.scrollIntoView({ behavior: 'smooth' });
                // form.querySelector(`input[name="form_fields[billing_full_name]"]`)?.focus();
            }

            updateForm();
        });
    });

    // Update form field values based on sessionStorage
    function updateForm() {
        const form = document.querySelector(FORM_SELECTOR);
        if (!form) return;

        const cart = loadCart();
        for (const fieldId in cart) {
            const input = form.querySelector(
                `input[name="form_fields[product_id]"]`
            );
            if (input) input.value = cart[fieldId].id;
        }
    }
});
