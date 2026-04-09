/**
 * SaaS Dashboard - Responsive & Precision interaction
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Standard Fetch ---
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

    // --- Tab Engine ---
    const switchTab = (id) => {
        document.querySelectorAll('.saas-tabs button').forEach(t => t.classList.toggle('active', t.dataset.tab === id));
        document.querySelectorAll('.saas-tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${id}`));
        const url = new URL(window.location);
        if (url.searchParams.get('tab') !== id) {
            url.searchParams.set('tab', id);
            window.history.pushState({}, '', url);
        }
        // Auto-scroll on small screens
        if (window.innerWidth < 1100) window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.querySelectorAll('.saas-tabs button').forEach(t => t.onclick = () => switchTab(t.dataset.tab));
    const initTab = new URLSearchParams(window.location.search).get('tab');
    if (initTab) switchTab(initTab);

    // --- Profile Switcher ---
    const switcher = document.querySelector('.profile-title');
    const dropdown = document.querySelector('.profile-dropdown');
    if (switcher) {
        switcher.onclick = (e) => { e.stopPropagation(); dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block'; };
        window.onclick = () => { if(dropdown) dropdown.style.display = 'none'; };
    }

    // --- Block Engine ---

    // Add Link
    document.getElementById('saas-add-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        const txt = btn.innerText; btn.innerText = 'Adding...';
        saasFetch('saas_add_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => { alert(err.message); btn.innerText = txt; });
    });

    // Edit Modal Click
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-link')) {
            const li = e.target.closest('li');
            const d = li.dataset;

            document.getElementById('edit-link-id').value = d.id;
            document.getElementById('edit-link-title').value = li.querySelector('.link-title').innerText;
            document.getElementById('edit-link-url').value = li.querySelector('.link-url').innerText;
            document.getElementById('edit-link-extra').value = d.extra || '';
            document.getElementById('edit-link-style').value = d.style || 'regular';
            document.getElementById('edit-link-animation').value = d.animation || 'none';
            document.getElementById('edit-link-start').value = d.start || '';
            document.getElementById('edit-link-end').value = d.end || '';
            document.getElementById('edit-link-hour-from').value = d.hourFrom || '';
            document.getElementById('edit-link-hour-to').value = d.hourTo || '';
            document.getElementById('edit-link-pass').value = d.password || '';

            document.getElementById('saas-edit-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        if (e.target.classList.contains('delete-link')) {
            if (!confirm('Delete this block?')) return;
            saasFetch('saas_delete_link', { link_id: e.target.closest('li').dataset.id })
                .then(() => e.target.closest('li').remove());
        }
    });

    // Advanced Toggle
    document.querySelector('.toggle-advanced')?.addEventListener('click', function() {
        const fields = document.getElementById('edit-advanced-fields');
        const isHidden = fields.style.display === 'none';
        fields.style.display = isHidden ? 'block' : 'none';
        this.innerText = isHidden ? '🔼 Hide Advanced Options' : '⚙️ Advanced Options';
    });

    // Save Edit
    document.getElementById('saas-edit-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saasFetch('saas_save_link', new FormData(this)).then(() => location.reload());
    });

    // --- Config Forms ---
    ['saas-profile-form', 'saas-branding-form', 'saas-automation-form'].forEach(id => {
        document.getElementById(id)?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const txt = btn.innerText; btn.innerText = 'Saving...';
            saasFetch('saas_save_profile', new FormData(this))
                .then(m => { alert(m); btn.innerText = txt; document.getElementById('saas-preview-frame').contentWindow.location.reload(); })
                .catch(err => { alert(err.message); btn.innerText = txt; });
        });
    });

    // --- Visuals ---
    document.querySelectorAll('.picker-item').forEach(item => {
        item.onclick = () => {
            document.querySelectorAll('.picker-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            document.getElementById('saas-block-type-hidden').value = item.dataset.type;
        };
    });

    const closeModals = () => {
        document.querySelectorAll('.saas-modal').forEach(m => m.style.display = 'none');
        document.body.style.overflow = 'auto';
    };
    document.querySelectorAll('.close-modal').forEach(b => b.onclick = closeModals);
    window.onclick = (e) => { if (e.target.classList.contains('saas-modal')) closeModals(); };

    // AI Assist
    document.querySelectorAll('.ai-assist-btn').forEach(btn => {
        btn.onclick = () => {
            const target = btn.dataset.target;
            const input = document.querySelector(`[name="${target}"]`);
            const originalText = btn.innerText;
            btn.innerText = '🤖...';
            setTimeout(() => {
                input.value = (target === 'headline') ? "Helping Professionals Scale with Proven Systems 🚀" : "Elite strategist driving results through conversion-first design.";
                btn.innerText = originalText;
            }, 800);
        };
    });

    // Simulation
    document.getElementById('saas-simulate-pro')?.onclick = () => {
        saasFetch('saas_simulate_pro_upgrade').then(() => location.reload());
    };

    document.getElementById('saas-add-profile-trigger')?.onclick = () => {
        const t = prompt('Profile Title:');
        if (t) saasFetch('saas_create_profile', { profile_title: t }).then(d => window.location.href = `?profile_id=${d.id}`);
    };

    // Chart
    const chartCtx = document.getElementById('saas-analytics-chart');
    if (chartCtx && typeof Chart !== 'undefined') {
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{ label: 'Views', data: [150, 230, 180, 290, 420, 390, 510], borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.05)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } }
        });
    }

});
