<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /src/account/index.php');
    exit;
}

// ── Stats ──
$r = $conn->query("SELECT COUNT(*) AS c FROM users");
$stats['users'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM accounts");
$stats['accounts'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM auth_logs WHERE DATE(created_at) = CURDATE()");
$stats['logins_today'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM visitor_logs WHERE DATE(created_at) = CURDATE()");
$stats['visitors_today'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT COUNT(*) AS c FROM auth_logs WHERE action='login_failed'");
$stats['login_failed'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

$r = $conn->query("SELECT SUM(balance) AS s FROM accounts WHERE status='active'");
$stats['total_balance'] = $r ? (float)($r->fetch_assoc()['s'] ?? 0) : 0;

// ── Recent auth logs ──
$recent_auth = $conn->query("
    SELECT al.*, u.name AS user_name
    FROM auth_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 6
");

// ── Recent visitor logs ──
$recent_visitors = $conn->query("
    SELECT vl.page_url, vl.ip_address, vl.created_at, u.name AS user_name
    FROM visitor_logs vl
    LEFT JOIN users u ON vl.user_id = u.id
    ORDER BY vl.created_at DESC LIMIT 6
");

// ── Recent accounts ──
$recent_accounts = $conn->query("
    SELECT a.*, u.name AS user_name
    FROM accounts a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC LIMIT 6
");

// ── Recent users ──
$recent_users = $conn->query("
    SELECT id, name, email, role, created_at
    FROM users
    ORDER BY created_at DESC LIMIT 6
");

$page_title = 'Dashboard';
$active_nav = 'dashboard';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<!-- ════ STAT CARDS ════ -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="stat-card accent">
        <div class="stat-icon accent">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">ຜູ້ໃຊ້ທັງໝົດ</div>
            <div class="value"><?= number_format($stats['users']) ?></div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">ບັນຊີທັງໝົດ</div>
            <div class="value"><?= number_format($stats['accounts']) ?></div>
        </div>
    </div>
    <div class="stat-card green" style="--accent:var(--success);">
        <div class="stat-icon green">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">ຍອດເງິນລວມ</div>
            <div class="value" style="font-size:1.3rem;"><?= number_format($stats['total_balance'], 0) ?></div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Login ວັນນີ້</div>
            <div class="value"><?= number_format($stats['logins_today']) ?></div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Login ລົ້ມເຫລວ</div>
            <div class="value"><?= number_format($stats['login_failed']) ?></div>
        </div>
    </div>
    <div class="stat-card red" style="--accent:var(--info);">
        <div class="stat-icon accent">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">ຜູ້ເຂົ້າຊົມວັນນີ້</div>
            <div class="value"><?= number_format($stats['visitors_today']) ?></div>
        </div>
    </div>
</div>

<!-- ════ ROW 1: Auth Logs + Visitor Logs ════ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                ການ Login ຫຼ້າສຸດ
            </div>
            <a href="/src/logs/auth_logs.php" class="btn btn-secondary btn-sm">ທັງໝົດ →</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ຜູ້ໃຊ້ / ອີເມວ</th><th>ການກະທຳ</th><th>ເວລາ</th></tr></thead>
                <tbody>
                    <?php while ($row = $recent_auth->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= $row['user_name'] ? '<strong>'.htmlspecialchars($row['user_name']).'</strong><br>' : '' ?>
                            <small style="color:var(--text-muted)"><?= htmlspecialchars($row['email']) ?></small>
                        </td>
                        <td>
                            <?php if ($row['action'] === 'login_success'): ?>
                                <span class="badge badge-success">ສຳເລັດ</span>
                            <?php elseif ($row['action'] === 'login_failed'): ?>
                                <span class="badge badge-error">ລົ້ມເຫລວ</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Logout</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.78rem;white-space:nowrap;"><?= date('d/m H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                ຜູ້ເຂົ້າຊົມຫຼ້າສຸດ
            </div>
            <a href="/src/logs/visitor_logs.php" class="btn btn-secondary btn-sm">ທັງໝົດ →</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ໜ້າ</th><th>IP</th><th>ຜູ້ໃຊ້</th><th>ເວລາ</th></tr></thead>
                <tbody>
                    <?php while ($row = $recent_visitors->fetch_assoc()): ?>
                    <tr>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.8rem;">
                            <?= htmlspecialchars($row['page_url']) ?>
                        </td>
                        <td style="font-size:0.8rem;"><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td>
                            <?php if ($row['user_name']): ?>
                                <span class="badge badge-accent"><?= htmlspecialchars($row['user_name']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-muted">Guest</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.78rem;white-space:nowrap;"><?= date('d/m H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════ ROW 2: Recent Accounts + Recent Users ════ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                ບັນຊີຫຼ້າສຸດ
            </div>
            <a href="/src/account/index.php" class="btn btn-secondary btn-sm">ຈັດການ →</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ຜູ້ໃຊ້</th><th>ເລກທີ່ບັນຊີ</th><th>ຍອດ</th><th>ສະຖານະ</th></tr></thead>
                <tbody>
                    <?php while ($row = $recent_accounts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></td>
                        <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= number_format($row['balance'], 0) ?> <small style="color:var(--text-muted)"><?= $row['currency'] ?></small></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <span class="badge badge-success">ໃຊ້ງານ</span>
                            <?php else: ?>
                                <span class="badge badge-error">ປິດ</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                ຜູ້ໃຊ້ຫຼ້າສຸດ
            </div>
            <a href="/src/register/index.php" class="btn btn-primary btn-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                ສ້າງໃໝ່
            </a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ຊື່</th><th>ອີເມວ</th><th>ສິດ</th><th>ສ້າງເມື່ອ</th></tr></thead>
                <tbody>
                    <?php while ($row = $recent_users->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?php if ($row['role'] === 'admin'): ?>
                                <span class="badge badge-accent">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-muted">User</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.78rem;white-space:nowrap;"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../../includes/layout/footer.php';
?>
