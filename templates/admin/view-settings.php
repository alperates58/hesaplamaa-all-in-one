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

if ( isset( $_GET['hao_updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible" style="margin-bottom:20px;"><p><strong>Hesaplamaa All-in-One başarıyla en son GitHub sürümüne güncellendi!</strong></p></div>';
}

$gh_updater  = new \HAO\Core\GithubUpdater();
$gh_settings = $gh_updater->get_settings();
$local_sha   = substr( (string) get_option( 'hao_last_update_sha', '' ), 0, 7 );
$last_update = get_option( 'hao_last_update_time', '' );

// HGE Uyumlu Değerler (Otomatik Aktarım ve Miras Alma)
$gsc_settings      = get_option( 'hge_settings', [] );
$ads_dev_token     = $gsc_settings['google_ads_developer_token'] ?? ( $gsc_settings['google_ads_dev_token'] ?? '' );
$ads_customer_id   = $gsc_settings['google_ads_customer_id'] ?? '7679956929';
$ads_client_id     = ! empty( $gsc_settings['google_ads_client_id'] ) ? $gsc_settings['google_ads_client_id'] : ( $gsc_settings['gsc_client_id'] ?? '' );
$ads_client_secret = ! empty( $gsc_settings['google_ads_client_secret'] ) ? $gsc_settings['google_ads_client_secret'] : ( $gsc_settings['gsc_client_secret'] ?? '' );
$ads_refresh_token = $gsc_settings['google_ads_refresh_token'] ?? '';
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:24px;">

    <!-- 1. Merkezi AI Hub Ayarları (DeepSeek V4 Flash / OpenAI / Gemini) -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-superhero-alt" style="color:#7c3aed;"></span>
                Merkezi AI Hub (DeepSeek / OpenAI / Gemini)
            </h2>
        </div>

        <form id="hao-form-ai-settings">
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Aktif / Varsayılan AI Sağlayıcı</label>
                <select name="provider" style="width:100%; border-radius:6px; padding:8px 10px; border:1px solid #cbd5e1; font-weight:700; color:#0f172a;">
                    <option value="deepseek" <?php selected( $ai_settings['provider'], 'deepseek' ); ?>>DeepSeek (deepseek-v4-flash)</option>
                    <option value="openai" <?php selected( $ai_settings['provider'], 'openai' ); ?>>OpenAI (GPT-4o mini / Flash)</option>
                    <option value="gemini" <?php selected( $ai_settings['provider'], 'gemini' ); ?>>Google Gemini (Gemini 2.0 Flash / 1.5 Flash)</option>
                </select>
            </div>

            <!-- DeepSeek Ayarları -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px; margin-bottom:16px;">
                <div style="font-weight:700; font-size:13px; color:#166534; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>DeepSeek Yapılandırması</span>
                    <span class="hao-badge hao-badge-emerald">deepseek-v4-flash</span>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#15803d; margin-bottom:2px;">DeepSeek API Key</label>
                    <input type="password" name="deepseek_key" class="widefat" value="<?php echo esc_attr( $ai_settings['deepseek_key'] ); ?>" placeholder="sk-..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#15803d; margin-bottom:2px;">Model</label>
                    <select name="deepseek_model" style="width:100%; border-radius:6px; padding:6px 8px; border:1px solid #86efac; font-size:12px; font-weight:600;">
                        <option value="deepseek-v4-flash" <?php selected( $ai_settings['deepseek_model'] ?? 'deepseek-v4-flash', 'deepseek-v4-flash' ); ?>>deepseek-v4-flash (DeepSeek V4 Flash - En Hızlı & Önerilen)</option>
                        <option value="deepseek-flash" <?php selected( $ai_settings['deepseek_model'] ?? '', 'deepseek-flash' ); ?>>deepseek-flash</option>
                        <option value="deepseek-chat" <?php selected( $ai_settings['deepseek_model'] ?? '', 'deepseek-chat' ); ?>>deepseek-chat (DeepSeek-V3)</option>
                        <option value="deepseek-reasoner" <?php selected( $ai_settings['deepseek_model'] ?? '', 'deepseek-reasoner' ); ?>>deepseek-reasoner (DeepSeek-R1)</option>
                    </select>
                </div>
            </div>

            <!-- OpenAI Ayarları -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px;">
                <div style="font-weight:700; font-size:12.5px; color:#0f172a; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>OpenAI Yapılandırması</span>
                    <span class="hao-badge hao-badge-indigo">GPT-4o Mini</span>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">OpenAI API Key</label>
                    <input type="password" name="openai_key" class="widefat" value="<?php echo esc_attr( $ai_settings['openai_key'] ); ?>" placeholder="sk-proj-..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:2px;">Model</label>
                    <select name="openai_model" style="width:100%; border-radius:6px; padding:6px 8px; border:1px solid #cbd5e1; font-size:12px;">
                        <option value="gpt-4o-mini" <?php selected( $ai_settings['openai_model'] ?? 'gpt-4o-mini', 'gpt-4o-mini' ); ?>>gpt-4o-mini (Hızlı & Ekonomik / Flash)</option>
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

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <button type="submit" class="hao-btn hao-btn-primary">AI Ayarlarını Kaydet</button>
                <button type="button" id="hao-btn-test-ai" class="hao-btn hao-btn-secondary">
                    Bağlantıyı Test Et
                </button>
            </div>
        </form>
    </div>

    <!-- 2. GitHub Güncelleme Merkezi -->
    <div class="hao-card">
        <div class="hao-card-header">
            <h2 class="hao-card-title">
                <span class="dashicons dashicons-update" style="color:#0f172a;"></span>
                GitHub Güncelleme Merkezi
            </h2>
            <span class="hao-badge hao-badge-slate">
                SHA: <?php echo esc_html( $local_sha ?: 'v1.0.0' ); ?>
            </span>
        </div>

        <div style="margin-bottom:16px;">
            <p style="font-size:13px; color:#475569; margin:0 0 12px 0;">
                Eklentiyi GitHub üzerindeki en son koda tek tıkla güncelleyin.
            </p>
            <div id="hao-github-status" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:14px; font-size:12.5px;">
                <div><strong>Depo (Repository):</strong> <code><?php echo esc_html( $gh_settings['repo'] ); ?></code></div>
                <div><strong>Dal (Branch):</strong> <code><?php echo esc_html( $gh_settings['branch'] ); ?></code></div>
                <?php if ( $last_update ) : ?>
                    <div style="color:#64748b; font-size:11.5px; margin-top:4px;">Son Güncelleme: <?php echo esc_html( gmdate( 'd.m.Y H:i', strtotime( $last_update ) ) ); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:center;">
            <button type="button" id="hao-btn-github-check" class="hao-btn hao-btn-secondary">
                <span class="dashicons dashicons-search"></span> Güncellemeleri Denetle
            </button>
            <button type="button" id="hao-btn-github-update" class="hao-btn hao-btn-primary">
                <span class="dashicons dashicons-download"></span> GitHub'dan Şimdi Güncelle (AJAX)
            </button>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                <input type="hidden" name="action" value="hao_update_from_github">
                <?php wp_nonce_field( 'hao_github_update_action', 'hao_nonce' ); ?>
                <button type="submit" class="hao-btn hao-btn-secondary" onclick="return confirm('GitHub üzerinden güncelleme başlatılsın mı?');">
                    Doğrudan Güncelle (Form)
                </button>
            </form>
        </div>

        <details style="border-top:1px solid #e2e8f0; padding-top:12px; font-size:12px; color:#64748b;">
            <summary style="cursor:pointer; font-weight:600; color:#475569;">Gelişmiş GitHub Ayarları (Token / Repo Değiştir)</summary>
            <form id="hao-form-github-settings" style="margin-top:12px;">
                <div style="margin-bottom:10px;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:2px;">Repo Adı</label>
                    <input type="text" name="repo" class="widefat" value="<?php echo esc_attr( $gh_settings['repo'] ); ?>" style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:2px;">Branch</label>
                    <input type="text" name="branch" class="widefat" value="<?php echo esc_attr( $gh_settings['branch'] ); ?>" style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:2px;">GitHub Personal Access Token (Opsiyonel)</label>
                    <input type="password" name="token" class="widefat" value="<?php echo esc_attr( $gh_settings['token'] ); ?>" placeholder="ghp_..." style="border-radius:6px; padding:6px 8px; font-size:12px;">
                </div>
                <button type="submit" class="hao-btn hao-btn-secondary hao-btn-sm">Kaydet</button>
            </form>
        </details>
    </div>

    <!-- 3. Google Search Console Ayarları -->
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

    <!-- 4. Google Ads API Ayarları -->
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
                <input type="password" name="ads_dev_token" class="widefat" value="<?php echo esc_attr( $ads_dev_token ); ?>" placeholder="HGE'de tanımlı Developer Token" style="border-radius:6px; padding:8px 10px;">
                <small style="color:#64748b; font-size:11px;">Google Ads API Developer Token</small>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Customer ID (Hesap No)</label>
                <input type="text" name="ads_customer_id" class="widefat" value="<?php echo esc_attr( $ads_customer_id ); ?>" placeholder="7679956929" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">OAuth Client ID & Secret (GSC ile Ortak)</label>
                <input type="text" name="ads_client_id" class="widefat" value="<?php echo esc_attr( $ads_client_id ); ?>" placeholder="OAuth Client ID" style="border-radius:6px; padding:8px 10px; margin-bottom:6px;">
                <input type="password" name="ads_client_secret" class="widefat" value="<?php echo esc_attr( $ads_client_secret ); ?>" placeholder="OAuth Client Secret" style="border-radius:6px; padding:8px 10px;">
                <small style="color:#64748b; font-size:11px;">GSC Client ID & Secret bilgileri otomatik miras alınır.</small>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; text-transform:uppercase;">Refresh Token</label>
                <input type="password" name="ads_refresh_token" class="widefat" value="<?php echo esc_attr( $ads_refresh_token ); ?>" placeholder="••••••••" style="border-radius:6px; padding:8px 10px;">
            </div>

            <div>
                <button type="submit" class="hao-btn hao-btn-primary">Google Ads Bilgilerini Kaydet</button>
            </div>
        </form>
    </div>

    <!-- 5. Sistem ve Cron Durumu -->
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
