<?php
namespace HAO\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress Admin Menü Yapılandırması
 */
class Menu {

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
    }

    public function register_menus() {
        $parent_slug = 'hesaplamaa-all-in-one';

        add_menu_page(
            __( 'Hesaplamaa All-in-One', 'hesaplamaa-all-in-one' ),
            __( 'All-in-One SEO', 'hesaplamaa-all-in-one' ),
            'manage_options',
            $parent_slug,
            [ $this, 'render_dashboard' ],
            $this->get_menu_icon(),
            30.2
        );

        $submenus = [
            [
                'slug'  => $parent_slug,
                'title' => __( 'Genel Bakış', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_dashboard' ],
            ],
            [
                'slug'  => 'hao-radar',
                'title' => __( 'SEO & Büyüme Radarı', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_radar' ],
            ],
            [
                'slug'  => 'hao-audit',
                'title' => __( 'Sayfa & Kalite Denetimi', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_audit' ],
            ],
            [
                'slug'  => 'hao-index',
                'title' => __( 'Google Dizin Durumu', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_index' ],
            ],
            [
                'slug'  => 'hao-ideas',
                'title' => __( 'Yeni Fikirler & Suggest', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_ideas' ],
            ],
            [
                'slug'  => 'hao-tools',
                'title' => __( 'Akıllı SEO Araçları', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_tools' ],
            ],
            [
                'slug'  => 'hao-settings',
                'title' => __( 'Ayarlar & AI Hub', 'hesaplamaa-all-in-one' ),
                'cb'    => [ $this, 'render_settings' ],
            ],
        ];

        foreach ( $submenus as $sub ) {
            add_submenu_page(
                $parent_slug,
                $sub['title'] . ' ‹ Hesaplamaa All-in-One',
                $sub['title'],
                'manage_options',
                $sub['slug'],
                $sub['cb']
            );
        }
    }

    public function render_dashboard() {
        $repo = new \HAO\DB\Repository();
        $summary = $repo->get_dashboard_summary();
        $daily_stats = $repo->get_daily_stats( 30 );
        $top_alerts = $repo->get_top_radar_alerts( 6 );
        include HAO_DIR . 'templates/admin/view-dashboard.php';
    }

    public function render_radar() {
        $repo = new \HAO\DB\Repository();
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $opportunities = $repo->get_opportunity_keywords( [ 'search' => $search, 'limit' => 100 ] );
        include HAO_DIR . 'templates/admin/view-seo-radar.php';
    }

    public function render_audit() {
        $auditor = new \HAO\Engine\PageAuditor();
        $filter  = sanitize_key( $_GET['filter'] ?? 'all' );
        $search  = sanitize_text_field( $_GET['s'] ?? '' );
        $pages   = $auditor->get_audited_pages( [ 'filter' => $filter, 'search' => $search ] );
        include HAO_DIR . 'templates/admin/view-page-audit.php';
    }

    public function render_index() {
        $repo = new \HAO\DB\Repository();
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $verdict = sanitize_text_field( $_GET['verdict'] ?? '' );
        $statuses = $repo->get_all_index_statuses( [ 'search' => $search, 'verdict' => $verdict, 'limit' => 150 ] );
        include HAO_DIR . 'templates/admin/view-index-status.php';
    }

    public function render_ideas() {
        $repo = new \HAO\DB\Repository();
        $search = sanitize_text_field( $_GET['s'] ?? '' );
        $suggestions = $repo->get_suggestions( [ 'search' => $search, 'limit' => 150 ] );
        include HAO_DIR . 'templates/admin/view-ideas.php';
    }

    public function render_tools() {
        $auditor = new \HAO\Engine\PageAuditor();
        $missing_meta_pages = $auditor->get_audited_pages( [ 'filter' => 'missing_meta', 'posts_per_page' => 100 ] );
        include HAO_DIR . 'templates/admin/view-tools.php';
    }

    public function render_settings() {
        $gsc_client = new \HAO\API\GscClient();
        $ai_hub     = new \HAO\API\AiHub();
        $gsc_settings = get_option( 'hge_settings', [] );
        $ai_settings  = $ai_hub->get_settings();
        include HAO_DIR . 'templates/admin/view-settings.php';
    }

    private function get_menu_icon(): string {
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>'
        );
    }
}
