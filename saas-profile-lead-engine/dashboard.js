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
                    data: [12, 19, 3, 5, 2, 3, 10], // Simulated daily data
                    borderColor: '#6c5ce7',
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // 0. Copy Link Handling
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

    // 0.1 Dynamic Block Extra Field Placeholder
    const blockTypeSelector = document.getElementById('saas-block-type');
    const extraField = document.querySelector('textarea[name="extra"]');
    if (blockTypeSelector && extraField) {
        blockTypeSelector.addEventListener('change', () => {
            const type = blockTypeSelector.value;
            const placeholders = {
                'testimonial': 'Enter Testimonial Quote...',
                'faq': 'Enter FAQ Answer...',
                'pricing': 'Enter Price (e.g. $19/mo)...',
                'image_gallery': 'Enter Image URLs (one per line)...',
                'social_icons': 'Enter platform:url (e.g. instagram:https://...) one per line',
                'countdown': 'Enter Expiry Date (YYYY-MM-DD HH:MM)',
                'button': 'Extra info (optional)...',
                'video': 'Extra info (optional)...',
                'calendar': 'Extra info (optional)...'
            };
            extraField.placeholder = placeholders[type] || 'Extra content...';
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
                    // Prepend to list or reload
                    location.reload();
                } else {
                    alert(data.data);
                }
            });
        });
    }

    // 3. Form Handling (Profile & Branding)
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
            // Refresh preview
            document.getElementById('saas-preview-frame').contentWindow.location.reload();
        });
    };

    const profileForm = document.getElementById('saas-profile-form');
    if (profileForm) profileForm.addEventListener('submit', genericFormHandler);

    const brandingForm = document.getElementById('saas-branding-form');
    if (brandingForm) brandingForm.addEventListener('submit', genericFormHandler);

    const automationForm = document.getElementById('saas-automation-form');
    if (automationForm) automationForm.addEventListener('submit', genericFormHandler);

    // 4. Edit & Delete Link Handling (Event Delegation)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('edit-link')) {
            const btn = e.target;
            const li = btn.closest('li');
            const linkId = li.dataset.id;
            const title = li.querySelector('strong').innerText;
            const url = li.querySelector('span:not(.handle)').innerText;

            // Fetch extra data for modal
            const startDate = li.dataset.start || '';
            const endDate = li.dataset.end || '';

            document.getElementById('edit-link-id').value = linkId;
            document.getElementById('edit-link-title').value = title;
            document.getElementById('edit-link-url').value = url;
            document.getElementById('edit-link-start').value = startDate;
            document.getElementById('edit-link-end').value = endDate;
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

    // Dark Mode Toggle Logic
    const initDarkMode = () => {
        const isDark = localStorage.getItem('saas-dark-mode') === 'true';
        if (isDark) document.body.classList.add('saas-admin-dark');

        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = isDark ? '☀️ Light' : '🌙 Dark';
        toggleBtn.className = 'saas-dark-toggle';
        document.querySelector('.saas-dashboard-header').appendChild(toggleBtn);

        toggleBtn.onclick = () => {
            const nowDark = document.body.classList.toggle('saas-admin-dark');
            localStorage.setItem('saas-dark-mode', nowDark);
            toggleBtn.innerHTML = nowDark ? '☀️ Light' : '🌙 Dark';
        };
    };
    initDarkMode();

    // Modal Close
    const modal = document.getElementById('saas-edit-modal');
    const leadModal = document.getElementById('saas-lead-modal');
    const closeBtns = document.querySelectorAll('.close-modal');

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            modal.style.display = 'none';
            leadModal.style.display = 'none';
        }
    });

    window.onclick = (e) => {
        if (e.target == modal) modal.style.display = 'none';
        if (e.target == leadModal) leadModal.style.display = 'none';
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

    // 5.5 Apply Template Handling
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

    // 6. Checkout Handling
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

    // 5.0 Lead Details Management
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('view-lead-btn')) {
            const leadId = e.target.dataset.id;
            const content = document.getElementById('lead-details-content');
            content.innerHTML = '<p>Loading lead details...</p>';
            leadModal.style.display = 'block';

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
                if (data.success) {
                    content.innerHTML = data.data;
                    // Bind Update Lead Form
                    const updateLeadForm = document.getElementById('saas-update-lead-form');
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
            });
        }
    });

    // 5. Drag-and-Drop Order (Sortable.js Integration)
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
            // Refresh preview to show new order
            document.getElementById('saas-preview-frame').contentWindow.location.reload();
        });
    }
});
