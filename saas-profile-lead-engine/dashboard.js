/**
 * SaaS Dashboard - Standard & Vibrant Refactor
 */

document.addEventListener('DOMContentLoaded', function() {

    // Helper: Standard Fetch
    const saasFetch = (action, data = {}) => {
        const fd = (data instanceof FormData) ? data : new URLSearchParams(data);
        if (!(data instanceof FormData)) {
            fd.append('action', action);
            fd.append('security', saas_dashboard_data.nonce);
        } else {
            fd.append('action', action);
            fd.append('security', saas_dashboard_data.nonce);
        }

        return fetch(saas_dashboard_data.ajax_url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Error');
                return res.data;
            });
    };

    // Tab Switching
    const tabs = document.querySelectorAll('.saas-tabs button');
    const contents = document.querySelectorAll('.saas-tab-content');

    const switchTab = (id) => {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === id));
        contents.forEach(c => c.classList.toggle('active', c.id === `tab-${id}`));
        const url = new URL(window.location);
        if (url.searchParams.get('tab') !== id) {
            url.searchParams.set('tab', id);
            window.history.pushState({}, '', url);
        }
    };

    tabs.forEach(t => t.onclick = () => switchTab(t.dataset.tab));
    const initTab = new URLSearchParams(window.location.search).get('tab');
    if (initTab) switchTab(initTab);

    // Profile Switcher
    const switcher = document.querySelector('.profile-title');
    const dropdown = document.querySelector('.profile-dropdown');
    if (switcher) {
        switcher.onclick = (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        };
        window.onclick = () => dropdown.style.display = 'none';
    }

    // Block Picker logic
    document.querySelectorAll('.picker-item').forEach(item => {
        item.onclick = () => {
            if (item.classList.contains('pro-locked')) return switchTab('billing');
            document.querySelectorAll('.picker-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            document.getElementById('saas-block-type-hidden').value = item.dataset.type;
        };
    });

    // Add Block
    document.getElementById('saas-add-link-form')?.onsubmit = function(e) {
        e.preventDefault();
        saasFetch('saas_add_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => alert(err.message));
    };

    // Edit Block
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-link')) {
            const li = e.target.closest('li');
            document.getElementById('edit-link-id').value = li.dataset.id;
            document.getElementById('edit-link-title').value = li.querySelector('.link-title').innerText;
            document.getElementById('edit-link-url').value = li.querySelector('.link-url').innerText;
            document.getElementById('edit-link-extra').value = li.dataset.extra || '';
            document.getElementById('saas-edit-modal').style.display = 'block';
        }
        if (e.target.classList.contains('delete-link')) {
            if (!confirm('Delete this block?')) return;
            saasFetch('saas_delete_link', { link_id: e.target.closest('li').dataset.id })
                .then(() => e.target.closest('li').remove());
        }
    });

    document.getElementById('saas-edit-link-form')?.onsubmit = function(e) {
        e.preventDefault();
        saasFetch('saas_save_link', new FormData(this)).then(() => location.reload());
    };

    // Forms Save
    ['saas-profile-form', 'saas-branding-form', 'saas-automation-form'].forEach(id => {
        document.getElementById(id)?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const txt = btn.innerText;
            btn.innerText = 'Saving...';
            saasFetch('saas_save_profile', new FormData(this))
                .then(m => { alert(m); btn.innerText = txt; document.getElementById('saas-preview-frame').contentWindow.location.reload(); })
                .catch(err => { alert(err.message); btn.innerText = txt; });
        });
    });

    // Modals
    document.querySelectorAll('.close-modal').forEach(b => b.onclick = () => {
        document.querySelectorAll('.saas-modal').forEach(m => m.style.display = 'none');
    });

    // Analytics Chart
    const ctx = document.getElementById('saas-analytics-chart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{ label: 'Views', data: [65, 59, 80, 81, 56, 55, 40], borderColor: '#6366f1', tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Simulation
    document.getElementById('saas-simulate-pro')?.onclick = () => {
        saasFetch('saas_simulate_pro_upgrade').then(() => location.reload());
    };

    document.getElementById('saas-add-profile-trigger')?.onclick = () => {
        const t = prompt('Profile Title:');
        if (t) saasFetch('saas_create_profile', { profile_title: t }).then(d => window.location.href = `?profile_id=${d.id}`);
    };
});
