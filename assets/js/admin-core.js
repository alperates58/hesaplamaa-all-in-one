/**
 * Hesaplamaa All-in-One — Core Admin JavaScript
 */

(function($) {
    'use strict';

    window.HAO = {
        init: function() {
            this.bindEvents();
            this.initCharts();
        },

        showToast: function(msg, isError) {
            let $toast = $('#hao-toast');
            if (!$toast.length) {
                $toast = $('<div id="hao-toast" class="hao-toast"></div>').appendTo('body');
            }
            $toast.text(msg).css({
                background: isError ? '#ef4444' : '#0f172a',
                display: 'block'
            }).fadeIn(200);

            setTimeout(function() {
                $toast.fadeOut(300);
            }, 3500);
        },

        bindEvents: function() {
            const self = this;

            // 1. GSC Sync
            $(document).on('click', '#hao-btn-sync-gsc', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Senkronize Ediliyor...');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_sync_gsc',
                    nonce: hao_vars.nonce
                }, function(res) {
                    $btn.prop('disabled', false).html(originalText);
                    if (res.success) {
                        self.showToast(res.data.message || 'GSC Senkronizasyonu tamamlandı!');
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        self.showToast(res.data.message || 'Senkronizasyon hatası!', true);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html(originalText);
                    self.showToast('Sunucu bağlantı hatası!', true);
                });
            });

            // 2. URL Inspection Tekil Buton
            $(document).on('click', '.hao-btn-inspect', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const url = $btn.data('url');
                const orig = $btn.text();
                $btn.prop('disabled', true).text('İnceleniyor...');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_inspect_url',
                    nonce: hao_vars.nonce,
                    url: url
                }, function(res) {
                    $btn.prop('disabled', false).text(orig);
                    if (res.success) {
                        self.showToast('Dizin durumu güncellendi: ' + res.data.verdict);
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        self.showToast(res.data.message || 'İnceleme hatası!', true);
                    }
                });
            });

            // 3. AI Meta Üret Modal Aç
            $(document).on('click', '.hao-btn-generate-meta', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                const postTitle = $(this).data('post-title');

                $('#hao-meta-modal-post-id').val(postId);
                $('#hao-meta-modal-title-display').text(postTitle);
                $('#hao-meta-modal-loading').show();
                $('#hao-meta-modal-body').hide();
                $('#hao-meta-modal-backdrop').css('display', 'flex');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_generate_meta',
                    nonce: hao_vars.nonce,
                    post_id: postId
                }, function(res) {
                    $('#hao-meta-modal-loading').hide();
                    if (res.success) {
                        $('#hao-input-seo-title').val(res.data.seo_title);
                        $('#hao-input-meta-desc').val(res.data.meta_description);
                        $('#hao-input-focus-kw').val(res.data.focus_keyword || '');
                        
                        // SERP Preview Güncelle
                        $('#hao-preview-title').text(res.data.seo_title);
                        $('#hao-preview-desc').text(res.data.meta_description);

                        $('#hao-meta-modal-body').show();
                    } else {
                        self.showToast(res.data.message || 'AI Üretim hatası!', true);
                        $('#hao-meta-modal-backdrop').hide();
                    }
                });
            });

            // Meta Uygula
            $(document).on('click', '#hao-btn-apply-meta', function(e) {
                e.preventDefault();
                const postId = $('#hao-meta-modal-post-id').val();
                const seoTitle = $('#hao-input-seo-title').val();
                const metaDesc = $('#hao-input-meta-desc').val();
                const focusKw = $('#hao-input-focus-kw').val();
                const $btn = $(this);

                $btn.prop('disabled', true).text('Kaydediliyor...');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_apply_meta',
                    nonce: hao_vars.nonce,
                    post_id: postId,
                    seo_title: seoTitle,
                    meta_desc: metaDesc,
                    focus_kw: focusKw
                }, function(res) {
                    $btn.prop('disabled', false).text('Kaydet ve Uygula');
                    if (res.success) {
                        self.showToast('SEO Meta bilgileri kaydedildi!');
                        $('#hao-meta-modal-backdrop').hide();
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        self.showToast(res.data.message || 'Kayıt hatası!', true);
                    }
                });
            });

            // Canlı SERP Preview Dinleyicisi
            $(document).on('input', '#hao-input-seo-title', function() {
                $('#hao-preview-title').text($(this).val());
            });
            $(document).on('input', '#hao-input-meta-desc', function() {
                $('#hao-preview-desc').text($(this).val());
            });

            // Modal Kapat
            $(document).on('click', '.hao-modal-close, .hao-modal-backdrop', function(e) {
                if (e.target === this) {
                    $('.hao-modal-backdrop').hide();
                }
            });

            // 4. İç Link Önerileri Modal
            $(document).on('click', '.hao-btn-link-suggest', function(e) {
                e.preventDefault();
                const postId = $(this).data('post-id');
                const postTitle = $(this).data('post-title');

                $('#hao-link-modal-post-id').val(postId);
                $('#hao-link-modal-title').text(postTitle);
                $('#hao-link-modal-list').html('<p style="padding:20px; text-align:center; color:#64748b;">Öneriler taranıyor...</p>');
                $('#hao-link-modal-backdrop').css('display', 'flex');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_get_link_suggestions',
                    nonce: hao_vars.nonce,
                    post_id: postId
                }, function(res) {
                    if (res.success && res.data.suggestions && res.data.suggestions.length) {
                        let html = '<div class="hao-link-items">';
                        res.data.suggestions.forEach(function(item) {
                            html += `
                                <div class="hao-link-item" style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-weight:600; color:#0f172a; margin-bottom:2px;">${item.target_title}</div>
                                        <div style="font-size:12px; color:#64748b;">Çapa: <code style="background:#f1f5f9; padding:2px 4px;">${item.anchor}</code></div>
                                    </div>
                                    <button type="button" class="hao-btn hao-btn-primary hao-btn-sm hao-btn-inject-link" data-post-id="${postId}" data-url="${item.target_url}" data-anchor="${item.anchor}">Linkle</button>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#hao-link-modal-list').html(html);
                    } else {
                        $('#hao-link-modal-list').html('<p style="padding:20px; text-align:center; color:#64748b;">Bu yazı için uygun iç link adayı bulunamadı.</p>');
                    }
                });
            });

            // İç Link Enjekte Et
            $(document).on('click', '.hao-btn-inject-link', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const postId = $btn.data('post-id');
                const targetUrl = $btn.data('url');
                const anchor = $btn.data('anchor');

                $btn.prop('disabled', true).text('Ekleniyor...');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_inject_link',
                    nonce: hao_vars.nonce,
                    post_id: postId,
                    target_url: targetUrl,
                    anchor: anchor
                }, function(res) {
                    if (res.success) {
                        $btn.removeClass('hao-btn-primary').addClass('hao-btn-secondary').text('Eklendi ✓');
                        self.showToast('İç link başarıyla yerleştirildi!');
                    } else {
                        $btn.prop('disabled', false).text('Linkle');
                        self.showToast(res.data.message || 'Eklenemedi!', true);
                    }
                });
            });

            // 5. Yeni Fikir Keşfi
            $(document).on('click', '#hao-btn-expand-ideas', function(e) {
                e.preventDefault();
                const seed = $('#hao-seed-input').val() || 'hesaplama';
                const $btn = $(this);
                $btn.prop('disabled', true).text('Google Taranıyor...');

                $.post(hao_vars.ajax_url, {
                    action: 'hao_expand_ideas',
                    nonce: hao_vars.nonce,
                    seed: seed
                }, function(res) {
                    $btn.prop('disabled', false).text('Yeni Fikirleri Tara');
                    if (res.success) {
                        self.showToast(res.data.saved_count + ' yeni hesaplama fikri arşive eklendi!');
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        self.showToast('Fikir tarama hatası!', true);
                    }
                });
            });

            // Fikir Durumu Değiştir
            $(document).on('change', '.hao-idea-toggle', function() {
                const id = $(this).data('id');
                const status = $(this).is(':checked') ? 1 : 0;
                $.post(hao_vars.ajax_url, {
                    action: 'hao_toggle_idea',
                    nonce: hao_vars.nonce,
                    id: id,
                    status: status
                });
            });

            // 6. Ayarlar Formları
            $('#hao-form-gsc-settings').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serialize() + '&action=hao_save_gsc_settings&nonce=' + hao_vars.nonce;
                $.post(hao_vars.ajax_url, data, function(res) {
                    self.showToast(res.data.message || 'Ayarlar kaydedildi!');
                });
            });

            $('#hao-form-ai-settings').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serialize() + '&action=hao_save_ai_settings&nonce=' + hao_vars.nonce;
                $.post(hao_vars.ajax_url, data, function(res) {
                    self.showToast(res.data.message || 'AI ayarları kaydedildi!');
                });
            });

            $('#hao-form-ads-settings').on('submit', function(e) {
                e.preventDefault();
                const data = $(this).serialize() + '&action=hao_save_ads_settings&nonce=' + hao_vars.nonce;
                $.post(hao_vars.ajax_url, data, function(res) {
                    self.showToast(res.data.message || 'Google Ads ayarları kaydedildi!');
                });
            });

            // AI Test Bağlantısı
            $('#hao-btn-test-ai').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                $btn.prop('disabled', true).text('Test Ediliyor...');
                $.post(hao_vars.ajax_url, {
                    action: 'hao_test_ai',
                    nonce: hao_vars.nonce
                }, function(res) {
                    $btn.prop('disabled', false).text('Bağlantıyı Test Et');
                    if (res.success) {
                        alert('AI Yanıtı: ' + res.data.response);
                    } else {
                        self.showToast(res.data.message || 'AI Test Hatası!', true);
                    }
                });
            });
        },

        initCharts: function() {
            const chartCanvas = document.getElementById('haoGrowthChart');
            if (!chartCanvas || typeof Chart === 'undefined') {
                return;
            }

            const rawData = $(chartCanvas).data('stats') || [];
            if (!rawData.length) {
                return;
            }

            const labels = rawData.map(d => d.stat_date);
            const clicks = rawData.map(d => parseInt(d.clicks) || 0);
            const impressions = rawData.map(d => parseInt(d.impressions) || 0);

            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tıklamalar',
                            data: clicks,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Gösterimler',
                            data: impressions,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Tıklama' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Gösterim' }
                        }
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        window.HAO.init();
    });

})(jQuery);
