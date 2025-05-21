<?php
/**
 * Plugin Name
 *
 * @package           UTermWP
 * @author            Linvio Inc.
 * @copyright         2019 Linvio Inc.
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       LinvioPay Universal Terminal for Wordpress
 * Description:       A proof of concept plugin that allows the user to configure the account's Test API Key secret and launch LinvioPay Universal Terminal on a page by using the [uterm] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Linvio Inc.
 * Text Domain:       uterm-wp
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once('uterm-shortcode.php');
include_once('uterm-settings.php');
