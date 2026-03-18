<?php
require_once '../../connection/conn.php';
require_once '../../connection/logger.php';

// ບັນທຶກການເຂົ້າຊົມໜ້ານີ້
session_start();
logVisitor($conn, $_SESSION['user_id'] ?? null);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $user_id = $_POST['user_id'];
        $account_number = $_POST['account_number'];
        $balance = $_POST['balance'] ?? 0.00;
        $status = $_POST['status'] ?? 'active';
        $currency = $_POST['currency'] ?? 'LAK';
        
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance, status, currency) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $account_number, $balance, $status, $currency);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'];
        $user_id = $_POST['user_id'];
        $account_number = $_POST['account_number'];
        $balance = $_POST['balance'];
        $status = $_POST['status'];
        $currency = $_POST['currency'];
        
        $stmt = $conn->prepare("UPDATE accounts SET user_id=?, account_number=?, balance=?, status=?, currency=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("isdssi", $user_id, $account_number, $balance, $status, $currency, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    }
}

// Get all accounts
$accounts = $conn->query("SELECT a.*, u.name as user_name FROM accounts a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC");

// Get all users for dropdown
$users = $conn->query("SELECT id, name FROM users ORDER BY name");

// Get account for editing
$edit_account = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການບັນຊີ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans Lao', sans-serif;
        }

        :root {
            --bg: #0d0f14;
            --surface: #14171f;
            --surface-2: #1c2030;
            --border: rgba(255,255,255,0.07);
            --accent: #6c63ff;
            --accent-glow: rgba(108, 99, 255, 0.35);
            --accent-light: #a89dff;
            --text: #e8eaf0;
            --text-muted: #7a7f96;
            --success: #22c55e;
            --success-bg: rgba(34,197,94,0.1);
            --error: #f87171;
            --error-bg: rgba(248,113,113,0.1);
            --warning: #fbbf24;
            --radius: 14px;
            --transition: 0.2s ease;
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -30%; left: -20%;
            width: 70%; height: 70%;
            background: radial-gradient(ellipse, rgba(108,99,255,0.12) 0%, transparent 70%);
            pointer-events: none;
            animation: floatBg 8s ease-in-out infinite alternate;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -30%; right: -20%;
            width: 60%; height: 60%;
            background: radial-gradient(ellipse, rgba(99,200,255,0.07) 0%, transparent 70%);
            pointer-events: none;
            animation: floatBg 10s ease-in-out infinite alternate-reverse;
        }

        @keyframes floatBg {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(3%, 5%) scale(1.05); }
        }

        .grid-dots {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 32px 80px rgba(0,0,0,0.5);
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 8px 32px rgba(0,0,0,0.3);
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 13px 16px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: 'Noto Sans Lao', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        input:focus, select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Noto Sans Lao', sans-serif;
            margin-right: 10px;
            transition: transform var(--transition), box-shadow var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 20px var(--accent-glow);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 30px var(--accent-glow);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(34,197,94,0.3);
        }

        .btn-success:hover {
            box-shadow: 0 8px 30px rgba(34,197,94,0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--error) 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(248,113,113,0.3);
        }

        .btn-danger:hover {
            box-shadow: 0 8px 30px rgba(248,113,113,0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #f59e0b 100%);
            color: #1a1a1a;
            box-shadow: 0 4px 20px rgba(251,191,36,0.3);
        }

        .btn-warning:hover {
            box-shadow: 0 8px 30px rgba(251,191,36,0.4);
        }

        .btn-secondary {
            background: var(--surface-2);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--surface-2);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.06em;
        }

        tr:hover {
            background: rgba(255,255,255,0.02);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-active {
            color: var(--success);
            font-weight: 600;
        }

        .status-inactive {
            color: var(--error);
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions button {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            body { padding: 12px; }
            .header, .card { padding: 20px; }
            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.2rem; }
            table { font-size: 0.85rem; }
            th, td { padding: 12px 8px; }
        }
    </style>
</head>
<body>
    <div class="grid-dots"></div>
    
    <div class="container">
        <div class="header">
            <h1>
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                </svg>
                ຈັດການບັນຊີ
            </h1>
        </div>
        
        <!-- Form for Create/Update -->
        <div class="card">
            <h2>
                <?php if ($edit_account): ?>
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    ແກ້ໄຂບັນຊີ
                <?php else: ?>
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    ເພີ່ມບັນຊີໃໝ່
                <?php endif; ?>
            </h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_account ? 'update' : 'create'; ?>">
                <?php if ($edit_account): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_account['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ຜູ້ໃຊ້</label>
                        <select name="user_id" required>
                            <option value="">-- ເລືອກຜູ້ໃຊ້ --</option>
                            <?php 
                            $users->data_seek(0);
                            while ($user = $users->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $user['id']; ?>" 
                                    <?php echo ($edit_account && $edit_account['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>ເລກທີ່ບັນຊີ</label>
                        <input type="text" name="account_number" 
                               value="<?php echo $edit_account ? htmlspecialchars($edit_account['account_number']) : ''; ?>" 
                               placeholder="ໃສ່ເລກທີ່ບັນຊີ"
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ຍອດເງິນ</label>
                        <input type="number" step="0.01" name="balance" 
                               value="<?php echo $edit_account ? $edit_account['balance'] : '0.00'; ?>" 
                               placeholder="0.00"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>ສະຖານະ</label>
                        <select name="status" required>
                            <option value="active" <?php echo ($edit_account && $edit_account['status'] == 'active') ? 'selected' : ''; ?>>ໃຊ້ງານ (Active)</option>
                            <option value="inactive" <?php echo ($edit_account && $edit_account['status'] == 'inactive') ? 'selected' : ''; ?>>ປິດການໃຊ້ງານ (Inactive)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>ສະກຸນເງິນ</label>
                    <select name="currency" required>
                        <option value="LAK" <?php echo ($edit_account && $edit_account['currency'] == 'LAK') ? 'selected' : ''; ?>>LAK (ກີບ)</option>
                        <option value="THB" <?php echo ($edit_account && $edit_account['currency'] == 'THB') ? 'selected' : ''; ?>>THB (ບາດ)</option>
                        <option value="USD" <?php echo ($edit_account && $edit_account['currency'] == 'USD') ? 'selected' : ''; ?>>USD (ໂດລາ)</option>
                    </select>
                </div>
                
                <div style="margin-top: 24px;">
                    <button type="submit" class="<?php echo $edit_account ? 'btn-success' : 'btn-primary'; ?>">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php echo $edit_account ? 'ບັນທຶກການແກ້ໄຂ' : 'ເພີ່ມບັນຊີ'; ?>
                    </button>
                    <?php if ($edit_account): ?>
                        <a href="index.php"><button type="button" class="btn-secondary">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            ຍົກເລີກ
                        </button></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Display all accounts -->
        <div class="card">
            <h2>
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                ລາຍການບັນຊີທັງໝົດ
            </h2>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ຜູ້ໃຊ້</th>
                            <th>ເລກທີ່ບັນຊີ</th>
                            <th>ຍອດເງິນ</th>
                            <th>ສະກຸນເງິນ</th>
                            <th>ສະຖານະ</th>
                            <th>ສ້າງເມື່ອ</th>
                            <th>ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($accounts->num_rows > 0): ?>
                            <?php while ($account = $accounts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $account['id']; ?></td>
                                    <td><?php echo htmlspecialchars($account['user_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                    <td><?php echo number_format($account['balance'], 2); ?></td>
                                    <td><?php echo $account['currency']; ?></td>
                                    <td class="status-<?php echo $account['status']; ?>">
                                        <?php echo $account['status'] == 'active' ? 'ໃຊ້ງານ' : 'ປິດການໃຊ້ງານ'; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($account['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="?edit=<?php echo $account['id']; ?>">
                                            <button class="btn-warning">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                ແກ້ໄຂ
                                            </button>
                                        </a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບບັນຊີນີ້?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" class="btn-danger">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                ລຶບ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z"/>
                                        </svg>
                                        <p>ບໍ່ມີຂໍ້ມູນບັນຊີ</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>