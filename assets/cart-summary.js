document.addEventListener('DOMContentLoaded', function () {
    console.log('cart-summary');
    const CART_KEY = 'fast-checkout-cart';
    const CART_SUMMARY_ID = 'fast-checkout-cart-items';

    function loadCart() {
        try {
            return JSON.parse(sessionStorage.getItem(CART_KEY)) || {};
        } catch {
            return {};
        }
    }
    function formatWithCommas(number) {
        return parseInt(number)
            .toString()
            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    function renderCartSummary() {
        const container = document.getElementById(CART_SUMMARY_ID);
        if (!container) return;

        container.classList.add('loading');

        const cart = loadCart();
        container.innerHTML = '';

        // Render the last added product only
        const productKeys = Object.keys(cart);
        if (productKeys.length === 0) return;

        const lastProductKey = productKeys[productKeys.length - 1];
        const product = cart[lastProductKey];

        const item = document.createElement('div');
        item.className = 'fast-cart-item';
        item.innerHTML = `
      <div class="fast-cart-info">
          <div class="fast-cart-item-image">
            <img src="${product.image}" alt="${product.title}" />
          </div>
          <div class="fast-cart-item-details">
            <strong>${product.title}</strong>
            <span>ราคาปกติ ${formatWithCommas(product.price.regular)}</span>
          </div>
      </div>
           <table class="fast-cart-table">
                
                <tr>
                  <th>ส่วนลด</th>
                  <td class="discount">-฿ ${formatWithCommas(
                      product.price.regular - product.price.sale
                  )}</td>
                </tr>
                <tr>
                  <th>ค่าจัดส่ง</th>
                  <td class="shipping">ฟรี</td>
                </tr>
                <tr>
                  <th>ราคาหลังหักส่วนลด</th>
                  <td class="total">฿ ${formatWithCommas(
                      product.price.sale
                  )}</td>
                </tr>
              </table>
              <div class="fast-saving">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="40" height="40" fill="none"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="92" cy="108" r="12"/><circle cx="164" cy="108" r="12"/><path d="M168,152c-8.3,14.35-22.23,24-40,24s-31.7-9.65-40-24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
              คุณประหยัด  ${formatWithCommas(
                  product.price.regular - product.price.sale
              )} บาทจากโปรโมชั่นนี้
              </div>
             </div>
    `;
        container.appendChild(item);

        setTimeout(() => {
            container.classList.remove('loading');
        }, 1000);
    }

    // Initial render
    setTimeout(() => {
        renderCartSummary();
    }, 500);

    // Optional: re-render on sessionStorage changes if needed
    window.addEventListener('storage', function (event) {
        if (event.key === CART_KEY) {
            renderCartSummary();
        }
    });

    // Add event listener to add-to-cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            renderCartSummary();
        });
    });
});
