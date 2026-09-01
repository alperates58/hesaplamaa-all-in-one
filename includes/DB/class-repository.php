<?php
namespace HAO\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Merkezi Veritabanı Repository Sınıfı
 */
class Repository {

    private \wpdb $wpdb;

    public string $keywords;
    public string $daily_stats;
    public string $page_stats;
    public string $suggestions;
    public string $ai_insights;
    public string $index_status;
    public string $keyword_volumes;
    public string $seo_opportunities;

    public function __construct() {
        global $wpdb;
        $this->wpdb              = $wpdb;
        $this->keywords          = $wpdb->prefix . 'hge_keywords';
        $this->daily_stats       = $wpdb->prefix . 'hge_daily_stats';
        $this->page_stats        = $wpdb->prefix . 'hge_page_stats';
        $this->suggestions       = $wpdb->prefix . 'hge_suggestions';
        $this->ai_insights       = $wpdb->prefix . 'hge_ai_insights';
        $this->index_status      = $wpdb->prefix . 'hge_index_status';
        $this->keyword_volumes   = $wpdb->prefix . 'hge_keyword_volumes';
        $this->seo_opportunities = $wpdb->prefix . 'hge_seo_opportunities';
    }

    // -------------------------------------------------------------------------
    // Dashboard KPI Özetleri
    // -------------------------------------------------------------------------

