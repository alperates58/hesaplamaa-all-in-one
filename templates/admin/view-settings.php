<?php
/**
 * View: Entegrasyonlar & AI Hub Ayarları
 */
defined( 'ABSPATH' ) || exit;

include HAO_DIR . 'templates/admin/layout-header.php';

// OAuth Callback Yakalama
if ( isset( $_GET['hao_gsc_callback'] ) && ! empty( $_GET['code'] ) ) {
    $tokens = $gsc_client->exchange_code_for_tokens( sanitize_text_field( $_GET['code'] ) );
    if ( is_wp_error( $tokens ) ) {
        echo '<div class="notice notice-error" style="margin-bottom:20px;"><p>GSC Yetkilendirme Hatası: ' . esc_html( $tokens->get_error_message() ) . '</p></div>';
    } else {
        echo '<div class="notice notice-success" style="margin-bottom:20px;"><p>Google Search Console başarıyla bağlandı!</p></div>';
    }
}
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:24px;">

    <!-- 1. Google Search Console Ayarları -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-google" style="color:#0284c7;"></span>
                Google Search Console Entegrasyonu
            </h2>
        </div>

        <form id="hao-form-gsc-settings">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Site URL (GSC Mülkü)</label>
                <input type="text" name="gsc_site_url" class="widefat" value="<?php echo esc_attr( $gsc_settings['gsc_site_url'] ?? get_site_url() ); ?>" style="border-radius:6px; padding:8px 10px;">
                <small style="color:#64748b; font-size:11px;">Örn: https://hesaplamaa.com/ veya sc-domain:hesaplamaa.com</small>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">OAuth Client ID</label>
                <input type="text" name="gsc_client_id" class="widefat" value="<?php echo esc_attr( $gsc_settings['gsc_client_id'] ?? '' ); ?>" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">OAuth Client Secret</label>
                <input type="password" name="gsc_client_secret" class="widefat" value="<?php echo esc_attr( $gsc_settings['gsc_client_secret'] ?? '' ); ?>" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:18px; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #e2e8f0; font-size:11.5px; color:#64748b;">
                <strong>Yetkili Yönlendirme URI (Redirect URI):</strong><br>
                <code style="word-break:break-all;"><?php echo esc_html( admin_url( 'admin.php?page=hao-settings&hao_gsc_callback=1' ) ); ?></code>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <button type="submit" class="hao-btn hao-btn-primary">GSC Bilgilerini Kaydet</button>
                <?php if ( ! empty( $gsc_settings['gsc_client_id'] ) ) : ?>
                    <a href="<?php echo esc_url( $gsc_client->get_auth_url() ); ?>" class="hao-btn hao-btn-secondary">
                        <span class="dashicons dashicons-external"></span> Google ile Bağlan
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 2. Merkezi AI Hub Ayarları -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-superhero-alt" style="color:#7c3aed;"></span>
                Merkezi AI Hub (OpenAI / DeepSeek / Gemini)
            </h2>
        </div>

        <form id="hao-form-ai-settings">
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Aktif / Varsayılan AI Sağlayıcı</label>
                <select name="provider" style="width:100%; border-radius:6px; padding:8px 10px; border:1px solid #cbd5e1; font-weight:600;">
                    <option value="openai" <?php selected( $ai_settings['provider'], 'openai' ); ?>>OpenAI (GPT-4o mini / Flash)</option>
                    <option value="gemini" <?php selected( $ai_settings['provider'], 'gemini' ); ?>>Google Gemini (Gemini 2.0 Flash / 1.5 Flash)</option>
                    <option value="deepseek" <?php selected( $ai_settings['provider'], 'deepseek' ); ?>>DeepSeek (V3 / R1)</option>
                </select>
            </div>

            <!-- OpenAI Ayarları -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px;">
                <div style="font-weight:700; font-size:12.5px; color:#0f172a; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>OpenAI Yapılandırması</span>
                    <span class="hao-badge hao-badge-indigo">GPT-4o Mini (Flash)</span>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">OpenAI API Key</label>
                    <input type="password" name="openai_key" class="widefat" value="<?php echo esc_attr( $ai_settings['openai_key'] ); ?>" placeholder="sk-proj-..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">Model</label>
                    <select name="openai_model" style="width:100%; border-radius:6px; padding:6px 8px; border:1px solid #cbd5e1; font-size:12px;">
                        <option value="gpt-4o-mini" <?php selected( $ai_settings['openai_model'] ?? 'gpt-4o-mini', 'gpt-4o-mini' ); ?>>gpt-4o-mini (Önerilen Hızlı & Ekonomik / Flash)</option>
                        <option value="gpt-4o" <?php selected( $ai_settings['openai_model'] ?? '', 'gpt-4o' ); ?>>gpt-4o (Tam Model)</option>
                        <option value="o3-mini" <?php selected( $ai_settings['openai_model'] ?? '', 'o3-mini' ); ?>>o3-mini (Reasoning)</option>
                    </select>
                </div>
            </div>

            <!-- Gemini Ayarları -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px;">
                <div style="font-weight:700; font-size:12.5px; color:#0f172a; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>Google Gemini Yapılandırması</span>
                    <span class="hao-badge hao-badge-emerald">Gemini Flash</span>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">Gemini API Key</label>
                    <input type="password" name="gemini_key" class="widefat" value="<?php echo esc_attr( $ai_settings['gemini_key'] ); ?>" placeholder="AIzaSy..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">Model</label>
                    <select name="gemini_model" style="width:100%; border-radius:6px; padding:6px 8px; border:1px solid #cbd5e1; font-size:12px;">
                        <option value="gemini-2.0-flash" <?php selected( $ai_settings['gemini_model'] ?? 'gemini-2.0-flash', 'gemini-2.0-flash' ); ?>>gemini-2.0-flash (En Hızlı Yeni Flash)</option>
                        <option value="gemini-1.5-flash" <?php selected( $ai_settings['gemini_model'] ?? '', 'gemini-1.5-flash' ); ?>>gemini-1.5-flash (Standart Flash)</option>
                        <option value="gemini-1.5-pro" <?php selected( $ai_settings['gemini_model'] ?? '', 'gemini-1.5-pro' ); ?>>gemini-1.5-pro (Gelişmiş)</option>
                    </select>
                </div>
            </div>

            <!-- DeepSeek Ayarları -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:16px;">
                <div style="font-weight:700; font-size:12.5px; color:#0f172a; margin-bottom:8px;">
                    <span>DeepSeek Yapılandırması</span>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">DeepSeek API Key</label>
                    <input type="password" name="deepseek_key" class="widefat" value="<?php echo esc_attr( $ai_settings['deepseek_key'] ); ?>" placeholder="sk-..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">Model</label>
                    <select name="deepseek_model" style="width:100%; border-radius:6px; padding:6px 8px; border:1px solid #cbd5e1; font-size:12px;">
                        <option value="deepseek-chat" <?php selected( $ai_settings['deepseek_model'] ?? 'deepseek-chat', 'deepseek-chat' ); ?>>deepseek-chat (DeepSeek-V3)</option>
                        <option value="deepseek-reasoner" <?php selected( $ai_settings['deepseek_model'] ?? '', 'deepseek-reasoner' ); ?>>deepseek-reasoner (DeepSeek-R1)</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <button type="submit" class="hao-btn hao-btn-primary">AI Ayarlarını Kaydet</button>
                <button type="button" id="hao-btn-test-ai" class="hao-btn hao-btn-secondary">
                    Bağlantıyı Test Et
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Google Ads API Ayarları -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-chart-bar" style="color:#10b981;"></span>
                Google Ads Keyword Planner API
            </h2>
        </div>

        <form id="hao-form-ads-settings">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Developer Token</label>
                <input type="password" name="ads_dev_token" class="widefat" value="<?php echo esc_attr( $gsc_settings['google_ads_dev_token'] ?? '' ); ?>" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Customer ID (Hesap No)</label>
                <input type="text" name="ads_customer_id" class="widefat" value="<?php echo esc_attr( $gsc_settings['google_ads_customer_id'] ?? '' ); ?>" placeholder="123-456-7890" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">OAuth Client ID & Secret</label>
                <input type="text" name="ads_client_id" class="widefat" value="<?php echo esc_attr( $gsc_settings['google_ads_client_id'] ?? '' ); ?>" placeholder="Client ID" style="border-radius:6px; padding:8px 10px; margin-bottom:6px;">
                <input type="password" name="ads_client_secret" class="widefat" value="<?php echo esc_attr( $gsc_settings['google_ads_client_secret'] ?? '' ); ?>" placeholder="Client Secret" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Refresh Token</label>
                <input type="password" name="ads_refresh_token" class="widefat" value="<?php echo esc_attr( $gsc_settings['google_ads_refresh_token'] ?? '' ); ?>" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div>
                <button type="submit" class="hao-btn hao-btn-primary">Google Ads Bilgilerini Kaydet</button>
            </div>
        </form>
    </div>

    <!-- 4. Sistem ve Cron Durumu -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-info" style="color:#64748b;"></span>
                Sistem & Cron Teşhis Paneli
            </h2>
        </div>

        <table class="widefat" style="border:none; box-shadow:none;">
            <tbody>
                <tr>
                    <td style="font-weight:600;">Eklenti Sürümü:</td>
                    <td><code>v<?php echo esc_html( HAO_VERSION ); ?></code></td>
                </tr>
                <tr>
                    <td style="font-weight:600;">PHP Sürümü:</td>
                    <td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
                </tr>
                <tr>
                    <td style="font-weight:600;">GSC Günlük Cron:</td>
                    <td>
                        <?php 
                        $next_gsc = wp_next_scheduled( 'hao_daily_sync' );
                        echo $next_gsc ? '<span class="hao-badge hao-badge-emerald">Aktif: ' . esc_html( wp_date( 'd.m.Y H:i', $next_gsc ) ) . '</span>' : '<span class="hao-badge hao-badge-amber">Planlanmamış</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:600;">Dizin Tarama Cron:</td>
                    <td>
                        <?php 
                        $next_idx = wp_next_scheduled( 'hao_index_status_sync' );
                        echo $next_idx ? '<span class="hao-badge hao-badge-emerald">Aktif: ' . esc_html( wp_date( 'd.m.Y H:i', $next_idx ) ) . '</span>' : '<span class="hao-badge hao-badge-amber">Planlanmamış</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:600;">Haftalık Fikir Cron:</td>
                    <td>
                        <?php 
                        $next_idea = wp_next_scheduled( 'hao_weekly_ideas' );
                        echo $next_idea ? '<span class="hao-badge hao-badge-emerald">Aktif: ' . esc_html( wp_date( 'd.m.Y H:i', $next_idea ) ) . '</span>' : '<span class="hao-badge hao-badge-amber">Planlanmamış</span>';
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php include HAO_DIR . 'templates/admin/layout-footer.php'; ?>
