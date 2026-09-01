<?php
namespace HAO\API;

defined( 'ABSPATH' ) || exit;

/**
 * Google URL Inspection API İstemcisi
 */
class UrlInspection {

    const INSPECT_ENDPOINT = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

    private GscClient $gsc_client;
    private string $site_url;

    public function __construct() {
        $this->gsc_client = new GscClient();
        $settings         = get_option( 'hge_settings', [] );
        $this->site_url   = (string) ( $settings['gsc_site_url'] ?? get_site_url() );
    }

    /**
     * Tekil URL İnceleme
     */
    public function inspect_url( string $inspection_url ) {
        $token = $this->gsc_client->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $body = [
            'inspectionUrl' => esc_url_raw( $inspection_url ),
            'siteUrl'       => $this->site_url,
            'languageCode'  => 'tr',
        ];

        $response = wp_remote_post(
            self::INSPECT_ENDPOINT,
            [
                'timeout' => 30,
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
            return new \WP_Error( 'inspection_error', $data['error']['message'] ?? 'URL Inspection API Hatası (' . $code . ')' );
        }

        $result = $data['inspectionResult']['indexStatusResult'] ?? [];

        return [
            'page_url'         => $inspection_url,
            'verdict'          => $result['verdict'] ?? 'UNKNOWN',
            'coverage_state'   => $result['coverageState'] ?? '',
            'robots_txt_state' => $result['robotsTxtState'] ?? '',
            'indexing_state'   => $result['indexingState'] ?? '',
            'page_fetch_state' => $result['pageFetchState'] ?? '',
            'google_canonical' => $result['googleCanonical'] ?? '',
            'user_canonical'   => $result['userCanonical'] ?? '',
            'crawled_as'       => $result['crawledAs'] ?? '',
            'last_crawl_time'  => ! empty( $result['lastCrawlTime'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $result['lastCrawlTime'] ) ) : null,
            'inspection_link'  => $data['inspectionResult']['inspectionUrl'] ?? '',
        ];
    }
}
