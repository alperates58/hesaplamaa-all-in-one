<?php
namespace HAO\API;

defined( 'ABSPATH' ) || exit;

/**
 * Merkezi Çoklu AI Köprüsü (DeepSeek V4 Flash / OpenAI / Gemini)
 */
class AiHub {

    const OPTION_KEY = 'hao_ai_settings';

    private array $settings;

    public function __construct() {
        $defaults = [
            'provider'       => 'deepseek',
            'deepseek_key'   => '',
            'deepseek_model' => 'deepseek-v4-flash',
            'openai_key'     => '',
            'openai_model'   => 'gpt-4o-mini',
            'gemini_key'     => '',
            'gemini_model'   => 'gemini-2.0-flash',
            'temperature'    => 0.7,
        ];

        $saved = get_option( self::OPTION_KEY, [] );
        
        // Geriye dönük uyumluluk: HGE veya AI-SEO ayarlarından otomatik anahtar transferi
        if ( empty( $saved['deepseek_key'] ) ) {
            $saved_ds = get_option( 'hc_deepseek_api_key', '' ) ?: get_option( 'deepseek_api_key', '' );
            if ( $saved_ds ) {
                $saved['deepseek_key'] = $saved_ds;
            }
        }

        if ( empty( $saved['openai_key'] ) ) {
            $hge_ai = get_option( 'hge_ai_settings', [] );
            if ( ! empty( $hge_ai['api_key'] ) ) {
                $saved['openai_key'] = $hge_ai['api_key'];
            } else {
                $aiseo_key = get_option( 'aiseo_openai_api_key', '' );
                if ( $aiseo_key ) {
                    $saved['openai_key'] = $aiseo_key;
                }
            }
        }

        $this->settings = wp_parse_args( $saved, $defaults );
    }

    public function get_settings(): array {
        return $this->settings;
    }

    public function save_settings( array $data ): bool {
        $clean = [
            'provider'       => sanitize_key( $data['provider'] ?? 'deepseek' ),
            'deepseek_key'   => sanitize_text_field( $data['deepseek_key'] ?? '' ),
            'deepseek_model' => sanitize_text_field( $data['deepseek_model'] ?? 'deepseek-v4-flash' ),
            'openai_key'     => sanitize_text_field( $data['openai_key'] ?? '' ),
            'openai_model'   => sanitize_text_field( $data['openai_model'] ?? 'gpt-4o-mini' ),
            'gemini_key'     => sanitize_text_field( $data['gemini_key'] ?? '' ),
            'gemini_model'   => sanitize_text_field( $data['gemini_model'] ?? 'gemini-2.0-flash' ),
            'temperature'    => max( 0.1, min( 1.0, (float) ( $data['temperature'] ?? 0.7 ) ) ),
        ];

        $this->settings = $clean;
        return update_option( self::OPTION_KEY, $clean );
    }

    public function is_configured(): bool {
        $p = $this->settings['provider'];
        if ( $p === 'deepseek' ) {
            return ! empty( $this->settings['deepseek_key'] );
        }
        if ( $p === 'gemini' ) {
            return ! empty( $this->settings['gemini_key'] );
        }
        return ! empty( $this->settings['openai_key'] );
    }

    /**
     * AI'a Generic Prompt Gönder
     */
    public function prompt( string $system_prompt, string $user_prompt, bool $json_mode = false ) {
        $provider = $this->settings['provider'];

        if ( $provider === 'deepseek' ) {
            return $this->call_deepseek( $system_prompt, $user_prompt, $json_mode );
        } elseif ( $provider === 'gemini' ) {
            return $this->call_gemini( $system_prompt, $user_prompt, $json_mode );
        } else {
            return $this->call_openai( $system_prompt, $user_prompt, $json_mode );
        }
    }

