<?php

function parse_product_data($text) {
    $products = [];
    if(empty($text)) return $products;

    $lines = explode("\n", trim($text));
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $slug = trim($parts[0]);
            $price = trim($parts[1]);

            if ($slug !== '' && is_numeric($price)) {
                $products[$slug] = $price;
            } else {
                throw new InvalidArgumentException("Invalid product data provided.");
            }
        } else {
            throw new InvalidArgumentException("Invalid product data provided.");
        }
    }

    return $products;
}

function validate_mappings_data($json) {
    if(empty($json)) {
        return true;
    }
    
    // Decode JSON
    $data = json_decode($json, true);

    // Check for valid JSON and that the root is an array
    if (!is_array($data)) {
        return false;
    }

    foreach ($data as $item) {
        // Each item must be an array with required keys
        if (!is_array($item) ||
            !isset($item['id'], $item['object_type'], $item['field_mappings']) ||
            !is_string($item['id']) ||
            !is_string($item['object_type']) ||
            !is_array($item['field_mappings'])
        ) {
            return false;
        }

        // Validate each field mapping
        foreach ($item['field_mappings'] as $mapping) {
            if (!is_array($mapping) ||
                !isset($mapping['source'], $mapping['destination']) ||
                !is_string($mapping['source']) ||
                !is_string($mapping['destination'])
            ) {
                return false;
            }
        }
    }

    return true;
}

function get_payment_mappings($json) {
    if(empty($json)) {
        return $json;
    }

    preg_match_all('/"\$\{([^}]+)\}"/', $json, $matches);
    $vars = $matches[1];
    
    foreach($vars as $varName) {
        if (!array_key_exists($varName, $_GET)) {
            continue;
        }

        $value = $_GET[$varName];
        $json = str_replace('${' . $varName . '}', $value, $json);
    }

    return json_decode($json, true);
}

function get_linviopay_contact($syncId, $secretKey) {
    $response = wp_remote_get('https://dev-api.linviopay.com/v2/contacts/' . $syncId, [
        'headers' => [
            'Authorization' => "Bearer $secretKey",
            'Content-Type'  => 'application/json',
        ],
    ]);
    
    $status_code = wp_remote_retrieve_response_code($response);
    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return null;
    }
    if($status_code != 200) {
        return null;
    }
    return wp_remote_retrieve_body($response);
}

function create_linviopay_contact($sync_id, $email, $first_name, $last_name, $secretKey) {
    $first_name = empty($first_name) ? 'N/A' : $first_name;
    $last_name = empty($last_name) ? 'N/A' : $last_name;
    $response = wp_remote_post('https://dev-api.linviopay.com/v2/contacts', [
        'headers' => [
            'Authorization' => "Bearer $secretKey",
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'synchronization_id' => $sync_id
        ])
    ]);
    
    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return null;
    }
    $status_code = wp_remote_retrieve_response_code($response);
    if($status_code != 200) {
        return null;
    }
    return wp_remote_retrieve_body($response);
}

function create_linviopay_payment_method($contact_id, $secretKey) {
    $response = wp_remote_post('https://dev-api.linviopay.com/v2/payment_methods', [
        'headers' => [
            'Authorization' => "Bearer $secretKey",
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'contact_id' => $contact_id
        ])
    ]);
    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
        return null;
    }
    return wp_remote_retrieve_body($response);
}