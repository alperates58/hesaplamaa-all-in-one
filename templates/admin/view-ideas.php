<?php
/**
 * View: Yeni Fikirler & Suggest Avcısı
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';
?>

<div class="hao-card">
    <div class="hao-card-header">
        <div>
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-lightbulb" style="color:#f59e0b;"></span>
                Yeni Hesaplama Fikirleri & Google Suggest Madencisi
            </h2>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                Google arama motoru tamamlama (Suggest) verilerini A-Z alfabesiyle derinlemesine tarayarak insanların en çok arattığı yeni hesaplama konularını keşfedin.
            </p>
        </div>
    </div>

    <!-- Suggest Generator Form -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px; margin-bottom:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <div style="flex:1; min-width:240px;">
            <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Tohum Arama Kalıbı</label>
            <input type="text" id="hao-seed-input" class="widefat" value="hesaplama" placeholder="örn: hesaplama, hesaplayıcı, faiz hesapla..." style="padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1;">
        </div>
        <div style="padding-top:18px;">
            <button type="button" id="hao-btn-expand-ideas" class="hao-btn hao-btn-primary">
                <span class="dashicons dashicons-search"></span> Google Suggest ile Yeni Fikirleri Tara
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="hao-filter-bar">
        <form method="get" action="" style="display:flex; gap:10px;">
            <input type="hidden" name="page" value="hao-ideas">
            <input type="text" name="s" class="hao-search-input" placeholder="Fikirlerde ara..." value="<?php echo esc_attr( $search ); ?>">
            <button type="submit" class="hao-btn hao-btn-secondary">Filtrele</button>
            <?php if ( ! empty( $search ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hao-ideas' ) ); ?>" class="hao-btn hao-btn-secondary">Temizle</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <?php if ( ! empty( $suggestions ) ) : ?>
        <div class="hao-table-wrap">
            <table class="hao-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Önerilen Konu / Hesaplama Başlığı</th>
                        <th>Tahmini Fırsat Skoru</th>
                        <th>Kaynak</th>
                        <th>Eklenme Tarihi</th>
                        <th style="text-align:right;">Plan Listesine Ekle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $suggestions as $item ) : 
                        $score = (int) $item['opportunity_score'];
                        $score_class = $score >= 70 ? 'hao-score-high' : ( $score >= 50 ? 'hao-score-mid' : 'hao-score-low' );
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $item['topic'] ); ?></strong>
                            </td>
                            <td>
                                <span class="hao-score-pill <?php echo esc_attr( $score_class ); ?>">
                                    <?php echo esc_html( $score ); ?>
                                </span>
                            </td>
                            <td>
                                <span class="hao-badge hao-badge-slate">
                                    <?php echo esc_html( strtoupper( (string) $item['source'] ) ); ?>
                                </span>
                            </td>
                            <td style="font-size:12px; color:#64748b;">
                                <?php echo esc_html( gmdate( 'd.m.Y', strtotime( (string) $item['created_at'] ) ) ); ?>
                            </td>
                            <td style="text-align:right;">
                                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; font-weight:600; color:#475569;">
                                    <input type="checkbox" class="hao-idea-toggle" data-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( (int) $item['should_create'], 1 ); ?>>
                                    <span>Geliştirilecek</span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p style="text-align:center; padding:40px; color:#64748b;">
            Henüz kayıtlı öneri bulunamadı. Yukarıdaki kutudan <strong>"Google Suggest ile Yeni Fikirleri Tara"</strong> butonuna basabilirsiniz.
        </p>
    <?php endif; ?>
</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
