<?php
/**
 * Plugin Name:       Hesaplamaa All-in-One (SEO, Intelligence & Growth Suite)
 * Plugin URI:        https://github.com/alperates58/hesaplamaa-all-in-one
 * Description:       hesaplamaa.com için hepsi bir arada SEO, Google Arama Zekası, Sayfa Kalite Denetimi, Dizin Takibi ve İçerik Büyüme Paneli.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Alper ATEŞ
 * Author URI:        https://hesaplamaa.com
 * License:           GPL v2 or later
 * Text Domain:       hesaplamaa-all-in-one
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// Plugin Sabitleri
$hao_last_update_sha     = substr( (string) get_option( 'hao_last_update_sha', '' ), 0, 7 );
$hao_last_update_version = (string) get_option( 'hao_last_update_version', '0' );

define( 'HAO_VERSION',    '1.0.0-' . $hao_last_update_version . ( $hao_last_update_sha ? '-' . $hao_last_update_sha : '' ) );
define( 'HAO_FILE',       __FILE__ );
define( 'HAO_DIR',        plugin_dir_path( __FILE__ ) );
define( 'HAO_URL',        plugin_dir_url( __FILE__ ) );
define( 'HAO_ASSETS_URL', HAO_URL . 'assets/' );
define( 'HAO_BASENAME',   plugin_basename( __FILE__ ) );

// Autoloader Yükle
require_once HAO_DIR . 'includes/Core/class-autoloader.php';
\HAO\Core\Autoloader::register();

/**
 * Eklentiyi Başlat
 */
function hao_init_plugin() {
    load_plugin_textdomain( 'hesaplamaa-all-in-one', false, dirname( HAO_BASENAME ) . '/languages' );
    \HAO\Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'hao_init_plugin' );

/**
 * Aktivasyon Hook
 */
register_activation_hook( HAO_FILE, function () {
    \HAO\DB\Migrator::run();
    \HAO\Cron\Scheduler::activate();
    flush_rewrite_rules();
} );

/**
 * Deaktivasyon Hook
 */
register_deactivation_hook( HAO_FILE, function () {
    \HAO\Cron\Scheduler::deactivate();
    flush_rewrite_rules();
} );
