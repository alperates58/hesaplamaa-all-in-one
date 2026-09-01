<?php
namespace HAO\API;

defined( 'ABSPATH' ) || exit;

/**
 * Google Ads Keyword Planner API İstemcisi
 */
class GoogleAdsClient {

    private string $developer_token;
    private string $client_id;
    private string $client_secret;
    private string $refresh_token;
    private string $customer_id;

    public function __construct() {
        $settings              = get_option( 'hge_settings', [] );
        $this->developer_token = (string) ( $settings['google_ads_dev_token'] ?? '' );
        $this->client_id       = (string) ( $settings['google_ads_client_id'] ?? '' );
        $this->client_secret   = (string) ( $settings['google_ads_client_secret'] ?? '' );
        $this->refresh_token   = (string) ( $settings['google_ads_refresh_token'] ?? '' );
        $this->customer_id     = preg_replace( '/[^0-9]/', '', (string) ( $settings['google_ads_customer_id'] ?? '' ) );
    }

    public function is_configured(): bool {
        return ! empty( $this->developer_token ) && ! empty( $this->client_id ) && ! empty( $this->refresh_token ) && ! empty( $this->customer_id );
    }

    /**
     * OAuth2 Access Token Al
     */
    private function get_access_token() {
        $transient_key = 'hao_google_ads_token';
        $cached = get_transient( $transient_key );
        if ( $cached ) {
            return $cached;
        }

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
                'timeout' => 30,
                'body'    => [
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'refresh_token' => $this->refresh_token,
                    'grant_type'    => 'refresh_token',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['error'] ) ) {
            return new \WP_Error( 'google_ads_auth_error', $body['error_description'] ?? $body['error'] );
        }

        $token = $body['access_token'];
        set_transient( $transient_key, $token, (int) ( $body['expires_in'] ?? 3600 ) - 60 );
        return $token;
    }

    /**
     * Toplu Anahtar Kelime Hacimleri Sorgula (Google Ads generateKeywordHistoricalMetrics)
     */
    public function get_keyword_volumes( array $keywords ) {
        if ( ! $this->is_configured() ) {
            return new \WP_Error( 'not_configured', 'Google Ads API yapılandırılmamış.' );
        }

        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $keywords = array_slice( array_unique( array_filter( $keywords ) ), 0, 1000 );
        if ( empty( $keywords ) ) {
            return [];
        }

        $url = "https://googleads.googleapis.com/v16/customers/{$this->customer_id}:generateKeywordHistoricalMetrics";

        $body = [
            'keywords'            => array_values( $keywords ),
            'geoTargetConstants'  => [ 'geoTargetConstants/2792' ], // Türkiye (2792)
            'keywordPlanNetwork'  => 'GOOGLE_SEARCH',
            'language'            => 'languageConstants/1037', // Türkçe (1037)
        ];

        $response = wp_remote_post(
            $url,
            [
                'timeout' => 60,
                'headers' => [
                    'Authorization'     => 'Bearer ' . $token,
                    'developer-token'   => $this->developer_token,
                    'Content-Type'      => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $results = [];

        if ( ! empty( $data['results'] ) ) {
            foreach ( $data['results'] as $row ) {
                $kw = $row['text'] ?? '';
                if ( $kw ) {
                    $metrics = $row['keywordMetrics'] ?? [];
                    $results[ $kw ] = [
                        'monthly_volume' => (int) ( $metrics['avgMonthlySearches'] ?? 0 ),
                        'competition'    => $metrics['competition'] ?? 'UNKNOWN',
                    ];
                }
            }
        }

        return $results;
    }
}
