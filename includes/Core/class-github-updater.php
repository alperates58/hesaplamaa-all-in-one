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
        if ( ! function_exists( 'download_url' ) || ! function_exists( 'unzip_file' ) || ! function_exists( 'copy_dir' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $s = $this->get_settings();
        if ( empty( $s['repo'] ) ) {
            return [ 'success' => false, 'message' => 'GitHub repository ayarı eksik.' ];
        }

        $zip_url = "https://github.com/{$s['repo']}/archive/refs/heads/{$s['branch']}.zip";
        $args    = [
            'timeout' => 60,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'hesaplamaa-all-in-one',
            ],
        ];

        if ( ! empty( $s['token'] ) ) {
            $args['headers']['Authorization'] = 'token ' . $s['token'];
        }

        // 1. Zip İndir
        $tmp_zip = download_url( $zip_url, 60, false );
        if ( is_wp_error( $tmp_zip ) ) {
            return [ 'success' => false, 'message' => 'Zip indirilemedi: ' . $tmp_zip->get_error_message() ];
        }

        // 2. Filesystem Başlat
        global $wp_filesystem;
        WP_Filesystem();

        if ( ! is_object( $wp_filesystem ) ) {
            @unlink( $tmp_zip );
            return [ 'success' => false, 'message' => 'WordPress Filesystem başlatılamadı.' ];
        }

        // 3. Geçici Klasöre Aç
        $upgrade_dir = $wp_filesystem->wp_content_dir() . 'upgrade/';
        if ( ! $wp_filesystem->is_dir( $upgrade_dir ) ) {
            $wp_filesystem->mkdir( $upgrade_dir );
        }

        $temp_extract = $upgrade_dir . 'hao_update_' . time() . '/';
        $unzip_res    = unzip_file( $tmp_zip, $temp_extract );
        @unlink( $tmp_zip );

        if ( is_wp_error( $unzip_res ) ) {
            $wp_filesystem->delete( $temp_extract, true );
            return [ 'success' => false, 'message' => 'Zip açılamadı: ' . $unzip_res->get_error_message() ];
        }

        // 4. İç Klasörü Bul (örn: hesaplamaa-all-in-one-main)
        $files = $wp_filesystem->dirlist( $temp_extract );
        if ( empty( $files ) ) {
            $wp_filesystem->delete( $temp_extract, true );
            return [ 'success' => false, 'message' => 'Açılan zip dosyası boş.' ];
        }

        $source_dir = '';
        foreach ( $files as $name => $info ) {
            if ( $info['type'] === 'd' ) {
                $source_dir = $temp_extract . $name . '/';
                break;
            }
        }

        if ( empty( $source_dir ) || ! $wp_filesystem->is_dir( $source_dir ) ) {
            $source_dir = $temp_extract;
        }

        // 5. Eklenti Klasörüne Kopyala
        $dest_dir = HAO_DIR;
        $copy_res = copy_dir( $source_dir, $dest_dir );

        // Geçici klasörü temizle
        $wp_filesystem->delete( $temp_extract, true );

        if ( is_wp_error( $copy_res ) ) {
            return [ 'success' => false, 'message' => 'Dosyalar kopyalanamadı: ' . $copy_res->get_error_message() ];
        }

        // 6. SHA ve Versiyon Kaydet
        $commit = $this->get_remote_commit();
        $sha    = is_array( $commit ) ? $commit['sha'] : '';
        if ( $sha ) {
            update_option( 'hao_last_update_sha', $sha );
        }
        update_option( 'hao_last_update_time', current_time( 'mysql' ) );

        return [
            'success' => true,
            'sha'     => $sha,
            'message' => 'Hesaplamaa All-in-One GitHub üzerinden başarıyla en son sürüme güncellendi!',
        ];
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
