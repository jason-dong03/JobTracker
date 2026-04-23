const API = 'api';
const STATUSES = ['Draft','Submitted','Interview','Offer','Rejected','Withdrawn'];

let apps = [], cycles = [], companies = [], cities = [];
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
    [apps, cycles, companies, cities] = await Promise.all([
        apiFetch('applications.php'),
        apiFetch('cycles.php'),
        apiFetch('companies.php'),
        apiFetch('cities.php'),
    ]);
}

// nav
function showPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + id).classList.add('active');
    document.querySelector(`.nav-item[data-page="${id}"]`).classList.add('active');

    const renders = {
        'dashboard': () => { populateDashFilters(); renderDashboard(); renderDashboardApps(); },
        'info':      renderInfo,
        'profile':   renderProfile,
    };
    if (renders[id]) renders[id]();
}

// dashboard
function populateDashFilters() {
    document.getElementById('dash-filter-cycle').innerHTML =
        '<option value="">All Cycles</option>' +
        cycles.map(c => `<option value="${c.cycle_id}">${esc(c.cycle_name)}</option>`).join('');
    document.getElementById('dash-filter-status').innerHTML =
        '<option value="">All Statuses</option>' +
        STATUSES.map(s => `<option value="${s}">${s}</option>`).join('');
}

function renderDashboard() {
    const total     = apps.length;
    const offer     = apps.filter(a => a.status === 'Offer').length;
    const interview = apps.filter(a => a.status === 'Interview').length;
    const rejected  = apps.filter(a => a.status === 'Rejected').length;

    document.getElementById('stat-total').textContent     = total;
    document.getElementById('stat-offer').textContent     = offer;
    document.getElementById('stat-interview').textContent = interview;
    document.getElementById('stat-rejected').textContent  = rejected;
}

