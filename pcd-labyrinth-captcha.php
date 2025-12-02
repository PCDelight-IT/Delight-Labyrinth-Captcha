<?php
/**
 * Plugin Name:       PCD Labyrinth Captcha
 * Plugin URI:        https://example.com/
 * Description:       Ein verspieltes Labyrinth-Captcha, das Menschen reinlässt und Bots verwirrt.
 * Version:           0.2.0
 * Author:            PCDelight
 * Author URI:        https://example.com/
 * Text Domain:       pcd-labyrinth-captcha
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PCD_LAB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PCD_LAB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PCD_LAB_PLUGIN_VERSION', '0.2.0' );

/**
 * Textdomain laden
 */
function pcd_labyrinth_load_textdomain() {
    load_plugin_textdomain(
        'pcd-labyrinth-captcha',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'pcd_labyrinth_load_textdomain' );

/**
 * Loader einbinden
 * Der Loader entscheidet, welche Integrationen geladen werden.
 */
require_once PCD_LAB_PLUGIN_PATH . 'includes/class-loader.php';

/**
 * Loader initialisieren
 */
add_action( 'plugins_loaded', array( 'PCD_Labyrinth_Loader', 'init' ) );
