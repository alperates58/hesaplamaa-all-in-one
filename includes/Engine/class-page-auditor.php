<?php
namespace HAO\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Birleşik Sayfa Kalite & SEO Denetim Motoru
 */
class PageAuditor {

    // Bozuk CSS / Köşeli parantez kalıntıları
    private static array $broken_patterns = [
        '[-0.094rem]',
        '[#303030]',
        '[#8F8F8F]',
        '[9px]',
        '[#F4F4F4]',
        '[show_150ms_ease-in]',
    ];

    /**
     * Tüm yayınlanmış sayfaları ve yazıları denetler, GSC verileriyle zenginleştirir
     */
    public function get_audited_pages( array $args = [] ): array {
        $defaults = [
            'post_type'      => [ 'post', 'page' ],
            'posts_per_page' => 2000,
            'filter'         => 'all', // all, missing_meta, missing_shortcode, broken_text, index_issue, high_opportunity
            'search'         => '',
        ];
        $params = wp_parse_args( $args, $defaults );

        $query_args = [
            'post_type'      => $params['post_type'],
            'post_status'    => 'publish',
            'posts_per_page' => (int) $params['posts_per_page'],
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ];

        if ( ! empty( $params['search'] ) ) {
            $query_args['s'] = $params['search'];
        }

        $posts = get_posts( $query_args );
        $repo  = new \HAO\DB\Repository();
        
        // GSC ve Dizin verilerini hafızaya al
        $db_page_stats = $repo->get_all_page_stats( 5000 );
        $page_stats_map = [];
        foreach ( $db_page_stats as $stat ) {
            $normalized_url = trailingslashit( strtolower( (string) $stat['page_url'] ) );
            $page_stats_map[ $normalized_url ] = $stat;
        }

        $db_index_stats = $repo->get_all_index_statuses( [ 'limit' => 5000 ] );
        $index_map = [];
        foreach ( $db_index_stats as $idx ) {
            $normalized_url = trailingslashit( strtolower( (string) $idx['page_url'] ) );
            $index_map[ $normalized_url ] = $idx;
        }

        $results = [];

        foreach ( $posts as $post ) {
            $post_id   = $post->ID;
            $permalink = get_permalink( $post_id );
            $norm_url  = trailingslashit( strtolower( (string) $permalink ) );
            $content   = (string) $post->post_content;
            $raw_text  = wp_strip_all_tags( $content );

            // Yoast / RankMath / PostMeta
            $yoast_title = (string) get_post_meta( $post_id, '_yoast_wpseo_title', true );
            $yoast_desc  = (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            $yoast_kw    = (string) get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );

            if ( empty( $yoast_desc ) ) {
                $yoast_desc = (string) get_post_meta( $post_id, '_aioseo_description', true )
                              ?: (string) get_post_meta( $post_id, 'rank_math_description', true );
            }

            // Shortcode kontrolü
            $has_calculator = ( false !== strpos( $content, '[hc_' ) );

            // Bozuk metin kontrolü
            $broken_issues = [];
            foreach ( self::$broken_patterns as $pattern ) {
                if ( false !== strpos( $content, $pattern ) ) {
                    $broken_issues[] = $pattern;
                }
            }

            // Başlıklar (H1, H2, H3)
            preg_match_all( '/<h1[^>]*>(.*?)<\/h1>/si', $content, $h1_matches );
            preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/si', $content, $h2_matches );
            preg_match_all( '/<h3[^>]*>(.*?)<\/h3>/si', $content, $h3_matches );

            $h1_count = count( $h1_matches[0] ?? [] );
            $h2_count = count( $h2_matches[0] ?? [] );
            $h3_count = count( $h3_matches[0] ?? [] );

            // Linkler
            preg_match_all( '/<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $link_matches );
            $all_links = $link_matches[1] ?? [];
            $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
            $internal_links = 0;
            $external_links = 0;

            foreach ( $all_links as $link ) {
                $host = wp_parse_url( $link, PHP_URL_HOST );
                if ( empty( $host ) || $host === $site_host ) {
                    $internal_links++;
                } else {
                    $external_links++;
                }
            }

            // Görseller & Alt Etiketleri
            preg_match_all( '/<img[^>]+>/i', $content, $img_matches );
            $total_images = count( $img_matches[0] ?? [] );
            $missing_alt  = 0;
            foreach ( ( $img_matches[0] ?? [] ) as $img_tag ) {
                if ( ! preg_match( '/alt=[\'"][^\'"]+[\'"]/i', $img_tag ) ) {
                    $missing_alt++;
                }
            }

            $word_count = count( preg_split( '/\s+/u', trim( $raw_text ), -1, PREG_SPLIT_NO_EMPTY ) );

            // GSC Verisi
            $gsc_stat   = $page_stats_map[ $norm_url ] ?? [];
            $clicks     = (int) ( $gsc_stat['clicks'] ?? 0 );
            $impressions = (int) ( $gsc_stat['impressions'] ?? 0 );
            $position   = (float) ( $gsc_stat['avg_position'] ?? 0 );
            $ctr        = (float) ( $gsc_stat['ctr'] ?? 0 );

            // Dizin Verisi
            $idx_stat     = $index_map[ $norm_url ] ?? [];
            $index_verdict = (string) ( $idx_stat['verdict'] ?? 'UNKNOWN' );
            $coverage_state = (string) ( $idx_stat['coverage_state'] ?? '' );

            // Sağlık Puanı (0 - 100)
            $health_score = $this->calculate_health_score( [
                'has_meta'       => ! empty( $yoast_desc ),
                'meta_len'       => mb_strlen( $yoast_desc ),
                'word_count'     => $word_count,
                'has_calculator' => $has_calculator,
                'broken_count'   => count( $broken_issues ),
                'h1_count'       => $h1_count,
                'h2_count'       => $h2_count,
                'internal_links' => $internal_links,
            ] );

            $audit_item = [
                'post_id'         => $post_id,
                'post_title'      => $post->post_title,
                'post_type'       => $post->post_type,
                'permalink'       => $permalink,
                'edit_url'        => get_edit_post_link( $post_id, 'raw' ),
                'word_count'      => $word_count,
                'has_meta'        => ! empty( $yoast_desc ),
                'meta_desc'       => $yoast_desc,
                'meta_len'        => mb_strlen( $yoast_desc ),
                'seo_title'       => $yoast_title ?: $post->post_title,
                'focus_keyword'   => $yoast_kw,
                'has_calculator'  => $has_calculator,
                'broken_issues'   => $broken_issues,
                'h1_count'        => $h1_count,
                'h2_count'        => $h2_count,
                'h3_count'        => $h3_count,
                'internal_links'  => $internal_links,
                'external_links'  => $external_links,
                'total_images'    => $total_images,
                'missing_alt'     => $missing_alt,
                'clicks'          => $clicks,
                'impressions'     => $impressions,
                'position'        => $position,
                'ctr'             => $ctr,
                'index_verdict'   => $index_verdict,
                'coverage_state'  => $coverage_state,
                'health_score'    => $health_score,
                'modified_date'   => get_the_modified_date( 'd.m.Y H:i', $post ),
            ];

            // Filtreleme
            if ( $this->passes_filter( $audit_item, $params['filter'] ) ) {
                $results[] = $audit_item;
            }
        }

        // Tıklama veya Sağlık Puanına göre sırala
        usort( $results, function ( $a, $b ) {
            if ( $b['clicks'] !== $a['clicks'] ) {
                return $b['clicks'] <=> $a['clicks'];
            }
            return $a['health_score'] <=> $b['health_score'];
        } );

        return $results;
    }

    /**
     * Filtre Uygunluk Kontrolü
     */
    private function passes_filter( array $item, string $filter ): bool {
        switch ( $filter ) {
            case 'missing_meta':
                return empty( $item['has_meta'] );
            case 'missing_shortcode':
                return ! $item['has_calculator'];
            case 'broken_text':
                return ! empty( $item['broken_issues'] );
            case 'index_issue':
                return in_array( $item['index_verdict'], [ 'FAIL', 'NEUTRAL', 'UNKNOWN' ], true );
            case 'high_opportunity':
                return ( $item['position'] >= 4 && $item['position'] <= 20 && $item['impressions'] >= 50 );
            default:
                return true;
        }
    }

    /**
     * İçerik Sağlık Puanı Hesapla
     */
    private function calculate_health_score( array $data ): int {
        $score = 100;

        // Meta Açıklama kontrolü (-25)
        if ( ! $data['has_meta'] ) {
            $score -= 25;
        } elseif ( $data['meta_len'] < 100 || $data['meta_len'] > 165 ) {
            $score -= 10;
        }

        // Hesaplama Shortcode kontrolü (-25)
        if ( ! $data['has_calculator'] ) {
            $score -= 25;
        }

        // Bozuk CSS / Kalıntı metin kontrolü (-20)
        if ( $data['broken_count'] > 0 ) {
            $score -= min( 20, $data['broken_count'] * 10 );
        }

        // Kelime sayısı kontrolü (-15)
        if ( $data['word_count'] < 300 ) {
            $score -= 15;
        } elseif ( $data['word_count'] < 600 ) {
            $score -= 5;
        }

        // H2 Başlık kontrolü (-10)
        if ( $data['h2_count'] < 2 ) {
            $score -= 10;
        }

        // İç link kontrolü (-5)
        if ( $data['internal_links'] < 2 ) {
            $score -= 5;
        }

        return max( 0, min( 100, $score ) );
    }
}
