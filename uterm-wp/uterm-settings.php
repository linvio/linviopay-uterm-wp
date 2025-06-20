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

// Hook into the 'admin_init' action to register settings and fields
add_action('admin_init', function () {
    global $key_settings_group, $key_admin_page;

    // Register 'uterm_store_in_wp_config' setting for storage method
    register_setting($key_settings_group, 'uterm_store_in_wp_config', [
        'sanitize_callback' => function ($value) {
            return $value === '1' ? 1 : 0;
        }
    ]);

    // Register 'uterm_public_key' setting for database storage
    register_setting($key_settings_group, 'uterm_public_key', [
        'sanitize_callback' => function ($value) {
            return empty(trim($value)) ? get_option('uterm_public_key') : $value;
        },
    ]);

    // Register 'uterm_secret_key' setting for database storage
    register_setting($key_settings_group, 'uterm_secret_key', [
        'sanitize_callback' => function ($value) {
            return empty(trim($value)) ? get_option('uterm_secret_key') : $value;
        },
    ]);

    // Add a settings section to the 'key-admin' settings page
    $key_settings_section = 'key_settings_section';
    add_settings_section(
        $key_settings_section,
        'API Key Settings',
        null,
        $key_admin_page
    );

    // Add storage method field (includes public and secret key fields)
    add_settings_field(
        'storage_method_field',
        'Storage Method',
        'storage_method_field_callback',
        $key_admin_page,
        $key_settings_section
    );

    // Add public key field (for settings API registration)
    add_settings_field(
        'public_key_field',
        'Public Key',
        'public_key_field_callback',
        $key_admin_page,
        $key_settings_section
    );

    // Add secret key field (for settings API registration)
    add_settings_field(
        'secret_key_field',
        'Secret Key',
        'secret_key_field_callback',
        $key_admin_page,
        $key_settings_section
    );

    // Product settings
    $product_settings_section = 'product_settings_section';
    register_setting($key_settings_group, 'uterm_products', [
        'sanitize_callback' => function ($value) {
            $old_value = get_option('uterm_products');
            try {
                $data = parse_product_data($value);
                if (empty($data)) {
                    return $old_value;
                } else {
                    return $value;
                }
            } catch (Exception $e) {
                return $old_value;
            }
        },
    ]);

    register_setting($key_settings_group, 'uterm_is_amount_updatable', [
        'sanitize_callback' => function ($value) {
            return $value === '1' ? 1 : 0;
        }
    ]);

    add_settings_section(
        $product_settings_section,
        'Product Settings',
        function () {
            echo '<p>Configure your products by adding one per line using the following format: <strong><product_slug>=<price_in_cents></strong>. 
            <br/>By default, two products are configured: p1 for $9.99 and p2 for $29.99.</p>';
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

    // Advanced settings
    $advanced_settings_section = 'advanced_settings_section';
    register_setting($key_settings_group, 'uterm_mappings', [
        'sanitize_callback' => function ($value) {
            $old_value = get_option('uterm_mappings');
            try {
                if (validate_mappings_data($value)) {
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
            <div style="margin-left: 0;">
                <p>You can map payments captured on your WordPress site to data in your Salesforce Org using the mappings JSON configuration.<br>
                    This allows your Payment object to both receive data from an external Salesforce object and push data to it.</p>
                <p><strong>Ex.</strong> To map your payments to specific Salesforce Accounts, the following mapping could be used:</p>
                <pre style="font-family: monospace; background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc;">
[
    {
        "id": "${m1}",
        "object_type": "account",
        "field_mappings": [
            {
                "source": "account.id",
                "destination": "pymt__paymentx__c.related_account__c"
            }
        ]
    }
]
                </pre>
                <p>Using this configuration, all captured payments will have the <code>related_account__c</code> field filled with the id specified by the URL variable <code>m1</code>.<br>
                    This will map them to a specific Salesforce Account record.</p>
                <p>For more information on how to configure your payment mappings, please refer to the "mappings" field specification<br>
                    in the <a href="https://api.linviopay.com/v2/docs#tag/Payments/operation/prepare_payment_v_version_name__payments_post" target="_blank">Prepare Payment endpoint</a> LinvioPay API Documentation.</p>
            </div>
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
function storage_method_field_callback()
{
    $store_in_wp_config = get_option('uterm_store_in_wp_config', 0);
    ?>
    <p>
        <input type="radio" name="uterm_store_in_wp_config" id="store_in_database" value="0" <?php checked(0, $store_in_wp_config); ?> onchange="toggleKeyFields()">
        <label for="store_in_database">Store API credentials in database</label>
        <br>
        <span id="database_fields" style="display: <?php echo $store_in_wp_config ? 'none' : 'block'; ?>; margin-left: 20px;">
            <?php
            // Public Key field
            $public_value = esc_attr(get_option('uterm_public_key', ''));
            $public_masked = !empty($public_value) && strlen($public_value) > 4 ? '********' . substr($public_value, -4) : '';
            ?>
            <label for="public_key" style="display: inline-block; width: 100px;">Public Key:</label>
            <input type="password" name="uterm_public_key" id="public_key" placeholder="<?php echo esc_attr($public_masked); ?>" style="width: 300px;" <?php echo $store_in_wp_config ? 'disabled' : ''; ?>><br><br>
            <?php
            // Secret Key field
            $secret_value = esc_attr(get_option('uterm_secret_key', ''));
            $secret_masked = !empty($secret_value) && strlen($secret_value) > 4 ? '********' . substr($secret_value, -4) : '';
            ?>
            <label for="secret_key" style="display: inline-block; width: 100px;">Secret Key:</label>
            <input type="password" name="uterm_secret_key" id="secret_key" placeholder="<?php echo esc_attr($secret_masked); ?>" style="width: 300px;" <?php echo $store_in_wp_config ? 'disabled' : ''; ?>>
        </span>
    </p>
    <p>
        <input type="radio" name="uterm_store_in_wp_config" id="store_in_wp_config" value="1" <?php checked(1, $store_in_wp_config); ?> onchange="toggleKeyFields()">
        <label for="store_in_wp_config">Store API credentials in wp-config.php file</label>
        <br>
        <span id="wp_config_instructions" style="display: <?php echo $store_in_wp_config ? 'block' : 'none'; ?>; margin-left: 20px;">
            Add the following to <code>wp-config.php</code>:<br>
            <pre style="background-color: #f4f4f4; padding: 10px; border: 1px solid #ccc;">
define('LINVIOPAY_PUBLIC_KEY', 'your_public_key');
define('LINVIOPAY_SECRET_KEY', 'your_secret_key');
            </pre>
        </span>
    </p>
    <script>
        function toggleKeyFields() {
            const wpConfigRadio = document.getElementById('store_in_wp_config');
            const databaseFields = document.getElementById('database_fields');
            const wpConfigInstructions = document.getElementById('wp_config_instructions');
            const publicKeyInput = document.getElementById('public_key');
            const secretKeyInput = document.getElementById('secret_key');

            databaseFields.style.display = wpConfigRadio.checked ? 'none' : 'block';
            wpConfigInstructions.style.display = wpConfigRadio.checked ? 'block' : 'none';
            publicKeyInput.disabled = wpConfigRadio.checked;
            secretKeyInput.disabled = wpConfigRadio.checked;
        }

        // Run on page load to set initial state
        document.addEventListener('DOMContentLoaded', toggleKeyFields);
    </script>
    <?php
}

function public_key_field_callback()
{
    // Empty to prevent rendering outside storage_method_field_callback
}

function secret_key_field_callback()
{
    // Empty to prevent rendering outside storage_method_field_callback
}

function products_field_callback()
{
    $value = esc_attr(get_option('uterm_products', "p1=999\np2=2999"));
    echo "<textarea name='uterm_products' style='width: 600px;font-family: monospace;' rows=\"5\">$value</textarea>";
}

function is_amount_updatable_field_callback()
{
    $option = get_option('uterm_is_amount_updatable');
    ?>
    <label>
        <input type="checkbox" name="uterm_is_amount_updatable" value="1" <?php checked(1, $option); ?> />
    </label>
    <label for="uterm_is_amount_updatable">Allow customer to edit Payment Amount.</label>
    <?php
}

function mappings_field_callback()
{
    $value = esc_attr(get_option('uterm_mappings', ""));
    ?>
    <label for="mappingsTextArea"></label><textarea id="mappingsTextArea" name='uterm_mappings' style='width: 600px;font-family: monospace;' oninput="autoResize(this)"><?php echo $value; ?></textarea>
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

// Helper function to retrieve API keys based on storage method
function get_linvio_api_keys()
{
    $store_in_wp_config = get_option('uterm_store_in_wp_config', 0);
    if ($store_in_wp_config) {
        return [
            'public_key' => defined('LINVIOPAY_PUBLIC_KEY') ? LINVIOPAY_PUBLIC_KEY : '',
            'secret_key' => defined('LINVIOPAY_SECRET_KEY') ? LINVIOPAY_SECRET_KEY : ''
        ];
    } else {
        return [
            'public_key' => get_option('uterm_public_key', ''),
            'secret_key' => get_option('uterm_secret_key', '')
        ];
    }
}

function uterm_migrate_options() {
    $old_options = [
        'public_key' => 'uterm_public_key',
        'secret_key' => 'uterm_secret_key',
        'store_in_wp_config' => 'uterm_store_in_wp_config',
        'products' => 'uterm_products',
        'is_amount_updatable' => 'uterm_is_amount_updatable',
        'mappings' => 'uterm_mappings'
    ];
    foreach ($old_options as $old => $new) {
        $value = get_option($old);
        if ($value !== false) {
            update_option($new, $value);
            // delete_option($old); // Uncomment after verifying migration
        }
    }
}
// Run on plugin activation or manually
register_activation_hook(__FILE__, 'uterm_migrate_options');
// Or run once via admin action: uterm_migrate_options();