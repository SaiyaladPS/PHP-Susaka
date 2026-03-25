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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'ອີເມວບໍ່ຖືກຕ້ອງ';
        $msg_type = 'error';
    } elseif (strlen($password) < 8) {
        $msg = 'ລະຫັດຜ່ານຕ້ອງຢ່າງໜ້ອຍ 8 ຕົວ';
        $msg_type = 'error';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $msg = 'ອີເມວນີ້ຖືກໃຊ້ງານແລ້ວ';
            $msg_type = 'error';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $role);
            if ($stmt->execute()) {
                $msg = 'ສ້າງຜູ້ໃຊ້ງານສຳເລັດ 🎉';
                $msg_type = 'success';
                $_POST = [];
            } else {
                $msg = 'ເກີດຂໍ້ຜິດພາດ: ' . $stmt->error;
                $msg_type = 'error';
            }
            $stmt->close();
        }
        $check->close();
    }
}

$page_title = 'ສ້າງຜູ້ໃຊ້ໃໝ່';
$active_nav = 'register';
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
    <?php if ($msg_type === 'success'): ?>
        <a href="/src/users/index.php" style="margin-left:auto;color:inherit;font-weight:700;">ເບິ່ງລາຍການຜູ້ໃຊ້ →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:560px;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            ສ້າງຜູ້ໃຊ້ງານໃໝ່
        </div>
        <a href="/src/users/index.php" class="btn btn-secondary btn-sm">ລາຍການຜູ້ໃຊ້</a>
    </div>

    <form method="POST" novalidate>
        <div class="form-group">
            <label>ຊື່ - ນາມສະກຸນ</label>
            <input type="text" name="name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   placeholder="ໃສ່ຊື່ຜູ້ໃຊ້" required>
        </div>
        <div class="form-group">
            <label>ອີເມວ</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="example@domain.com" required>
        </div>
        <div class="form-group">
            <label>ລະຫັດຜ່ານ</label>
            <input type="password" name="password"
                   placeholder="ຢ່າງໜ້ອຍ 8 ຕົວ" minlength="8" required>
        </div>
        <div class="form-group">
            <label>ສິດເຂົ້າໃຊ້ງານ</label>
            <select name="role">
                <option value="user"  <?= (($_POST['role'] ?? '') === 'user')  ? 'selected' : '' ?>>👤 User</option>
                <option value="staff" <?= (($_POST['role'] ?? '') === 'staff') ? 'selected' : '' ?>>🛠️ Staff</option>
                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>⚡ Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            ບັນທຶກຜູ້ໃຊ້ງານ
        </button>
    </form>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../../includes/layout/footer.php';
?>