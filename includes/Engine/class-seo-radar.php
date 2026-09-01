<?php
namespace HAO\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * SEO Radar & Fırsat Puanlama Motoru
 */
class SeoRadar {

    /**
     * Anahtar Kelime Fırsat Puanı Hesapla (0 - 100)
     * Formül: Pozisyon (4-20) + Gösterim Hacmi + CTR Açığı
     */
    public static function calculate_opportunity_score( float $position, int $impressions, int $clicks, float $ctr, int $volume = 0 ): int {
        if ( $position < 3.5 || $position > 35 ) {
            return 0;
        }

        // 1. Pozisyon Skoru: Pozisyon 4 - 10 arası maksimum puan (40 puan)
        $pos_score = 0;
        if ( $position >= 3.5 && $position <= 10.5 ) {
            $pos_score = 40 - ( ( $position - 3.5 ) * 2 );
        } elseif ( $position > 10.5 && $position <= 20 ) {
            $pos_score = 25 - ( ( $position - 10.5 ) * 1.5 );
        } else {
            $pos_score = 10;
        }

        // 2. Gösterim Skoru (35 puan)
        $imp_score = 0;
        if ( $impressions >= 1000 ) {
            $imp_score = 35;
        } elseif ( $impressions >= 500 ) {
            $imp_score = 28;
        } elseif ( $impressions >= 100 ) {
            $imp_score = 20;
        } elseif ( $impressions >= 30 ) {
            $imp_score = 12;
        } else {
            $imp_score = 5;
        }

        // 3. CTR Açığı Skoru (15 puan)
        $expected_ctr = $position <= 5 ? 0.15 : ( $position <= 10 ? 0.05 : 0.02 );
        $ctr_gap_score = 0;
        if ( $ctr < $expected_ctr && $impressions >= 50 ) {
            $ctr_gap_score = 15;
        } else {
            $ctr_gap_score = 5;
        }

        // 4. Arama Hacmi Skoru (10 puan)
        $vol_score = $volume >= 1000 ? 10 : ( $volume >= 250 ? 6 : 2 );

        $total = (int) round( $pos_score + $imp_score + $ctr_gap_score + $vol_score );
        return min( 100, max( 0, $total ) );
    }

    /**
     * Otomatik Eylem Önerisi Etiketi Üret
     */
    public static function get_recommended_action( float $position, float $ctr, int $impressions ): array {
        if ( $position >= 4 && $position <= 10 && $ctr < 0.05 && $impressions >= 100 ) {
            return [
                'tag'   => 'CTR_BOOST',
                'label' => 'Başlıkta CTR Artır',
                'color' => 'amber',
                'desc'  => 'İlk sayfadasınız fakat tıklama oranı düşük. Başlığı ve meta açıklamayı daha çekici yapın.',
            ];
        }

        if ( $position >= 8 && $position <= 20 && $impressions >= 50 ) {
            return [
                'tag'   => 'CONTENT_EXPAND',
                'label' => 'H2 / FAQ Ekle',
                'color' => 'indigo',
                'desc'  => 'Sıralama 2. sayfada. Yazıya bu anahtar kelimeyi içeren H2 başlığı veya SSS bloğu ekleyin.',
            ];
        }

        if ( $position >= 4 && $position <= 7 ) {
            return [
                'tag'   => 'TOP3_PUSH',
                'label' => 'Top 3 Adayı',
                'color' => 'emerald',
                'desc'  => 'İlk 3 sıraya çok yakın. İç linklerle sayfayı güçlendirin.',
            ];
        }

        return [
            'tag'   => 'MONITOR',
            'label' => 'Takip Et',
            'color' => 'slate',
            'desc'  => 'Pozisyon ve gösterim değişimlerini izleyin.',
        ];
    }
}
