/**
 * SaaS Dashboard - Core Interactions (Tab Switching, Form Handling, AJAX)
 */

document.addEventListener('DOMContentLoaded', function() {

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

    // 3. Profile Form Handling
    const profileForm = document.getElementById('saas-profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
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
        });
    }

    // 4. Delete Link Handling (Event Delegation)
    document.addEventListener('click', function(e) {
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
