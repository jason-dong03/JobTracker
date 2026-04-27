<?php
require_once 'config/db.php';
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = $_GET['error'] ?? '';
$mode  = $_GET['mode']  ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JobTracker — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">JobTracker</div>

        <?php if ($error === 'credentials'): ?>
            <div class="auth-error">Invalid email or password.</div>
        <?php elseif ($error === 'email'): ?>
            <div class="auth-error">That email is already registered.</div>
        <?php endif; ?>

        <!-- LOGIN -->
        <form id="form-login" action="auth.php" method="POST" style="<?= $mode === 'register' ? 'display:none' : '' ?>">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" required placeholder="you@example.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:6px">Sign In</button>
            <p class="auth-toggle">No account? <a href="#" onclick="toggleMode()">Create one</a></p>
        </form>

        <!-- REGISTER -->
        <form id="form-register" action="auth.php" method="POST" style="<?= $mode === 'register' ? '' : 'display:none' ?>">
            <input type="hidden" name="action" value="register">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" required placeholder="Jane">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" required placeholder="Doe">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" required placeholder="you@gmail.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required placeholder="••••••••" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:6px">Create Account</button>
            <p class="auth-toggle">Already have an account? <a href="#" onclick="toggleMode()">Sign in</a></p>
        </form>
    </div>
</div>

<script>
function toggleMode() {
    const login = document.getElementById('form-login');
    const reg   = document.getElementById('form-register');
    login.style.display = login.style.display === 'none' ? '' : 'none';
    reg.style.display   = reg.style.display   === 'none' ? '' : 'none';
}
</script>
</body>
</html>
