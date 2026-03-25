<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$msg = '';
$msg_type = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email)) {
            $msg = 'ກະລຸນາໃສ່ຊື່ ແລະ ອີເມວ';
            $msg_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'ຮູບແບບອີເມວບໍ່ຖືກຕ້ອງ';
            $msg_type = 'error';
        } else {
            // Check email not taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $msg = 'ອີເມວນີ້ຖືກໃຊ້ງານແລ້ວ';
                $msg_type = 'error';
            } else {
                $upd = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
                $upd->bind_param("ssi", $name, $email, $user_id);
                $upd->execute(); $upd->close();
                $_SESSION['user_name'] = $name;
                $msg = 'ອັບເດດໂປຣໄຟລ໌ສຳເລັດ';
                $msg_type = 'success';
            }
            $stmt->close();
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new_pw  = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $r['password'])) {
            $msg = 'ລະຫັດຜ່ານເດີມບໍ່ຖືກຕ້ອງ';
            $msg_type = 'error';
        } elseif (strlen($new_pw) < 8) {
            $msg = 'ລະຫັດຜ່ານໃໝ່ຕ້ອງຢ່າງໜ້ອຍ 8 ຕົວ';
            $msg_type = 'error';
        } elseif ($new_pw !== $confirm) {
            $msg = 'ລະຫັດຜ່ານໃໝ່ບໍ່ຕົງກັນ';
            $msg_type = 'error';
        } else {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hashed, $user_id);
            $upd->execute(); $upd->close();
            $msg = 'ປ່ຽນລະຫັດຜ່ານສຳເລັດ';
            $msg_type = 'success';
        }
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's accounts
$accounts = $conn->query("SELECT * FROM accounts WHERE user_id=$user_id ORDER BY created_at DESC");

// Fetch user's recent auth logs
$auth_logs = $conn->query("SELECT action, ip_address, status_message, created_at FROM auth_logs WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 8");

$page_title = 'ໂປຣໄຟລ໌ຂອງຂ້ອຍ';
$active_nav = 'profile';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <?php if ($msg_type === 'success'): ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        <?php else: ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <?php endif; ?>
    </svg>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Profile Header -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:20px;">
        <div style="width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:white;flex-shrink:0;box-shadow:0 8px 24px var(--accent-glow);">
            <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
        </div>
        <div>
            <div style="font-size:1.4rem;font-weight:700;letter-spacing:-0.02em;"><?= htmlspecialchars($user['name']) ?></div>
            <div style="color:var(--text-muted);font-size:0.9rem;margin-top:4px;"><?= htmlspecialchars($user['email']) ?></div>
            <div style="margin-top:8px;display:flex;gap:8px;">
                <?php if ($user['role'] === 'admin'): ?>
                    <span class="badge badge-accent">⚡ Admin</span>
                <?php elseif ($user['role'] === 'staff'): ?>
                    <span class="badge badge-success">🛠️ Staff</span>
                <?php else: ?>
                    <span class="badge badge-muted">👤 User</span>
                <?php endif; ?>
                <span class="badge badge-muted">ສ້າງ: <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

    <!-- Edit Profile -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                ແກ້ໄຂໂປຣໄຟລ໌
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
                <label>ຊື່</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>ອີເມວ</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                ບັນທຶກ
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                ປ່ຽນລະຫັດຜ່ານ
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>ລະຫັດຜ່ານເດີມ</label>
                <input type="password" name="current_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>ລະຫັດຜ່ານໃໝ່</label>
                <input type="password" name="new_password" placeholder="ຢ່າງໜ້ອຍ 8 ຕົວ" minlength="8" required>
            </div>
            <div class="form-group">
                <label>ຢືນຢັນລະຫັດຜ່ານ</label>
                <input type="password" name="confirm_password" placeholder="ຢ່າງໜ້ອຍ 8 ຕົວ" required>
            </div>
            <button type="submit" class="btn btn-warning">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                ປ່ຽນລະຫັດ
            </button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- My Accounts -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                ບັນຊີຂອງຂ້ອຍ
            </div>
        </div>
        <?php if ($accounts->num_rows > 0): ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ເລກທີ່ບັນຊີ</th><th>ຍອດ</th><th>ສະກຸນ</th><th>ສະຖານະ</th></tr></thead>
                <tbody>
                    <?php while ($a = $accounts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['account_number']) ?></td>
                        <td><?= number_format($a['balance'], 2) ?></td>
                        <td><?= $a['currency'] ?></td>
                        <td class="status-<?= $a['status'] ?>"><?= $a['status'] === 'active' ? 'ໃຊ້ງານ' : 'ປິດ' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <p>ຍັງບໍ່ມີບັນຊີ</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- My Login History -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ປະຫວັດ Login
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>ການກະທຳ</th><th>IP</th><th>ເວລາ</th></tr></thead>
                <tbody>
                    <?php while ($log = $auth_logs->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($log['action'] === 'login_success'): ?>
                                <span class="badge badge-success">Login ສຳເລັດ</span>
                            <?php elseif ($log['action'] === 'login_failed'): ?>
                                <span class="badge badge-error">Login ລົ້ມ</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Logout</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;"><?= date('d/m H:i', strtotime($log['created_at'])) ?></td>
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
