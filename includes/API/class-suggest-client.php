<?php
namespace HAO\API;

defined( 'ABSPATH' ) || exit;

/**
 * Google Autocomplete Suggest Scraper
 */
class SuggestClient {

    const SUGGEST_URL = 'https://suggestqueries.google.com/complete/search';

    /**
     * Verilen kök kelime için Google arama tamamlama önerilerini çeker
     */
    public function get_suggestions( string $query ): array {
        $url = add_query_arg(
            [
                'client' => 'firefox',
                'q'      => $query,
                'hl'     => 'tr',
                'gl'     => 'tr',
            ],
            self::SUGGEST_URL
        );

        $response = wp_remote_get(
            $url,
            [
                'timeout'    => 10,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( is_array( $data ) && isset( $data[1] ) && is_array( $data[1] ) ) {
            return array_map( 'sanitize_text_field', $data[1] );
        }

        return [];
    }

    /**
     * A-Z Alfabatik derin tamamlama taraması
     */
    public function expand_seed( string $seed_keyword ): array {
        $results = [];
        
        // 1. Doğrudan öneriler
        $direct = $this->get_suggestions( $seed_keyword );
        foreach ( $direct as $item ) {
            $results[ $item ] = true;
        }

        // 2. Türkçe alfabeyle harf bazlı türetme (a, b, c, ç, d, e...)
        $alphabet = [ 'a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'h', 'ı', 'i', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'r', 's', 'ş', 't', 'u', 'ü', 'v', 'y', 'z' ];
        
        foreach ( $alphabet as $char ) {
            $sub = $this->get_suggestions( $seed_keyword . ' ' . $char );
            foreach ( $sub as $item ) {
                $results[ $item ] = true;
            }
            usleep( 50000 ); // 50ms bekleme
        }

        return array_keys( $results );
    }
}