    /**
     * DeepSeek API Çağrısı (Varsayılan: DeepSeek V4 Flash)
     */
    private function call_deepseek( string $system, string $user, bool $json_mode = false ) {
        $key   = $this->settings['deepseek_key'];
        $model = $this->settings['deepseek_model'] ?: 'deepseek-v4-flash';

        if ( empty( $key ) ) {
            return new \WP_Error( 'ai_no_key', 'DeepSeek API Anahtarı eksik. Lütfen ayarlardan girin.' );
        }

        $body = [
            'model'       => $model,
            'messages'    => [
                [ 'role' => 'system', 'content' => $system ],
                [ 'role' => 'user',   'content' => $user ],
            ],
            'temperature' => (float) $this->settings['temperature'],
        ];

        if ( $json_mode ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }

        $response = wp_remote_post(
            'https://api.deepseek.com/v1/chat/completions',
            [
                'timeout' => 45,
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['error'] ) ) {
            return new \WP_Error( 'deepseek_error', $data['error']['message'] ?? 'DeepSeek Hatası' );
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return $json_mode ? json_decode( $content, true ) : $content;
    }

    /**
     * OpenAI API Çağrısı
     */
    private function call_openai( string $system, string $user, bool $json_mode = false ) {
        $key   = $this->settings['openai_key'];
        $model = $this->settings['openai_model'] ?: 'gpt-4o-mini';

        if ( empty( $key ) ) {
            return new \WP_Error( 'ai_no_key', 'OpenAI API Anahtarı eksik. Lütfen ayarlardan kaydedin.' );
        }

        $body = [
            'model'       => $model,
            'messages'    => [
                [ 'role' => 'system', 'content' => $system ],
                [ 'role' => 'user',   'content' => $user ],
            ],
            'temperature' => (float) $this->settings['temperature'],
        ];

        if ( $json_mode ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'timeout' => 45,
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['error'] ) ) {
            return new \WP_Error( 'openai_error', $data['error']['message'] ?? 'OpenAI Hatası' );
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return $json_mode ? json_decode( $content, true ) : $content;
    }

    /**
     * Gemini API Çağrısı
     */
    private function call_gemini( string $system, string $user, bool $json_mode = false ) {
        $key   = $this->settings['gemini_key'];
        $model = $this->settings['gemini_model'] ?: 'gemini-2.0-flash';

        if ( empty( $key ) ) {
            return new \WP_Error( 'ai_no_key', 'Google Gemini API Anahtarı eksik.' );
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $body = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        [ 'text' => "SYSTEM INSTRUCTION:\n{$system}\n\nUSER REQUEST:\n{$user}" ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) $this->settings['temperature'],
            ],
        ];

        if ( $json_mode ) {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }

        $response = wp_remote_post(
            $url,
            [
                'timeout' => 45,
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['error'] ) ) {
            return new \WP_Error( 'gemini_error', $data['error']['message'] ?? 'Gemini Hatası' );
        }

        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $json_mode ? json_decode( $content, true ) : $content;
    }

    // -------------------------------------------------------------------------
    // Uzmanlaşmış SEO Metodları
    // -------------------------------------------------------------------------

    /**
     * AI ile Eksik SEO Başlığı ve Meta Açıklaması Üret
     */
    public function generate_seo_meta( string $title, string $content_snippet, string $keyword = '' ) {
        $system = "Sen hesaplamaa.com için profesyonel bir Türkçe SEO uzmanısın. Kullanıcının verdiği yazı başlığı, içerik özeti ve odak anahtar kelimeye göre yüksek CTR sağlayacak, Google standartlarına tam uygun (Başlık 50-60 karakter, Meta açıklama 130-155 karakter) SEO başlığı ve meta açıklaması üret. Çıktıyı kesinlikle geçerli bir JSON nesnesi olarak döndür: {\"seo_title\": \"...\", \"meta_description\": \"...\", \"focus_keyword\": \"...\"}";

        $user = "Yazı Başlığı: {$title}\nOdak Kelime: {$keyword}\nİçerik Özeti: " . mb_substr( wp_strip_all_tags( $content_snippet ), 0, 400 );

        return $this->prompt( $system, $user, true );
    }

    /**
     * AI ile Yeni Hesaplama Fikirleri Türet
     */
    public function expand_ideas( array $seed_topics ) {
        $system = "Sen hesaplamaa.com için yeni niş hesaplama araçları tasarlayan bir ürün ve SEO danışmanısın. Verilen tohum konuları analiz et ve Türkiye'de insanların arattığı, faydalı ve tıklanma potansiyeli yüksek 10 adet spesifik 'hesaplayıcı / hesaplama aracı' konusu öner. Çıktıyı JSON formatında ver: {\"ideas\": [{\"topic\": \"...\", \"reason\": \"...\"}]}";

        $user = "Tohum Konular: " . implode( ', ', array_slice( $seed_topics, 0, 20 ) );

        return $this->prompt( $system, $user, true );
    }
}
