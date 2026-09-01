<?php
namespace HAO\Cron;

defined( 'ABSPATH' ) || exit;

/**
 * Otomatik Zamanlanmış Görevler (Cron) Yöneticisi
 */
class Scheduler {

    public static function init() {
        add_action( 'hao_daily_sync', [ __CLASS__, 'run_daily_gsc_sync' ] );
        add_action( 'hao_index_status_sync', [ __CLASS__, 'run_index_status_sync' ] );
        add_action( 'hao_weekly_ideas', [ __CLASS__, 'run_weekly_ideas_discovery' ] );
    }

    public static function activate() {
        if ( ! wp_next_scheduled( 'hao_daily_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'hao_daily_sync' );
        }
        if ( ! wp_next_scheduled( 'hao_index_status_sync' ) ) {
            wp_schedule_event( time() + 300, 'hourly', 'hao_index_status_sync' );
        }
        if ( ! wp_next_scheduled( 'hao_weekly_ideas' ) ) {
            wp_schedule_event( time() + ( 2 * HOUR_IN_SECONDS ), 'weekly', 'hao_weekly_ideas' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'hao_daily_sync' );
        wp_clear_scheduled_hook( 'hao_index_status_sync' );
        wp_clear_scheduled_hook( 'hao_weekly_ideas' );
    }

    /**
     * Günlük GSC Senkronizasyonu
     */
    public static function run_daily_gsc_sync(): array {
        $gsc_client = new \HAO\API\GscClient();
        if ( ! $gsc_client->is_connected() ) {
            return [ 'success' => false, 'message' => 'GSC bağlı değil.' ];
        }

        global $wpdb;
        $repo = new \HAO\DB\Repository();

        // 1. Günlük genel verileri çek
        $daily_rows = $gsc_client->query_daily_overview( 30 );
        if ( is_array( $daily_rows ) && ! is_wp_error( $daily_rows ) ) {
            foreach ( $daily_rows as $row ) {
                $date        = $row['keys'][0] ?? '';
                $clicks      = (int) ( $row['clicks'] ?? 0 );
                $impressions = (int) ( $row['impressions'] ?? 0 );
                $ctr         = (float) ( $row['ctr'] ?? 0 );
                $position    = (float) ( $row['position'] ?? 0 );

                if ( $date ) {
                    $wpdb->replace(
                        $repo->daily_stats,
                        [
                            'stat_date'    => $date,
                            'clicks'       => $clicks,
                            'impressions'  => $impressions,
                            'ctr'          => $ctr,
                            'avg_position' => $position,
                            'created_at'   => current_time( 'mysql' ),
                        ]
                    );
                }
            }
        }

        // 2. Query + Page verilerini çek
        $query_rows = $gsc_client->query_search_analytics( [
            'startDate'  => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
            'endDate'    => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
            'dimensions' => [ 'query', 'page' ],
            'rowLimit'   => 5000,
        ] );

        $processed_count = 0;
        if ( is_array( $query_rows ) && ! is_wp_error( $query_rows ) ) {
            // Tabloyu güncelle
            foreach ( $query_rows as $row ) {
                $kw          = sanitize_text_field( $row['keys'][0] ?? '' );
                $page_url    = esc_url_raw( $row['keys'][1] ?? '' );
                $clicks      = (int) ( $row['clicks'] ?? 0 );
                $impressions = (int) ( $row['impressions'] ?? 0 );
                $ctr         = (float) ( $row['ctr'] ?? 0 );
                $position    = (float) ( $row['position'] ?? 0 );

                if ( empty( $kw ) ) {
                    continue;
                }

                $opp_score = \HAO\Engine\SeoRadar::calculate_opportunity_score( $position, $impressions, $clicks, $ctr );
                $opp_action = \HAO\Engine\SeoRadar::get_recommended_action( $position, $ctr, $impressions );

                // Mevcut kaydı kontrol et
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$repo->keywords} WHERE keyword = %s AND page_url = %s", $kw, $page_url ) );

                $data = [
                    'keyword'           => $kw,
                    'page_url'          => $page_url,
                    'clicks'            => $clicks,
                    'impressions'       => $impressions,
                    'ctr'               => $ctr,
                    'avg_position'      => $position,
                    'opportunity_score' => $opp_score,
                    'opportunity_type'  => $opp_action['tag'],
                    'last_updated'      => current_time( 'mysql' ),
                ];

                if ( $exists ) {
                    $wpdb->update( $repo->keywords, $data, [ 'id' => $exists ] );
                } else {
                    $wpdb->insert( $repo->keywords, $data );
                }

                $processed_count++;
            }
        }

        $repo->clear_dashboard_cache();

        return [
            'success'   => true,
            'processed' => $processed_count,
            'message'   => sprintf( '%d anahtar kelime senkronize edildi.', $processed_count ),
        ];
    }

    /**
     * Dizin Durumu Toplu Taraması
     */
    public static function run_index_status_sync(): array {
        $inspector = new \HAO\API\UrlInspection();
        $repo      = new \HAO\DB\Repository();

        $posts = get_posts( [
            'post_type'      => [ 'post', 'page' ],
            'post_status'    => 'publish',
            'posts_per_page' => 10, // Google API kotalarını korumak için küçük partiler
            'orderby'        => 'rand',
        ] );

        $inspected = 0;
        foreach ( $posts as $post ) {
            $url = get_permalink( $post->ID );
            $res = $inspector->inspect_url( $url );
            if ( is_array( $res ) && ! is_wp_error( $res ) ) {
                $res['post_id']    = $post->ID;
                $res['page_title'] = $post->post_title;
                $repo->save_index_status( $res );
                $inspected++;
            }
            usleep( 200000 ); // 200ms bekleme
        }

        return [ 'success' => true, 'inspected' => $inspected ];
    }

    /**
     * Haftalık Yeni Fikir Keşfi
     */
    public static function run_weekly_ideas_discovery(): array {
        $suggest = new \HAO\API\SuggestClient();
        $repo    = new \HAO\DB\Repository();

        $seeds = [ 'hesaplama', 'hesaplayıcı', 'hesapla nasıl', 'fiyat hesaplama', 'oran hesaplama' ];
        $saved = 0;

        foreach ( $seeds as $seed ) {
            $suggestions = $suggest->get_suggestions( $seed );
            foreach ( $suggestions as $topic ) {
                if ( mb_strlen( $topic ) >= 6 ) {
                    if ( $repo->save_suggestion( $topic, 0, 'suggest' ) ) {
                        $saved++;
                    }
                }
            }
        }

        return [ 'success' => true, 'new_ideas_saved' => $saved ];
    }
}
