/**
 * SaaS Dashboard - Robust, Responsive & Feature-Complete
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Helper: Standard Fetch Engine ---
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
                if (!res.success) throw new Error(res.data || 'Operation failed');
                return res.data;
            });
    };

    // --- 1. Navigation & Tabs ---
    const switchTab = (id) => {
        document.querySelectorAll('.saas-tabs button').forEach(t => t.classList.toggle('active', t.dataset.tab === id));
        document.querySelectorAll('.saas-tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${id}`));

        // Persist Tab in URL
        const url = new URL(window.location);
        if (url.searchParams.get('tab') !== id) {
            url.searchParams.set('tab', id);
            window.history.pushState({}, '', url);
        }

        // Scroll to top on mobile when switching tabs
        if (window.innerWidth < 1100) window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.querySelectorAll('.saas-tabs button').forEach(t => t.onclick = () => switchTab(t.dataset.tab));

    const initialTab = new URLSearchParams(window.location.search).get('tab');
    if (initialTab) switchTab(initialTab);

    // --- 2. Profile Switcher Dropdown ---
    const switcher = document.querySelector('.profile-title');
    const dropdown = document.querySelector('.profile-dropdown');
    if (switcher) {
        switcher.onclick = (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        };
        window.addEventListener('click', () => { if(dropdown) dropdown.style.display = 'none'; });
    }

    // --- 3. Block Management (Link Engine) ---

    // Add Block Form
    document.getElementById('saas-add-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        const originalText = btn.innerText;
        btn.innerText = 'Adding...';
        btn.disabled = true;

        saasFetch('saas_add_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => { alert(err.message); btn.innerText = originalText; btn.disabled = false; });
    });

    // Edit Modal Interaction
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-link')) {
            const li = e.target.closest('li');
            const d = li.dataset;

            // Map Data to Modal Fields
            document.getElementById('edit-link-id').value = d.id;
            document.getElementById('edit-link-title').value = li.querySelector('.link-title').innerText;
            document.getElementById('edit-link-url').value = li.querySelector('.link-url').innerText;
            document.getElementById('edit-link-extra').value = d.extra || '';
            document.getElementById('edit-link-style').value = d.style || 'regular';
            document.getElementById('edit-link-animation').value = d.animation || 'none';
            document.getElementById('edit-link-bg').value = d.customBg || '#ffffff';
            document.getElementById('edit-link-text').value = d.customText || '#000000';
            document.getElementById('edit-link-ab-title').value = d.abTitle || '';
            document.getElementById('edit-link-start').value = d.start || '';
            document.getElementById('edit-link-end').value = d.end || '';
            document.getElementById('edit-link-hour-from').value = d.hourFrom || '';
            document.getElementById('edit-link-hour-to').value = d.hourTo || '';
            document.getElementById('edit-link-pass').value = d.password || '';

            // Open Modal
            document.getElementById('saas-edit-modal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }

        if (e.target.classList.contains('delete-link')) {
            if (!confirm('Permanently delete this block?')) return;
            const li = e.target.closest('li');
            saasFetch('saas_delete_link', { link_id: li.dataset.id })
                .then(() => {
                    li.style.opacity = '0';
                    li.style.transform = 'translateX(20px)';
                    setTimeout(() => li.remove(), 300);
                })
                .catch(err => alert(err.message));
        }
    });

    // Save Edited Block
    document.getElementById('saas-edit-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.innerText = 'Saving Changes...';
        saasFetch('saas_save_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => { alert(err.message); btn.innerText = 'Save Changes'; });
    });

    // Toggle Advanced Settings in Modal
    document.querySelector('.toggle-advanced')?.addEventListener('click', function() {
        const fields = document.getElementById('edit-advanced-fields');
        const isHidden = fields.style.display === 'none';
        fields.style.display = isHidden ? 'block' : 'none';
        this.innerText = isHidden ? '🔼 Hide Advanced Settings' : '⚙️ Show Advanced Settings';
    });

    // --- 4. Forms & Auto-Save Simulation ---
    const configForms = ['saas-profile-form', 'saas-branding-form', 'saas-automation-form'];
    configForms.forEach(id => {
        document.getElementById(id)?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = 'Saving...';
            btn.disabled = true;

            saasFetch('saas_save_profile', new FormData(this))
                .then(msg => {
                    alert('🚀 ' + msg);
                    btn.innerText = originalText;
                    btn.disabled = false;
                    document.getElementById('saas-preview-frame').contentWindow.location.reload();
                })
                .catch(err => {
                    alert('❌ ' + err.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        });
    });

    // --- 5. UI Components & Polish ---

    // Block Picker Visuals
    document.querySelectorAll('.picker-item').forEach(item => {
        item.onclick = () => {
            if (item.classList.contains('pro-locked')) {
                switchTab('billing');
                return;
            }
            document.querySelectorAll('.picker-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            document.getElementById('saas-block-type-hidden').value = item.dataset.type;
        };
    });

    // Modal Close
    const closeModals = () => {
        document.querySelectorAll('.saas-modal').forEach(m => m.style.display = 'none');
        document.body.style.overflow = 'auto';
    };
    document.querySelectorAll('.close-modal').forEach(b => b.onclick = closeModals);
    window.addEventListener('click', (e) => { if (e.target.classList.contains('saas-modal')) closeModals(); });

    // AI Assist Stubs
    document.querySelectorAll('.ai-assist-btn').forEach(btn => {
        btn.onclick = () => {
            const target = btn.dataset.target;
            const input = document.querySelector(`[name="${target}"]`);
            const originalText = btn.innerText;
            btn.innerText = '🤖...';

            setTimeout(() => {
                if (target === 'headline') input.value = "Helping High-Performers Scale with Proven Systems 🚀";
                else if (target === 'bio') input.value = "Elite strategist focused on building high-converting digital identities for modern professionals.";
                input.dispatchEvent(new Event('input'));
                btn.innerText = originalText;
            }, 800);
        };
    });

    // Pro Simulation
    document.getElementById('saas-simulate-pro')?.onclick = () => {
        if (!confirm('Activate Elite Pro Features for this demo?')) return;
        saasFetch('saas_simulate_pro_upgrade').then(() => location.reload());
    };

    // New Profile
    document.getElementById('saas-add-profile-trigger')?.onclick = () => {
        const title = prompt('Enter Profile Title:');
        if (title) {
            saasFetch('saas_create_profile', { profile_title: title })
                .then(data => window.location.href = `?profile_id=${data.id}`);
        }
    };

    // Analytics Chart (Simple Visual)
    const ctx = document.getElementById('saas-analytics-chart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Page Views',
                    data: [120, 190, 300, 250, 400, 380, 500],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
            }
        });
    }

});
