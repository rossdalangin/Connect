/**
 * SaaS Dashboard - Core Interactions (Tab Switching, Form Handling, AJAX)
 */

document.addEventListener('DOMContentLoaded', function() {

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

    // 4. Drag-and-Drop Order (Stub for Sortable.js or native)
    // In production, we'd use 'new Sortable(list, { onEnd: updateOrder })'
    function updateOrder(linkIds) {
        fetch(saas_dashboard_data.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'saas_update_link_order',
                security: saas_dashboard_data.nonce,
                link_ids: linkIds
            })
        });
    }
});
