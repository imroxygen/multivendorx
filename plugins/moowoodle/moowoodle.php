<?php
/**
 * Plugin Name: MooWoodle
 * Plugin URI: https://dualcube.com/
 * Description: The MooWoodle plugin is an extention of WooCommerce that acts as a bridge between WordPress/Woocommerce and Moodle.
 * Author: DualCube
 * Version: 3.3.0
 * Author URI: https://dualcube.com/
 * Requires at least: 6.0.0
 * Tested up to: 6.7.2
 * WC requires at least: 8.4.0
 * WC tested up to: 9.7.1
 *
 * Text Domain: moowoodle
 * Domain Path: /languages/
 *
 * @package MooWoodle
 */

defined( 'ABSPATH' ) || exit;

// autoload classes.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Returns the main instance of the MooWoodle plugin.
 *
 * @return \MooWoodle\MooWoodle
 */
function MooWoodle() {
    return \MooWoodle\MooWoodle::init( __FILE__ );
}

MooWoodle();
