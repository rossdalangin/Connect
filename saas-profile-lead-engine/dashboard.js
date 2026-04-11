/**
 * SaaS Dashboard - Responsive & Precision interaction
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Standard Fetch ---
    const saasFetch = (action, data = {}) => {
        let fd;
        if (data instanceof FormData) {
            fd = data;
        } else {
            fd = new FormData();
            for (const key in data) {
                if (Array.isArray(data[key])) {
                    data[key].forEach(val => fd.append(key, val));
                } else {
                    fd.append(key, data[key]);
                }
            }
        }
        fd.append('action', action);
        fd.append('security', saas_dashboard_data.nonce);

        return fetch(saas_dashboard_data.ajax_url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Error');
                return res.data;
            });
    };

    // --- Tab Engine ---
    const switchTab = (id) => {
        if (!id) return;
        const buttons = document.querySelectorAll('.saas-tabs button');
        const contents = document.querySelectorAll('.saas-tab-content');

        buttons.forEach(t => t.classList.toggle('active', t.dataset.tab === id));
        contents.forEach(c => c.classList.toggle('active', c.id === `tab-${id}`));

        const url = new URL(window.location.href);
        if (url.searchParams.get('tab') !== id) {
            url.searchParams.set('tab', id);
            window.history.pushState({}, '', url);
        }
        // Auto-scroll on small screens
        if (window.innerWidth < 1100) window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.querySelector('.saas-tabs')?.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (btn && btn.dataset.tab) {
            switchTab(btn.dataset.tab);
        }
    });

    const initTab = new URLSearchParams(window.location.search).get('tab');
    if (initTab) {
        switchTab(initTab);
    } else {
        // Ensure first tab is active if no tab in URL
        const firstTab = document.querySelector('.saas-tabs button')?.dataset.tab;
        if (firstTab) switchTab(firstTab);
    }

    // --- Profile Switcher ---
    const switcher = document.querySelector('.profile-title');
    const dropdown = document.querySelector('.profile-dropdown');
    if (switcher && dropdown) {
        switcher.onclick = (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        };
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    // Profile Cloning
    document.querySelectorAll('.clone-profile-btn').forEach(btn => {
        btn.onclick = (e) => {
            e.stopPropagation();
            if (!confirm('Clone this profile and all its links?')) return;
            const id = btn.dataset.id;
            saasFetch('saas_clone_profile', { profile_id: id })
                .then(res => window.location.href = `?profile_id=${res.id}`);
        };
    });

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

    // --- Interaction Engine (Edit/Delete/Clone/Leads) ---
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-link');
        if (editBtn) {
            const li = editBtn.closest('li');
            const d = li.dataset;

            document.getElementById('edit-link-id').value = d.id;
            document.getElementById('edit-link-title').value = li.querySelector('.link-title')?.innerText || '';
            document.getElementById('edit-link-url').value = li.querySelector('.link-url')?.innerText || '';
            document.getElementById('edit-link-extra').value = d.extra || '';
            document.getElementById('edit-link-style').value = d.style || 'regular';
            document.getElementById('edit-link-animation').value = d.animation || 'none';
            document.getElementById('edit-link-start').value = d.start || '';
            document.getElementById('edit-link-end').value = d.end || '';
            document.getElementById('edit-link-hour-from').value = d.hourFrom || '';
            document.getElementById('edit-link-hour-to').value = d.hourTo || '';
            document.getElementById('edit-link-pass').value = d.password || '';
            document.getElementById('edit-link-ab-title').value = d.abTitle || '';
            document.getElementById('edit-link-ab-url').value = d.abUrl || '';
            document.getElementById('edit-link-url-mobile').value = d.urlMobile || '';
            document.getElementById('edit-link-geo-country').value = d.geoCountry || '';
            document.getElementById('edit-link-url-geo').value = d.urlGeo || '';
            document.getElementById('edit-link-custom-bg').value = d.customBg || '#6366f1';
            document.getElementById('edit-link-custom-text').value = d.customText || '#ffffff';

            document.getElementById('saas-edit-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            return;
        }

        const deleteBtn = e.target.closest('.delete-link');
        if (deleteBtn) {
            if (!confirm('Delete this block?')) return;
            const li = deleteBtn.closest('li');
            saasFetch('saas_delete_link', { link_id: li.dataset.id })
                .then(() => {
                    li.remove();
                    document.getElementById('saas-preview-frame')?.contentWindow.location.reload();
                })
                .catch(err => alert(err.message));
            return;
        }

        const cloneBtn = e.target.closest('.clone-link');
        if (cloneBtn) {
            saasFetch('saas_clone_link', { link_id: cloneBtn.closest('li').dataset.id })
                .then(() => location.reload())
                .catch(err => alert(err.message));
            return;
        }

        const viewLeadBtn = e.target.closest('.view-lead');
        if (viewLeadBtn) {
            const id = viewLeadBtn.dataset.id;
            saasFetch('saas_get_lead_details', { lead_id: id })
                .then(html => {
                    document.getElementById('lead-details-content').innerHTML = html;
                    document.getElementById('saas-lead-modal').style.display = 'block';

                    // Re-bind update form inside modal
                    document.getElementById('saas-update-lead-form')?.addEventListener('submit', function(ev) {
                        ev.preventDefault();
                        saasFetch('saas_update_lead', new FormData(this))
                            .then(() => location.reload())
                            .catch(er => alert(er.message));
                    });
                })
                .catch(err => alert(err.message));
        }
    });

    // Advanced Toggle
    document.querySelector('.toggle-advanced')?.addEventListener('click', function() {
        const fields = document.getElementById('edit-advanced-fields');
        if (!fields) return;
        const isHidden = fields.style.display === 'none' || fields.style.display === '';
        fields.style.display = isHidden ? 'block' : 'none';
        this.innerText = isHidden ? '🔼 Hide Advanced Options' : '⚙️ Advanced Options';
    });

    // Save Edit
    document.getElementById('saas-edit-link-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const txt = btn.innerText; btn.innerText = 'Saving...';
        saasFetch('saas_save_link', new FormData(this))
            .then(() => location.reload())
            .catch(err => { alert(err.message); btn.innerText = txt; });
    });

    // --- Config Forms ---
    // Bulk Leads
    const bulkBtn = document.getElementById('saas-bulk-delete-leads');
    const selectAll = document.getElementById('leads-select-all');
    if (selectAll) {
        selectAll.onclick = (e) => {
            document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = e.target.checked);
            bulkBtn.style.display = e.target.checked ? 'block' : 'none';
        };
    }
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('lead-checkbox')) {
            const checked = document.querySelectorAll('.lead-checkbox:checked').length;
            bulkBtn.style.display = checked > 0 ? 'block' : 'none';
        }
    });
    bulkBtn?.addEventListener('click', () => {
        const ids = Array.from(document.querySelectorAll('.lead-checkbox:checked')).map(cb => cb.value);
        if (confirm(`Delete ${ids.length} leads?`)) {
            saasFetch('saas_bulk_delete_leads', { 'lead_ids[]': ids }).then(() => location.reload());
        }
    });

    ['saas-profile-form', 'saas-branding-form', 'saas-automation-form', 'saas-integrations-form'].forEach(id => {
        document.getElementById(id)?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const txt = btn.innerText; btn.innerText = 'Saving...';
            saasFetch('saas_save_profile', new FormData(this))
                .then(m => { alert(m); btn.innerText = txt; document.getElementById('saas-preview-frame').contentWindow.location.reload(); })
                .catch(err => { alert(err.message); btn.innerText = txt; });
        });
    });

    // --- Visuals & Live Preview ---
    const previewPane = document.querySelector('.saas-preview-pane');
    const previewTrigger = document.getElementById('saas-preview-trigger');
    const previewClose = document.getElementById('saas-close-preview');
    const previewIframe = document.getElementById('saas-preview-frame');

    if (previewTrigger && previewPane) {
        previewTrigger.onclick = () => {
            previewPane.classList.add('show');
            document.body.style.overflow = 'hidden';
            previewIframe.contentWindow.location.reload();
        };
    }

    if (previewClose && previewPane) {
        previewClose.onclick = () => {
            previewPane.classList.remove('show');
            document.body.style.overflow = 'auto';
        };
    }

    const emitUpdate = (key, value) => {
        previewIframe?.contentWindow.postMessage({ type: 'live_update', key, value }, '*');
    };

    document.querySelectorAll('#saas-profile-form input, #saas-profile-form textarea').forEach(el => {
        el.oninput = () => emitUpdate(el.name, el.value);
    });

    document.querySelectorAll('#saas-branding-form input, #saas-branding-form select').forEach(el => {
        el.oninput = () => {
            emitUpdate(el.name, el.value);
            if (el.name === 'bg_type') {
                const bgInput = document.getElementById('saas-bg-value-input');
                if (el.value === 'flat') {
                    if (bgInput.value.includes('gradient')) bgInput.value = '#f3f3f1';
                } else if (el.value === 'gradient') {
                    if (!bgInput.value.includes('gradient')) bgInput.value = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                }
                bgInput.dispatchEvent(new Event('input'));
            }
        };
    });

    // Style Presets
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.onclick = () => {
            const p = btn.dataset.preset;
            const color = document.querySelector('[name="theme_color"]');
            const bg = document.getElementById('profile-bg-type');
            const themeSelect = document.getElementById('profile-theme-select');
            const bgInput = document.getElementById('saas-bg-value-input');

            if (p === 'midnight') {
                color.value = '#ffffff'; bg.value = 'flat';
                bgInput.value = '#0f172a';
                if (themeSelect) themeSelect.value = 'dark';
            }
            else if (p === 'glassy') {
                color.value = '#6366f1'; bg.value = 'mesh';
                bgInput.value = '#ffffff';
                if (themeSelect) themeSelect.value = 'light';
            }
            else if (p === 'vibrant') {
                color.value = '#ffffff'; bg.value = 'gradient';
                bgInput.value = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                if (themeSelect) themeSelect.value = 'vibrant';
            }
            else if (p === 'minimal') {
                color.value = '#0f172a'; bg.value = 'flat';
                bgInput.value = '#ffffff';
                if (themeSelect) themeSelect.value = 'light';
            }
            else if (p === 'luxury') {
                color.value = '#d4af37'; bg.value = 'flat';
                bgInput.value = '#0a0a0a';
                if (themeSelect) themeSelect.value = 'luxury';
            }

            // Trigger UI updates
            color.dispatchEvent(new Event('input'));
            bg.dispatchEvent(new Event('input'));
            bgInput.dispatchEvent(new Event('input'));
            if (themeSelect) themeSelect.dispatchEvent(new Event('input'));
        };
    });

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

    // Check Integrations
    document.querySelectorAll('.check-integration').forEach(btn => {
        btn.onclick = () => {
            const platform = btn.dataset.platform;
            saasFetch('saas_check_integration', { platform }).then(msg => alert(msg)).catch(e => alert(e.message));
        };
    });

    document.getElementById('saas-test-webhook-btn')?.addEventListener('click', function() {
        const url = document.querySelector('[name="lead_webhook"]').value;
        if (!url) return alert('Please enter a webhook URL first.');
        saasFetch('saas_test_webhook', { webhook_url: url }).then(msg => alert(msg)).catch(e => alert(e.message));
    });

    // AI Assist
    document.querySelectorAll('.ai-assist-btn').forEach(btn => {
        btn.onclick = () => {
            const target = btn.dataset.target;
            const input = document.querySelector(`[name="${target}"]`);
            const niche = document.getElementById('profile-niche')?.value || 'business';
            const originalText = btn.innerText;
            btn.innerText = '🤖...';

            setTimeout(() => {
                const suggestions = {
                    coach: { h: "Helping Founders Scale with Proven Systems 🚀", b: "Elite high-performance coach specializing in sustainable growth for 7-figure entrepreneurs." },
                    creator: { h: "Exclusive Content & Daily Insights 🎥", b: "Sharing daily tips on digital growth and community building for the next generation of creators." },
                    realtor: { h: "Modern Homes for Modern Families 🏡", b: "Helping you find your dream luxury property in the city's most exclusive neighborhoods." },
                    business: { h: "Driving Results through Strategic Design 📈", b: "Providing high-impact solutions for modern organizations ready to scale their digital infrastructure." }
                };

                input.value = (target === 'headline') ? suggestions[niche].h : suggestions[niche].b;
                input.dispatchEvent(new Event('input'));
                btn.innerText = originalText;
            }, 800);
        };
    });

    // Simulation
    document.getElementById('saas-simulate-pro')?.onclick = function() {
        const btn = this;
        const original = btn.innerText;
        btn.innerText = 'Upgrading...';
        saasFetch('saas_simulate_pro_upgrade')
            .then(msg => {
                alert(msg);
                location.reload();
            })
            .catch(err => {
                alert(err.message);
                btn.innerText = original;
            });
    };

    document.getElementById('saas-add-profile-trigger')?.onclick = () => {
        const t = prompt('Profile Title:');
        if (t) saasFetch('saas_create_profile', { profile_title: t }).then(d => window.location.href = `?profile_id=${d.id}`);
    };

    document.getElementById('saas-copy-btn')?.addEventListener('click', function() {
        const copyText = document.getElementById('saas-my-link');
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        const original = this.innerText;
        this.innerText = 'Copied! ✅';
        setTimeout(() => this.innerText = original, 2000);
    });

    // Wizard Logic
    let currentStep = 1;
    const wizardModal = document.getElementById('saas-wizard-modal');
    const updateWizard = (step) => {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.toggle('active', parseInt(s.dataset.step) === step));
        const progress = (step / 3) * 100;
        document.querySelector('.progress-bar-fill').style.width = progress + '%';
    };

    wizardModal?.querySelectorAll('.next-step').forEach(btn => btn.onclick = () => { currentStep++; updateWizard(currentStep); });
    wizardModal?.querySelectorAll('.prev-step').forEach(btn => btn.onclick = () => { currentStep--; updateWizard(currentStep); });

    document.getElementById('wizard-finish')?.addEventListener('click', function() {
        const data = {
            profile_id: document.querySelector('[name="profile_id"]').value,
            headline: document.getElementById('wizard-headline').value,
            bio: document.getElementById('wizard-bio').value,
            niche: document.getElementById('wizard-niche').value
        };
        saasFetch('saas_save_profile', data).then(() => location.reload());
    });

    // Sortable Link List
    const sortableList = document.getElementById('saas-links-list');
    if (sortableList && typeof Sortable !== 'undefined') {
        new Sortable(sortableList, {
            animation: 150,
            handle: '.handle',
            onEnd: function() {
                const ids = Array.from(sortableList.querySelectorAll('li')).map(li => li.dataset.id);
                saasFetch('saas_update_link_order', { link_ids: ids })
                    .then(() => {
                        document.getElementById('saas-preview-frame').contentWindow.location.reload();
                    });
            }
        });
    }

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
