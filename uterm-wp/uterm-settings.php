<?php

include_once('uterm-common.php');

// Add the menu item in admin
add_action('admin_menu', function () {
    add_menu_page(
        'Universal Terminal Settings',  // Page Title
        'UTerminal',                    // Menu Title
        'manage_options',               // Capability
        'secret-key-admin',             // Menu Slug
        'render_settings_page'          // Callback
    );
});

$key_settings_group = 'key_settings_group';
$product_settings_group = 'product_settings_group';
$key_admin_page = 'key-admin';

// Render the settings page
function render_settings_page()
{
    global $key_settings_group, $key_admin_page;
    ?>
    <div class="wrap">
        <h1>Settings</h1>
        <form method="post" action="options.php" style="max-width: 800px;">
            <?php
            settings_fields($key_settings_group);
            do_settings_sections($key_admin_page);
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Hook into the 'admin_init' action to register settings and fields for the admin panel
add_action('admin_init', function () {
    global $key_settings_group, $key_admin_page;

    // Register 'secret_key' setting under the 'settings_group'
    register_setting($key_settings_group, 'secret_key',[
        // If the input is empty or only whitespace, keep the previous value
        'sanitize_callback' => function($value) {return empty(trim($value)) ? get_option('secret_key') : $value;},
    ]);

    // Register 'public_key' setting under the 'settings_group'
    register_setting($key_settings_group, 'public_key',[
        // If the input is empty or only whitespace, keep the previous value
        'sanitize_callback' => function($value) {return empty(trim($value)) ? get_option('public_key') : $value;},
    ]);

    // Add a settings section to the 'key-admin' settings page
    $key_settings_section = 'key_settings_section';
    add_settings_section(
        $key_settings_section,
        'Api Key Settings',
        null,
        $key_admin_page
    );

    // Add a settings field for the public key input
    add_settings_field(
        'public_key_field',
        'Public Key',
        'public_key_field_callback',
        $key_admin_page,
        $key_settings_section
    );

    // Add a settings field for the secret key input
    add_settings_field(
        'secret_key_field',
        'Secret Key',
        'secret_key_field_callback',
        $key_admin_page,
        $key_settings_section
    );

    $product_settings_section = 'product_settings_section';
    register_setting($key_settings_group, 'products',[
        'sanitize_callback' => function($value) {
            $old_value = get_option('products');
            try {
                $data = parse_product_data($value);
                if(empty($data)) {
                    return $old_value;
                } else {
                    return $value;
                }
            } catch (Exception $e) {
                return $old_value;
            }
        },
    ]);

    register_setting($key_settings_group, 'is_amount_updatable', [
        'sanitize_callback' => function($value) {
            return $value === '1' ? 1 : 0;
        }
    ]);

    add_settings_section(
        $product_settings_section,
        'Product Settings',
        function () {
            echo '<p>Configure your products by adding one per line using the following format: <strong>&lt;product_slug&gt;=&lt;price_in_cents&gt;</strong>. 
            <br/>By default, two products are configured: p1 for $9,99 and p2 for $29.99.</p>';
        },
        $key_admin_page
    );

    add_settings_field(
        'products_field',
        'Products',
        'products_field_callback',
        $key_admin_page,
        $product_settings_section
    );

    add_settings_field(
        'is_amount_updatable_field',
        'Editable Amount',
        'is_amount_updatable_field_callback',
        $key_admin_page,
        $product_settings_section
    );


    $advanced_settings_section = 'advanced_settings_section';
    register_setting($key_settings_group, 'mappings',[
        'sanitize_callback' => function($value) {
            $old_value = get_option('mappings');
            try {
                if(validate_mappings_data($value)) {
                    return $value;
                } else {
                    return $old_value;
                }
            } catch (Exception $e) {
                return $old_value;
            }
        },
    ]);

    add_settings_section(
        $advanced_settings_section,
        'Advanced Settings',
        function () {
            ?>
                <p>
                    You can map payments captured on your WordPress site to data in your Salesforce Org using the mappings JSON configuration.<br/> 
                    This allows your Payment object to both receive data from an external Salesforce object and push data to it.<br>
                    
                </p>
                <p><strong>Ex.</strong> To map your payments to specific Salesforce Accounts, the following mapping could be used:</p>
                <p style="font-family: monospace; white-space: pre; background-color: #f4f4f4;; padding: 0rem 2rem; border: 1px solid #ccc;">
[
   {
      "id":"${m1}",
      "object_type":"account",
      "field_mappings":[
         {
            "source":"account.id",
            "destination":"pymt__paymentx__c.related_account__c"
         }
      ]
   }
]
                </p>
                <p>
                    Using this configuration, all captured payments will have the <code>related_account__c</code> field filled with the id specified by the URL variable <code>m1</code>.<br/>
                    This will map them to a specific Salesforce Account record.
                </p>
                <p>
                    For more information on how to configure your payment mappings, please refer to the "mappings" field specification<br/> in the
                    <a href="https://api.linviopay.com/v2/docs#tag/Payments/operation/prepare_payment_v_version_name__payments_post" target="_blank">Prepare Payment endpoint</a>
                    LinvioPay API Documentation.
                </p>
            <?php
        },
        $key_admin_page
    );

    add_settings_field(
        'mappings_field',
        'Mappings',
        'mappings_field_callback',
        $key_admin_page,
        $advanced_settings_section
    );
});

// Setting fields rendering
function secret_key_field_callback()
{
    $value = esc_attr(get_option('secret_key', ''));
    $maskedValue = '';
    if(!empty($value) && strlen($value) > 4) {
        $maskedValue = '********'.substr($value, -4);
    }
    
    echo "<input type='password' name='secret_key' placeholder='$maskedValue' style='width: 300px;'>";
}

function public_key_field_callback()
{
    $value = esc_attr(get_option('public_key', ''));
    $maskedValue = '';
    if(!empty($value) && strlen($value) > 4) {
        $maskedValue = '********'.substr($value, -4);
    }
    
    echo "<input type='password' name='public_key' placeholder='$maskedValue' style='width: 300px;'>";
}

function products_field_callback()
{
    $value = esc_attr(get_option('products', "p1=999\np2=2999"));
    echo "<textarea name='products' style='width: 600px;font-family: monospace;' rows=\"5\">$value</textarea>";
}

function is_amount_updatable_field_callback() {
    $option = get_option('is_amount_updatable');
    ?>
    <input type="checkbox" name="is_amount_updatable" value="1" <?php checked(1, $option); ?> />
    <label for="is_amount_updatable">Allow customer to edit Payment Amount.</label>
    <?php
}

function mappings_field_callback()
{
    $value = esc_attr(get_option('mappings', ""));
    ?>
    <textarea id="mappingsTextArea" name='mappings' style='width: 600px;font-family: monospace;' oninput="autoResize(this)"><?=$value?></textarea>
    <script>
    function autoResize(textarea) {
        const calculatedHeight = textarea.scrollHeight;
        textarea.style.height = (calculatedHeight < 150 ? 150 : calculatedHeight) + 'px';
    }
    const textarea = document.getElementById('mappingsTextArea')
    autoResize(textarea)
    </script>
    <?php
}