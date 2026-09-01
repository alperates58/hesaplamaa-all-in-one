<?php
namespace HAO\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * AI Destekli SEO Başlık ve Meta Açıklama Optimizasyon Motoru
 */
class MetaOptimizer {

    private \HAO\API\AiHub $ai_hub;

    public function __construct() {
        $this->ai_hub = new \HAO\API\AiHub();
    }

    /**
     * Tekil Yazı İçin AI ile SEO Başlığı ve Meta Açıklaması Üretip Kaydeder
     */
    public function optimize_post_meta( int $post_id, bool $apply = false ): array {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return [ 'success' => false, 'message' => 'Yazı bulunamadı.' ];
        }

        $existing_kw = (string) get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
        $ai_result   = $this->ai_hub->generate_seo_meta( $post->post_title, $post->post_content, $existing_kw );

        if ( is_wp_error( $ai_result ) ) {
            return [ 'success' => false, 'message' => $ai_result->get_error_message() ];
        }

        if ( ! is_array( $ai_result ) || empty( $ai_result['meta_description'] ) ) {
            return [ 'success' => false, 'message' => 'AI geçerli bir meta açıklaması üretemedi.' ];
        }

        $seo_title   = sanitize_text_field( $ai_result['seo_title'] ?? $post->post_title );
        $meta_desc   = sanitize_textarea_field( $ai_result['meta_description'] ?? '' );
        $focus_kw    = sanitize_text_field( $ai_result['focus_keyword'] ?? $existing_kw );

        if ( $apply ) {
            // Yoast SEO alanlarına kaydet
            update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
            if ( $focus_kw ) {
                update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
            }

            // Yedek olarak RankMath / All-in-One SEO alanlarına da kaydet
            update_post_meta( $post_id, 'rank_math_title', $seo_title );
            update_post_meta( $post_id, 'rank_math_description', $meta_desc );
            update_post_meta( $post_id, '_aioseo_title', $seo_title );
            update_post_meta( $post_id, '_aioseo_description', $meta_desc );
        }

        return [
            'success'          => true,
            'post_id'          => $post_id,
            'seo_title'        => $seo_title,
            'meta_description' => $meta_desc,
            'focus_keyword'    => $focus_kw,
            'applied'          => $apply,
        ];
    }
}
