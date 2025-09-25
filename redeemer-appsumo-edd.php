<?php
/**
 * Plugin Name:  Redeemer for AppSumo + EDD
 * Description:  Validates AppSumo promo codes and auto-issues Easy Digital Downloads (EDD) Software Licensing licenses. OOP + Composer.
 * Version:      1.0.0
 * Author:       Nexiby LLC
 * Author URI:   https://nexiby.com
 * Text Domain:  redeemer-appsumo-edd
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Namespace:    REDEASEDD\RedeemerAppSumoEDD
 * Prefix:       REDEASEDD_
 */

if ( ! defined('ABSPATH') ) exit;

define('REDEASEDD_FILE', __FILE__);
define('REDEASEDD_PATH', plugin_dir_path(__FILE__));
define('REDEASEDD_URL',  plugin_dir_url(__FILE__));

/** Composer autoload */
if ( file_exists( REDEASEDD_PATH . 'vendor/autoload.php' ) ) {
    require REDEASEDD_PATH . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        if ( current_user_can('activate_plugins') ) {
            echo '<div class="notice notice-warning"><p><strong>Redeemer for AppSumo + EDD:</strong> run <code>composer dump-autoload -o</code> inside the plugin folder.</p></div>';
        }
    });
}

add_action('plugins_loaded', function () {
    if ( class_exists('\REDEASEDD\RedeemerAppSumoEDD\Plugin') ) {
        (new \REDEASEDD\RedeemerAppSumoEDD\Plugin())->boot();
    }
});