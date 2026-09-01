<?php
namespace HAO\Core;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub Üzerinden Tek Tıkla ve Otomatik Güncelleme Sistemi
 */
class GithubUpdater {

    const OPTION_KEY = 'hao_github_settings';

    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup' ], 20, 3 );
        add_action( 'admin_post_hao_update_from_github', [ $this, 'handle_form_update' ] );
    }

    public function get_settings(): array {
        $defaults = [
            'repo'   => 'alperates58/hesaplamaa-all-in-one',
            'branch' => 'main',
            'token'  => '',
        ];
        return wp_parse_args( (array) get_option( self::OPTION_KEY, [] ), $defaults );
    }

    public function save_settings( array $data ): bool {
        $clean = [
            'repo'   => sanitize_text_field( $data['repo'] ?? 'alperates58/hesaplamaa-all-in-one' ),
            'branch' => sanitize_text_field( $data['branch'] ?? 'main' ),
            'token'  => sanitize_text_field( $data['token'] ?? '' ),
        ];
        return update_option( self::OPTION_KEY, $clean );
    }

    /**
     * Standart Form POST veya GET ile Güncelleme Yakalayıcı
     */
    public function handle_form_update() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Yetkisiz işlem.', 403 );
        }
        
        $nonce = $_REQUEST['hao_nonce'] ?? ( $_REQUEST['_wpnonce'] ?? '' );
        if ( ! wp_verify_nonce( $nonce, 'hao_github_update_action' ) ) {
            wp_die( 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.', 400 );
        }

        $result = $this->perform_update();
        if ( ! empty( $result['success'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hao-settings&hao_updated=1' ) );
        } else {
            wp_die( 'Güncelleme Hatası: ' . esc_html( $result['message'] ?? 'Bilinmeyen hata' ) );
        }
        exit;
    }

    /**
     * GitHub API'den Son Commit Bilgisini Al
     */
    public function get_remote_commit() {
        $s   = $this->get_settings();
        $url = "https://api.github.com/repos/{$s['repo']}/commits/{$s['branch']}";

        $args = [
            'timeout' => 15,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'hesaplamaa-all-in-one',
            ],
        ];

        if ( ! empty( $s['token'] ) ) {
            $args['headers']['Authorization'] = 'token ' . $s['token'];
        }

        $response = wp_remote_get( $url, $args );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['sha'] ) ) {
            return new \WP_Error( 'github_error', $body['message'] ?? 'GitHub commit bilgisi alınamadı (' . $code . ')' );
        }

        return [
            'sha'     => substr( $body['sha'], 0, 7 ),
            'full_sha'=> $body['sha'],
            'message' => $body['commit']['message'] ?? '',
            'date'    => $body['commit']['author']['date'] ?? '',
        ];
    }

    /**
     * Tek Tıkla GitHub'dan İndir ve Kur (Instant Update)
     */
    public function perform_update(): array {
        if ( ! function_exists( 'download_url' ) || ! function_exists( 'unzip_file' ) || ! function_exists( 'copy_dir' ) || ! function_exists( 'wp_tempnam' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $s = $this->get_settings();
        if ( empty( $s['repo'] ) ) {
            return [ 'success' => false, 'message' => 'GitHub repository ayarı eksik.' ];
        }

        $zip_url = "https://github.com/{$s['repo']}/archive/refs/heads/{$s['branch']}.zip";
        $tmp     = wp_tempnam( $zip_url );

        if ( ! $tmp ) {
            return [ 'success' => false, 'message' => 'Geçici indirme dosyası oluşturulamadı.' ];
        }

        $args = [
            'timeout'  => 60,
            'stream'   => true,
            'filename' => $tmp,
            'headers'  => [
                'User-Agent' => 'hesaplamaa-all-in-one',
            ],
        ];

        if ( ! empty( $s['token'] ) ) {
            $args['headers']['Authorization'] = 'token ' . $s['token'];
        }

        // 1. Streaming ZIP İndirme
        $response = wp_remote_get( $zip_url, $args );
        if ( is_wp_error( $response ) ) {
            @unlink( $tmp );
            return [ 'success' => false, 'message' => 'ZIP indirilemedi: ' . $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            @unlink( $tmp );
            return [ 'success' => false, 'message' => 'GitHub ZIP indirme başarısız (HTTP ' . $code . ').' ];
        }

        // 2. Filesystem Başlat
        global $wp_filesystem;
        WP_Filesystem();

        $plugin_base   = dirname( HAO_DIR );
        $dest_dir      = HAO_DIR;
        $temp_extract  = $plugin_base . '/hao_temp_update_' . time();

        // 3. Arşivi Aç
        $unzip_res = unzip_file( $tmp, $temp_extract );
        @unlink( $tmp );

        if ( is_wp_error( $unzip_res ) ) {
            $this->delete_dir_recursive( $temp_extract );
            return [ 'success' => false, 'message' => 'ZIP açılamadı: ' . $unzip_res->get_error_message() ];
        }

        // 4. İç Klasörü Bul (hesaplamaa-all-in-one-main vb.)
        $source_dir = $temp_extract;
        if ( is_dir( $temp_extract ) ) {
            $sub_items = scandir( $temp_extract );
            foreach ( $sub_items as $item ) {
                if ( $item !== '.' && $item !== '..' && is_dir( $temp_extract . '/' . $item ) ) {
                    if ( file_exists( $temp_extract . '/' . $item . '/hesaplamaa-all-in-one.php' ) ) {
                        $source_dir = $temp_extract . '/' . $item;
                        break;
                    }
                }
            }
        }

        // 5. Dosyaları Eklenti Dizinine Kopyala
        $copy_res = copy_dir( $source_dir, $dest_dir );

        if ( is_wp_error( $copy_res ) ) {
            $copy_res = $this->native_recursive_copy( $source_dir, $dest_dir );
        }

        // Geçici klasörü temizle
        $this->delete_dir_recursive( $temp_extract );

        if ( is_wp_error( $copy_res ) || false === $copy_res ) {
            $msg = is_wp_error( $copy_res ) ? $copy_res->get_error_message() : 'Dosyalar kopyalanamadı.';
            return [ 'success' => false, 'message' => 'Güncelleme kopyalama hatası: ' . $msg ];
        }

        // 6. SHA ve Versiyon Kaydet
        $commit = $this->get_remote_commit();
        $sha    = is_array( $commit ) ? $commit['sha'] : '';
        if ( $sha ) {
            update_option( 'hao_last_update_sha', $sha );
        }
        update_option( 'hao_last_update_time', current_time( 'mysql' ) );

        // 7. Önbellekleri Temizle
        if ( function_exists( 'wp_clean_plugins_cache' ) ) {
            wp_clean_plugins_cache( true );
        }
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
        if ( function_exists( 'opcache_reset' ) ) {
            @opcache_reset();
        }

        return [
            'success' => true,
            'sha'     => $sha,
            'message' => 'Hesaplamaa All-in-One GitHub üzerinden başarıyla güncellendi!',
        ];
    }

    private function native_recursive_copy( string $src, string $dst ): bool {
        $dir = @opendir( $src );
        if ( ! $dir ) return false;
        @mkdir( $dst, 0755, true );

        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( ( $file != '.' ) && ( $file != '..' ) ) {
                if ( is_dir( $src . '/' . $file ) ) {
                    $this->native_recursive_copy( $src . '/' . $file, $dst . '/' . $file );
                } else {
                    @copy( $src . '/' . $file, $dst . '/' . $file );
                }
            }
        }
        closedir( $dir );
        return true;
    }

    private function delete_dir_recursive( string $dir ): bool {
        if ( ! is_dir( $dir ) ) return false;
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        foreach ( $files as $file ) {
            ( is_dir( "$dir/$file" ) ) ? $this->delete_dir_recursive( "$dir/$file" ) : @unlink( "$dir/$file" );
        }
        return @rmdir( $dir );
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();
        if ( ! $remote_version ) {
            return $transient;
        }

        $current_version = HAO_VERSION;

        if ( version_compare( $current_version, $remote_version, '<' ) ) {
            $s   = $this->get_settings();
            $obj = new \stdClass();
            $obj->slug        = 'hesaplamaa-all-in-one';
            $obj->new_version = $remote_version;
            $obj->url         = 'https://github.com/' . $s['repo'];
            $obj->package     = "https://github.com/{$s['repo']}/archive/refs/heads/{$s['branch']}.zip";
            $obj->plugin      = HAO_BASENAME;

            $transient->response[ HAO_BASENAME ] = $obj;
        }

        return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== 'hesaplamaa-all-in-one' ) {
            return $result;
        }

        $s              = $this->get_settings();
        $remote_version = $this->get_remote_version();

        $obj = new \stdClass();
        $obj->name          = 'Hesaplamaa All-in-One';
        $obj->slug          = 'hesaplamaa-all-in-one';
        $obj->version       = $remote_version ?: HAO_VERSION;
        $obj->author        = '<a href="https://hesaplamaa.com">Alper ATEŞ</a>';
        $obj->homepage      = 'https://github.com/' . $s['repo'];
        $obj->requires      = '6.0';
        $obj->tested        = '6.7';
        $obj->download_link = "https://github.com/{$s['repo']}/archive/refs/heads/{$s['branch']}.zip";
        $obj->sections      = [
            'description' => 'Hesaplamaa.com için profesyonel all-in-one SEO, GSC zekası ve büyüme paketi.',
        ];

        return $obj;
    }

    private function get_remote_version(): ?string {
        $s   = $this->get_settings();
        $url = "https://raw.githubusercontent.com/{$s['repo']}/{$s['branch']}/hesaplamaa-all-in-one.php";
        $res = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $res ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $res );
        if ( preg_match( '/Version:\s*([0-9\.]+)/i', $body, $matches ) ) {
            return $matches[1];
        }

        return null;
    }
}