    public function get_dashboard_summary() {
        $cache_key = 'hao_dashboard_summary';
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        // Tablo varlık kontrolü
        $has_keywords = $this->table_exists( $this->keywords );
        $has_index    = $this->table_exists( $this->index_status );

        if ( ! $has_keywords ) {
            return [
                'total_keywords'    => 0,
                'top3_count'        => 0,
                'top10_count'       => 0,
                'avg_position'      => 0,
                'total_clicks'      => 0,
                'total_impressions' => 0,
                'indexed_count'     => 0,
                'radar_count'       => 0,
            ];
        }

        $total_keywords    = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->keywords}" );
        $top3_count        = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->keywords} WHERE avg_position <= 3" );
        $top10_count       = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->keywords} WHERE avg_position <= 10" );
        $avg_pos           = (float) $this->wpdb->get_var( "SELECT AVG(avg_position) FROM {$this->keywords}" );
        $total_clicks      = (int) $this->wpdb->get_var( "SELECT SUM(clicks) FROM {$this->keywords}" );
        $total_impressions = (int) $this->wpdb->get_var( "SELECT SUM(impressions) FROM {$this->keywords}" );
        
        $indexed_count = 0;
        if ( $has_index ) {
            $indexed_count = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->index_status} WHERE verdict = 'PASS' OR indexing_state = 'INDEXING_ALLOWED'" );
        }

        $radar_count = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->keywords} WHERE avg_position BETWEEN 4 AND 20 AND impressions >= 50" );

        $data = [
            'total_keywords'    => $total_keywords,
            'top3_count'        => $top3_count,
            'top10_count'       => $top10_count,
            'avg_position'      => round( $avg_pos, 1 ),
            'total_clicks'      => $total_clicks,
            'total_impressions' => $total_impressions,
            'indexed_count'     => $indexed_count,
            'radar_count'       => $radar_count,
        ];

        set_transient( $cache_key, $data, 900 );
        return $data;
    }

    public function get_daily_stats( int $days = 30 ) {
        if ( ! $this->table_exists( $this->daily_stats ) ) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT stat_date, clicks, impressions, ctr, avg_position
                 FROM {$this->daily_stats}
                 WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                 ORDER BY stat_date ASC",
                $days
            ),
            ARRAY_A
        );
    }

    // -------------------------------------------------------------------------
    // Keyword Fırsatları & SEO Radar
    // -------------------------------------------------------------------------

    public function get_opportunity_keywords( array $args = [] ) {
        if ( ! $this->table_exists( $this->keywords ) ) {
            return [];
        }

        $defaults = [
            'limit'        => 50,
            'offset'       => 0,
            'min_position' => 3.5,
            'max_position' => 20.4,
            'min_impressions' => 10,
            'search'       => '',
            'orderby'      => 'opportunity_score',
            'order'        => 'DESC',
        ];

        $params = wp_parse_args( $args, $defaults );
        $where  = [ '1=1' ];
        $values = [];

        if ( $params['min_position'] > 0 ) {
            $where[]  = 'avg_position >= %f';
            $values[] = $params['min_position'];
        }
        if ( $params['max_position'] > 0 ) {
            $where[]  = 'avg_position <= %f';
            $values[] = $params['max_position'];
        }
        if ( $params['min_impressions'] > 0 ) {
            $where[]  = 'impressions >= %d';
            $values[] = $params['min_impressions'];
        }
        if ( ! empty( $params['search'] ) ) {
            $where[]  = 'keyword LIKE %s';
            $values[] = '%' . $this->wpdb->esc_like( $params['search'] ) . '%';
        }

        $allowed_sorts = [ 'opportunity_score', 'impressions', 'clicks', 'avg_position', 'ctr', 'keyword' ];
        $orderby = in_array( $params['orderby'], $allowed_sorts, true ) ? $params['orderby'] : 'opportunity_score';
        $order   = strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $where_sql = implode( ' AND ', $where );
        $query     = "SELECT * FROM {$this->keywords} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $values[]  = $params['limit'];
        $values[]  = $params['offset'];

        return $this->wpdb->get_results( $this->wpdb->prepare( $query, $values ), ARRAY_A );
    }

    public function get_top_radar_alerts( int $limit = 5 ) {
        if ( ! $this->table_exists( $this->keywords ) ) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->keywords}
                 WHERE avg_position BETWEEN 4 AND 15 AND impressions >= 50
                 ORDER BY impressions DESC, opportunity_score DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    // -------------------------------------------------------------------------
    // Sayfa Performansı & GSC Eşleme
    // -------------------------------------------------------------------------

    public function get_all_page_stats( int $limit = 5000 ) {
        if ( ! $this->table_exists( $this->page_stats ) ) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->page_stats} ORDER BY clicks DESC, impressions DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    public function get_page_stat_by_url( string $url ) {
        if ( ! $this->table_exists( $this->page_stats ) ) {
            return null;
        }

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->page_stats} WHERE page_url = %s LIMIT 1",
                $url
            ),
            ARRAY_A
        );
    }

    // -------------------------------------------------------------------------
    // Google Dizin Durumu (Index Status)
    // -------------------------------------------------------------------------

    public function get_all_index_statuses( array $args = [] ) {
        if ( ! $this->table_exists( $this->index_status ) ) {
            return [];
        }

        $defaults = [
            'verdict' => '',
            'search'  => '',
            'limit'   => 100,
            'offset'  => 0,
        ];
        $params = wp_parse_args( $args, $defaults );

        $where  = [ '1=1' ];
        $values = [];

        if ( ! empty( $params['verdict'] ) ) {
            $where[]  = 'verdict = %s';
            $values[] = $params['verdict'];
        }
        if ( ! empty( $params['search'] ) ) {
            $where[]  = '(page_title LIKE %s OR page_url LIKE %s)';
            $like     = '%' . $this->wpdb->esc_like( $params['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $query     = "SELECT * FROM {$this->index_status} WHERE {$where_sql} ORDER BY last_checked DESC LIMIT %d OFFSET %d";
        $values[]  = $params['limit'];
        $values[]  = $params['offset'];

        return $this->wpdb->get_results( $this->wpdb->prepare( $query, $values ), ARRAY_A );
    }

    public function save_index_status( array $data ) {
        if ( ! $this->table_exists( $this->index_status ) ) {
            return false;
        }

        $url_hash = md5( $data['page_url'] );
        $exists   = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$this->index_status} WHERE url_hash = %s", $url_hash ) );

        $row = [
            'url_hash'          => $url_hash,
            'page_url'          => esc_url_raw( $data['page_url'] ),
            'page_title'        => sanitize_text_field( $data['page_title'] ?? '' ),
            'post_id'           => (int) ( $data['post_id'] ?? 0 ),
            'verdict'           => sanitize_text_field( $data['verdict'] ?? 'UNKNOWN' ),
            'coverage_state'    => sanitize_text_field( $data['coverage_state'] ?? '' ),
            'robots_txt_state'  => sanitize_text_field( $data['robots_txt_state'] ?? '' ),
            'indexing_state'    => sanitize_text_field( $data['indexing_state'] ?? '' ),
            'page_fetch_state'  => sanitize_text_field( $data['page_fetch_state'] ?? '' ),
            'google_canonical'  => esc_url_raw( $data['google_canonical'] ?? '' ),
            'user_canonical'    => esc_url_raw( $data['user_canonical'] ?? '' ),
            'crawled_as'        => sanitize_text_field( $data['crawled_as'] ?? '' ),
            'last_crawl_time'   => ! empty( $data['last_crawl_time'] ) ? $data['last_crawl_time'] : null,
            'inspection_link'   => esc_url_raw( $data['inspection_link'] ?? '' ),
            'error_message'     => sanitize_textarea_field( $data['error_message'] ?? '' ),
            'last_checked'      => current_time( 'mysql' ),
        ];

        if ( $exists ) {
            return $this->wpdb->update( $this->index_status, $row, [ 'id' => $exists ] );
        }

        return $this->wpdb->insert( $this->index_status, $row );
    }

    // -------------------------------------------------------------------------
    // Yeni Fikirler & Suggest Arşivi
    // -------------------------------------------------------------------------

    public function get_suggestions( array $args = [] ) {
        if ( ! $this->table_exists( $this->suggestions ) ) {
            return [];
        }

        $defaults = [
            'should_create' => null,
            'search'        => '',
            'limit'         => 100,
            'offset'        => 0,
        ];
        $params = wp_parse_args( $args, $defaults );

        $where  = [ '1=1' ];
        $values = [];

        if ( null !== $params['should_create'] ) {
            $where[]  = 'should_create = %d';
            $values[] = (int) $params['should_create'];
        }
        if ( ! empty( $params['search'] ) ) {
            $where[]  = 'topic LIKE %s';
            $values[] = '%' . $this->wpdb->esc_like( $params['search'] ) . '%';
        }

        $where_sql = implode( ' AND ', $where );
        $query     = "SELECT * FROM {$this->suggestions} WHERE {$where_sql} ORDER BY opportunity_score DESC, monthly_volume DESC LIMIT %d OFFSET %d";
        $values[]  = $params['limit'];
        $values[]  = $params['offset'];

        return $this->wpdb->get_results( $this->wpdb->prepare( $query, $values ), ARRAY_A );
    }

    public function save_suggestion( string $topic, int $volume = 0, string $source = 'suggest' ) {
        if ( ! $this->table_exists( $this->suggestions ) ) {
            return false;
        }

        $topic  = sanitize_text_field( $topic );
        $exists = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$this->suggestions} WHERE topic = %s", $topic ) );

        if ( $exists ) {
            return false;
        }

        return $this->wpdb->insert(
            $this->suggestions,
            [
                'topic'             => $topic,
                'monthly_volume'    => $volume,
                'source'            => sanitize_key( $source ),
                'opportunity_score' => min( 100, max( 30, (int) ( $volume > 0 ? log10( $volume + 10 ) * 25 : 50 ) ) ),
                'created_at'        => current_time( 'mysql' ),
            ]
        );
    }

    public function toggle_suggestion_status( int $id, int $should_create ) {
        if ( ! $this->table_exists( $this->suggestions ) ) {
            return false;
        }
        return $this->wpdb->update( $this->suggestions, [ 'should_create' => $should_create ], [ 'id' => $id ] );
    }

    // -------------------------------------------------------------------------
    // Yardımcı Metodlar
    // -------------------------------------------------------------------------

    private function table_exists( string $table_name ): bool {
        return strtolower( (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) === strtolower( $table_name );
    }

    public function clear_dashboard_cache() {
        delete_transient( 'hao_dashboard_summary' );
        delete_transient( 'hge_dashboard_summary' );
    }
}
