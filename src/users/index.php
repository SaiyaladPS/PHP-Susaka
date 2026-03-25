<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php'); exit;
}
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /src/dashboard/index.php'); exit;
}

$msg = '';
$msg_type = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_role') {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $_POST['role'], $_POST['id']);
        $stmt->execute(); $stmt->close();
        $msg = 'ອັບເດດສິດຜູ້ໃຊ້ສຳເລັດ';
        $msg_type = 'success';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute(); $stmt->close();
            $msg = 'ລຶບຜູ້ໃຊ້ສຳເລັດ';
            $msg_type = 'success';
        } else {
            $msg = 'ບໍ່ສາມາດລຶບຕົນເອງໄດ້';
            $msg_type = 'error';
        }
    }
}

// Get all users
$search = $_GET['s'] ?? '';
if ($search) {
    $s = '%' . $conn->real_escape_string($search) . '%';
    $users_result = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM accounts WHERE user_id=u.id) AS acc_count FROM users u WHERE u.name LIKE '$s' OR u.email LIKE '$s' ORDER BY u.id DESC");
} else {
    $users_result = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM accounts WHERE user_id=u.id) AS acc_count FROM users u ORDER BY u.id DESC");
}

$total_users = $users_result->num_rows;

$page_title = 'ຈັດການຜູ້ໃຊ້';
$active_nav = 'users';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <?php if ($msg_type === 'success'): ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        <?php else: ?>
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/>
        <?php endif; ?>
    </svg>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            ຜູ້ໃຊ້ທັງໝົດ (<?= $total_users ?>)
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="GET" style="display:flex;gap:8px;">
                <input type="text" name="s" value="<?= htmlspecialchars($search) ?>"
                       placeholder="ຄົ້ນຫາຊື່ / ອີເມວ..."
                       style="width:220px;padding:9px 14px;">
                <button type="submit" class="btn btn-secondary btn-sm">ຄົ້ນຫາ</button>
                <?php if ($search): ?><a href="index.php" class="btn btn-secondary btn-sm">ລ້າງ</a><?php endif; ?>
            </form>
            <a href="/src/register/index.php" class="btn btn-primary btn-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                ສ້າງຜູ້ໃຊ້
            </a>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>ຊື່</th><th>ອີເມວ</th>
                    <th>ສິດ</th><th>ບັນຊີ</th><th>ສ້າງເມື່ອ</th><th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users_result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                            <span class="badge badge-accent" style="margin-left:6px;font-size:0.65rem;">ຕົນເອງ</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <select name="role" style="padding:6px 10px;width:auto;" onchange="this.form.submit()">
                                <option value="user"  <?= $u['role']==='user'  ?'selected':'' ?>>User</option>
                                <option value="staff" <?= $u['role']==='staff' ?'selected':'' ?>>Staff</option>
                                <option value="admin" <?= $u['role']==='admin' ?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <span class="badge badge-<?= $u['acc_count'] > 0 ? 'success' : 'muted' ?>">
                            <?= $u['acc_count'] ?> ບັນຊີ
                        </span>
                    </td>
                    <td style="color:var(--text-muted);white-space:nowrap;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td class="actions">
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('ລຶບຜູ້ໃຊ້ <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                ລຶບ
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../../includes/layout/footer.php';
?>
