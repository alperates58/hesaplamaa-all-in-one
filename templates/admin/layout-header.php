<?php
/**
 * All-in-One Admin Layout Header
 */
defined( 'ABSPATH' ) || exit;

$current_page = sanitize_text_field( $_GET['page'] ?? 'hesaplamaa-all-in-one' );
$gsc_client   = new \HAO\API\GscClient();
$is_connected = $gsc_client->is_connected();
?>

<div class="hao-wrap">
    <!-- Header -->
    <div class="hao-header">
        <div class="hao-brand">
            <div class="hao-logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="hao-title-area">
                <h1>Hesaplamaa All-in-One</h1>
                <p>SEO Zekası, Büyüme Radarı, Sayfa Kalite Denetimi & Dizin Merkezi</p>
            </div>
        </div>

        <div class="hao-header-actions">
            <?php if ( $is_connected ) : ?>
                <span class="hao-badge hao-badge-emerald">
                    <span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; line-height:14px;"></span> GSC Bağlı
                </span>
                <button type="button" id="hao-btn-sync-gsc" class="hao-btn hao-btn-primary">
                    <span class="dashicons dashicons-update"></span> GSC Verilerini Çek
                </button>
            <?php else : ?>
                <span class="hao-badge hao-badge-amber">GSC Bağlı Değil</span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-settings' ) ); ?>" class="hao-btn hao-btn-secondary">
                    Bağlantı Kur
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="hao-nav-tabs">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hesaplamaa-all-in-one' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hesaplamaa-all-in-one' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span> Genel Bakış
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-radar' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-radar' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-chart-area"></span> SEO & Büyüme Radarı
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-audit' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-search"></span> Sayfa & Kalite Denetimi
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-index' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-index' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-google"></span> Google Dizin Durumu
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-ideas' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-ideas' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-lightbulb"></span> Yeni Fikirler & Suggest
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-tools' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-tools' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-admin-tools"></span> Akıllı SEO Araçları
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-settings' ) ); ?>" class="hao-nav-tab <?php echo $current_page === 'hao-settings' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span> Ayarlar & AI Hub
        </a>
    </nav>
