jQuery(document).ready(function ($) {
    // Function to get the user's IP address.
    function getUserIpAddress(callback) {
        // Using ipify.org to get the public IP address.
        $.getJSON('https://api.ipify.org?format=json', function (data) {
            if (data && data.ip) {
                console.log(data.ip);
                callback(data.ip);
            } else {
                console.error('Could not retrieve IP address from ipify.org');
                callback(null);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error(
                'Error fetching IP address:',
                textStatus,
                errorThrown
            );
            callback(null);
        });
    }

    // Function to send IP to PHP via AJAX.
    function sendIpToPhp(ipAddress) {
        if (!ipAddress) {
            console.error('No IP address to send to PHP.');
            return;
        }

        // Make an AJAX request to the WordPress backend.
        $.ajax({
            url: userOrderLimitVerify.ajax_url, // WordPress AJAX URL provided by wp_localize_script.
            type: 'POST',
            data: {
                action: 'order_limit', // This matches the 'wp_ajax_' and 'wp_ajax_nopriv_' hook.
                ip_address: ipAddress,
                nonce: userOrderLimitVerify.nonce, // Security nonce.
            },
            success: function (response) {
                if (response.success) {
                    // PHP returned success. Now check the 'status' data.
                    const isNotTimeout = response.data.status;
                    const message = response.data.message;
                    const status = response.data.status;
                    console.log('PHP Response:', message, status);
                    // If value is true, replace innerHTML of #form-id.
                    if (isNotTimeout) {
                        console.log('chasdfsadf');
                        const formIdElement = $('#fast-checkout_form'); // Target element.
                        if (formIdElement.length) {
                            // Check if the element exists.
                            formIdElement.html(
                                '<div class="order-limit-warning">คุณได้รับสิทธิ์แคมเปญนี้เรียบร้อยแล้ว</div>'
                            );
                            // You can customize the div content and styling here.
                        } else {
                            console.warn(
                                '#form-id element not found on the page.'
                            );
                        }
                    } else {
                        // IP was not timed out, or transient was just set.
                        // You might want to do something else here, or nothing.
                        console.log(
                            'IP was not timed out or transient was just set. No DOM change required.'
                        );
                    }
                } else {
                    // PHP returned an error.
                    console.error('PHP Error:', response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
            },
        });
    }

    // Execute the process: Get IP, then send to PHP.
    getUserIpAddress(sendIpToPhp);
});
