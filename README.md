# Minimal LinvioPay Universal Terminal Wordpress plugin

**Security Note**: This plugin is provided as is and it is not intended to be used in production without further customization. Specifically, API keys are stored using WordPress’s options API, which is not encrypted. Ensure your server and site are properly secured before deploying your production keys.

A minimal WordPress plugin that integrates LinvioPay Universal Terminal to process payments on any page or post by means of a provided shortcode. It also provides a minimal admin interface to store API keys securely.

## Features

- Admin settings screen to configure Public and Secret API keys
- Shortcode `[uterm]` to render and initialize the LinvioPay terminal
- Sends secure API calls to LinvioPay
- Displays a terminal widget dynamically via JavaScript

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

Add the `[uterm]` shortcode to any page or post where you want the LinvioPay terminal to appear.

Example:

```html
<h1>CheckoutPage</h1>

[uterm]
```

# License

MIT License. Feel free to modify and reuse.

# Support

This is a custom plugin. For official LinvioPay support, visit https://linviopay.com.
