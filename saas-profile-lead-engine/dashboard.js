/**
 * SaaS Dashboard - Core Interactions (Tab Switching, Form Handling, AJAX)
 */

document.addEventListener('DOMContentLoaded', function() {

    // 0.0 Analytics Chart Integration
    const chartCtx = document.getElementById('saas-analytics-chart');
    if (chartCtx && typeof Chart !== 'undefined') {
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Page Views',
                    data: [120, 190, 30, 50, 20, 30, 100], // Last 7 days
                    borderColor: '#6c5ce7',
                    backgroundColor: 'rgba(108, 92, 231, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Link Clicks',
                    data: [45, 70, 12, 20, 5, 10, 35],
                    borderColor: '#39e09b',
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // 0. Media Uploader Integration
    const profileUploadBtn = document.getElementById('profile-image-upload');
    const profileImageId = document.getElementById('profile-image-id');
    const profilePreview = document.getElementById('profile-image-preview');

    if (profileUploadBtn) {
        profileUploadBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({
                title: 'Select Profile Image',
                multiple: false,
                button: { text: 'Select Image' }
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                profileImageId.value = attachment.id;
                profilePreview.innerHTML = `<img src="${attachment.sizes.thumbnail.url}">`;
                updatePreview({ type: 'live_update', key: 'cover_update', value: attachment.id });
            });
            frame.open();
        };
    }

    const lmUploadBtn = document.getElementById('saas-lead-magnet-upload');
    if (lmUploadBtn) {
        lmUploadBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({ title: 'Select Lead Magnet File', multiple: false });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('saas-lead-magnet-url').value = attachment.url;
                document.getElementById('lead-magnet-preview').innerHTML = `<span>📄 ${attachment.filename}</span>`;
            });
            frame.open();
        };
    }

    const faviconUploadBtn = document.getElementById('saas-favicon-upload');
    if (faviconUploadBtn) {
        faviconUploadBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({ title: 'Select Favicon', multiple: false });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('saas-favicon-url').value = attachment.url;
                document.getElementById('favicon-preview').innerHTML = `<img src="${attachment.url}" style="width:32px; height:32px;">`;
            });
            frame.open();
        };
    }

    const linkThumbBtn = document.getElementById('edit-link-image-btn');
    if (linkThumbBtn) {
        linkThumbBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({ title: 'Select Link Thumbnail', multiple: false });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('edit-link-image-id').value = attachment.id;
                document.getElementById('edit-link-thumb-preview').innerHTML = `<img src="${attachment.sizes.thumbnail.url}" style="width:100%; height:100%; object-fit:cover;">`;
            });
            frame.open();
        };
    }

    const coverUploadBtn = document.getElementById('cover-image-upload');
    const coverImageId = document.getElementById('cover-image-id');
    const coverPreview = document.getElementById('cover-image-preview');

    if (coverUploadBtn) {
        coverUploadBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({
                title: 'Select Cover Banner',
                multiple: false,
                button: { text: 'Select Banner' }
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                coverImageId.value = attachment.id;
                coverPreview.innerHTML = `<img src="${attachment.sizes.medium.url}" style="max-height:100px;">`;
                updatePreview({ type: 'live_update', key: 'cover_update', value: attachment.id });
            });
            frame.open();
        };
    }

    // 0. Copy Link Handling
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('saas-copy-btn')) {
            const btn = e.target;
            const linkInput = btn.previousElementSibling;
            if (linkInput) {
                linkInput.select();
                document.execCommand('copy');
                const originalText = btn.innerText;
                btn.innerText = 'Copied!';
                setTimeout(() => btn.innerText = originalText, 2000);
            }
        }
    });

    const copyBtn = document.getElementById('saas-copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const linkInput = document.getElementById('saas-my-link');
            linkInput.select();
            document.execCommand('copy');
            copyBtn.innerText = 'Copied!';
            setTimeout(() => copyBtn.innerText = 'Copy My Link', 2000);
        });
    }

    // 0.1 Visual Block Picker & Dynamic Placeholder
    const pickerItems = document.querySelectorAll('.picker-item');
    const blockTypeHidden = document.getElementById('saas-block-type-hidden');
    const extraField = document.querySelector('textarea[name="extra"]');

    if (pickerItems && blockTypeHidden) {
        pickerItems.forEach(item => {
            item.addEventListener('click', () => {
                if (item.classList.contains('pro-locked')) {
                    document.querySelector('[data-tab=billing]').click();
                    return;
                }

                pickerItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');

                const type = item.dataset.type;
                blockTypeHidden.value = type;

                const placeholders = {
                    'testimonial': 'Enter Testimonial Quote...',
                    'faq': 'Enter FAQ Answer...',
                    'pricing': 'Enter Price (e.g. $19/mo)...',
                    'image_gallery': 'Enter Image URLs (one per line)...',
                    'social_icons': 'Enter platform:url (e.g. instagram:https://...) one per line',
                    'countdown': 'Enter Expiry Date (YYYY-MM-DD HH:MM)',
                    'button': 'Extra info (optional)...',
                    'video': 'Extra info (optional)...',
                    'calendar': 'Extra info (optional)...',
                    'newsletter': 'Extra info (optional)...',
                    'milestone': 'Enter label:percent (e.g. Sales:85)',
                    'product': 'Enter Price (e.g. $49)'
                };
                if (extraField) extraField.placeholder = placeholders[type] || 'Extra content...';
            });
        });
    }

    // 1. Tab Switching
    const tabButtons = document.querySelectorAll('.saas-tabs button');
    const tabContents = document.querySelectorAll('.saas-tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            // Toggle buttons
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Toggle content
            tabContents.forEach(content => {
                if (content.id === `tab-${target}`) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });

    // 2. Add New Link Handling
    const addLinkForm = document.getElementById('saas-add-link-form');
    if (addLinkForm) {
        addLinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'saas_add_link');
            formData.append('security', saas_dashboard_data.nonce);

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data);
                }
            });
        });
    }

    // 3. Real-Time Preview PostMessage
    const previewFrame = document.getElementById('saas-preview-frame');
    const updatePreview = (msg) => {
        if (previewFrame && previewFrame.contentWindow) {
            previewFrame.contentWindow.postMessage(msg, '*');
        }
    };

    const liveFields = [
        { selector: 'input[name="headline"]', key: 'headline' },
        { selector: 'textarea[name="bio"]', key: 'bio' },
        { selector: 'input[name="theme_color"]', key: 'theme_color' },
        { selector: 'input[name="bg_value"]', key: 'bg_value' }
    ];

    liveFields.forEach(field => {
        const el = document.querySelector(field.selector);
        if (el) {
            el.addEventListener('input', (e) => {
                updatePreview({ type: 'live_update', key: field.key, value: e.target.value });
            });
        }
    });

    // 3. Form Handling (Profile, Branding, Automation)
    const genericFormHandler = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'saas_save_profile');
        formData.append('security', saas_dashboard_data.nonce);

        fetch(saas_dashboard_data.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            alert(data.data);
            if (document.getElementById('saas-preview-frame')) {
                document.getElementById('saas-preview-frame').contentWindow.location.reload();
            }
        });
    };

    const profileForm = document.getElementById('saas-profile-form');
    if (profileForm) profileForm.addEventListener('submit', genericFormHandler);

    const brandingForm = document.getElementById('saas-branding-form');
    if (brandingForm) brandingForm.addEventListener('submit', genericFormHandler);

    const seoForm = document.getElementById('saas-seo-form');
    if (seoForm) seoForm.addEventListener('submit', genericFormHandler);

    const trackingForm = document.getElementById('saas-tracking-form');
    if (trackingForm) trackingForm.addEventListener('submit', genericFormHandler);

    const automationForm = document.getElementById('saas-automation-form');
    if (automationForm) automationForm.addEventListener('submit', genericFormHandler);

    // 4. Edit & Delete Link Handling
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('edit-link')) {
            const btn = e.target;
            const li = btn.closest('li');
            const linkId = li.dataset.id;
            const title = li.querySelector('strong').innerText;
            const url = li.querySelector('span:not(.handle)').innerText;

            const startDate = li.dataset.start || '';
            const endDate = li.dataset.end || '';
            const customBg = li.dataset.customBg || '';
            const customText = li.dataset.customText || '';
            const urlMobile = li.dataset.urlMobile || '';
            const urlGeo = li.dataset.urlGeo || '';
            const blockStyle = li.dataset.style || 'regular';
            const blockAnimation = li.dataset.animation || 'fadeinup';
            const linkPass = li.dataset.password || '';
            const imageId = li.dataset.imageId || '';
            const imageUrl = li.dataset.imageUrl || '';

            document.getElementById('edit-link-id').value = linkId;
            document.getElementById('edit-link-title').value = title;
            document.getElementById('edit-link-url').value = url;
            document.getElementById('edit-link-start').value = startDate;
            document.getElementById('edit-link-end').value = endDate;
            document.getElementById('edit-link-bg').value = customBg;
            document.getElementById('edit-link-text').value = customText;
            if (document.getElementById('edit-link-mobile')) document.getElementById('edit-link-mobile').value = urlMobile;
            if (document.getElementById('edit-link-geo')) document.getElementById('edit-link-geo').value = urlGeo;
            if (document.getElementById('edit-link-style')) document.getElementById('edit-link-style').value = blockStyle;
            if (document.getElementById('edit-link-animation')) document.getElementById('edit-link-animation').value = blockAnimation;
            if (document.getElementById('edit-link-pass')) document.getElementById('edit-link-pass').value = linkPass;
            if (document.getElementById('edit-link-image-id')) document.getElementById('edit-link-image-id').value = imageId;
            if (document.getElementById('edit-link-thumb-preview')) {
                document.getElementById('edit-link-thumb-preview').innerHTML = imageUrl ? `<img src="${imageUrl}" style="width:100%; height:100%; object-fit:cover;">` : '';
            }
            document.getElementById('saas-edit-modal').style.display = 'block';
        }

        if (e.target && e.target.classList.contains('delete-link')) {
            const btn = e.target;
            const linkId = btn.closest('li').dataset.id;
            if (!confirm('Are you sure?')) return;

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'saas_delete_link',
                    security: saas_dashboard_data.nonce,
                    link_id: linkId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.closest('li').remove();
                    document.getElementById('saas-preview-frame').contentWindow.location.reload();
                }
            });
        }
    });

    // Dark Mode Toggle
    const initDarkMode = () => {
        const isDark = localStorage.getItem('saas-dark-mode') === 'true';
        if (isDark) document.body.classList.add('saas-admin-dark');

        const header = document.querySelector('.saas-dashboard-header');
        if (header) {
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = isDark ? '☀️ Light' : '🌙 Dark';
            toggleBtn.className = 'saas-dark-toggle';
            header.appendChild(toggleBtn);

            toggleBtn.onclick = () => {
                const nowDark = document.body.classList.toggle('saas-admin-dark');
                localStorage.setItem('saas-dark-mode', nowDark);
                toggleBtn.innerHTML = nowDark ? '☀️ Light' : '🌙 Dark';
            };
        }
    };
    initDarkMode();

    // Wizard Logic
    const wizardModal = document.getElementById('saas-wizard-modal');
    const wizardSteps = document.querySelectorAll('.wizard-step');
    const progressBar = document.querySelector('.progress-bar-fill');
    let currentStep = 1;

    const showStep = (step) => {
        wizardSteps.forEach(s => s.classList.remove('active'));
        document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');
        progressBar.style.width = `${(step / wizardSteps.length) * 100}%`;
    };

    document.getElementById('saas-start-wizard')?.addEventListener('click', () => {
        wizardModal.style.display = 'block';
    });

    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => {
            currentStep++;
            showStep(currentStep);
        });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
            currentStep--;
            showStep(currentStep);
        });
    });

    // Wizard Photo Sync
    const wizardPhotoBtn = document.getElementById('wizard-photo-btn');
    if (wizardPhotoBtn) {
        wizardPhotoBtn.onclick = (e) => {
            e.preventDefault();
            const frame = wp.media({ title: 'Profile Photo', multiple: false });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('profile-image-id').value = attachment.id;
                document.getElementById('wizard-photo-preview').innerHTML = `<img src="${attachment.sizes.thumbnail.url}">`;
                updatePreview({ type: 'live_update', key: 'cover_update', value: attachment.id });
            });
            frame.open();
        };
    }

    document.getElementById('wizard-finish-btn')?.addEventListener('click', () => {
        // Collect data and save
        const formData = new FormData();
        formData.append('action', 'saas_save_profile');
        formData.append('security', saas_dashboard_data.nonce);
        formData.append('profile_id', document.querySelector('input[name="profile_id"]').value);
        formData.append('headline', document.getElementById('wizard-headline').value);
        formData.append('bio', document.getElementById('wizard-bio').value);
        formData.append('theme_color', document.getElementById('wizard-color').value);
        formData.append('profile_image_id', document.getElementById('profile-image-id').value);

        fetch(saas_dashboard_data.ajax_url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert('Profile saved! 🚀');
            location.reload();
        });
    });

    // Notifications Logic
    const notifModal = document.getElementById('saas-notif-modal');
    document.getElementById('saas-notif-trigger')?.addEventListener('click', () => {
        notifModal.style.display = 'block';
    });

    // Modal Close
    const modal = document.getElementById('saas-edit-modal');
    const leadModal = document.getElementById('saas-lead-modal');
    const closeBtns = document.querySelectorAll('.close-modal');

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            if (modal) modal.style.display = 'none';
            if (leadModal) leadModal.style.display = 'none';
        }
    });

    window.onclick = (e) => {
        if (modal && e.target == modal) modal.style.display = 'none';
        if (leadModal && e.target == leadModal) leadModal.style.display = 'none';
        if (wizardModal && e.target == wizardModal) wizardModal.style.display = 'none';
        if (notifModal && e.target == notifModal) notifModal.style.display = 'none';
    };

    // Edit Form Submission
    const editForm = document.getElementById('saas-edit-link-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'saas_save_link');
            formData.append('security', saas_dashboard_data.nonce);

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                alert(data.data);
                location.reload();
            });
        });
    }

    // Style Presets Logic
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const preset = btn.dataset.preset;
            const config = {
                midnight: { color: '#ffffff', bg: '#1a1a1a', theme: 'dark', shape: 'rounded' },
                glass: { color: '#6c5ce7', bg: 'rgba(255,255,255,0.7)', theme: 'light', shape: 'pill' },
                vibrant: { color: '#ffffff', bg: 'linear-gradient(45deg, #f093fb, #f5576c)', theme: 'vibrant', shape: 'pill' },
                minimal: { color: '#333333', bg: '#ffffff', theme: 'light', shape: 'square' }
            };
            const c = config[preset];
            if (c) {
                document.querySelector('input[name="theme_color"]').value = c.color;
                document.querySelector('input[name="bg_value"]').value = c.bg;
                document.querySelector('select[name="bg_type"]').value = preset === 'vibrant' ? 'gradient' : 'flat';
                document.querySelector('select[name="btn_shape"]').value = c.shape;

                // Trigger real-time update
                updatePreview({ type: 'live_update', key: 'theme_color', value: c.color });
                updatePreview({ type: 'live_update', key: 'bg_value', value: c.bg });
            }
        });
    });

    // Apply Template
    // Lead Filtering logic
    const statusFilter = document.getElementById('crm-filter-status');
    const leadSearch = document.getElementById('crm-search-leads');
    if (statusFilter && leadSearch) {
        const filterLeads = () => {
            const status = statusFilter.value.toLowerCase();
            const search = leadSearch.value.toLowerCase();
            document.querySelectorAll('.saas-table tbody tr').forEach(row => {
                const rowStatus = row.className.replace('lead-row-', '').toLowerCase();
                const rowText = row.innerText.toLowerCase();
                const statusMatch = status === 'all' || rowStatus === status;
                const searchMatch = rowText.includes(search);
                row.style.display = (statusMatch && searchMatch) ? '' : 'none';
            });
        };
        statusFilter.addEventListener('change', filterLeads);
        leadSearch.addEventListener('input', filterLeads);
    }

    const applyTemplateBtn = document.getElementById('saas-btn-apply-template');
    if (applyTemplateBtn) {
        applyTemplateBtn.addEventListener('click', () => {
            const template = document.getElementById('saas-apply-template').value;
            if (!template || !confirm('This will delete all current blocks and reset to template. Continue?')) return;

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'saas_apply_template',
                    security: saas_dashboard_data.nonce,
                    template: template
                })
            })
            .then(r => r.json())
            .then(data => {
                alert(data.data);
                location.reload();
            });
        });
    }

    // Checkout
    document.querySelectorAll('.checkout-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'saas_checkout');

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    alert(data.data || 'Checkout failed');
                }
            });
        });
    });

    // Lead Management
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-lead-btn')) {
            const leadId = e.target.dataset.id;
            if (!confirm('Are you sure you want to delete this lead?')) return;

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'saas_delete_lead',
                    security: saas_dashboard_data.nonce,
                    lead_id: leadId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    e.target.closest('tr').remove();
                }
            });
        }

        if (e.target && e.target.classList.contains('view-lead-btn')) {
            const leadId = e.target.dataset.id;
            const content = document.getElementById('lead-details-content');
            if (content) content.innerHTML = '<p>Loading lead details...</p>';
            if (leadModal) leadModal.style.display = 'block';

            fetch(saas_dashboard_data.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'saas_get_lead_details',
                    security: saas_dashboard_data.nonce,
                    lead_id: leadId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && content) {
                    content.innerHTML = data.data;
                    const updateLeadForm = document.getElementById('saas-update-lead-form');
                    if (updateLeadForm) {
                        updateLeadForm.addEventListener('submit', function(ev) {
                            ev.preventDefault();
                            const updateData = new FormData(this);
                            updateData.append('action', 'saas_update_lead');
                            updateData.append('security', saas_dashboard_data.nonce);

                            fetch(saas_dashboard_data.ajax_url, {
                                method: 'POST',
                                body: updateData
                            })
                            .then(r => r.json())
                            .then(d => {
                                alert(d.data);
                                location.reload();
                            });
                        });
                    }
                }
            });
        }
    });

    // Drag-and-Drop
    const sortableList = document.getElementById('saas-links-list');
    if (sortableList && typeof Sortable !== 'undefined') {
        new Sortable(sortableList, {
            handle: '.handle',
            animation: 150,
            onEnd: function() {
                const linkIds = Array.from(sortableList.querySelectorAll('li')).map(li => li.dataset.id);
                updateOrder(linkIds);
            }
        });
    }

    function updateOrder(linkIds) {
        fetch(saas_dashboard_data.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'saas_update_link_order',
                security: saas_dashboard_data.nonce,
                'link_ids[]': linkIds
            })
        })
        .then(r => r.json())
        .then(data => {
            if (document.getElementById('saas-preview-frame')) {
                document.getElementById('saas-preview-frame').contentWindow.location.reload();
            }
        });
    }
});
