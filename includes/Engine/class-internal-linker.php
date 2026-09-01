<?php
namespace HAO\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Otomatik İç Linkleme Motoru
 */
class InternalLinker {

    /**
     * İlgili yazı için aynı kategorideki yayınlanmış diğer yazılardan akıllı iç link önerileri bulur
     */
    public function get_link_suggestions( int $post_id, int $limit = 5 ): array {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return [];
        }

        $categories = wp_get_post_categories( $post_id );
        if ( empty( $categories ) ) {
            return [];
        }

        $candidates = get_posts( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'category__in'   => $categories,
            'post__not_in'   => [ $post_id ],
            'orderby'        => 'rand',
        ] );

        $suggestions = [];
        $content     = $post->post_content;

        foreach ( $candidates as $candidate ) {
            $target_title = $candidate->post_title;
            $target_url   = get_permalink( $candidate->ID );

            // Başlıktan temiz bir çapa metin (anchor) türet
            $anchor = trim( preg_replace( '/\s+hesaplama(sı)?/iu', '', $target_title ) );
            if ( empty( $anchor ) ) {
                $anchor = $target_title;
            }

            // Eğer zaten linklenmemişse
            if ( false === strpos( $content, $target_url ) ) {
                $suggestions[] = [
                    'target_id'    => $candidate->ID,
                    'target_title' => $target_title,
                    'target_url'   => $target_url,
                    'anchor'       => $anchor,
                ];
            }

            if ( count( $suggestions ) >= $limit ) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Yazı içeriğine güvenli (bracket ve shortcode korumalı) iç link yerleştirir
     */
    public function inject_link( int $post_id, string $target_url, string $anchor_text ): bool {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        $content = $post->post_content;
        
        // Zaten link varsa tekrar ekleme
        if ( false !== strpos( $content, $target_url ) ) {
            return true;
        }

        // Metin içinde ilk geçen eşleşmeyi linkle
        $escaped_anchor = preg_quote( $anchor_text, '/' );
        $pattern        = '/(?!(?:[^<]+>|[^>]+<\/a>))\b(' . $escaped_anchor . ')\b/iu';
        $replacement    = '<a href="' . esc_url( $target_url ) . '" title="$1">$1</a>';

        $new_content = preg_replace( $pattern, $replacement, $content, 1 );

        if ( $new_content && $new_content !== $content ) {
            wp_update_post( [
                'ID'           => $post_id,
                'post_content' => $new_content,
            ] );
            return true;
        }

        return false;
    }
}
