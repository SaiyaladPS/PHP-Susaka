<?php
// This file acts as the layout header for all admin pages.
// Each page should start with: 
//   require_once __DIR__ . '/../../includes/layout/header.php';
//   defining: $page_title, $active_nav

// Auth guard: Must be logged in to access admin pages
if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
$user_role = $_SESSION['user_role'] ?? 'user';
$user_initial = strtoupper(mb_substr($user_name, 0, 1));
$page_title = $page_title ?? 'Dashboard';
$active_nav = $active_nav ?? 'dashboard';

// Base path for assets (relative to web root)
$asset_base = '/assets/css/admin.css';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — ລະບົບຈັດການ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= $asset_base ?>">
</head>
<body>
<div class="grid-dots"></div>

<!-- ════ SIDEBAR ════ -->
<aside class="sidebar" id="sidebar">
    <a href="/src/dashboard/index.php" class="sidebar-brand">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
        </div>
        <div class="brand-text">
            <span class="title">AdminSys</span>
            <span class="subtitle">ລະບົບຈັດການ</span>
        </div>
    </a>

    <nav class="sidebar-nav">
        <span class="nav-section-title">ຫຼັກ</span>

        <a href="/src/dashboard/index.php"
           class="nav-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Dashboard
        </a>

        <a href="/src/profile/index.php"
           class="nav-link <?= $active_nav === 'profile' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            ໂປຣໄຟລ໌ຂອງຂ້ອຍ
        </a>

        <a href="/src/account/index.php"
           class="nav-link <?= $active_nav === 'account' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            ຈັດການບັນຊີ
        </a>

        <span class="nav-section-title">ລາຍງານ</span>

        <a href="/src/logs/auth_logs.php"
           class="nav-link <?= $active_nav === 'auth-logs' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            ບັນທຶກ Login
        </a>

        <a href="/src/logs/visitor_logs.php"
           class="nav-link <?= $active_nav === 'visitor-logs' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            ບັນທຶກຜູ້ເຂົ້າຊົມ
        </a>

        <?php if ($user_role === 'admin'): ?>
        <span class="nav-section-title">ຜູ້ໃຊ້ / Admin</span>

        <a href="/src/users/index.php"
           class="nav-link <?= $active_nav === 'users' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            ຈັດການຜູ້ໃຊ້
        </a>

        <a href="/src/register/index.php"
           class="nav-link <?= $active_nav === 'register' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            ສ້າງຜູ້ໃຊ້ໃໝ່
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar"><?= htmlspecialchars($user_initial) ?></div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($user_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
        <a href="/src/logout.php" class="nav-link danger" style="margin-top:8px;">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            ອອກຈາກລະບົບ
        </a>
    </div>
</aside>

<!-- ════ MAIN WRAP ════ -->
<div class="main-wrap">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:16px;">
            <button class="btn btn-secondary btn-sm" id="sidebarToggle" style="display:none;"
                    onclick="document.getElementById('sidebar').classList.toggle('open')">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="page-title"><?= htmlspecialchars($page_title) ?></span>
        </div>
        <div class="topbar-actions">
            <span style="font-size:0.8rem;color:var(--text-muted);">
                <?= date('d/m/Y H:i') ?>
            </span>
        </div>
    </header>
    <main class="content">
