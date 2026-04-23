<?php
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JobTracker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app">

    <aside class="sidebar">
        <div class="sidebar-logo">JobTracker</div>
        <nav class="sidebar-nav">
            <div class="nav-item" data-page="dashboard"    onclick="showPage('dashboard')"><span>Dashboard</span></div>
            <div class="nav-item" data-page="info"         onclick="showPage('info')"><span>Info</span></div>
            <div class="nav-item" data-page="profile"      onclick="showPage('profile')"><span>Profile</span></div>
        </nav>
    </aside>

    <main class="main">

        <!-- DASHBOARD -->
        <div id="page-dashboard" class="page">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <div class="btn-group">
                    <button class="btn btn-ghost" onclick="exportData('csv')">Download CSV</button>
                    <button class="btn btn-ghost" onclick="exportData('json')">Download JSON</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card accent"><div class="label">Total</div><div class="value" id="stat-total">—</div></div>
                <div class="stat-card success"><div class="label">Offers</div><div class="value" id="stat-offer">—</div></div>
                <div class="stat-card warning"><div class="label">Interviews</div><div class="value" id="stat-interview">—</div></div>
                <div class="stat-card danger"><div class="label">Rejected</div><div class="value" id="stat-rejected">—</div></div>
            </div>
            <div class="table-wrap">
                <div class="filters">
                    <span style="font-weight:600;font-size:13px;margin-right:6px">My Applications</span>
                    <select id="dash-filter-cycle"  class="filter-select" onchange="renderDashboardApps()"></select>
                    <select id="dash-filter-status" class="filter-select" onchange="renderDashboardApps()"></select>
                    <button class="btn btn-primary btn-sm" onclick="openAddModal()" style="margin-left:auto">+ Add</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Cycle</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="dash-app-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- INFO (My Companies / My Cities / My Cycles) -->
        <div id="page-info" class="page">
            <div class="page-header"><h1 class="page-title">Info</h1></div>
            <div class="subtab-bar">
                <button class="subtab active" data-subtab="my-companies" onclick="showInfoTab('my-companies')">My Companies</button>
                <button class="subtab"        data-subtab="my-cities"    onclick="showInfoTab('my-cities')">My Cities</button>
                <button class="subtab"        data-subtab="my-cycles"    onclick="showInfoTab('my-cycles')">My Cycles</button>
            </div>

            <!-- My Companies -->
            <div id="subtab-my-companies" class="subtab-content">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <span style="color:var(--text-muted);font-size:13px">Companies from your applications.</span>
                    <button class="btn btn-primary btn-sm" onclick="openAddCompanyModal()">+ Add Company</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Company</th><th style="text-align:right">Applications</th><th></th></tr></thead>
                        <tbody id="my-co-tbody"></tbody>
                    </table>
                </div>
            </div>

            <!-- My Cities -->
            <div id="subtab-my-cities" class="subtab-content" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <span style="color:var(--text-muted);font-size:13px">Cities from your applications.</span>
                    <button class="btn btn-primary btn-sm" onclick="openAddCityModal()">+ Add City</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>City</th><th>State</th><th style="text-align:right">Applications</th><th></th></tr></thead>
                        <tbody id="my-ci-tbody"></tbody>
                    </table>
                </div>
            </div>

            <!-- My Cycles -->
            <div id="subtab-my-cycles" class="subtab-content" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <span style="color:var(--text-muted);font-size:13px">Cycles from your applications.</span>
                    <button class="btn btn-primary btn-sm" onclick="openAddCycleModal()">+ Add Cycle</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Cycle</th><th style="text-align:right">Applications</th><th></th></tr></thead>
                        <tbody id="my-cy-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PROFILE -->
        <div id="page-profile" class="page">
            <div class="page-header"><h1 class="page-title">Profile</h1></div>
            <div class="profile-card">
                <div class="profile-avatar" id="profile-avatar">?</div>
                <div class="profile-info">
                    <div class="profile-name"  id="profile-name">—</div>
                    <div class="profile-email" id="profile-email">—</div>
                    <div class="profile-since" id="profile-since">—</div>
                </div>
            </div>
            <div style="margin-top:20px">
                <a href="auth.php?action=logout" class="btn btn-danger">Sign Out</a>
            </div>
        </div>

    </main>
</div>

<!-- Application Modal -->
<div id="app-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title" id="modal-title">Add Application</div>
        <form id="app-form" onsubmit="return false">
            <div class="form-group">
                <label class="form-label">Role Title *</label>
                <input type="text" id="f-role" placeholder="e.g. Software Engineer Intern">
            </div>
            <div class="form-group">
                <label class="form-label">Company *</label>
                <div class="inline-add-row">
                    <select id="f-company"></select>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-company')">+</button>
                </div>
                <div id="inline-company" class="inline-add-form" style="display:none">
                    <input type="text" id="new-company-name" placeholder="Company name">
                    <button type="button" class="btn btn-primary btn-sm" onclick="quickAddCompany()">Add</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-company')">Cancel</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">City *</label>
                <div class="inline-add-row">
                    <select id="f-city"></select>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-city')">+</button>
                </div>
                <div id="inline-city" class="inline-add-form" style="display:none">
                    <input type="text" id="new-city-name" placeholder="City">
                    <input type="text" id="new-city-state" placeholder="State" style="width:80px">
                    <button type="button" class="btn btn-primary btn-sm" onclick="quickAddCity()">Add</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-city')">Cancel</button>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Cycle *</label>
                    <div class="inline-add-row">
                        <select id="f-cycle"></select>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-cycle')">+</button>
                    </div>
                    <div id="inline-cycle" class="inline-add-form" style="display:none">
                        <input type="text" id="new-cycle-name" placeholder="Cycle name">
                        <button type="button" class="btn btn-primary btn-sm" onclick="quickAddCycle()">Add</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-cycle')">Cancel</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select id="f-status"></select>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeModal('app-modal')">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="saveApp()">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Company Modal -->
<div id="co-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title">Add Company</div>
        <form id="co-form" onsubmit="return false">
            <div class="form-group">
                <label class="form-label">Company Name *</label>
                <input type="text" id="co-name" placeholder="e.g. Google">
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeModal('co-modal')">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="saveCompany()">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- City Modal -->
<div id="ci-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title">Add City</div>
        <form id="ci-form" onsubmit="return false">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">City Name *</label>
                    <input type="text" id="ci-name" placeholder="e.g. Austin">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" id="ci-state" placeholder="e.g. TX">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeModal('ci-modal')">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="saveCity()">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Cycle Modal -->
<div id="cy-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title">Add Cycle</div>
        <form id="cy-form" onsubmit="return false">
            <div class="form-group">
                <label class="form-label">Cycle Name *</label>
                <input type="text" id="cy-name" placeholder="e.g. Summer 2026">
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeModal('cy-modal')">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="saveCycle()">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="toast"></div>
<script>const USER_ID = <?= $user_id ?>;</script>
<script src="js/app.js"></script>
</body>
</html>
