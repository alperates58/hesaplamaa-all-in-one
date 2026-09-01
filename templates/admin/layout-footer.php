<?php
/**
 * All-in-One Admin Layout Footer
 */
defined( 'ABSPATH' ) || exit;
?>

    <!-- Modals -->
    <!-- 1. AI SEO Meta Modal -->
    <div id="hao-meta-modal-backdrop" class="hao-modal-backdrop">
        <div class="hao-modal">
            <div class="hao-modal-header">
                <h3>AI ile SEO Başlık & Meta Üret</h3>
                <button type="button" class="hao-modal-close">&times;</button>
            </div>
            
            <div id="hao-meta-modal-loading" style="text-align:center; padding:30px 0;">
                <span class="dashicons dashicons-update spin" style="font-size:28px; width:28px; height:28px; color:#4f46e5;"></span>
                <p style="margin-top:10px; font-weight:600; color:#64748b;">AI en uygun başlık ve meta açıklamasını üretiyor...</p>
            </div>

            <div id="hao-meta-modal-body" style="display:none;">
                <input type="hidden" id="hao-meta-modal-post-id" value="">
                
                <p style="font-size:13px; color:#64748b; margin-bottom:14px;">
                    Yazı: <strong id="hao-meta-modal-title-display" style="color:#0f172a;"></strong>
                </p>

                <!-- Google SERP Snippet Preview -->
                <div class="hao-serp-preview">
                    <div class="hao-serp-url">https://hesaplamaa.com › ...</div>
                    <div id="hao-preview-title" class="hao-serp-title"></div>
                    <div id="hao-preview-desc" class="hao-serp-desc"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px; text-transform:uppercase; color:#475569;">SEO Başlığı (50-60 Karakter)</label>
                    <input type="text" id="hao-input-seo-title" class="widefat" style="border-radius:6px; padding:8px 10px;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px; text-transform:uppercase; color:#475569;">Meta Açıklaması (130-155 Karakter)</label>
                    <textarea id="hao-input-meta-desc" rows="3" class="widefat" style="border-radius:6px; padding:8px 10px;"></textarea>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; font-weight:700; margin-bottom:4px; text-transform:uppercase; color:#475569;">Odak Anahtar Kelime</label>
                    <input type="text" id="hao-input-focus-kw" class="widefat" style="border-radius:6px; padding:8px 10px;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="hao-btn hao-btn-secondary hao-modal-close">İptal</button>
                    <button type="button" id="hao-btn-apply-meta" class="hao-btn hao-btn-primary">Kaydet ve Uygula</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. İç Link Önerileri Modal -->
    <div id="hao-link-modal-backdrop" class="hao-modal-backdrop">
        <div class="hao-modal">
            <div class="hao-modal-header">
                <h3>Akıllı İç Link Önerileri</h3>
                <button type="button" class="hao-modal-close">&times;</button>
            </div>
            <input type="hidden" id="hao-link-modal-post-id" value="">
            <p style="font-size:13px; color:#64748b; margin-bottom:14px;">
                Hedef Yazı: <strong id="hao-link-modal-title" style="color:#0f172a;"></strong>
            </p>
            <div id="hao-link-modal-list"></div>
        </div>
    </div>

    <div style="margin-top:40px; padding-top:20px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#94a3b8;">
        <div>Hesaplamaa All-in-One v<?php echo esc_html( HAO_VERSION ); ?> &bull; Geliştirici: <strong>Alper ATEŞ</strong></div>
        <div>Tüm hakları saklıdır &copy; <?php echo esc_html( date( 'Y' ) ); ?></div>
    </div>
</div><!-- /.hao-wrap -->
