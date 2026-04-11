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

            $('#saas-edit-modal').show();
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
        $('#saas-profile-form, #saas-branding-form, #saas-automation-form, #saas-integrations-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            saasFetch('saas_save_profile', new FormData(this), $form.find('button'))
                .done(function(msg) {
                    alert(msg);
                    var frame = document.getElementById('saas-preview-frame');
                    if (frame) frame.contentWindow.location.reload();
                });
        });

        // 6. Copy Link
        $('#saas-copy-btn').on('click', function() {
            var $input = $('#saas-my-link');
            $input.select();
            document.execCommand('copy');
            var $btn = $(this);
            var oldText = $btn.text();
            $btn.text('Copied! ✅');
            setTimeout(function() { $btn.text(oldText); }, 2000);
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

        // 11. Lead Management
        $(document).on('click', '.view-lead', function() {
            var leadId = $(this).attr('data-id');
            saasFetch('saas_get_lead_details', { lead_id: leadId }, $(this))
                .done(function(html) {
                    $('#lead-details-content').html(html);
                    $('#saas-lead-modal').show();
                });
        });

        // 12. Sortable Initializer
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
    });

})(jQuery);
