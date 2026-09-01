<?php
namespace HAO\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Merkezi AJAX İstek Yöneticisi
 */
class AjaxHandler {

    public function init() {
        $actions = [
            'hao_sync_gsc'             => [ $this, 'ajax_sync_gsc' ],
            'hao_inspect_url'          => [ $this, 'ajax_inspect_url' ],
            'hao_generate_meta'        => [ $this, 'ajax_generate_meta' ],
            'hao_apply_meta'           => [ $this, 'ajax_apply_meta' ],
            'hao_get_link_suggestions' => [ $this, 'ajax_get_link_suggestions' ],
            'hao_inject_link'          => [ $this, 'ajax_inject_link' ],
            'hao_expand_ideas'         => [ $this, 'ajax_expand_ideas' ],
            'hao_toggle_idea'          => [ $this, 'ajax_toggle_idea' ],
            'hao_save_gsc_settings'    => [ $this, 'ajax_save_gsc_settings' ],
            'hao_save_ai_settings'     => [ $this, 'ajax_save_ai_settings' ],
            'hao_save_ads_settings'    => [ $this, 'ajax_save_ads_settings' ],
            'hao_test_ai'              => [ $this, 'ajax_test_ai' ],
        ];

        foreach ( $actions as $action => $cb ) {
            add_action( 'wp_ajax_' . $action, $cb );
        }
    }

    private function verify_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Yetkisiz işlem.' ], 403 );
        }
        check_ajax_referer( 'hao_admin_nonce', 'nonce' );
    }

    public function ajax_sync_gsc() {
        $this->verify_request();
        $result = \HAO\Cron\Scheduler::run_daily_gsc_sync();
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    public function ajax_inspect_url() {
        $this->verify_request();
        $url = esc_url_raw( $_POST['url'] ?? '' );
        if ( empty( $url ) ) {
            wp_send_json_error( [ 'message' => 'Geçersiz URL.' ] );
        }

        $inspector = new \HAO\API\UrlInspection();
        $repo      = new \HAO\DB\Repository();
        $result    = $inspector->inspect_url( $url );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        $repo->save_index_status( $result );
        wp_send_json_success( $result );
    }

    public function ajax_generate_meta() {
        $this->verify_request();
        $post_id   = (int) ( $_POST['post_id'] ?? 0 );
        $optimizer = new \HAO\Engine\MetaOptimizer();
        $result    = $optimizer->optimize_post_meta( $post_id, false );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    public function ajax_apply_meta() {
        $this->verify_request();
        $post_id   = (int) ( $_POST['post_id'] ?? 0 );
        $seo_title = sanitize_text_field( $_POST['seo_title'] ?? '' );
        $meta_desc = sanitize_textarea_field( $_POST['meta_desc'] ?? '' );
        $focus_kw  = sanitize_text_field( $_POST['focus_kw'] ?? '' );

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'Geçersiz yazı ID.' ] );
        }

        if ( $seo_title ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
            update_post_meta( $post_id, 'rank_math_title', $seo_title );
        }
        if ( $meta_desc ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
            update_post_meta( $post_id, 'rank_math_description', $meta_desc );
            update_post_meta( $post_id, '_aioseo_description', $meta_desc );
        }
        if ( $focus_kw ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
        }

        wp_send_json_success( [ 'message' => 'Meta bilgileri başarıyla güncellendi.' ] );
    }

    public function ajax_get_link_suggestions() {
        $this->verify_request();
        $post_id = (int) ( $_POST['post_id'] ?? 0 );
        $linker  = new \HAO\Engine\InternalLinker();
        $items   = $linker->get_link_suggestions( $post_id, 10 );
        wp_send_json_success( [ 'suggestions' => $items ] );
    }

    public function ajax_inject_link() {
        $this->verify_request();
        $post_id    = (int) ( $_POST['post_id'] ?? 0 );
        $target_url = esc_url_raw( $_POST['target_url'] ?? '' );
        $anchor     = sanitize_text_field( $_POST['anchor'] ?? '' );

        $linker  = new \HAO\Engine\InternalLinker();
        $success = $linker->inject_link( $post_id, $target_url, $anchor );

        if ( $success ) {
            wp_send_json_success( [ 'message' => 'İç link başarıyla eklendi.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'İç link metinde bulunamadı veya eklenemedi.' ] );
        }
    }

    public function ajax_expand_ideas() {
        $this->verify_request();
        $seed   = sanitize_text_field( $_POST['seed'] ?? 'hesaplama' );
        $suggest = new \HAO\API\SuggestClient();
        $repo    = new \HAO\DB\Repository();

        $topics = $suggest->expand_seed( $seed );
        $saved  = 0;
        foreach ( $topics as $topic ) {
            if ( $repo->save_suggestion( $topic, 0, 'suggest' ) ) {
                $saved++;
            }
        }

        wp_send_json_success( [ 'saved_count' => $saved, 'total_scanned' => count( $topics ) ] );
    }

    public function ajax_toggle_idea() {
        $this->verify_request();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $status = (int) ( $_POST['status'] ?? 0 );
        $repo   = new \HAO\DB\Repository();
        $repo->toggle_suggestion_status( $id, $status );
        wp_send_json_success( [ 'status' => $status ] );
    }

    public function ajax_save_gsc_settings() {
        $this->verify_request();
        $settings = get_option( 'hge_settings', [] );
        $settings['gsc_client_id']     = sanitize_text_field( $_POST['gsc_client_id'] ?? '' );
        $settings['gsc_client_secret'] = sanitize_text_field( $_POST['gsc_client_secret'] ?? '' );
        $settings['gsc_site_url']      = esc_url_raw( $_POST['gsc_site_url'] ?? get_site_url() );
        update_option( 'hge_settings', $settings );
        wp_send_json_success( [ 'message' => 'Google Search Console ayarları kaydedildi.' ] );
    }

    public function ajax_save_ai_settings() {
        $this->verify_request();
        $ai_hub = new \HAO\API\AiHub();
        $saved  = $ai_hub->save_settings( $_POST );
        if ( $saved ) {
            wp_send_json_success( [ 'message' => 'AI ayarları başarıyla kaydedildi.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Ayarlar kaydedilemedi.' ] );
        }
    }

    public function ajax_save_ads_settings() {
        $this->verify_request();
        $settings = get_option( 'hge_settings', [] );
        $settings['google_ads_dev_token']     = sanitize_text_field( $_POST['ads_dev_token'] ?? '' );
        $settings['google_ads_client_id']     = sanitize_text_field( $_POST['ads_client_id'] ?? '' );
        $settings['google_ads_client_secret'] = sanitize_text_field( $_POST['ads_client_secret'] ?? '' );
        $settings['google_ads_refresh_token'] = sanitize_text_field( $_POST['ads_refresh_token'] ?? '' );
        $settings['google_ads_customer_id']   = sanitize_text_field( $_POST['ads_customer_id'] ?? '' );
        update_option( 'hge_settings', $settings );
        wp_send_json_success( [ 'message' => 'Google Ads ayarları kaydedildi.' ] );
    }

    public function ajax_test_ai() {
        $this->verify_request();
        $ai_hub = new \HAO\API\AiHub();
        $res    = $ai_hub->prompt( 'Sen yardımcı bir asistansın.', 'Merhaba, bağlantı testi yapıyorum. Kısa ve net Türkçe bir yanıt ver.' );

        if ( is_wp_error( $res ) ) {
            wp_send_json_error( [ 'message' => $res->get_error_message() ] );
        }

        wp_send_json_success( [ 'response' => $res ] );
    }
}
