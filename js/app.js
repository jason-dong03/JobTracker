const API = 'api';
const STATUSES = ['Draft', 'Submitted', 'Interview', 'Offer', 'Rejected', 'Withdrawn'];

let apps = [], cycles = [], companies = [], cities = [], profile = {}, documents = [], schools = [];
let editingId = null;
let sortCol = 'created_at', sortDir = 'desc';

// fetch
async function apiFetch(path, opts = {}) {
    const res = await fetch(`${API}/${path}`, opts);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

async function loadAll() {
    [apps, cycles, companies, cities, profile, documents, schools] = await Promise.all([
        apiFetch('applications.php'),
        apiFetch('cycles.php'),
        apiFetch('companies.php'),
        apiFetch('cities.php'),
        apiFetch('profile.php'),
        apiFetch('documents.php'),
        apiFetch('schools.php')
    ]);
}

let currentCycleId = null;

// nav
function showPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + id).classList.add('active');
    document.querySelector(`.nav-item[data-page="${id}"]`).classList.add('active');

    const renders = {
        'dashboard': () => { populateDashFilters(); renderDashboard(); renderDashboardApps(); },
        'cycles': renderCyclesPage,
        'connections': renderConnectionsPage,
        'documents': renderDocumentsPage,
        'profile': renderProfile,
    };
    if (renders[id]) renders[id]();
}

// dashboard
function greeting() {
    const h = new Date().getHours();
    if (h < 12) return 'Good Morning';
    if (h < 18) return 'Good Afternoon';
    return 'Good Evening';
}

function populateDashFilters() {
    document.getElementById('dash-filter-cycle').innerHTML =
        '<option value="">All Cycles</option>' +
        cycles.map(c => `<option value="${c.cycle_id}">${esc(c.cycle_name)}</option>`).join('');
    document.getElementById('dash-filter-status').innerHTML =
        '<option value="">All Statuses</option>' +
        STATUSES.map(s => `<option value="${s}">${s}</option>`).join('');
}

function renderDashboard() {
    const name = profile.first_name ? `${profile.first_name} ${profile.last_name}` : '';
    document.getElementById('dash-title').textContent = `${greeting()}, ${name}`;

    const total = apps.length;
    const offer = apps.filter(a => a.status === 'Offer').length;
    const interview = apps.filter(a => a.status === 'Interview').length;
    const rejected = apps.filter(a => a.status === 'Rejected').length;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-offer').textContent = offer;
    document.getElementById('stat-interview').textContent = interview;
    document.getElementById('stat-rejected').textContent = rejected;
}

