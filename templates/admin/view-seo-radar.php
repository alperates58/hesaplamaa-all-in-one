<?php
/**
 * View: SEO & Büyüme Radarı
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<div class="hao-card">
    <div class="hao-card-header">
        <div>
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-chart-area" style="color:var(--hao-primary);"></span>
                SEO Büyüme Radarı & Anahtar Kelime Fırsatları
            </h2>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                Google arama sonuçlarında <strong>4. ile 20. sıra arasında</strong> olan ve gösterim hacmi bulunan, küçük dokunuşlarla ilk 3'e taşınabilecek kelimeler.
            </p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="hao-filter-bar">
        <form method="get" action="" style="display:flex; gap:10px;">
            <input type="hidden" name="page" value="hao-radar">
            <input type="text" name="s" class="hao-search-input" placeholder="Anahtar kelime ara..." value="<?php echo esc_attr( $search ); ?>">
            <button type="submit" class="hao-btn hao-btn-secondary">Filtrele</button>
            <?php if ( ! empty( $search ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-radar' ) ); ?>" class="hao-btn hao-btn-secondary">Temizle</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if ( ! empty( $opportunities ) ) : ?>
        <div class="hao-table-wrap">
            <table class="hao-table">
                <thead>
                    <tr>
                        <th>Anahtar Kelime</th>
                        <th>Hedef URL</th>
                        <th>Ortalama Sıra</th>
                        <th>Gösterim</th>
                        <th>Tıklama</th>
                        <th>CTR</th>
                        <th>Fırsat Skoru</th>
                        <th>AI Eylem Önerisi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $opportunities as $item ) : 
                        $score = (int) $item['opportunity_score'];
                        $score_class = $score >= 70 ? 'hao-score-high' : ( $score >= 50 ? 'hao-score-mid' : 'hao-score-low' );
                        $action = \HAO\Engine\SeoRadar::get_recommended_action( (float) $item['avg_position'], (float) $item['ctr'], (int) $item['impressions'] );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $item['keyword'] ); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url( $item['page_url'] ); ?>" target="_blank" style="color:var(--hao-slate-600); text-decoration:none; font-size:12px;">
                                    <?php echo esc_html( wp_trim_words( $item['page_url'], 5, '...' ) ); ?>
                                    <span class="dashicons dashicons-external" style="font-size:12px; width:12px; height:12px;"></span>
                                </a>
                            </td>
                            <td><span class="hao-badge hao-badge-slate">#<?php echo esc_html( round( (float) $item['avg_position'], 1 ) ); ?></span></td>
                            <td><strong><?php echo number_format_i18n( (int) $item['impressions'] ); ?></strong></td>
                            <td><?php echo number_format_i18n( (int) $item['clicks'] ); ?></td>
                            <td><?php echo esc_html( round( (float) $item['ctr'] * 100, 1 ) ); ?>%</td>
                            <td><span class="hao-score-pill <?php echo esc_attr( $score_class ); ?>"><?php echo esc_html( $score ); ?></span></td>
                            <td>
                                <span class="hao-badge hao-badge-<?php echo esc_attr( $action['color'] ); ?>" title="<?php echo esc_attr( $action['desc'] ); ?>">
                                    <?php echo esc_html( $action['label'] ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p style="text-align:center; padding:40px; color:#64748b;">
            Eşleşen anahtar kelime fırsatı bulunamadı.
        </p>
    <?php endif; ?>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
