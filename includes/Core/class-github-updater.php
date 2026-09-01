<?php
namespace HAO\Core;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub Üzerinden Otomatik Güncelleme Sistemi
 */
class GithubUpdater {

    const GITHUB_REPO = 'alperates58/hesaplamaa-all-in-one';

    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup' ], 20, 3 );
        add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
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
            $obj = new \stdClass();
            $obj->slug        = 'hesaplamaa-all-in-one';
            $obj->new_version = $remote_version;
            $obj->url         = 'https://github.com/' . self::GITHUB_REPO;
            $obj->package     = 'https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip';
            $obj->plugin      = HAO_BASENAME;

            $transient->response[ HAO_BASENAME ] = $obj;
        }

        return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== 'hesaplamaa-all-in-one' ) {
            return $result;
        }

        $remote_version = $this->get_remote_version();

        $obj = new \stdClass();
        $obj->name          = 'Hesaplamaa All-in-One';
        $obj->slug          = 'hesaplamaa-all-in-one';
        $obj->version       = $remote_version ?: HAO_VERSION;
        $obj->author        = '<a href="https://hesaplamaa.com">Alper ATEŞ</a>';
        $obj->homepage      = 'https://github.com/' . self::GITHUB_REPO;
        $obj->requires      = '6.0';
        $obj->tested        = '6.7';
        $obj->download_link = 'https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip';
        $obj->sections      = [
            'description' => 'Hesaplamaa.com için profesyonel all-in-one SEO, GSC zekası ve büyüme paketi.',
        ];

        return $obj;
    }

    public function after_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;
        $install_directory = plugin_dir_path( HAO_FILE );
        $wp_filesystem->move( $result['destination'], $install_directory );
        $result['destination'] = $install_directory;
        return $result;
    }

    private function get_remote_version(): ?string {
        $transient_key = 'hao_remote_version';
        $cached = get_transient( $transient_key );
        if ( $cached ) {
            return $cached;
        }

        $url = 'https://raw.githubusercontent.com/' . self::GITHUB_REPO . '/main/hesaplamaa-all-in-one.php';
        $res = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $res ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $res );
        if ( preg_match( '/Version:\s*([0-9\.]+)/i', $body, $matches ) ) {
            $version = $matches[1];
            set_transient( $transient_key, $version, 3600 );
            return $version;
        }

        return null;
    }
}