async function renderDashboardApps() {
    const cycle = document.getElementById('dash-filter-cycle').value;
    const status = document.getElementById('dash-filter-status').value;
    let params = `sort=${sortCol}&dir=${sortDir}`;
    if (cycle) params += `&cycle_id=${cycle}`;
    if (status) params += `&status=${encodeURIComponent(status)}`;

    const data = await apiFetch(`applications.php?${params}`);
    document.getElementById('dash-app-tbody').innerHTML = data.length ? data.map(a => `
        <tr class="clickable-row" draggable="true" onclick="openEditModal(${a.application_id})">
            <td><strong>${esc(a.role_title)}</strong></td>
            <td>${esc(a.company_name)}</td>
            <td>${badge(a.status)}</td>
            <td style="color:var(--text-muted)">${esc(a.cycle_name)}</td>
            <td style="color:var(--text-muted)">${esc(a.location || '—')}</td>
            <td style="color:var(--text-muted)">${a.created_at.slice(0, 10)}</td>
            <td onclick="event.stopPropagation()">
                <div class="action-cell">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(${a.application_id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteApp(${a.application_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="7" class="empty">No applications yet.</td></tr>';
    initDragSort('dash-app-tbody');
}

// apps page
function renderApplications() {
    document.getElementById('filter-cycle').innerHTML =
        '<option value="">All Cycles</option>' +
        cycles.map(c => `<option value="${c.cycle_id}">${esc(c.cycle_name)}</option>`).join('');
    document.getElementById('filter-status').innerHTML =
        '<option value="">All Statuses</option>' +
        STATUSES.map(s => `<option value="${s}">${s}</option>`).join('');
    filterAndRender();
}

async function filterAndRender() {
    const cycle = document.getElementById('filter-cycle').value;
    const status = document.getElementById('filter-status').value;
    let params = `sort=${sortCol}&dir=${sortDir}`;
    if (cycle) params += `&cycle_id=${cycle}`;
    if (status) params += `&status=${encodeURIComponent(status)}`;

    apps = await apiFetch(`applications.php?${params}`);
    document.getElementById('app-tbody').innerHTML = apps.length ? apps.map(a => `
        <tr>
            <td><strong>${esc(a.role_title)}</strong></td>
            <td>${esc(a.company_name)}</td>
            <td>${badge(a.status)}</td>
            <td style="color:var(--text-muted)">${esc(a.cycle_name)}</td>
            <td style="color:var(--text-muted)">${esc(a.location || '—')}</td>
            <td style="color:var(--text-muted)">${a.created_at.slice(0, 10)}</td>
            <td>
                <div class="action-cell">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(${a.application_id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteApp(${a.application_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="7" class="empty">No applications found.</td></tr>';
}

// app modal
function openAddModal() {
    editingId = null;
    document.getElementById('modal-title').textContent = 'Add Application';
    document.getElementById('app-form').reset();
    populateModalSelects();
    openModal('app-modal');
}

async function openEditModal(id) {
    editingId = id;
    document.getElementById('modal-title').textContent = 'Edit Application';
    populateModalSelects();
    const data = await apiFetch(`applications.php?id=${id}`);
    document.getElementById('f-cycle').value = data.cycle_id;
    document.getElementById('f-company').value = data.company_id;
    document.getElementById('f-city').value = data.city_id;
    document.getElementById('f-role').value = data.role_title;
    document.getElementById('f-status').value = data.status;

    const selectedIds = data.document_ids || [];
    document.querySelectorAll('.doc-checkbox').forEach(cb => {
        cb.checked = selectedIds.includes(parseInt(cb.value));
    });

    openModal('app-modal');
}

function populateModalSelects() {
    document.getElementById('f-cycle').innerHTML =
        '<option value="">— Select Cycle —</option>' +
        cycles.map(c => `<option value="${c.cycle_id}">${esc(c.cycle_name)}</option>`).join('');
    document.getElementById('f-company').innerHTML =
        '<option value="">— Select Company —</option>' +
        companies.map(c => `<option value="${c.company_id}">${esc(c.company_name)}</option>`).join('');
    document.getElementById('f-city').innerHTML =
        '<option value="">— Select City —</option>' +
        cities.map(c => `<option value="${c.city_id}">${esc(c.city_name)}${c.state_name ? ', ' + c.state_name : ''}</option>`).join('');
    document.getElementById('f-status').innerHTML =
        STATUSES.map(s => `<option value="${s}">${s}</option>`).join('');
    document.getElementById('f-docs-checkboxes').innerHTML = documents.length
        ? documents.map(d => `<label style="display:flex;align-items:center;justify-content:flex-start;gap:8px;padding:4px 0;cursor:pointer;font-size:13px;text-align:left;width:100%;">
            <input type="checkbox" class="doc-checkbox" value="${d.doc_id}" style="margin:0;flex-shrink:0;width:16px;height:16px;"> <span style="text-align:left;">${esc(d.file_name)}</span>
          </label>`).join('')
        : '<span style="color:var(--text-dim);font-size:13px;">No documents uploaded yet.</span>';
}

async function saveApp() {
    const docIds = Array.from(document.querySelectorAll('.doc-checkbox:checked')).map(cb => parseInt(cb.value));

    const body = {
        cycle_id: +document.getElementById('f-cycle').value,
        company_id: +document.getElementById('f-company').value,
        city_id: +document.getElementById('f-city').value,
        role_title: document.getElementById('f-role').value.trim(),
        status: document.getElementById('f-status').value,
        document_ids: docIds
    };
    if (!body.role_title) return toast('Role title required', 'error');
    if (!body.cycle_id) return toast('Select a cycle', 'error');
    if (!body.company_id) return toast('Select a company', 'error');
    if (!body.city_id) return toast('Select a city', 'error');

    try {
        if (editingId) {
            await apiFetch(`applications.php?id=${editingId}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            toast('Updated');
        } else {
            await apiFetch('applications.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            toast('Added');
        }
        closeModal('app-modal');
        await loadAll();
        renderDashboard();
        renderDashboardApps();
        const activePage = document.querySelector('.nav-item.active')?.dataset.page;
        if (activePage === 'cycles' && currentCycleId) renderCycleApps();
    } catch (e) { toast('Error saving', 'error'); }
}

async function deleteApp(id) {
    if (!confirm('Delete this application?')) return;
    await apiFetch(`applications.php?id=${id}`, { method: 'DELETE' });
    toast('Deleted');
    await loadAll();
    renderDashboard();
    renderDashboardApps();
    const activePage = document.querySelector('.nav-item.active')?.dataset.page;
    if (activePage === 'cycles' && currentCycleId) renderCycleApps();
}

// cycles page
function renderCyclesPage() {
    currentCycleId = null;
    document.getElementById('cycles-list-view').style.display = 'block';
    document.getElementById('cycle-detail-view').style.display = 'none';
    renderMyCycles();
}

async function renderMyCycles() {
    const data = await apiFetch('cycles.php?mine=1');
    document.getElementById('my-cy-tbody').innerHTML = data.length ? data.map(c => `
        <tr class="clickable-row" onclick="viewCycle(${c.cycle_id}, '${esc(c.cycle_name)}')">
            <td><strong>${esc(c.cycle_name)}</strong></td>
            <td style="text-align:right;color:var(--text-muted)">${c.app_count}</td>
            <td onclick="event.stopPropagation()">
                <div class="action-cell">
                    <button class="btn btn-danger btn-sm" onclick="deleteCycle(${c.cycle_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="3" class="empty">No cycles yet. Click "+ Add Cycle" to begin.</td></tr>';
}

async function viewCycle(cycleId, cycleName) {
    currentCycleId = cycleId;
    document.getElementById('cycles-list-view').style.display = 'none';
    document.getElementById('cycle-detail-view').style.display = 'block';
    document.getElementById('cycle-detail-title').textContent = cycleName;
    await renderCycleApps();
}

async function renderCycleApps() {
    if (!currentCycleId) return;
    const data = await apiFetch(`applications.php?cycle_id=${currentCycleId}`);
    document.getElementById('cycle-app-tbody').innerHTML = data.length ? data.map(a => `
        <tr class="clickable-row" draggable="true" onclick="openEditModal(${a.application_id})">
            <td><strong>${esc(a.role_title)}</strong></td>
            <td>${esc(a.company_name)}</td>
            <td>${badge(a.status)}</td>
            <td style="color:var(--text-muted)">${esc(a.location || '—')}</td>
            <td style="color:var(--text-muted)">${a.created_at.slice(0, 10)}</td>
            <td onclick="event.stopPropagation()">
                <div class="action-cell">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(${a.application_id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteApp(${a.application_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="6" class="empty">No applications in this cycle. Click "+ Add Application".</td></tr>';
    initDragSort('cycle-app-tbody');
}

function openAddAppInCycle() {
    openAddModal();
    document.getElementById('f-cycle').value = currentCycleId;
}

// cycles
function openAddCycleModal() { document.getElementById('cy-form').reset(); openModal('cy-modal'); }

async function saveCycle() {
    const name = document.getElementById('cy-name').value.trim();
    if (!name) return toast('Name required', 'error');
    await apiFetch('cycles.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cycle_name: name }) });
    toast('Cycle added');
    closeModal('cy-modal');
    cycles = await apiFetch('cycles.php');
    renderMyCycles();
}

async function deleteCycle(id) {
    if (!confirm('Delete cycle?')) return;
    await apiFetch(`cycles.php?id=${id}`, { method: 'DELETE' });
    toast('Deleted');
    cycles = await apiFetch('cycles.php');
    renderMyCycles();
}

// profile
async function renderProfile() {
    const d = await apiFetch('profile.php');
    profile = d;
    if (d.profile_picture) {
        document.getElementById('profile-avatar').innerHTML = `<img src="storage/profiles/${d.profile_picture}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
    } else {
        document.getElementById('profile-avatar').innerHTML = '';
        document.getElementById('profile-avatar').textContent = (d.first_name || '?')[0].toUpperCase();
    }
    document.getElementById('profile-name').textContent = `${d.first_name} ${d.last_name}`;
    document.getElementById('profile-email').textContent = d.email;
    document.getElementById('profile-since').textContent = 'Member since ' + d.created_at.slice(0, 10);
    
    let edu = '—';
    if (d.school_name) {
        edu = `${d.school_name}`;
        if (d.degree_type || d.major) edu += ` • ${d.degree_type || ''} ${d.major || ''}`;
    }
    document.getElementById('profile-education').textContent = edu;
    document.getElementById('profile-bio').textContent = d.biography || 'No biography added.';

    document.getElementById('p-first-name').value = d.first_name || '';
    document.getElementById('p-last-name').value = d.last_name || '';
    document.getElementById('p-bio').value = d.biography || '';
    
    document.getElementById('p-school').innerHTML = '<option value="">— Select School —</option>' + 
        schools.map(s => `<option value="${s.school_id}">${esc(s.school_name)}</option>`).join('');
    document.getElementById('p-school').value = d.school_id || '';
    
    document.getElementById('p-major').value = d.major || '';
    document.getElementById('p-degree').value = d.degree_type || '';
    document.getElementById('p-start').value = d.start_date || '';
    document.getElementById('p-end').value = d.end_date || '';
}

function previewProfilePic(input) {
    const preview = document.getElementById('p-pic-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

function toggleEditProfile() {
    const view = document.getElementById('profile-view-mode');
    const edit = document.getElementById('profile-edit-mode');
    if (view.style.display === 'none') {
        view.style.display = 'block';
        edit.style.display = 'none';
    } else {
        view.style.display = 'none';
        edit.style.display = 'block';
    }
}

async function saveProfile() {
    const fn = document.getElementById('p-first-name').value.trim();
    const ln = document.getElementById('p-last-name').value.trim();
    const bio = document.getElementById('p-bio').value.trim();
    const schoolId = document.getElementById('p-school').value;
    const major = document.getElementById('p-major').value.trim();
    const degree = document.getElementById('p-degree').value.trim();
    const start = document.getElementById('p-start').value;
    const end = document.getElementById('p-end').value;

    if (!fn || !ln) return toast('First and last name required', 'error');

    const formData = new FormData();
    formData.append('first_name', fn);
    formData.append('last_name', ln);
    formData.append('biography', bio);
    if (schoolId) formData.append('school_id', schoolId);
    formData.append('major', major);
    formData.append('degree_type', degree);
    formData.append('start_date', start);
    formData.append('end_date', end);

    const fileInput = document.getElementById('p-pic');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('profile_picture', fileInput.files[0]);
    }

    try {
        await apiFetch('profile.php', {
            method: 'POST',
            body: formData
        });
        toast('Profile updated');
        toggleEditProfile();
        await loadAll();
        renderProfile();
        renderDashboard();
    } catch (e) {
        toast('Error saving profile', 'error');
    }
}

async function quickAddSchool() {
    const v = document.getElementById('new-school-name').value.trim();
    if (!v) return;
    try {
        const res = await apiFetch('schools.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({school_name:v}) });
        schools = await apiFetch('schools.php');
        const sel = document.getElementById('p-school');
        sel.innerHTML = '<option value="">— Select School —</option>' + schools.map(s => `<option value="${s.school_id}">${esc(s.school_name)}</option>`).join('');
        sel.value = res.school_id;
        document.getElementById('new-school-name').value = '';
        toggleInline('inline-school');
    } catch(e) { toast('Error adding school', 'error'); }
}

// quick add
function toggleInline(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'flex' : 'none';
}

async function quickAddCompany() {
    const name = document.getElementById('new-company-name').value.trim();
    if (!name) return toast('Enter a company name', 'error');
    const res = await apiFetch('companies.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ company_name: name }) });
    companies = await apiFetch('companies.php');
    document.getElementById('f-company').innerHTML =
        '<option value="">— Select Company —</option>' +
        companies.map(c => `<option value="${c.company_id}">${esc(c.company_name)}</option>`).join('');
    document.getElementById('f-company').value = res.company_id;
    document.getElementById('new-company-name').value = '';
    toggleInline('inline-company');
    toast(`"${name}" added`);
}

async function quickAddCity() {
    const city = document.getElementById('new-city-name').value.trim();
    const state = document.getElementById('new-city-state').value.trim();
    if (!city) return toast('Enter a city name', 'error');
    const res = await apiFetch('cities.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ city_name: city, state_name: state }) });
    cities = await apiFetch('cities.php');
    document.getElementById('f-city').innerHTML =
        '<option value="">— Select City —</option>' +
        cities.map(c => `<option value="${c.city_id}">${esc(c.city_name)}${c.state_name ? ', ' + c.state_name : ''}</option>`).join('');
    document.getElementById('f-city').value = res.city_id;
    document.getElementById('new-city-name').value = '';
    document.getElementById('new-city-state').value = '';
    toggleInline('inline-city');
    toast(`"${city}" added`);
}

async function quickAddCycle() {
    const name = document.getElementById('new-cycle-name').value.trim();
    if (!name) return toast('Enter a cycle name', 'error');
    const res = await apiFetch('cycles.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cycle_name: name }) });
    cycles = await apiFetch('cycles.php');
    document.getElementById('f-cycle').innerHTML =
        '<option value="">— Select Cycle —</option>' +
        cycles.map(c => `<option value="${c.cycle_id}">${esc(c.cycle_name)}</option>`).join('');
    document.getElementById('f-cycle').value = res.cycle_id;
    document.getElementById('new-cycle-name').value = '';
    toggleInline('inline-cycle');
    toast(`"${name}" added`);
}

// sort
function setSort(col) {
    if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    else { sortCol = col; sortDir = 'asc'; }
    filterAndRender();
}

// export
function exportData(fmt) {
    const cycleEl = document.getElementById('dash-filter-cycle') || document.getElementById('filter-cycle');
    const cycle = cycleEl?.value || '';
    let url = `${API}/export.php?format=${fmt}`;
    if (cycle) url += `&cycle_id=${cycle}`;
    window.open(url, '_blank');
}

// modal
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

// toast
let toastTimer;
function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = `show ${type}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.className = '', 2500);
}

// util
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function badge(status) {
    const map = { 'Draft': 'applied', 'Submitted': 'phone', 'Interview': 'interview', 'Offer': 'offer', 'Rejected': 'rejected', 'Withdrawn': 'withdrawn' };
    return `<span class="badge badge-${map[status] || 'applied'}">${esc(status)}</span>`;
}

// sidebar
function toggleSidebar() {
    document.querySelector('.app').classList.toggle('nav-open');
}

function closeSidebar() {
    document.querySelector('.app').classList.remove('nav-open');
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 600) closeSidebar();
});

// init
showPage('dashboard');

(async () => {
    try {
        await loadAll();
        renderDashboard();
        populateDashFilters();
        renderDashboardApps();
    } catch (e) {
        console.error('Load error:', e);
    }
})();

// connections
async function renderConnectionsPage() {
    showConnListView();
    const conns = await apiFetch('connections.php');
    document.getElementById('conn-tbody').innerHTML = conns.length ? conns.map(c => `
        <tr class="clickable-row" onclick="viewConnection(${c.user_id})">
            <td><strong>${esc(c.first_name)} ${esc(c.last_name)}</strong></td>
            <td>${esc(c.email)}</td>
            <td>${esc(c.school_name || '—')} ${c.major ? `(${esc(c.major)})` : ''}</td>
        </tr>
    `).join('') : '<tr><td colspan="3" class="empty">No connections yet.</td></tr>';
}

async function addConnection(e) {
    e.preventDefault();
    const email = document.getElementById('conn-email').value.trim();
    if (!email) return;
    try {
        await apiFetch('connections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        toast('Connection added!');
        document.getElementById('conn-email').value = '';
        renderConnectionsPage();
    } catch (err) {
        toast(err.message || 'Error adding connection', 'error');
    }
}

function showConnListView() {
    document.getElementById('conn-list-view').style.display = 'block';
    document.getElementById('conn-detail-view').style.display = 'none';
}

async function viewConnection(id) {
    document.getElementById('conn-list-view').style.display = 'none';
    document.getElementById('conn-detail-view').style.display = 'block';
    document.getElementById('cd-name').textContent = 'Loading...';
    document.getElementById('cd-cycles-container').innerHTML = '';

    try {
        const data = await apiFetch(`connection_data.php?id=${id}`);
        const p = data.profile;
        document.getElementById('cd-name').textContent = `${p.first_name} ${p.last_name}`;
        
        document.getElementById('cd-avatar').innerHTML = '';
        document.getElementById('cd-avatar').textContent = (p.first_name || '?')[0].toUpperCase();

        document.getElementById('cd-email').textContent = p.email;
        let edu = '—';
        if (p.school_name) {
            edu = `${p.school_name}`;
            if (p.degree_type || p.major) edu += ` • ${p.degree_type || ''} ${p.major || ''}`;
        }
        document.getElementById('cd-education').textContent = edu;
        document.getElementById('cd-bio').textContent = p.biography || 'No biography.';

        let html = '';
        if (data.cycles.length === 0) {
            html = '<div class="empty">No cycles found.</div>';
        } else {
            for (const c of data.cycles) {
                const cycleApps = data.applications.filter(a => a.cycle_id === c.cycle_id);
                html += `
                    <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:8px; padding:15px;">
                        <h4 style="margin:0 0 10px 0; color:var(--primary-color)">${esc(c.cycle_name)}</h4>
                        ${cycleApps.length ? `
                            <table style="margin:0">
                                <thead><tr><th>Role</th><th>Company</th><th>Status</th><th>City</th></tr></thead>
                                <tbody>
                                    ${cycleApps.map(a => `
                                        <tr>
                                            <td><strong>${esc(a.role_title)}</strong></td>
                                            <td>${esc(a.company_name)}</td>
                                            <td>${badge(a.status)}</td>
                                            <td style="color:var(--text-muted)">${esc(a.city_name)}, ${esc(a.state_name)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<div class="empty" style="padding:10px 0">No applications in this cycle.</div>'}
                    </div>
                `;
            }
        }
        document.getElementById('cd-cycles-container').innerHTML = html;
    } catch (e) {
        toast('Failed to load data', 'error');
        showConnListView();
    }
}

// documents
async function renderDocumentsPage() {
    documents = await apiFetch('documents.php');
    initDocDropzone();
    const tbody = document.getElementById('docs-tbody');
    if (!documents.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty">No documents uploaded yet.</td></tr>';
        return;
    }
    tbody.innerHTML = documents.map(d => {
        let apps;
        if (!d.linked_apps) {
            apps = '<span class="doc-app-tag none">None</span>';
        } else {
            const all = d.linked_apps.split(', ');
            const visible = all.slice(0, 2).map(a => `<span class="doc-app-tag">${esc(a)}</span>`).join('');
            const rest = all.slice(2).map(a => `<span class="doc-app-tag">${esc(a)}</span>`).join('');
            const extra = all.length > 2
                ? `<span class="doc-app-tag doc-app-more" onclick="this.style.display='none';this.nextElementSibling.style.display='contents'">+${all.length - 2} more</span><span style="display:none">${rest}</span>`
                : '';
            apps = visible + extra;
        }
        const canPreview = /\.(pdf|png|jpg|jpeg|gif|webp)$/i.test(d.file_name);
        return `<tr>
            <td><strong>${esc(d.file_name)}</strong></td>
            <td><div class="doc-app-tags">${apps}</div></td>
            <td>${esc(d.uploaded_at)}</td>
            <td style="white-space:nowrap;">
                <div class="action-cell">
                    ${canPreview ? `<button class="btn btn-ghost btn-sm" onclick="previewDocument(${d.doc_id}, '${esc(d.file_name)}')">Preview</button>` : ''}
                    <a class="btn btn-ghost btn-sm" href="api/documents.php?id=${d.doc_id}&download=1">Download</a>
                    <button class="btn btn-danger btn-sm" onclick="deleteDocument(${d.doc_id})">Delete</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function initDocDropzone() {
    const dz = document.getElementById('doc-dropzone');
    if (!dz || dz._initialized) return;
    dz._initialized = true;

    ['dragenter', 'dragover'].forEach(evt =>
        dz.addEventListener(evt, e => { e.preventDefault(); dz.classList.add('drag-over'); })
    );
    ['dragleave', 'drop'].forEach(evt =>
        dz.addEventListener(evt, e => { e.preventDefault(); dz.classList.remove('drag-over'); })
    );
    dz.addEventListener('drop', handleDocUpload);

    document.getElementById('doc-file').addEventListener('change', handleDocUpload);
}

async function handleDocUpload(e) {
    const files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
    if (!files || !files.length) return;

    for (const file of files) {
        const formData = new FormData();
        formData.append('document', file);
        try {
            await apiFetch('documents.php', { method: 'POST', body: formData });
            toast(`Uploaded: ${file.name}`);
        } catch(err) {
            toast(`Error uploading ${file.name}`, 'error');
        }
    }
    const fileInput = document.getElementById('doc-file');
    if (fileInput) fileInput.value = '';
    renderDocumentsPage();
}

function previewDocument(docId, fileName) {
    const url = `api/documents.php?id=${docId}`;
    const ext = fileName.split('.').pop().toLowerCase();
    const contentDiv = document.getElementById('doc-preview-content');
    document.getElementById('doc-preview-title').textContent = fileName;
    document.getElementById('doc-download-link').href = url + '&download=1';

    if (ext === 'pdf') {
        contentDiv.innerHTML = `<iframe src="${url}" style="width:100%;height:100%;border:none;border-radius:8px;"></iframe>`;
    } else if (['png','jpg','jpeg','gif','webp'].includes(ext)) {
        contentDiv.innerHTML = `<img src="${url}" style="max-width:100%;max-height:100%;display:block;margin:auto;">`;
    } else {
        contentDiv.innerHTML = '<div class="empty" style="padding:40px;">Preview not available for this file type.</div>';
    }
    openModal('doc-preview-modal');
}

async function deleteDocument(id) {
    if (!confirm('Delete this document?')) return;
    await apiFetch(`documents.php?id=${id}`, { method: 'DELETE' });
    toast('Deleted');
    renderDocumentsPage();
}

// drag-to-reorder table rows
let _dragging = null;

function initDragSort(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.querySelectorAll('tr[draggable="true"]').forEach(row => {
        row.addEventListener('dragstart', e => {
            _dragging = row;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(() => row.classList.add('dragging'), 0);
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('dragging');
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over'));
            _dragging = null;
        });
        row.addEventListener('dragover', e => {
            e.preventDefault();
            if (!_dragging || row === _dragging) return;
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over'));
            row.classList.add('drag-over');
        });
        row.addEventListener('drop', e => {
            e.preventDefault();
            if (!_dragging || _dragging === row) return;
            const rows = [...tbody.children];
            const fromIdx = rows.indexOf(_dragging);
            const toIdx = rows.indexOf(row);
            if (fromIdx < toIdx) row.after(_dragging);
            else row.before(_dragging);
            tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over'));
        });
    });
}

async function uploadModalDocument() {
    const fileInput = document.getElementById('modal-doc-file');
    if (!fileInput.files.length) return toast('Select a file to upload', 'error');

    const formData = new FormData();
    formData.append('document', fileInput.files[0]);

    try {
        const res = await apiFetch('documents.php', { method: 'POST', body: formData });
        toast('Document uploaded & attached');
        fileInput.value = '';

        documents = await apiFetch('documents.php');
        const currentlyChecked = Array.from(document.querySelectorAll('.doc-checkbox:checked')).map(cb => cb.value);
        currentlyChecked.push(res.doc_id.toString());

        const container = document.getElementById('f-docs-checkboxes');
        container.innerHTML = documents.map(d => `<label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;font-size:13px;text-align:left;">
            <input type="checkbox" class="doc-checkbox" value="${d.doc_id}" style="margin:0;flex-shrink:0;"> ${esc(d.file_name)}
          </label>`).join('');
        document.querySelectorAll('.doc-checkbox').forEach(cb => {
            if (currentlyChecked.includes(cb.value)) cb.checked = true;
        });
    } catch(err) {
        toast('Error uploading', 'error');
    }
}
