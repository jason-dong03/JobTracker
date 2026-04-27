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

<!-- mobile top bar -->
<div class="topbar">
    <button class="hamburger" onclick="toggleSidebar()">&#9776;</button>
    <span class="topbar-logo">JobTracker</span>
</div>

<!-- mobile overlay -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="app">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">JobTracker</div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item" data-page="dashboard" onclick="showPage('dashboard')"><span>Dashboard</span></div>
            <div class="nav-item" data-page="cycles"    onclick="showPage('cycles')"><span>My Cycles</span></div>
            <div class="nav-item" data-page="connections" onclick="showPage('connections')"><span>Connections</span></div>
            <div class="nav-item" data-page="documents" onclick="showPage('documents')"><span>Documents</span></div>
            <div class="nav-item" data-page="profile"   onclick="showPage('profile')"><span>Profile</span></div>
        </nav>
    </aside>

    <main class="main">

        <!-- DASHBOARD -->
        <div id="page-dashboard" class="page">
            <div class="page-header">
                <h1 class="page-title" id="dash-title">Dashboard</h1>
                <div class="btn-group">
                    <button class="btn btn-ghost" onclick="exportData('json')">Download JSON</button>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="label">Total</div><div class="value" id="stat-total">—</div></div>
                <div class="stat-card success"><div class="label">Offers</div><div class="value" id="stat-offer">—</div></div>
                <div class="stat-card warning"><div class="label">Interviews</div><div class="value" id="stat-interview">—</div></div>
                <div class="stat-card danger"><div class="label">Rejected</div><div class="value" id="stat-rejected">—</div></div>
            </div>
            <div class="table-wrap">
                <div class="filters">
                    <span class="filters-label">My Applications</span>
                    <select id="dash-filter-cycle"  class="filter-select" onchange="renderDashboardApps()"></select>
                    <select id="dash-filter-status" class="filter-select" onchange="renderDashboardApps()"></select>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th class="hide-sm">Cycle</th>
                                <th class="hide-sm">Location</th>
                                <th class="hide-xs">Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="dash-app-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CYCLES -->
        <div id="page-cycles" class="page">
            <div id="cycles-list-view">
                <div class="page-header"><h1 class="page-title">My Cycles</h1></div>
                <div class="subtab-toolbar">
                    <span style="color:var(--text-muted);font-size:13px">Manage your application cycles.</span>
                    <button class="btn btn-primary btn-sm" onclick="openAddCycleModal()">+ Add Cycle</button>
                </div>
                <div class="table-wrap">
                    <div class="table-scroll">
                        <table>
                            <thead><tr><th>Cycle Name</th><th style="text-align:right">Applications</th><th></th></tr></thead>
                            <tbody id="my-cy-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="cycle-detail-view" style="display:none">
                <div class="page-header">
                    <div style="display:flex;flex-direction:column;align-items:flex-start">
                        <button class="btn btn-ghost btn-sm" onclick="renderCyclesPage()" style="margin-bottom:8px">&larr; Back</button>
                        <h1 class="page-title" id="cycle-detail-title">Cycle Name</h1>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openAddAppInCycle()">+ Add Application</button>
                </div>
                <div class="table-wrap">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th class="hide-sm">Location</th>
                                    <th class="hide-xs">Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cycle-app-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROFILE -->
        <div id="page-profile" class="page">
            <div class="page-header">
                <h1 class="page-title">Profile</h1>
                <button class="btn btn-primary btn-sm" onclick="toggleEditProfile()">Edit Profile</button>
            </div>
            
            <div id="profile-view-mode">
                <div class="profile-card">
                    <div class="profile-avatar" id="profile-avatar">?</div>
                    <div class="profile-info">
                        <div class="profile-name"  id="profile-name">—</div>
                        <div class="profile-email" id="profile-email">—</div>
                        <div class="profile-since" id="profile-since">—</div>
                        <div class="profile-since" id="profile-education" style="margin-top:4px">—</div>
                        <div style="margin-top: 10px; color: var(--text-muted); font-size: 14px;" id="profile-bio">—</div>
                    </div>
                </div>
                <div style="margin-top:20px">
                    <a href="auth.php?action=logout" class="btn btn-danger">Sign Out</a>
                </div>
            </div>

            <div id="profile-edit-mode" style="display:none; max-width: 500px; margin-top: 20px;">
                <form id="profile-form" onsubmit="return false">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" id="p-first-name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" id="p-last-name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">School Name</label>
                            <div class="inline-add-row">
                                <select id="p-school"></select>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-school')">+</button>
                            </div>
                            <div id="inline-school" class="inline-add-form" style="display:none">
                                <input type="text" id="new-school-name" placeholder="School name">
                                <button type="button" class="btn btn-primary btn-sm" onclick="quickAddSchool()">Add</button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInline('inline-school')">Cancel</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Major</label>
                            <input type="text" id="p-major" placeholder="e.g. Computer Science">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Degree</label>
                            <input type="text" id="p-degree" placeholder="e.g. BS, MS">
                        </div>
                        <div class="form-group" style="display:flex; gap:10px;">
                            <div style="flex:1">
                                <label class="form-label">Start</label>
                                <input type="date" id="p-start">
                            </div>
                            <div style="flex:1">
                                <label class="form-label">End</label>
                                <input type="date" id="p-end">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biography</label>
                        <textarea id="p-bio" rows="4" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit;" placeholder="Tell us about yourself..."></textarea>
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" id="p-pic" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                    <div class="modal-actions" style="margin-top:15px">
                        <button class="btn btn-ghost" type="button" onclick="toggleEditProfile()">Cancel</button>
                        <button class="btn btn-primary" type="button" onclick="saveProfile()">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CONNECTIONS -->
        <div id="page-connections" class="page">
            <div id="conn-list-view">
                <div class="page-header"><h1 class="page-title">Connections</h1></div>
                <div class="subtab-toolbar" style="margin-bottom:20px;">
                    <form id="add-conn-form" onsubmit="addConnection(event)" style="display:flex;gap:10px;align-items:center;">
                        <input type="email" id="conn-email" placeholder="Connection's email" required style="padding:8px;border:1px solid var(--border-color);border-radius:6px;width:250px;">
                        <button type="submit" class="btn btn-primary btn-sm">Connect</button>
                    </form>
                </div>
                <div class="table-wrap">
                    <div class="table-scroll">
                        <table>
                            <thead><tr><th>Name</th><th>Email</th><th>School / Major</th></tr></thead>
                            <tbody id="conn-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="conn-detail-view" style="display:none">
                <div class="page-header" style="flex-direction:column; align-items:flex-start; gap:10px;">
                    <button class="btn btn-ghost btn-sm" onclick="showConnListView()">&larr; Back to Connections</button>
                    <h1 class="page-title" id="cd-name">—</h1>
                </div>
                <div class="profile-card" style="margin-bottom:20px;">
                    <div class="profile-avatar" id="cd-avatar">?</div>
                    <div class="profile-info">
                        <div class="profile-email" id="cd-email">—</div>
                        <div class="profile-since" id="cd-education">—</div>
                        <div style="margin-top: 10px; color: var(--text-muted); font-size: 14px;" id="cd-bio">—</div>
                    </div>
                </div>
                
                <h3>Applications by Cycle</h3>
                <div id="cd-cycles-container" style="margin-top:15px; display:flex; flex-direction:column; gap:20px;"></div>
            </div>
        </div>
        <!-- DOCUMENTS -->
        <div id="page-documents" class="page">
            <div class="page-header"><h1 class="page-title">Documents</h1></div>
            <div class="subtab-toolbar" style="margin-bottom:20px;">
                <form id="upload-doc-form" onsubmit="uploadDocument(event)" style="display:flex;gap:10px;align-items:center;">
                    <input type="file" id="doc-file" required style="padding:6px;border:1px solid var(--border-color);border-radius:4px;">
                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                </form>
            </div>
            <div class="table-wrap">
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Filename</th><th>Uploaded At</th><th>Actions</th></tr></thead>
                        <tbody id="docs-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- modals -->
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
            <div class="form-group" style="display:none">
                <select id="f-cycle"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select id="f-status"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Documents</label>
                <div class="inline-add-row">
                    <select id="f-docs" multiple style="height:80px;"></select>
                </div>
                <div style="margin-top:8px; font-size:13px; color:var(--text-muted);">
                    Hold Ctrl (Windows) or Cmd (Mac) to select multiple.
                    Or upload a new one: 
                    <input type="file" id="modal-doc-file" style="max-width:180px;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="uploadModalDocument()">Upload</button>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeModal('app-modal')">Cancel</button>
                <button class="btn btn-primary" type="button" onclick="saveApp()">Save</button>
            </div>
        </form>
    </div>
</div>

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
