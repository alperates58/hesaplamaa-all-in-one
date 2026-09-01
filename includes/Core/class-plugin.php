<?php
namespace HAO\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Ana Eklenti Singleton Sınıfı
 */
class Plugin {

    private static ?Plugin $instance = null;

    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // DB Migration kontrolü
        \HAO\DB\Migrator::maybe_run();

        // Cron sistemini bağla
        \HAO\Cron\Scheduler::init();

        // Admin bağlamı
        if ( is_admin() ) {
            $menu = new \HAO\Admin\Menu();
            $menu->init();

            $ajax = new \HAO\Admin\AjaxHandler();
            $ajax->init();

            $updater = new \HAO\Core\GithubUpdater();
            $updater->init();

            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        }
    }

    public function enqueue_admin_assets( $hook ) {
        // Sadece All-in-One sayfalarında yükle
        if ( false === strpos( (string) $hook, 'hao-' ) && false === strpos( (string) $hook, 'hesaplamaa-all-in-one' ) ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'hao-admin-premium',
            HAO_ASSETS_URL . 'css/admin-premium.css',
            [],
            HAO_VERSION
        );

        // Chart.js CDN
        wp_enqueue_script(
            'hao-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // JS
        wp_enqueue_script(
            'hao-admin-core',
            HAO_ASSETS_URL . 'js/admin-core.js',
            [ 'jquery', 'hao-chartjs' ],
            HAO_VERSION,
            true
        );

        wp_localize_script(
            'hao-admin-core',
            'hao_vars',
            [
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'hao_admin_nonce' ),
                'assets_url' => HAO_ASSETS_URL,
            ]
        );
    }
}
