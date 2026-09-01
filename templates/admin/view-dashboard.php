<?php
/**
 * View: Dashboard (Genel Bakış)
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<!-- KPI Grid -->
<div class="hao-kpi-grid">
    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>Toplam Tıklama</span>
            <span class="dashicons dashicons-chart-line" style="color:var(--hao-primary);"></span>
        </div>
        <div class="hao-kpi-val"><?php echo number_format_i18n( $summary['total_clicks'] ); ?></div>
        <div class="hao-kpi-sub">Son 30 günlük Google organik arama</div>
    </div>

    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>Toplam Gösterim</span>
            <span class="dashicons dashicons-visibility" style="color:var(--hao-success);"></span>
        </div>
        <div class="hao-kpi-val"><?php echo number_format_i18n( $summary['total_impressions'] ); ?></div>
        <div class="hao-kpi-sub">Google arama sonuçlarında görünme</div>
    </div>

    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>Ortalama Sıra</span>
            <span class="dashicons dashicons-awards" style="color:var(--hao-warning);"></span>
        </div>
        <div class="hao-kpi-val"><?php echo esc_html( $summary['avg_position'] > 0 ? $summary['avg_position'] : '—' ); ?></div>
        <div class="hao-kpi-sub">Tüm kelimelerin ortalaması</div>
    </div>

    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>Top 3 & Top 10 Kelimeler</span>
            <span class="dashicons dashicons-star-filled" style="color:#f59e0b;"></span>
        </div>
        <div class="hao-kpi-val">
            <span style="color:#10b981;"><?php echo number_format_i18n( $summary['top3_count'] ); ?></span>
            <span style="font-size:18px; color:#94a3b8; font-weight:400;">/ <?php echo number_format_i18n( $summary['top10_count'] ); ?></span>
        </div>
        <div class="hao-kpi-sub">İlk sayfadaki anahtar kelimeler</div>
    </div>

    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>Google Dizin Durumu</span>
            <span class="dashicons dashicons-google" style="color:#0284c7;"></span>
        </div>
        <div class="hao-kpi-val"><?php echo number_format_i18n( $summary['indexed_count'] ); ?></div>
        <div class="hao-kpi-sub">Doğrulanmış indeksli sayfalar</div>
    </div>

    <div class="hao-kpi-card">
        <div class="hao-kpi-label">
            <span>SEO Radar Fırsatları</span>
            <span class="dashicons dashicons-bell" style="color:#ef4444;"></span>
        </div>
        <div class="hao-kpi-val" style="color:#ef4444;"><?php echo number_format_i18n( $summary['radar_count'] ); ?></div>
        <div class="hao-kpi-sub">Pozisyon 4-20 arası sıçrama adayları</div>
    </div>
</div>

<!-- Growth Trends Chart -->
<div class="hao-card">
    <div class="hao-card-header">
        <h2 class="hao-card-title">
            <span class="dashicons dashicons-chart-area" style="color:var(--hao-primary);"></span>
            Google Organik Büyüme Trendi (Son 30 Gün)
        </h2>
        <div>
            <span class="hao-badge hao-badge-indigo">Canlı GSC Verisi</span>
        </div>
    </div>
    
    <div style="height: 300px; position: relative;">
        <canvas id="haoGrowthChart" data-stats="<?php echo esc_attr( wp_json_encode( $daily_stats ) ); ?>"></canvas>
    </div>
</div>

<!-- Radar Alerts -->
<div class="hao-card">
    <div class="hao-card-header">
        <h2 class="hao-card-title">
            <span class="dashicons dashicons-lightbulb" style="color:#f59e0b;"></span>
            Kritik Büyüme Fırsatları & Sıralama Sıçrama Adayları
        </h2>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-radar' ) ); ?>" class="hao-btn hao-btn-secondary hao-btn-sm">
            Tüm Radar Fırsatlarını Gör &rarr;
        </a>
    </div>

    <?php if ( ! empty( $top_alerts ) ) : ?>
        <div class="hao-table-wrap">
            <table class="hao-table">
                <thead>
                    <tr>
                        <th>Anahtar Kelime</th>
                        <th>Hedef URL</th>
                        <th>Sıra</th>
                        <th>Gösterim</th>
                        <th>Tıklama</th>
                        <th>CTR</th>
                        <th>Fırsat Skoru</th>
                        <th>Eylem Önerisi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $top_alerts as $alert ) : 
                        $score = (int) $alert['opportunity_score'];
                        $score_class = $score >= 70 ? 'hao-score-high' : ( $score >= 50 ? 'hao-score-mid' : 'hao-score-low' );
                        $action = \HAO\Engine\SeoRadar::get_recommended_action( (float) $alert['avg_position'], (float) $alert['ctr'], (int) $alert['impressions'] );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $alert['keyword'] ); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url( $alert['page_url'] ); ?>" target="_blank" style="color:var(--hao-slate-600); text-decoration:none; font-size:12px;">
                                    <?php echo esc_html( wp_trim_words( $alert['page_url'], 6, '...' ) ); ?>
                                    <span class="dashicons dashicons-external" style="font-size:12px; width:12px; height:12px;"></span>
                                </a>
                            </td>
                            <td><span class="hao-badge hao-badge-slate">#<?php echo esc_html( round( (float) $alert['avg_position'], 1 ) ); ?></span></td>
                            <td><strong><?php echo number_format_i18n( (int) $alert['impressions'] ); ?></strong></td>
                            <td><?php echo number_format_i18n( (int) $alert['clicks'] ); ?></td>
                            <td><?php echo esc_html( round( (float) $alert['ctr'] * 100, 1 ) ); ?>%</td>
                            <td><span class="hao-score-pill <?php echo esc_attr( $score_class ); ?>"><?php echo esc_html( $score ); ?></span></td>
                            <td><span class="hao-badge hao-badge-<?php echo esc_attr( $action['color'] ); ?>"><?php echo esc_html( $action['label'] ); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p style="text-align:center; padding:30px; color:#64748b;">
            Henüz GSC verisi senkronize edilmedi veya eşleşen radar fırsatı yok. Yukarıdaki <strong>"GSC Verilerini Çek"</strong> butonuna tıklayarak senkronizasyon yapabilirsiniz.
        </p>
    <?php endif; ?>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
