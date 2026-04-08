/**
 * SaaS Dashboard - Core Interactions (Tab Switching, Form Handling, AJAX)
 * Robust Refactor with Error Handling and Consistent Feedback
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Helper: Standard AJAX Wrapper ---
    const saasFetch = (action, data = {}, method = 'POST') => {
        const formData = (data instanceof FormData) ? data : new URLSearchParams(data);
        if (!(data instanceof FormData)) {
            formData.append('action', action);
            formData.append('security', saas_dashboard_data.nonce);
        } else {
            data.append('action', action);
            data.append('security', saas_dashboard_data.nonce);
        }

        return fetch(saas_dashboard_data.ajax_url, {
            method: method,
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.data || 'Unknown error');
            return res.data;
        });
    };

    // --- 0. Analytics Chart ---
    const chartCtx = document.getElementById('saas-analytics-chart');
    if (chartCtx && typeof Chart !== 'undefined') {
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Page Views',
                    data: [120, 190, 30, 50, 20, 30, 100],
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

    // --- 1. Tab Switching Engine ---
    const tabButtons = document.querySelectorAll('.saas-tabs button');
    const tabContents = document.querySelectorAll('.saas-tab-content');

    const switchTab = (target) => {
        if (!target) return;
        const targetContent = document.getElementById(`tab-${target}`);
        if (!targetContent) return;

        tabButtons.forEach(b => b.classList.toggle('active', b.dataset.tab === target));
        tabContents.forEach(c => c.classList.toggle('active', c.id === `tab-${target}`));

        const url = new URL(window.location);
        if (url.searchParams.get('tab') !== target) {
            url.searchParams.set('tab', target);
            window.history.pushState({}, '', url);
        }
    };

    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(btn.dataset.tab);
        });
    });

    const initialTab = new URLSearchParams(window.location.search).get('tab');
    if (initialTab) switchTab(initialTab);

    // --- 2. Profile Switcher & Global Actions ---
    const switcher = document.querySelector('.profile-switcher-wrapper h2');
    const dropdown = document.querySelector('.profile-dropdown');
    if (switcher && dropdown) {
        switcher.onclick = (e) => {
            e.stopPropagation();
            dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
        };
        window.addEventListener('click', () => dropdown.style.display = 'none');
    }

    document.getElementById('saas-add-profile-trigger')?.addEventListener('click', () => {
        const title = prompt('Enter a title for your new profile:');
        if (!title) return;
        saasFetch('saas_create_profile', { profile_title: title })
            .then(data => window.location.href = `?profile_id=${data.id}`)
            .catch(err => alert(err.message));
    });

    // --- 3. Block Management (Add/Edit/Sort) ---
    const pickerItems = document.querySelectorAll('.picker-item');
    const blockTypeHidden = document.getElementById('saas-block-type-hidden');
    const extraField = document.querySelector('textarea[name="extra"]');

    pickerItems.forEach(item => {
        item.addEventListener('click', () => {
            if (item.classList.contains('pro-locked')) {
                switchTab('billing');
                return;
            }
            pickerItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            if (blockTypeHidden) blockTypeHidden.value = item.dataset.type;
        });
    });

    document.getElementById('saas-add-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        saasFetch('saas_add_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => { alert(err.message); btn.disabled = false; });
    });

    const editModal = document.getElementById('saas-edit-modal');
    const editForm = document.getElementById('saas-edit-link-form');

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-link')) {
            const li = e.target.closest('li');
            const d = li.dataset;

            // Populate Modal
            document.getElementById('edit-link-id').value = d.id;
            document.getElementById('edit-link-title').value = li.querySelector('.link-title').innerText;
            document.getElementById('edit-link-url').value = li.querySelector('.link-url').innerText;
            document.getElementById('edit-link-extra').value = d.extra || '';
            document.getElementById('edit-link-style').value = d.style || 'regular';
            document.getElementById('edit-link-animation').value = d.animation || 'none';
            document.getElementById('edit-link-start').value = d.start || '';
            document.getElementById('edit-link-end').value = d.end || '';

            // Pro fields
            if (document.getElementById('edit-link-mobile')) document.getElementById('edit-link-mobile').value = d.urlMobile || '';
            if (document.getElementById('edit-link-geo')) document.getElementById('edit-link-geo').value = d.urlGeo || '';
            if (document.getElementById('edit-link-geo-country')) document.getElementById('edit-link-geo-country').value = d.geoCountry || '';
            if (document.getElementById('edit-link-bg')) document.getElementById('edit-link-bg').value = d.customBg || '#ffffff';
            if (document.getElementById('edit-link-text')) document.getElementById('edit-link-text').value = d.customText || '#000000';
            if (document.getElementById('edit-link-pass')) document.getElementById('edit-link-pass').value = d.password || '';
            if (document.getElementById('edit-link-ab-title')) document.getElementById('edit-link-ab-title').value = d.abTitle || '';
            if (document.getElementById('edit-link-ab-url')) document.getElementById('edit-link-ab-url').value = d.abUrl || '';
            if (document.getElementById('edit-link-hour-from')) document.getElementById('edit-link-hour-from').value = d.hourFrom || '';
            if (document.getElementById('edit-link-hour-to')) document.getElementById('edit-link-hour-to').value = d.hourTo || '';
            if (document.getElementById('edit-link-image-id')) document.getElementById('edit-link-image-id').value = d.imageId || '';

            const preview = document.getElementById('edit-link-thumb-preview');
            if (preview) {
                preview.innerHTML = d.imageUrl ? `<img src="${d.imageUrl}" style="width:100%; height:100%; object-fit:cover;">` : '';
            }

            if (editModal) editModal.style.display = 'block';
        }

        if (e.target.classList.contains('delete-link')) {
            if (!confirm('Are you sure?')) return;
            saasFetch('saas_delete_link', { link_id: e.target.closest('li').dataset.id })
                .then(() => e.target.closest('li').remove())
                .catch(err => alert(err.message));
        }
    });

    editForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        saasFetch('saas_save_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => alert(err.message));
    });

    // --- 4. Real-Time Preview Engine ---
    const previewFrame = document.getElementById('saas-preview-frame');
    const updatePreview = (msg) => {
        if (previewFrame?.contentWindow) previewFrame.contentWindow.postMessage(msg, '*');
    };

    document.querySelectorAll('#saas-profile-form input, #saas-profile-form textarea, #saas-branding-form input, #saas-branding-form select').forEach(el => {
        const eventType = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(eventType, (e) => {
            updatePreview({ type: 'live_update', key: el.name, value: e.target.value });
        });
    });

    // --- 5. Form Auto-Save / Generic Handler ---
    const forms = ['saas-profile-form', 'saas-branding-form', 'saas-seo-form', 'saas-tracking-form', 'saas-automation-form', 'saas-integrations-form'];
    forms.forEach(id => {
        document.getElementById(id)?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = '⏳ Saving...';
            btn.disabled = true;

            saasFetch('saas_save_profile', new FormData(this))
                .then(msg => {
                    alert(msg);
                    btn.innerText = originalText;
                    btn.disabled = false;
                    updatePreview({ type: 'refresh' });
                })
                .catch(err => {
                    alert(err.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        });
    });

    // --- 6. Sorting (Drag & Drop) ---
    const sortableList = document.getElementById('saas-links-list');
    if (sortableList && typeof Sortable !== 'undefined') {
        new Sortable(sortableList, {
            handle: '.handle',
            animation: 150,
            onEnd: () => {
                const ids = Array.from(sortableList.querySelectorAll('li')).map(li => li.dataset.id);
                saasFetch('saas_update_link_order', { 'link_ids[]': ids }).catch(err => alert(err.message));
            }
        });
    }

    // --- 7. Modals & Wizard ---
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.saas-modal').forEach(m => m.style.display = 'none');
        };
    });

    window.onclick = (e) => {
        if (e.target.classList.contains('saas-modal')) e.target.style.display = 'none';
    };

    // --- 8. Simulation & AI Stubs ---
    document.getElementById('saas-simulate-pro')?.addEventListener('click', () => {
        saasFetch('saas_simulate_pro_upgrade')
            .then(msg => { alert(msg); location.reload(); })
            .catch(err => alert(err.message));
    });

    document.querySelectorAll('.ai-assist-btn').forEach(btn => {
        btn.onclick = () => {
            const target = btn.dataset.target;
            const input = document.querySelector(`[name="${target}"]`);
            const originalText = btn.innerText;
            btn.innerText = '🤖...';

            setTimeout(() => {
                input.value = (target === 'headline') ? "Helping [Niche] Scale with Proven Systems 🚀" : "Elite strategist driving results through data-backed funnels.";
                input.dispatchEvent(new Event('input'));
                btn.innerText = originalText;
            }, 800);
        };
    });

});
