# Minimal LinvioPay Universal Terminal Wordpress plugin

**Security Note**: This plugin is provided as is and it is not intended to be used in production without further customization. Specifically, API keys are stored using WordPress’s options API, which is not encrypted. Ensure your server and site are properly secured before deploying your production keys.

A minimal WordPress plugin that integrates LinvioPay Universal Terminal to capture payments and save payment methods on any page or post by means of a provided shortcode. It also provides a minimal admin interface.

## Features

- Minimal Admin settings screen to configure Public and Secret API keys, products and mappings.
- Shortcode `[uterm]` to render and initialize the LinvioPay terminal.
- Sends secure API calls to LinvioPay.
- Displays a terminal widget dynamically via JavaScript.

# Requirements

- [Optional] Docker compose.
- PHP 7.2+
- WordPress 5.0+
- A valid LinvioPay API key pair (public & secret)

## Installation

1. [Optional] Execute `docker compose up` to get a new Wordpress installation. Open http://localhost:8080 and perform the initial Wordpress configuration.
2. Copy the plugin directory (`uterm-wp`) and place them in the `wp-content/plugins` directory inside your wordpress installation directory. If running from docker, the plugin should be automatically copied.
3. Activate the plugin through the WordPress admin dashboard (http://localhost:8080/wp-admin/plugins.php).
4. Go to the **Uterm** section in the Admin Area to enter your Public and Secret API keys.

If you want to locally test this code, you can use the provided `docker-compose.yml` file. It will automatically download all the required docker images and install the plugin. Once running, the wordpress site will be accessible from http://localhost:8080

## Usage

### Payment capture mode

Add the `[uterm]` shortcode to any page or post where you want the LinvioPay terminal to appear.

Example:

```html
<h1>CheckoutPage</h1>

[uterm]
```

### Payment Method save mode

Add the `[uterm mode="payment_method"]` shortcode to any page or post where you want the LinvioPay terminal to appear.

Example:

```html
<h1>CheckoutPage</h1>

[uterm mode="payment_method"]
```

When opening the page containing the terminal, please provide the following URL params:

- **cid**: The Salesforce Contact Synchronization Id. Payment methods saved will be attached to this Salesforce Contact Record.
- **email**: Salesforce Contact email. Will be used for creating a LinvioPay Contact for the provided `cid` in case it was not previously created.
- [Optional] **first_name**
- [Optional] **last_name**

# License

MIT License. Feel free to modify and reuse.

# Support

This is a custom plugin. For official LinvioPay support, visit https://linviopay.com.
