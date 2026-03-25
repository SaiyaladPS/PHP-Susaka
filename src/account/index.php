<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance, status, currency) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $_POST['user_id'], $_POST['account_number'], $_POST['balance'], $_POST['status'], $_POST['currency']);
        $stmt->execute(); $stmt->close();
        header("Location: index.php"); exit;
    }
    if ($action === 'update') {
        $stmt = $conn->prepare("UPDATE accounts SET user_id=?, account_number=?, balance=?, status=?, currency=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("isdssi", $_POST['user_id'], $_POST['account_number'], $_POST['balance'], $_POST['status'], $_POST['currency'], $_POST['id']);
        $stmt->execute(); $stmt->close();
        header("Location: index.php"); exit;
    }
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id=?");
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute(); $stmt->close();
        header("Location: index.php"); exit;
    }
}

$accounts = $conn->query("SELECT a.*, u.name as user_name FROM accounts a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC");
$users    = $conn->query("SELECT id, name FROM users ORDER BY name");

$edit_account = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id=?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $edit_account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$page_title = 'ຈັດການບັນຊີ';
$active_nav = 'account';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Create/Update Form -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php if ($edit_account): ?>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                ແກ້ໄຂບັນຊີ
            <?php else: ?>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                ເພີ່ມບັນຊີໃໝ່
            <?php endif; ?>
        </div>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_account ? 'update' : 'create' ?>">
        <?php if ($edit_account): ?><input type="hidden" name="id" value="<?= $edit_account['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>ຜູ້ໃຊ້</label>
                <select name="user_id" required>
                    <option value="">-- ເລືອກຜູ້ໃຊ້ --</option>
                    <?php $users->data_seek(0); while ($u = $users->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= ($edit_account && $edit_account['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ເລກທີ່ບັນຊີ</label>
                <input type="text" name="account_number"
                       value="<?= $edit_account ? htmlspecialchars($edit_account['account_number']) : '' ?>"
                       placeholder="ໃສ່ເລກທີ່ບັນຊີ" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>ຍອດເງິນ</label>
                <input type="number" step="0.01" name="balance"
                       value="<?= $edit_account ? $edit_account['balance'] : '0.00' ?>" required>
            </div>
            <div class="form-group">
                <label>ສະຖານະ</label>
                <select name="status" required>
                    <option value="active"   <?= ($edit_account && $edit_account['status'] === 'active')   ? 'selected' : '' ?>>ໃຊ້ງານ (Active)</option>
                    <option value="inactive" <?= ($edit_account && $edit_account['status'] === 'inactive') ? 'selected' : '' ?>>ປິດ (Inactive)</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>ສະກຸນເງິນ</label>
            <select name="currency" required>
                <option value="LAK" <?= ($edit_account && $edit_account['currency'] === 'LAK') ? 'selected' : '' ?>>LAK (ກີບ)</option>
                <option value="THB" <?= ($edit_account && $edit_account['currency'] === 'THB') ? 'selected' : '' ?>>THB (ບາດ)</option>
                <option value="USD" <?= ($edit_account && $edit_account['currency'] === 'USD') ? 'selected' : '' ?>>USD (ໂດລາ)</option>
            </select>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn <?= $edit_account ? 'btn-success' : 'btn-primary' ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <?= $edit_account ? 'ບັນທຶກການແກ້ໄຂ' : 'ເພີ່ມບັນຊີ' ?>
            </button>
            <?php if ($edit_account): ?>
            <a href="index.php" class="btn btn-secondary">ຍົກເລີກ</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Accounts Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            ລາຍການບັນຊີທັງໝົດ
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>ຜູ້ໃຊ້</th><th>ເລກທີ່ບັນຊີ</th>
                    <th>ຍອດເງິນ</th><th>ສະກຸນ</th><th>ສະຖານະ</th>
                    <th>ສ້າງເມື່ອ</th><th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($accounts->num_rows > 0): ?>
                    <?php while ($acc = $accounts->fetch_assoc()): ?>
                    <tr>
                        <td><?= $acc['id'] ?></td>
                        <td><?= htmlspecialchars($acc['user_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($acc['account_number']) ?></td>
                        <td><?= number_format($acc['balance'], 2) ?></td>
                        <td><?= $acc['currency'] ?></td>
                        <td class="status-<?= $acc['status'] ?>">
                            <?= $acc['status'] === 'active' ? 'ໃຊ້ງານ' : 'ປິດ' ?>
                        </td>
                        <td style="color:var(--text-muted);white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($acc['created_at'])) ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $acc['id'] ?>" class="btn btn-warning btn-sm">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                ແກ້ໄຂ
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('ລຶບບັນຊີນີ້?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    ລຶບ
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z"/>
                            </svg>
                            <p>ບໍ່ມີຂໍ້ມູນບັນຊີ</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>