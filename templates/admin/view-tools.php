<?php
/**
 * View: Akıllı SEO Araçları (Tools)
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<div class="hao-card">
    <div class="hao-card-header">
        <div>
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-admin-tools" style="color:var(--hao-primary);"></span>
                Akıllı SEO Araçları & Eksik Tamamlayıcı
            </h2>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                Sitedeki eksik meta açıklamalarını, SEO başlıklarını ve iç link köprülerini tek tıkla optimize edin.
            </p>
        </div>
    </div>

    <!-- Eksik Meta Açıklamaları Listesi -->
    <div style="margin-bottom:20px;">
        <h3 style="font-size:15px; font-weight:700; margin-bottom:12px; color:#0f172a;">
            ⚠️ Meta Açıklaması Eksik Olan Yazılar (<?php echo count( $missing_meta_pages ); ?> Adet)
        </h3>

        <?php if ( ! empty( $missing_meta_pages ) ) : ?>
            <div class="hao-table-wrap">
                <table class="hao-table">
                    <thead>
                        <tr>
                            <th style="width:50%;">Yazı Başlığı</th>
                            <th>Kelime Sayısı</th>
                            <th>Mevcut Durum</th>
                            <th style="text-align:right;">AI İle Düzelt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_slice( $missing_meta_pages, 0, 25 ) as $item ) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $item['post_title'] ); ?></strong>
                                    <div style="font-size:11.5px; color:#64748b;">
                                        <a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" style="color:#64748b;">
                                            <?php echo esc_html( $item['permalink'] ); ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo number_format_i18n( $item['word_count'] ); ?> kelime</td>
                                <td><span class="hao-badge hao-badge-amber">Meta Yok</span></td>
                                <td style="text-align:right;">
                                    <button type="button" class="hao-btn hao-btn-primary hao-btn-sm hao-btn-generate-meta" data-post-id="<?php echo esc_attr( $item['post_id'] ); ?>" data-post-title="<?php echo esc_attr( $item['post_title'] ); ?>">
                                        <span class="dashicons dashicons-superhero-alt"></span> AI Meta Üret
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:16px; color:#065f46; font-size:13px; font-weight:600;">
                ✓ Harika! Meta açıklaması eksik olan herhangi bir yayınlanmış yazı bulunmuyor.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
