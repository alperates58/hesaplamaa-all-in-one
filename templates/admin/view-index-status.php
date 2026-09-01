<?php
/**
 * View: Google Dizin Durumu (Index Status)
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<div class="hao-card">
    <div class="hao-card-header">
        <div>
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-google" style="color:#0284c7;"></span>
                Google URL Inspection & Dizin Durumu Takibi
            </h2>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                Sayfalarınızın Google arama dizininde yer alıp almadığını (Index Status), Google tarafından seçilen canonical adresini ve tarama tarihlerini izleyin.
            </p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="hao-filter-bar">
        <form method="get" action="" style="display:flex; gap:10px; flex-wrap:wrap;">
            <input type="hidden" name="page" value="hao-index">
            
            <select name="verdict" style="border-radius:6px; font-size:13px; padding:6px 12px; border:1px solid #cbd5e1;">
                <option value="">Tüm Kararlar</option>
                <option value="PASS" <?php selected( $verdict, 'PASS' ); ?>>Geçti (PASS / İndeksli)</option>
                <option value="FAIL" <?php selected( $verdict, 'FAIL' ); ?>>Başarısız (FAIL / İndekssiz)</option>
                <option value="NEUTRAL" <?php selected( $verdict, 'NEUTRAL' ); ?>>Nötr (NEUTRAL)</option>
            </select>

            <input type="text" name="s" class="hao-search-input" placeholder="URL veya Başlık ara..." value="<?php echo esc_attr( $search ); ?>">
            <button type="submit" class="hao-btn hao-btn-secondary">Filtrele</button>
            <?php if ( ! empty( $search ) || ! empty( $verdict ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-index' ) ); ?>" class="hao-btn hao-btn-secondary">Temizle</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if ( ! empty( $statuses ) ) : ?>
        <div class="hao-table-wrap">
            <table class="hao-table">
                <thead>
                    <tr>
                        <th style="width:30%;">Sayfa URL / Başlık</th>
                        <th>Dizin Kararı</th>
                        <th>Kapsama Durumu (Coverage)</th>
                        <th>Tarama Zamanı</th>
                        <th>Seçilen Canonical</th>
                        <th style="text-align:right;">Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $statuses as $st ) : 
                        $v = strtoupper( (string) $st['verdict'] );
                        $v_class = $v === 'PASS' ? 'hao-badge-emerald' : ( $v === 'FAIL' ? 'hao-badge-rose' : 'hao-badge-amber' );
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $st['page_title'] ?: $st['page_url'] ); ?></strong>
                                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                    <a href="<?php echo esc_url( $st['page_url'] ); ?>" target="_blank" style="color:#64748b;">
                                        <?php echo esc_html( $st['page_url'] ); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="hao-badge <?php echo esc_attr( $v_class ); ?>">
                                    <?php echo esc_html( $v ); ?>
                                </span>
                            </td>
                            <td style="font-size:12px; color:#475569;">
                                <?php echo esc_html( $st['coverage_state'] ?: '—' ); ?>
                            </td>
                            <td style="font-size:12px; color:#64748b;">
                                <?php echo esc_html( $st['last_crawl_time'] ? gmdate( 'd.m.Y H:i', strtotime( $st['last_crawl_time'] ) ) : '—' ); ?>
                            </td>
                            <td style="font-size:11.5px; color:#64748b;">
                                <?php echo esc_html( $st['google_canonical'] ? wp_trim_words( $st['google_canonical'], 4, '...' ) : '—' ); ?>
                            </td>
                            <td style="text-align:right;">
                                <button type="button" class="hao-btn hao-btn-secondary hao-btn-sm hao-btn-inspect" data-url="<?php echo esc_attr( $st['page_url'] ); ?>" title="Google URL Inspection ile Yeniden İncele">
                                    <span class="dashicons dashicons-update"></span> İncele
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p style="text-align:center; padding:40px; color:#64748b;">
            Kayıtlı dizin verisi bulunamadı. URL Inspection arka planda cron ile veya tekil "İncele" butonuyla taranır.
        </p>
    <?php endif; ?>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
