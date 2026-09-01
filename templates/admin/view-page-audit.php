<?php
/**
 * View: Birleşik Sayfa & Kalite Denetimi (Master Audit Table)
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<div class="hao-card">
    <div class="hao-card-header">
        <div>
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-search" style="color:var(--hao-primary);"></span>
                Birleşik Sayfa & Kalite Denetimi
            </h2>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                Tüm yayınlanmış yazılarınızın hem <strong>Google canlı performansını (GSC)</strong> hem de <strong>On-Page SEO, eksik modül, meta ve bozuk metin</strong> analizlerini tek merkezden yönetin.
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="hao-filter-bar">
        <div class="hao-filter-tabs">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=all' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                Tüm Sayfalar
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=missing_meta' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'missing_meta' ? 'active' : ''; ?>">
                ⚠️ Eksik Metalı
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=missing_shortcode' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'missing_shortcode' ? 'active' : ''; ?>">
                🔢 Eksik Shortcode [hc_]
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=broken_text' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'broken_text' ? 'active' : ''; ?>">
                🚨 Bozuk Metin / CSS
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=index_issue' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'index_issue' ? 'active' : ''; ?>">
                🌐 Dizin Sorunlular
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=high_opportunity' ) ); ?>" class="hao-filter-btn <?php echo $filter === 'high_opportunity' ? 'active' : ''; ?>">
                🚀 Yüksek Fırsatlı
            </a>
        </div>

        <form method="get" action="" style="display:flex; gap:10px;">
            <input type="hidden" name="page" value="hao-audit">
            <input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>">
            <input type="text" name="s" class="hao-search-input" placeholder="Sayfa başlığı ara..." value="<?php echo esc_attr( $search ); ?>">
            <button type="submit" class="hao-btn hao-btn-secondary">Ara</button>
            <?php if ( ! empty( $search ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-audit&filter=' . $filter ) ); ?>" class="hao-btn hao-btn-secondary">Temizle</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if ( ! empty( $pages ) ) : ?>
        <div class="hao-table-wrap">
            <table class="hao-table">
                <thead>
                    <tr>
                        <th style="width:28%;">Yazı / Sayfa Başlığı</th>
                        <th>Sağlık Puanı</th>
                        <th>GSC Tıklama / Gösterim</th>
                        <th>Google Sıra</th>
                        <th>İçerik Yapısı</th>
                        <th>Durum & Uyarılar</th>
                        <th style="text-align:right;">Hızlı Aksiyonlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $pages as $p ) : 
                        $score = (int) $p['health_score'];
                        $score_class = $score >= 80 ? 'hao-score-high' : ( $score >= 60 ? 'hao-score-mid' : 'hao-score-low' );
                    ?>
                        <tr>
                            <td>
                                <strong style="font-size:13.5px; color:#0f172a;"><?php echo esc_html( $p['post_title'] ); ?></strong>
                                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                    <a href="<?php echo esc_url( $p['permalink'] ); ?>" target="_blank" style="color:#64748b; text-decoration:none;">
                                        <?php echo esc_html( wp_trim_words( $p['permalink'], 6, '...' ) ); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="hao-score-pill <?php echo esc_attr( $score_class ); ?>" title="İçerik Sağlık Puanı: <?php echo esc_attr( $score ); ?>/100">
                                    <?php echo esc_html( $score ); ?>
                                </span>
                            </td>
                            <td>
                                <div><strong><?php echo number_format_i18n( $p['clicks'] ); ?></strong> tık</div>
                                <div style="font-size:11px; color:#64748b;"><?php echo number_format_i18n( $p['impressions'] ); ?> gösterim</div>
                            </td>
                            <td>
                                <?php if ( $p['position'] > 0 ) : ?>
                                    <span class="hao-badge hao-badge-slate">#<?php echo esc_html( round( $p['position'], 1 ) ); ?></span>
                                <?php else : ?>
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;">
                                <div><?php echo number_format_i18n( $p['word_count'] ); ?> kelime</div>
                                <div style="color:#64748b; font-size:11px;"><?php echo esc_html( $p['h2_count'] ); ?> H2 &bull; <?php echo esc_html( $p['internal_links'] ); ?> iç link</div>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <?php if ( ! $p['has_calculator'] ) : ?>
                                        <span class="hao-badge hao-badge-rose" title="Sayfada [hc_] hesaplama shortcode'u bulunamadı!">Eksik Shortcode</span>
                                    <?php endif; ?>

                                    <?php if ( ! $p['has_meta'] ) : ?>
                                        <span class="hao-badge hao-badge-amber" title="Meta açıklaması girilmemiş!">Eksik Meta</span>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $p['broken_issues'] ) ) : ?>
                                        <span class="hao-badge hao-badge-rose" title="Bozuk kalıp: <?php echo esc_attr( implode( ', ', $p['broken_issues'] ) ); ?>">Bozuk Metin</span>
                                    <?php endif; ?>

                                    <?php if ( $p['index_verdict'] === 'PASS' ) : ?>
                                        <span class="hao-badge hao-badge-emerald">İndeksli</span>
                                    <?php elseif ( $p['index_verdict'] === 'FAIL' ) : ?>
                                        <span class="hao-badge hao-badge-rose">İndekssiz</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <button type="button" class="hao-btn hao-btn-secondary hao-btn-sm hao-btn-generate-meta" data-post-id="<?php echo esc_attr( $p['post_id'] ); ?>" data-post-title="<?php echo esc_attr( $p['post_title'] ); ?>" title="AI ile Başlık ve Meta Açıklama Üret">
                                        <span class="dashicons dashicons-superhero-alt"></span> AI Meta
                                    </button>
                                    <button type="button" class="hao-btn hao-btn-secondary hao-btn-sm hao-btn-link-suggest" data-post-id="<?php echo esc_attr( $p['post_id'] ); ?>" data-post-title="<?php echo esc_attr( $p['post_title'] ); ?>" title="İç Link Önerileri">
                                        <span class="dashicons dashicons-admin-links"></span> Link
                                    </button>
                                    <a href="<?php echo esc_url( $p['edit_url'] ); ?>" class="hao-btn hao-btn-secondary hao-btn-sm" target="_blank" title="Düzenle">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p style="text-align:center; padding:40px; color:#64748b;">
            Seçilen filtreye uygun sayfa bulunamadı.
        </p>
    <?php endif; ?>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
