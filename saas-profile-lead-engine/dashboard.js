/**
 * SaaS Dashboard - Elite High-Compatibility Engine (jQuery + ES5)
 * This version uses jQuery for maximum reliability in WordPress environments.
 */

(function($) {
    "use strict";

    $(document).ready(function() {
        console.log("SaaS Dashboard: Initializing...");

        // 1. Verification
        if (typeof saas_dashboard_data === 'undefined') {
            console.error("SaaS Dashboard: Localized data 'saas_dashboard_data' missing.");
            return;
        }

        // 2. Tab Switching Logic
        function switchTab(tabId) {
            if (!tabId) return;
            console.log("SaaS Dashboard: Switching to tab -> " + tabId);

            // Update Buttons
            $('.saas-tabs button').removeClass('active');
            $('.saas-tabs button[data-tab="' + tabId + '"]').addClass('active');

            // Update Content
            $('.saas-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');

            // Persist in URL
            if (window.history && window.history.pushState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            }
        }

        // Tab click event
        $('.saas-tabs').on('click', 'button', function(e) {
            e.preventDefault();
            var tabId = $(this).attr('data-tab');
            switchTab(tabId);
        });

        $(document).on('click', '.pro-locked, .pro-gated-inline', function(e) {
            if ($(this).hasClass('pro-gated-inline') && !$(e.target).is('input, select, textarea')) return;
            e.preventDefault();
            e.stopPropagation();
            if (confirm('This feature is only available for Elite Pro users. Would you like to view our Pro plans?')) {
                switchTab('billing');
            }
        });

        // Initialize from URL
        var currentTab = new URLSearchParams(window.location.search).get('tab');
        if (currentTab) {
            switchTab(currentTab);
        } else {
            var firstTab = $('.saas-tabs button:first').attr('data-tab');
            if (firstTab) switchTab(firstTab);
        }

        // 3. AJAX Wrapper
        function saasFetch(action, data, $btn) {
            var fd = (data instanceof FormData) ? data : new FormData();
            if (!(data instanceof FormData)) {
                for (var key in data) {
                    if (Array.isArray(data[key])) {
                        for(var i=0; i<data[key].length; i++) {
                            fd.append(key + '[]', data[key][i]);
                        }
                    } else {
                        fd.append(key, data[key]);
                    }
                }
            }
            fd.append('action', action);
            fd.append('security', saas_dashboard_data.nonce);

            var originalText = $btn ? $btn.text() : '';
            if ($btn) $btn.text('Processing...').prop('disabled', true);

            return $.ajax({
                url: saas_dashboard_data.ajax_url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).then(function(res) {
                if ($btn) $btn.text(originalText).prop('disabled', false);
                if (res.success) return res.data;
                throw new Error(res.data || 'Execution failed');
            }).fail(function(err) {
                if ($btn) $btn.text(originalText).prop('disabled', false);
                alert("Error: " + (err.message || "Request failed"));
                throw err;
            });
        }

        // 4. Block Management (Edit/Delete/Clone)
        $(document).on('click', '.edit-link', function() {
            var $li = $(this).closest('li');
            var d = $li.data();

            $('#edit-link-id').val($li.attr('data-id'));
            $('#edit-link-title').val($li.find('.link-title').text());
            $('#edit-link-url').val($li.find('.link-url').text());

            // Map data attributes to modal fields
            $('#edit-link-extra').val($li.attr('data-extra'));
            $('#edit-link-style').val($li.attr('data-style'));
            $('#edit-link-animation').val($li.attr('data-animation'));
            $('#edit-link-start').val($li.attr('data-start'));
            $('#edit-link-end').val($li.attr('data-end'));
            $('#edit-link-pass').val($li.attr('data-password'));
            $('#edit-link-ab-title').val($li.attr('data-ab-title'));
            $('#edit-link-ab-url').val($li.attr('data-ab-url'));
            $('#edit-link-url-mobile').val($li.attr('data-url-mobile'));
            $('#edit-link-geo-country').val($li.attr('data-geo-country'));
            $('#edit-link-url-geo').val($li.attr('data-url-geo'));
            $('#edit-link-custom-bg').val($li.attr('data-custom-bg') || '#6366f1');
            $('#edit-link-custom-text').val($li.attr('data-custom-text') || '#ffffff');
            $('#edit-link-hour-from').val($li.attr('data-hour-from'));
            $('#edit-link-hour-to').val($li.attr('data-hour-to'));

            $('#saas-edit-modal').css('display', 'flex');
            $('body').css('overflow', 'hidden');
        });

        $(document).on('click', '.delete-link', function() {
            if (!confirm("Delete this block permanently?")) return;
            var $li = $(this).closest('li');
            saasFetch('saas_delete_link', { link_id: $li.attr('data-id') }, $(this))
                .done(function() {
                    $li.fadeOut(function() { $(this).remove(); });
                    var frame = document.getElementById('saas-preview-frame');
                    if (frame) frame.contentWindow.location.reload();
                });
        });

        $(document).on('click', '.clone-link', function() {
            var $li = $(this).closest('li');
            saasFetch('saas_clone_link', { link_id: $li.attr('data-id') }, $(this))
                .done(function() { location.reload(); });
        });

        $(document).on('click', '.clone-profile-btn', function(e) {
            e.stopPropagation();
            if(!confirm('Clone this profile and all its blocks?')) return;
            saasFetch('saas_clone_profile', { profile_id: $(this).attr('data-id') }, $(this))
                .done(function(res) { window.location.href = '?profile_id=' + res.id; });
        });

        $(document).on('click', '#saas-add-profile-trigger', function() {
            var t = prompt('Profile Title:');
            if (t) saasFetch('saas_create_profile', { profile_title: t }, $(this)).done(function(res) {
                window.location.href = '?profile_id=' + res.id;
            });
        });

        $(document).on('click', '.profile-title', function(e) {
            e.stopPropagation();
            $('.profile-dropdown').toggle();
        });
        $(document).on('click', function() {
            $('.profile-dropdown').hide();
        });

        // AI Assist Logic
        $(document).on('click', '.ai-assist-btn', function() {
            var $btn = $(this);
            var target = $btn.data('target');
            var $input = $('[name="' + target + '"]');
            var niche = $('#profile-niche').val() || 'business';
            var oldText = $btn.text();

            $btn.text('🤖...').prop('disabled', true);

            setTimeout(function() {
                var suggestions = {
                    coach: { h: "Helping Founders Scale with Proven Systems 🚀", b: "Elite high-performance coach specializing in sustainable growth for 7-figure entrepreneurs." },
                    creator: { h: "Exclusive Content & Daily Insights 🎥", b: "Sharing daily tips on digital growth and community building for the next generation of creators." },
                    realtor: { h: "Modern Homes for Modern Families 🏡", b: "Helping you find your dream luxury property in the city's most exclusive neighborhoods." },
                    business: { h: "Driving Results through Strategic Design 📈", b: "Providing high-impact solutions for modern organizations ready to scale their digital infrastructure." }
                };

                var content = (target === 'headline') ? suggestions[niche].h : suggestions[niche].b;
                $input.val(content).trigger('input');
                $btn.text(oldText).prop('disabled', false);
            }, 800);
        });

        // 5. Form Submissions
        $('#saas-add-link-form').on('submit', function(e) {
            e.preventDefault();
            saasFetch('saas_add_link', new FormData(this), $(this).find('button'))
                .done(function() { location.reload(); });
        });

        $('#saas-edit-link-form').on('submit', function(e) {
            e.preventDefault();
            saasFetch('saas_save_link', new FormData(this), $(this).find('button[type="submit"]'))
                .done(function() { location.reload(); });
        });

        // Global Settings Forms
        $('#saas-profile-form, #saas-branding-form, #saas-automation-form, #saas-integrations-form, #saas-seo-form, #saas-tracking-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            saasFetch('saas_save_profile', new FormData(this), $form.find('button'))
                .done(function(msg) {
                    alert(msg);
                    var frame = document.getElementById('saas-preview-frame');
                    if (frame) frame.contentWindow.location.reload();
                });
        });

        // 6. Copy Link (Profile & Ref)
        // Cancel Subscription
        $('#saas-cancel-sub').on('click', function() {
            if(!confirm("Are you sure you want to cancel your elite subscription?")) return;
            saasFetch('saas_cancel_subscription', {}, $(this)).done(function(msg) {
                alert(msg);
                location.reload();
            });
        });

        // 6. Copy Link (Profile & Ref)
        $('#saas-copy-btn, #saas-copy-ref-btn').on('click', function() {
            var targetId = ($(this).attr('id') === 'saas-copy-btn') ? '#saas-my-link' : '#saas-ref-link';
            var $input = $(targetId);
            $input.select();
            document.execCommand('copy');
            var $btn = $(this);
            var oldText = $btn.text();
            $btn.text('Copied! ✅');
            setTimeout(function() { $btn.text(oldText); }, 2000);
        });

        // Checkout Button
        $('.saas-checkout-btn').on('click', function() {
            var data = {
                gateway: $(this).data('gateway'),
                plan_id: $(this).data('plan')
            };
            saasFetch('saas_checkout', data, $(this)).done(function(res) {
                window.location.href = res.redirect_url;
            });
        });

        // Affiliate/Support Messaging
        $('#saas-support-msg-form, #saas-new-support-msg, #saas-reply-msg-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var data = {
                to_user: $form.find('[name="to_user"]').val() || 1, // Default to admin
                message: $form.find('textarea').val(),
                subject: $form.find('[name="subject"]').val() || 'Support Request'
            };
            saasFetch('saas_send_message', data, $form.find('button')).done(function(msg) {
                alert(msg);
                $form.find('textarea, input[type="text"]').val('');
                if($form.closest('.saas-modal').length) {
                    $form.closest('.saas-modal').hide();
                    $('body').css('overflow', 'auto');
                }
            });
        });

        // Payout Request
        $('#saas-payout-request-form').on('submit', function(e) {
            e.preventDefault();
            saasFetch('saas_request_payout', new FormData(this), $(this).find('button')).done(function(msg) {
                alert(msg);
                location.reload();
            });
        });

        // Inbox: View Message
        $(document).on('click', '.view-message', function() {
            var msgId = $(this).attr('data-id');
            saasFetch('saas_get_message_content', { msg_id: msgId }, $(this)).done(function(res) {
                $('#msg-modal-title').text(res.title);
                $('#msg-modal-content').html(res.content);
                $('#msg-reply-to').val(res.from_id);
                $('#saas-message-modal').css('display', 'flex');
            });
        });

        // 7. Modal Control
        $(document).on('click', '.close-modal, .saas-modal', function(e) {
            if (e.target !== this && !$(this).hasClass('close-modal')) return;
            $('.saas-modal').hide();
            $('body').css('overflow', 'auto');
        });

        // 8. Advanced Toggle
        $(document).on('click', '.toggle-advanced', function() {
            $('#edit-advanced-fields').slideToggle();
            var isVisible = $('#edit-advanced-fields').is(':visible');
            $(this).text(isVisible ? '🔼 Hide Advanced Options' : '⚙️ Advanced Options');
        });

        // 9. Block Picker
        $('.picker-item').on('click', function() {
            $('.picker-item').removeClass('active');
            $(this).addClass('active');
            $('#saas-block-type-hidden').val($(this).attr('data-type'));
        });

        // 10. Preview Controls
        $('#saas-preview-trigger').on('click', function() {
            $('.saas-preview-pane').addClass('show').fadeIn();
            $('body').css('overflow', 'hidden');
        });

        $('#saas-close-preview').on('click', function() {
            $('.saas-preview-pane').removeClass('show').fadeOut();
            $('body').css('overflow', 'auto');
        });

        // 10.1 Marketing Material Copy
        $('.copy-html-btn').on('click', function() {
            var html = $(this).closest('div').find('img').prop('outerHTML');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(html).select();
            document.execCommand("copy");
            $temp.remove();
            var $btn = $(this);
            var old = $btn.text();
            $btn.text('HTML Copied! ✅');
            setTimeout(function() { $btn.text(old); }, 2000);
        });

        // 11. Lead Management (Search)
        $('#lead-search').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('.saas-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });

        // 11. Lead Management
        $(document).on('click', '.view-lead', function() {
            var leadId = $(this).attr('data-id');
            saasFetch('saas_get_lead_details', { lead_id: leadId }, $(this))
                .done(function(html) {
                    $('#lead-details-content').html(html);
                    $('#saas-lead-modal').css('display', 'flex');
                });
        });

        // Update Lead Details
        $(document).on('submit', '#saas-update-lead-form', function(e) {
            e.preventDefault();
            saasFetch('saas_update_lead', new FormData(this), $(this).find('button')).done(function() {
                location.reload();
            });
        });

        // Email Lead
        $(document).on('submit', '#saas-email-lead-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            saasFetch('saas_email_lead', new FormData(this), $form.find('button')).done(function(msg) {
                alert(msg);
                $form.find('textarea').val('');
            });
        });

        // 12. Wizard Logic
        var currentStep = 1;
        $(document).on('click', '.next-step', function() {
            currentStep++;
            updateWizard(currentStep);
        });
        $(document).on('click', '.prev-step', function() {
            currentStep--;
            updateWizard(currentStep);
        });
        function updateWizard(step) {
            $('.wizard-step').hide().filter('[data-step="' + step + '"]').show();
            var progress = (step / 3) * 100;
            $('.progress-bar-fill').css('width', progress + '%');
        }
        $('#wizard-finish').on('click', function() {
            var data = {
                profile_id: $('[name="profile_id"]').val(),
                headline: $('#wizard-headline').val(),
                bio: $('#wizard-bio').val(),
                niche: $('#wizard-niche').val()
            };
            saasFetch('saas_save_profile', data, $(this)).done(function() { location.reload(); });
        });

        // 13. Sortable Initializer
        if ($('#saas-links-list').length && typeof Sortable !== 'undefined') {
            new Sortable(document.getElementById('saas-links-list'), {
                animation: 150,
                handle: '.handle',
                onEnd: function() {
                    var ids = [];
                    $('#saas-links-list li').each(function() {
                        ids.push($(this).attr('data-id'));
                    });
                    saasFetch('saas_update_link_order', { link_ids: ids });
                }
            });
        }

        // 13. Charts (Analytics)
        if ($('#saas-analytics-chart').length && typeof Chart !== 'undefined' && typeof saas_chart_data !== 'undefined') {
            new Chart(document.getElementById('saas-analytics-chart'), {
                type: 'line',
                data: {
                    labels: saas_chart_data.labels.length ? saas_chart_data.labels : ['No Data'],
                    datasets: [
                        { label: 'Views', data: saas_chart_data.views.length ? saas_chart_data.views : [0], borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.05)', fill: true, tension: 0.4 },
                        { label: 'Clicks', data: saas_chart_data.clicks.length ? saas_chart_data.clicks : [0], borderColor: '#10b981', fill: false, tension: 0.4 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } } }
            });
        }

        if ($('#saas-ab-chart').length && typeof Chart !== 'undefined') {
            new Chart(document.getElementById('saas-ab-chart'), {
                type: 'bar',
                data: {
                    labels: ['Variant A', 'Variant B'],
                    datasets: [{
                        label: 'Click-through Rate (%)',
                        data: [12.5, 18.2],
                        backgroundColor: ['#6366f1', '#10b981'],
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });
        }
    });

})(jQuery);