async function renderDashboardApps() {
    const cycle  = document.getElementById('dash-filter-cycle').value;
    const status = document.getElementById('dash-filter-status').value;
    let params = `sort=${sortCol}&dir=${sortDir}`;
    if (cycle)  params += `&cycle_id=${cycle}`;
    if (status) params += `&status=${encodeURIComponent(status)}`;

    const data = await apiFetch(`applications.php?${params}`);
    document.getElementById('dash-app-tbody').innerHTML = data.length ? data.map(a => `
        <tr class="clickable-row" onclick="openEditModal(${a.application_id})">
            <td><strong>${esc(a.role_title)}</strong></td>
            <td>${esc(a.company_name)}</td>
            <td>${badge(a.status)}</td>
            <td style="color:var(--text-muted)">${esc(a.cycle_name)}</td>
            <td style="color:var(--text-muted)">${esc(a.location || '—')}</td>
            <td style="color:var(--text-muted)">${a.created_at.slice(0,10)}</td>
            <td onclick="event.stopPropagation()">
                <div class="action-cell">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(${a.application_id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteApp(${a.application_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="7" class="empty">No applications yet. Click "+ Add" to get started.</td></tr>';
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
    const cycle  = document.getElementById('filter-cycle').value;
    const status = document.getElementById('filter-status').value;
    let params = `sort=${sortCol}&dir=${sortDir}`;
    if (cycle)  params += `&cycle_id=${cycle}`;
    if (status) params += `&status=${encodeURIComponent(status)}`;

    apps = await apiFetch(`applications.php?${params}`);
    document.getElementById('app-tbody').innerHTML = apps.length ? apps.map(a => `
        <tr>
            <td><strong>${esc(a.role_title)}</strong></td>
            <td>${esc(a.company_name)}</td>
            <td>${badge(a.status)}</td>
            <td style="color:var(--text-muted)">${esc(a.cycle_name)}</td>
            <td style="color:var(--text-muted)">${esc(a.location || '—')}</td>
            <td style="color:var(--text-muted)">${a.created_at.slice(0,10)}</td>
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
    document.getElementById('f-cycle').value   = data.cycle_id;
    document.getElementById('f-company').value = data.company_id;
    document.getElementById('f-city').value    = data.city_id;
    document.getElementById('f-role').value    = data.role_title;
    document.getElementById('f-status').value  = data.status;
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
}

async function saveApp() {
    const body = {
        cycle_id:   +document.getElementById('f-cycle').value,
        company_id: +document.getElementById('f-company').value,
        city_id:    +document.getElementById('f-city').value,
        role_title: document.getElementById('f-role').value.trim(),
        status:     document.getElementById('f-status').value,
    };
    if (!body.role_title)  return toast('Role title required', 'error');
    if (!body.cycle_id)    return toast('Select a cycle', 'error');
    if (!body.company_id)  return toast('Select a company', 'error');
    if (!body.city_id)     return toast('Select a city', 'error');

    try {
        if (editingId) {
            await apiFetch(`applications.php?id=${editingId}`, { method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
            toast('Updated');
        } else {
            await apiFetch('applications.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
            toast('Added');
        }
        closeModal('app-modal');
        await loadAll();
        renderDashboard();
        renderDashboardApps();
        const activePage = document.querySelector('.nav-item.active')?.dataset.page;
        if (activePage === 'info') refreshActiveInfoTab();
    } catch(e) { toast('Error saving', 'error'); }
}

async function deleteApp(id) {
    if (!confirm('Delete this application?')) return;
    await apiFetch(`applications.php?id=${id}`, { method:'DELETE' });
    toast('Deleted');
    await loadAll();
    renderDashboard();
    renderDashboardApps();
    const activePage = document.querySelector('.nav-item.active')?.dataset.page;
    if (activePage === 'info') refreshActiveInfoTab();
}

// info tab
function renderInfo() {
    const active = document.querySelector('.subtab.active')?.dataset.subtab || 'my-companies';
    showInfoTab(active);
}

function showInfoTab(tab) {
    document.querySelectorAll('.subtab').forEach(b => b.classList.toggle('active', b.dataset.subtab === tab));
    document.querySelectorAll('.subtab-content').forEach(c => c.style.display = 'none');
    document.getElementById('subtab-' + tab).style.display = 'block';
    if (tab === 'my-companies') renderMyCompanies();
    if (tab === 'my-cities')    renderMyCities();
    if (tab === 'my-cycles')    renderMyCycles();
}

function refreshActiveInfoTab() {
    const active = document.querySelector('.subtab.active')?.dataset.subtab;
    if (active) showInfoTab(active);
}

async function renderMyCompanies() {
    const data = await apiFetch('companies.php?mine=1');
    document.getElementById('my-co-tbody').innerHTML = data.length ? data.map(c => `
        <tr>
            <td><strong>${esc(c.company_name)}</strong></td>
            <td style="text-align:right;color:var(--text-muted)">${c.app_count}</td>
            <td>
                <div class="action-cell">
                    <button class="btn btn-danger btn-sm" onclick="deleteCompany(${c.company_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="3" class="empty">No companies yet — add an application first.</td></tr>';
}

async function renderMyCities() {
    const data = await apiFetch('cities.php?mine=1');
    document.getElementById('my-ci-tbody').innerHTML = data.length ? data.map(c => `
        <tr>
            <td><strong>${esc(c.city_name)}</strong></td>
            <td style="color:var(--text-muted)">${esc(c.state_name || '—')}</td>
            <td style="text-align:right;color:var(--text-muted)">${c.app_count}</td>
            <td>
                <div class="action-cell">
                    <button class="btn btn-danger btn-sm" onclick="deleteCity(${c.city_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="4" class="empty">No cities yet — add an application first.</td></tr>';
}

async function renderMyCycles() {
    const data = await apiFetch('cycles.php?mine=1');
    document.getElementById('my-cy-tbody').innerHTML = data.length ? data.map(c => `
        <tr>
            <td><strong>${esc(c.cycle_name)}</strong></td>
            <td style="text-align:right;color:var(--text-muted)">${c.app_count}</td>
            <td>
                <div class="action-cell">
                    <button class="btn btn-danger btn-sm" onclick="deleteCycle(${c.cycle_id})">Delete</button>
                </div>
            </td>
        </tr>`).join('') :
        '<tr><td colspan="3" class="empty">No cycles yet — add an application first.</td></tr>';
}

// companies
function openAddCompanyModal() { document.getElementById('co-form').reset(); openModal('co-modal'); }

async function saveCompany() {
    const name = document.getElementById('co-name').value.trim();
    if (!name) return toast('Name required', 'error');
    await apiFetch('companies.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ company_name: name }) });
    toast('Company added');
    closeModal('co-modal');
    companies = await apiFetch('companies.php');
    renderMyCompanies();
}

async function deleteCompany(id) {
    if (!confirm('Delete company? This may affect existing applications.')) return;
    await apiFetch(`companies.php?id=${id}`, { method:'DELETE' });
    toast('Deleted');
    companies = await apiFetch('companies.php');
    renderMyCompanies();
}

// cities
function openAddCityModal() { document.getElementById('ci-form').reset(); openModal('ci-modal'); }

async function saveCity() {
    const city  = document.getElementById('ci-name').value.trim();
    const state = document.getElementById('ci-state').value.trim();
    if (!city) return toast('City name required', 'error');
    await apiFetch('cities.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ city_name: city, state_name: state }) });
    toast('City added');
    closeModal('ci-modal');
    cities = await apiFetch('cities.php');
    renderMyCities();
}

async function deleteCity(id) {
    if (!confirm('Delete city?')) return;
    await apiFetch(`cities.php?id=${id}`, { method:'DELETE' });
    toast('Deleted');
    cities = await apiFetch('cities.php');
    renderMyCities();
}

// cycles
function openAddCycleModal() { document.getElementById('cy-form').reset(); openModal('cy-modal'); }

async function saveCycle() {
    const name = document.getElementById('cy-name').value.trim();
    if (!name) return toast('Name required', 'error');
    await apiFetch('cycles.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ cycle_name: name }) });
    toast('Cycle added');
    closeModal('cy-modal');
    cycles = await apiFetch('cycles.php');
    renderMyCycles();
}

async function deleteCycle(id) {
    if (!confirm('Delete cycle?')) return;
    await apiFetch(`cycles.php?id=${id}`, { method:'DELETE' });
    toast('Deleted');
    cycles = await apiFetch('cycles.php');
    renderMyCycles();
}

// profile
async function renderProfile() {
    const d = await apiFetch('profile.php');
    document.getElementById('profile-avatar').textContent = (d.first_name || '?')[0].toUpperCase();
    document.getElementById('profile-name').textContent   = `${d.first_name} ${d.last_name}`;
    document.getElementById('profile-email').textContent  = d.email;
    document.getElementById('profile-since').textContent  = 'Member since ' + d.created_at.slice(0, 10);
}

// quick add
function toggleInline(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'flex' : 'none';
}

async function quickAddCompany() {
    const name = document.getElementById('new-company-name').value.trim();
    if (!name) return toast('Enter a company name', 'error');
    const res = await apiFetch('companies.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ company_name: name }) });
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
    const city  = document.getElementById('new-city-name').value.trim();
    const state = document.getElementById('new-city-state').value.trim();
    if (!city) return toast('Enter a city name', 'error');
    const res = await apiFetch('cities.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ city_name: city, state_name: state }) });
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
    const res = await apiFetch('cycles.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ cycle_name: name }) });
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
function openModal(id)  { document.getElementById(id).classList.add('open'); }
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
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function badge(status) {
    const map = { 'Draft':'applied','Submitted':'phone','Interview':'interview','Offer':'offer','Rejected':'rejected','Withdrawn':'withdrawn' };
    return `<span class="badge badge-${map[status] || 'applied'}">${esc(status)}</span>`;
}

// init
showPage('dashboard');

(async () => {
    try {
        await loadAll();
        renderDashboard();
        populateDashFilters();
        renderDashboardApps();
    } catch(e) {
        console.error('Load error:', e);
    }
})();
