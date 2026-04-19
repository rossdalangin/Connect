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

        // Guidance Helper Function
        function updateGuidance(type, isEdit) {
            var guidance = {
                button: {
                    instruction: "Standard Button: Perfect for links to your website, scheduler, or social profiles.",
                    title: "Button Label", url: "Destination URL", extra: "Description (Optional)",
                    title_ph: "e.g. Schedule a Call", url_ph: "https://calendly.com/yourname", extra_ph: "Brief sub-text to appear below the label."
                },
                video: {
                    instruction: "Video Block: Paste a link from YouTube or Vimeo. We'll automatically embed the player.",
                    title: "Video Title", url: "Video URL", extra: "Caption (Optional)",
                    title_ph: "e.g. Watch my latest Masterclass", url_ph: "https://www.youtube.com/watch?v=...", extra_ph: "A short description of the video content."
                },
                testimonial: {
                    instruction: "Testimonial: Social proof builds trust. Enter the client's quote and use the URL field if you want to link to a case study.",
                    title: "Client Name / Citation", url: "Link to Proof (Optional)", extra: "The Testimonial / Quote",
                    title_ph: "e.g. Sarah Jenkins, CEO", url_ph: "https://yourwebsite.com/case-study", extra_ph: "Alex helped me double my revenue in just 90 days! Highly recommended."
                },
                faq: {
                    instruction: "FAQ: Answer common questions before they ask. This block creates a toggleable accordion.",
                    title: "The Question", url: "Internal Link (Optional)", extra: "The Answer",
                    title_ph: "e.g. What is included in the Elite package?", url_ph: "#", extra_ph: "The Elite package includes 4 strategy calls, private Slack access, and a custom audit."
                },
                pricing: {
                    instruction: "Pricing Card: Show your offer clearly. Enter features one per line in the Extra Content box.",
                    title: "Package Title", url: "Checkout / Buy Link", extra: "Price and Features (First line is price, rest are features)",
                    title_ph: "e.g. Executive Coaching", url_ph: "https://stripe.com/checkout/...", extra_ph: "$2,500/mo\n4 Weekly Calls\nUnlimited Email Support\nFull Business Audit"
                },
                image_gallery: {
                    instruction: "Image Gallery (Pro): Showcase your portfolio. Enter one image URL per line in the Extra Content box.",
                    title: "Gallery Title", url: "Gallery View All Link", extra: "Image URLs (one per line)",
                    title_ph: "e.g. Recent Logo Designs", url_ph: "https://behance.net/yourname", extra_ph: "https://yoursite.com/img1.jpg\nhttps://yoursite.com/img2.jpg"
                },
                social_icons: {
                    instruction: "Social Icons: Display a row of icons. Enter platform:url per line (e.g. twitter:https://...).",
                    title: "Section Heading", url: "Main Profile Link", extra: "Platforms (platform:url per line)",
                    title_ph: "e.g. Connect with Me", url_ph: "https://linktr.ee/yourname", extra_ph: "twitter:https://twitter.com/...\nlinkedin:https://linkedin.com/in/...\ninstagram:https://instagram.com/..."
                },
                newsletter: {
                    instruction: "Newsletter (Pro): Capture emails directly into your list. Connect Mailchimp in the Sync tab.",
                    title: "Form Heading", url: "Privacy Policy Link", extra: "Success Message",
                    title_ph: "e.g. Join my Weekly Newsletter", url_ph: "https://yoursite.com/privacy", extra_ph: "Thanks for joining! Check your inbox for your first issue."
                },
                lead_form: {
                    instruction: "Custom Lead Form: Capture high-intent inquiries. Configure fields in the Settings tab.",
                    title: "Form Title", url: "Redirect URL (Optional)", extra: "Footer / Disclaimer",
                    title_ph: "e.g. Request a Quote", url_ph: "https://yoursite.com/thank-you", extra_ph: "We usually respond within 24 hours. No spam, ever."
                },
                calendar: {
                    instruction: "Calendar (Pro): Embed your booking page (Calendly, etc) directly.",
                    title: "Calendar Title", url: "Booking Page URL", extra: "Instructions",
                    title_ph: "e.g. Book a Discovery Call", url_ph: "https://calendly.com/yourname/30min", extra_ph: "Please select a time that works best for you. Note: All calls are on Zoom."
                }
            };

            var g = guidance[type] || guidance.button;
            var prefix = isEdit ? 'edit-' : '';
            $('#' + prefix + 'guidance-text').text(g.instruction);
            $('#' + prefix + 'label-title').text(g.title);
            $('#' + prefix + 'label-url').text(g.url);
            $('#' + prefix + 'label-extra').text(g.extra);

            var $form = isEdit ? $('#saas-edit-link-form') : $('#saas-add-link-form');
            $form.find('[name="title"]').attr('placeholder', g.title_ph);
            $form.find('[name="url"]').attr('placeholder', g.url_ph);
            $form.find('[name="extra"]').attr('placeholder', g.extra_ph);
        }

        // 4. Block Management (Edit/Delete/Clone)
        $(document).on('click', '.edit-link', function() {
            var $li = $(this).closest('li');
            var d = $li.data();

            var type = $li.attr('data-type') || 'button';
            $('#edit-link-id').val($li.attr('data-id'));
            $('#edit-link-title').val($li.find('.link-title').text());
            $('#edit-link-url').val($li.find('.link-url').text());
            $('#edit-block-type-badge').text(type.toUpperCase().replace('_', ' '));

            updateGuidance(type, true);

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
            $('#edit-link-image-id').val($li.attr('data-link-image-id'));
            var imgUrl = $li.find('.btn-thumb').attr('src');
            $('#edit-link-image-preview').html(imgUrl ? '<img src="' + imgUrl + '" style="width:100%; height:100%; object-fit:cover;">' : '');

            // Show Inline instead of Modal
            $('#saas-edit-inline').slideDown(400, function() {
                $('html, body').animate({
                    scrollTop: $(this).offset().top - 120
                }, 500);
            });

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

        $(document).on('click', '#saas-demo-upgrade-btn', function() {
            if(!confirm('This will simulate a successful payment and grant you Pro access. Continue?')) return;
            saasFetch('saas_simulate_pro_upgrade', {}, $(this)).done(function(msg) {
                alert(msg);
                location.reload();
            });
        });

        $(document).on('click', '#saas-simulate-payment-btn', function() {
            if(!confirm('This will trigger the Stripe Webhook simulator for $19. Continue?')) return;
            var $btn = $(this);
            var userId = $btn.text().match(/\(UID: (\d+)\)/) ? $btn.text().match(/\(UID: (\d+)\)/)[1] : 1;

            $.ajax({
                url: '/wp-json/saas/v1/webhook',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id: userId, status: 'succeeded', plan: 'pro' }),
                success: function(res) {
                    alert('Webhook processed successfully! You are now Pro.');
                    location.reload();
                },
                error: function() { alert('Webhook simulation failed.'); }
            });
        });

        $(document).on('click', '.delete-profile-btn', function(e) {
            e.stopPropagation();
            if(!confirm('DELETE this profile and all its links forever?')) return;
            saasFetch('saas_delete_profile', { profile_id: $(this).attr('data-id') }, $(this))
                .done(function() { window.location.href = '?'; });
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

            saasFetch('saas_ai_assist', { target: target, niche: niche }, $(this))
                .done(function(res) {
                    $input.val(res).trigger('input');
                    updatePreview(target, res);
                    $btn.text(oldText).prop('disabled', false);
                });
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

        // Live Preview Bridge (postMessage)
        function updatePreview(key, value) {
            var frame = document.getElementById('saas-preview-frame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.postMessage({ type: 'live_update', key: key, value: value }, '*');
            }
        }

        $('#saas-profile-form [name="headline"]').on('input', function() { updatePreview('headline', $(this).val()); });
        $('#saas-profile-form [name="bio"]').on('input', function() { updatePreview('bio', $(this).val()); });
        $('#saas-branding-form [name="theme_color"]').on('change', function() { updatePreview('theme_color', $(this).val()); });
        $('#saas-branding-form [name="bg_value"]').on('input', function() { updatePreview('bg_value', $(this).val()); });
        $('#saas-branding-form [name="profile_theme"]').on('change', function() { updatePreview('profile_theme', $(this).val()); });
        $('#saas-branding-form [name="container_shadow"]').on('change', function() { updatePreview('container_shadow', $(this).val()); });
        $('#saas-branding-form [name="font_family"]').on('change', function() { updatePreview('font_family', $(this).val()); });
        $('#saas-branding-form [name="btn_shape"]').on('change', function() { updatePreview('btn_shape', $(this).val()); });
        $('#saas-branding-form [name="custom_css"]').on('input', function() { updatePreview('custom_css', $(this).val()); });
        $('#saas-profile-form [name="verified_badge"]').on('change', function() { updatePreview('verified_badge', $(this).is(':checked')); });

        // Global Settings Forms
        $('#saas-profile-form, #saas-branding-form, #saas-automation-form, #saas-integrations-form, #saas-seo-form, #saas-tracking-form, #saas-account-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var isProfileTab = $form.attr('id') === 'saas-profile-form';
            var isAccountTab = $form.attr('id') === 'saas-account-form';
            var newSlug = isProfileTab ? $form.find('[name="profile_slug"]').val() : null;
            var action = isAccountTab ? 'saas_save_account' : 'saas_save_profile';

            saasFetch(action, new FormData(this), $form.find('button'))
                .done(function(msg) {
                    alert(msg);
                    var frame = document.getElementById('saas-preview-frame');
                    if (frame) {
                        if (newSlug) {
                            var baseUrl = new URL(frame.src).origin;
                            frame.src = baseUrl + '/' + newSlug;
                        } else {
                            frame.contentWindow.location.reload();
                        }
                    }
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
                plan_id: $(this).data('plan'),
                coupon: $('#saas-checkout-coupon').val()
            };
            saasFetch('saas_checkout', data, $(this)).done(function(res) {
                window.location.href = res.redirect_url;
            });
        });

        $('#saas-apply-checkout-coupon').on('click', function() {
            var coupon = $('#saas-checkout-coupon').val();
            if (!coupon) return;
            var $status = $('#coupon-status');
            $status.text('Validating...').css('color', '#666');

            saasFetch('saas_apply_coupon', { coupon: coupon }, $(this))
                .done(function(msg) {
                    $status.text('✓ ' + msg).css('color', 'var(--secondary)');
                })
                .fail(function(err) {
                    $status.text('✗ ' + err.message).css('color', 'var(--danger)');
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

        });

        // 8. Advanced Toggle
        // Style Presets
        $(document).on('click', '.preset-btn', function() {
            var p = $(this).data('preset');
            var $form = $('#saas-branding-form');
            var presets = {
                midnight: { theme: 'dark', bg_type: 'flat', bg_value: '#0f172a', accent: '#6366f1', shadow: 'soft', font: "'Inter', sans-serif" },
                glassy: { theme: 'light', bg_type: 'gradient', bg_value: 'linear-gradient(135deg, #e0e7ff 0%, #ffffff 100%)', accent: '#4f46e5', shadow: 'soft', font: "'Inter', sans-serif" },
                vibrant: { theme: 'vibrant', bg_type: 'gradient', bg_value: 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)', accent: '#ffffff', shadow: 'hard', font: "'Montserrat', sans-serif" },
                minimal: { theme: 'light', bg_type: 'flat', bg_value: '#ffffff', accent: '#000000', shadow: 'none', font: "'Inter', sans-serif" },
                luxury: { theme: 'luxury', bg_type: 'flat', bg_value: '#000000', accent: '#d4af37', shadow: 'soft', font: "'Playfair Display', serif" }
            };

            var data = presets[p];
            if (data) {
                $form.find('[name="profile_theme"]').val(data.theme);
                $form.find('[name="bg_type"]').val(data.bg_type);
                $form.find('[name="bg_value"]').val(data.bg_value);
                $form.find('[name="theme_color"]').val(data.accent);
                $form.find('[name="container_shadow"]').val(data.shadow);
                $form.find('[name="font_family"]').val(data.font);

                // Trigger live updates
                updatePreview('profile_theme', data.theme);
                updatePreview('bg_value', data.bg_value);
                updatePreview('theme_color', data.accent);
                updatePreview('container_shadow', data.shadow);

                alert('Preset "' + p + '" applied! Click "Apply Styles" to save permanently.');
            }
        });

        $(document).on('click', '.toggle-advanced', function() {
            $('#edit-advanced-fields').slideToggle();
            var isVisible = $('#edit-advanced-fields').is(':visible');
            $(this).text(isVisible ? '🔼 Hide Advanced Options' : '⚙️ Advanced Options');
        });

        // 9. Block Picker
        $('.picker-item').on('click', function() {
            if ($(this).hasClass('pro-locked')) return;
            $('.picker-item').removeClass('active');
            $(this).addClass('active');
            var type = $(this).attr('data-type');
            $('#saas-block-type-hidden').val(type);

            updateGuidance(type, false);
        });

        $(document).on('click', '.check-integration', function() {
            var platform = $(this).data('platform');
            saasFetch('saas_check_integration', { platform: platform }, $(this)).done(function(msg) {
                alert(msg);
            });
        });

        $(document).on('click', '#saas-test-webhook-btn', function() {
            var url = $('[name="lead_webhook"]').val();
            if (!url) return alert('Please enter a webhook URL first.');
            saasFetch('saas_test_webhook', { webhook_url: url }, $(this)).done(function(msg) {
                alert(msg);
            });
        });

        // 10. Preview Controls
        $('#saas-preview-trigger').on('click', function() {
            $('#saas-preview-inline').slideDown(400, function() {
                $('html, body').animate({
                    scrollTop: $(this).offset().top - 120
                }, 500);
            });
        });

        $(document).on('click', '.close-inline', function() {
            var target = $(this).data('target');
            $('#' + target).slideUp();
        });

        // Media Library Integration
        $(document).on('click', '.select-media', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var target = $btn.data('target');
            var custom_uploader = wp.media({
                title: 'Select Image',
                button: { text: 'Use Image' },
                multiple: false
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                if (target === 'profile-image') {
                    $('#profile-image-id').val(attachment.id);
                    $('#profile-image-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
                    updatePreview('profile_image_update', attachment.url);
                } else if (target === 'cover-image') {
                    $('#cover-image-id').val(attachment.id);
                    $('#cover-image-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
                    updatePreview('cover_update', attachment.url);
                } else if (target === 'link-image') {
                    $('#edit-link-image-id').val(attachment.id);
                    $('#edit-link-image-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
                }
            }).open();
        });

        // 10.1 Marketing Material Copy
        $('.copy-html-btn').on('click', function() {
            var refLink = $('#saas-ref-link').val();
            var imgHtml = $(this).closest('div').find('img').prop('outerHTML');
            var fullHtml = '<a href="' + refLink + '">' + imgHtml + '</a>';

            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(fullHtml).select();
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

        // Bulk Lead Actions
        $('#leads-select-all').on('change', function() {
            $('.lead-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
        });

        $(document).on('change', '.lead-checkbox', function() {
            var anyChecked = $('.lead-checkbox:checked').length > 0;
            $('#saas-bulk-delete-leads').toggle(anyChecked);
        });

        $('#saas-bulk-delete-leads').on('click', function() {
            var ids = [];
            $('.lead-checkbox:checked').each(function() { ids.push($(this).val()); });
            if(!confirm('Delete ' + ids.length + ' leads forever?')) return;
            saasFetch('saas_bulk_delete_leads', { lead_ids: ids }, $(this)).done(function() {
                location.reload();
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
            if (currentStep === 1) {
                var niche = $('#wizard-niche').val();
                var suggestions = saas_dashboard_data.templates || {};

                if (suggestions[niche]) {
                    $('#wizard-headline').val(suggestions[niche].headline);
                    $('#wizard-bio').val(suggestions[niche].bio);
                }
            }
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
        $(document).on('click', '.apply-template-btn', function() {
            var t = $(this).data('template');
            if(!confirm('This will delete all current blocks and apply the ' + t + ' template. Continue?')) return;
            saasFetch('saas_apply_template', { template: t, profile_id: $('[name="profile_id"]').val() }, $(this)).done(function(msg) {
                alert(msg);
                location.reload();
            });
        });

        $('#wizard-finish').on('click', function() {
            var $btn = $(this);
            var profileId = $('[name="profile_id"]').val();
            var niche = $('#wizard-niche').val();
            var data = {
                profile_id: profileId,
                headline: $('#wizard-headline').val(),
                bio: $('#wizard-bio').val(),
                niche: niche,
                form_context: 'all'
            };

            saasFetch('saas_save_profile', data, $btn).done(function() {
                // After saving info, apply the template for the selected niche, but skip headline/bio overwrite
                saasFetch('saas_apply_template', { template: niche, profile_id: profileId, skip_meta: 1 }, $btn)
                    .done(function() { location.reload(); });
            });
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

        if ($('#saas-ab-chart').length && typeof Chart !== 'undefined' && typeof saas_ab_data !== 'undefined') {
            new Chart(document.getElementById('saas-ab-chart'), {
                type: 'bar',
                data: {
                    labels: ['Variant A', 'Variant B'],
                    datasets: [{
                        label: 'Total Clicks',
                        data: [saas_ab_data.a, saas_ab_data.b],
                        backgroundColor: ['#6366f1', '#10b981'],
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    });

})(jQuery);
