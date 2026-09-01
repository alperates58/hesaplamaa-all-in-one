<?php
namespace HAO\API;

defined( 'ABSPATH' ) || exit;

/**
 * Google Search Console OAuth2 & Search Analytics API İstemcisi
 */
class GscClient {

    const TOKEN_OPTION     = 'hge_gsc_tokens';
    const SETTINGS_OPTION  = 'hge_settings';
    const TOKEN_ENDPOINT   = 'https://oauth2.googleapis.com/token';
    const SEARCH_ANALYTICS = 'https://searchconsole.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query';
    const URL_INSPECTION   = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
    const SITES_LIST       = 'https://www.googleapis.com/webmasters/v3/sites';

    private string $client_id;
    private string $client_secret;
    private string $redirect_uri;
    private string $site_url;

    public function __construct() {
        $settings            = get_option( self::SETTINGS_OPTION, [] );
        $this->client_id     = (string) ( $settings['gsc_client_id'] ?? '' );
        $this->client_secret = (string) ( $settings['gsc_client_secret'] ?? '' );
        $this->site_url      = (string) ( $settings['gsc_site_url'] ?? get_site_url() );
        $this->redirect_uri  = admin_url( 'admin.php?page=hao-settings&hao_gsc_callback=1' );
    }

    public function is_connected(): bool {
        $tokens = get_option( self::TOKEN_OPTION, [] );
        return ! empty( $tokens['access_token'] ) || ! empty( $tokens['refresh_token'] );
    }

    public function get_auth_url(): string {
        $params = [
            'client_id'       => $this->client_id,
            'redirect_uri'    => $this->redirect_uri,
            'response_type'   => 'code',
            'scope'           => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type'     => 'offline',
            'prompt'          => 'consent',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
    }

    public function exchange_code_for_tokens( string $code ) {
        $response = wp_remote_post(
            self::TOKEN_ENDPOINT,
            [
                'timeout' => 30,
                'body'    => [
                    'code'          => $code,
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'redirect_uri'  => $this->redirect_uri,
                    'grant_type'    => 'authorization_code',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['error'] ) ) {
            return new \WP_Error( 'gsc_token_error', $body['error_description'] ?? $body['error'] );
        }

        $tokens = [
            'access_token'  => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? '',
            'expires_at'    => time() + (int) ( $body['expires_in'] ?? 3600 ),
            'token_type'    => $body['token_type'] ?? 'Bearer',
        ];

        update_option( self::TOKEN_OPTION, $tokens );

        // Bağlantı durumunu kaydet
        $settings = get_option( self::SETTINGS_OPTION, [] );
        $settings['gsc_connected'] = true;
        update_option( self::SETTINGS_OPTION, $settings );

        return $tokens;
    }

    public function get_access_token() {
        $tokens = get_option( self::TOKEN_OPTION, [] );

        if ( empty( $tokens['access_token'] ) ) {
            return new \WP_Error( 'gsc_not_connected', __( 'GSC bağlı değil. Lütfen ayarlardan bağlayın.', 'hesaplamaa-all-in-one' ) );
        }

        if ( ! empty( $tokens['expires_at'] ) && $tokens['expires_at'] > ( time() + 60 ) ) {
            return $tokens['access_token'];
        }

        return $this->refresh_access_token( (string) ( $tokens['refresh_token'] ?? '' ) );
    }

    private function refresh_access_token( string $refresh_token ) {
        if ( empty( $refresh_token ) ) {
            return new \WP_Error( 'gsc_no_refresh', __( 'Refresh token bulunamadı. Lütfen yeniden bağlayın.', 'hesaplamaa-all-in-one' ) );
        }

        $response = wp_remote_post(
            self::TOKEN_ENDPOINT,
            [
                'timeout' => 30,
                'body'    => [
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['error'] ) ) {
            return new \WP_Error( 'gsc_refresh_error', $body['error_description'] ?? $body['error'] );
        }

        $tokens = get_option( self::TOKEN_OPTION, [] );
        $tokens['access_token'] = $body['access_token'];
        $tokens['expires_at']   = time() + (int) ( $body['expires_in'] ?? 3600 );
        update_option( self::TOKEN_OPTION, $tokens );

        return $tokens['access_token'];
    }

    /**
     * GSC Search Analytics Sorgusu
     */
    public function query_search_analytics( array $params = [] ) {
        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $defaults = [
            'startDate'  => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
            'endDate'    => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
            'dimensions' => [ 'query', 'page' ],
            'rowLimit'   => 5000,
        ];
        $body = wp_parse_args( $params, $defaults );

        $endpoint = str_replace( '{siteUrl}', rawurlencode( $this->site_url ), self::SEARCH_ANALYTICS );

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 45,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            return new \WP_Error( 'gsc_api_error', $data['error']['message'] ?? 'GSC API Hatası (' . $code . ')' );
        }

        return $data['rows'] ?? [];
    }

    /**
     * Günlük Tarih Bazlı İstatistik Çekme (Date dimension)
     */
    public function query_daily_overview( int $days = 30 ) {
        return $this->query_search_analytics( [
            'startDate'  => gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
            'endDate'    => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
            'dimensions' => [ 'date' ],
            'rowLimit'   => $days + 5,
        ] );
    }

    public function disconnect() {
        delete_option( self::TOKEN_OPTION );
        $settings = get_option( self::SETTINGS_OPTION, [] );
        $settings['gsc_connected'] = false;
        update_option( self::SETTINGS_OPTION, $settings );
        return true;
    }
}
