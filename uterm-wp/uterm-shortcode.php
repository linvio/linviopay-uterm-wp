<?php

include_once('uterm-common.php');

function uterm_shortcode($atts) {
    $normalized_atts = shortcode_atts([
        'mode' => 'payment',
    ], $atts, 'uterm');
    $mode = $normalized_atts['mode'];

    if($mode == 'payment_method') {
        return uterm_payment_method_shortcode($atts);
    } else {
        return uterm_payment_shortcode($atts);
    }
}

// Shortcode function for Terminal on Payment Mode
function uterm_payment_shortcode($atts) {
    // Get the secret and public keys from WordPress options and sanitize them
    $secretKey = esc_attr(get_option('secret_key', ''));
    $publicKey = esc_attr(get_option('public_key', ''));
    $products = esc_attr(get_option('products', ''));
    $isAmountUpdatable = esc_attr(get_option('is_amount_updatable', ''));
    $mappings = get_option('mappings', "");

    $product_data = parse_product_data($products);
    $default_product_id = array_key_first($product_data);
    $atts = shortcode_atts([
        'prod_id' => $default_product_id,
    ], $atts, 'uterm');

    $product_id = $atts['prod_id'];
    $amount = $product_data[$product_id];

    // Make a POST request to create a test payment via the LinvioPay API.
    // IMPORTANT: For connecting this payment to your Salesforce Org data
    // please use the mappings request field. 
    // See the API documentation for more information on how to use this field.
    $payment_mappings = get_payment_mappings($mappings);
    $response = wp_remote_post('https://dev-api.linviopay.com/v2/payments', [
        'headers' => [
            'Authorization' => "Bearer $secretKey",
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode([
            'amount' => $amount,
            'name' => 'Test Payment',
            "source_terminal" => "uterm",
            'is_amount_updatable' => $isAmountUpdatable,
            'mappings' => $payment_mappings
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return '<div>Failed to create Terminal Payment.</div>';
    }
    
    // Retrieve the HTTP status code and response body.
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    // Decode the JSON response and extract the payment id.
    $data = json_decode($body);
    $id = $data->id;

    // Create the terminal container div and JavaScript block that initializes the uTerm widget.
    // This code will replace the shortcode inserted in the page.
    $uterm_panel = "<div id=\"terminal\" class=\"flex justify-center\">Loading Terminal...</div>";
    $uterm_script = "
        <script type=\"text/javascript\">
            const configuration = {
                linvioPayPublicKey: '$publicKey',
                mode: 'dev',
                paymentId: '$id'
            }
            const startUterm = () => {
                if (!window.uTerm) {
                    setTimeout(startUterm, 500)
                    return
                }
                window.uTerm('#terminal', configuration)
            }

            startUterm()
        </script>
    ";

    // Return the combined HTML and JavaScript block
    return $uterm_panel.$uterm_script;
}

// Shortcode function for Terminal on Payment Method mode
function uterm_payment_method_shortcode($atts) {
    // Get the secret and public keys from WordPress options and sanitize them
    $secretKey = esc_attr(get_option('secret_key', ''));
    $publicKey = esc_attr(get_option('public_key', ''));

    $contact_sync_id = $_GET['cid'];
    if(empty($contact_sync_id)) {
        return '<div>Please provide a valid contact synchronization id.</div>';
    }

    $contact_email = $_GET['email'];
    $contact_first_name = $_GET['first_name'];
    $contact_last_name = $_GET['last_name'];

    // Is there a contact related to this contact sync id?
    // If not, let's create one now using the provided email
    $linviopay_contact_data = get_linviopay_contact($contact_sync_id, $secretKey);
    if(empty($linviopay_contact_data)){
        if(empty($contact_email)) {
            return '<div>Please provide a valid contact email.</div>';
        }

        $linviopay_contact_data = create_linviopay_contact(
            $contact_sync_id, $contact_email, $contact_first_name, $contact_last_name, $secretKey
        );
    }
    if(empty($linviopay_contact_data)) {
        return '<div>Failed creating contact.</div>';
    }
    $linviopay_contact = json_decode($linviopay_contact_data, true);
    $contact_id = $linviopay_contact['id'];

    // Create LinvioPay Payment Method
    $linviopay_payment_method_data = create_linviopay_payment_method($contact_id, $secretKey);
    $linviopay_payment_method = json_decode($linviopay_payment_method_data, true);
    $linviopay_payment_method_id = $linviopay_payment_method['id'];

    // Create the terminal container div and JavaScript block that initializes the uTerm widget.
    // This code will replace the shortcode inserted in the page.
    $uterm_panel = "<div id=\"terminal\" class=\"flex justify-center\">Loading Terminal...</div>";
    $uterm_script = "
        <script type=\"text/javascript\">
            const configuration = {
                linvioPayPublicKey: '$publicKey',
                mode: 'dev',
                paymentMethodId: '$linviopay_payment_method_id'
            }
            const startUterm = () => {
                if (!window.uTerm) {
                    setTimeout(startUterm, 500)
                    return
                }
                window.uTerm('#terminal', configuration)
            }

            startUterm()
        </script>
    ";

    // Return the combined HTML and JavaScript block
    return $uterm_panel.$uterm_script;
}

// Register the shortcode
add_shortcode('uterm', 'uterm_shortcode');

// Enqueue the Universal Terminal JS and CSS files
function enqueue_uterm_files() {
    wp_enqueue_script('uterm-js', 'https://uterm-dev.linviopay.com/assets/uterm.js', [], false, true);
    wp_enqueue_style('uterm-css', 'https://uterm-dev.linviopay.com/assets/uterm.css', [], false);
}

add_action('wp_enqueue_scripts', 'enqueue_uterm_files');
?>