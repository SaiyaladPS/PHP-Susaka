<?php
$host = "db";
$user = "root";
$pass = "96778932";
$db   = "not_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs to prevent SQL Injection
    $name     = $conn->real_escape_string(trim($_POST["name"]));
    $email    = $conn->real_escape_string(trim($_POST["email"]));
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role     = $conn->real_escape_string($_POST["role"]);

    // Validate email
    if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $message = "ອີເມວບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່";
        $message_type = "error";
    } else {
        // Use prepared statement for safety
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $message = "ບັນທືກຂໍ້ມູນສຳເລັດ 🎉";
            $message_type = "success";
        } else {
            if ($conn->errno === 1062) {
                $message = "ອີເມວນີ້ຖືກໃຊ້ງານແລ້ວ ກະລຸນາໃຊ້ອີເມວອື່ນ";
            } else {
                $message = "ເກີດຂໍ້ຜິດພາດ: " . $stmt->error;
            }
            $message_type = "error";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພຶ່ມຜູ້ໃຊ້ງານ</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            --radius: 14px;
            --transition: 0.2s ease;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* Background effects */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            left: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(ellipse, rgba(108,99,255,0.12) 0%, transparent 70%);
            pointer-events: none;
            animation: floatBg 8s ease-in-out infinite alternate;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(ellipse, rgba(99,200,255,0.07) 0%, transparent 70%);
            pointer-events: none;
            animation: floatBg 10s ease-in-out infinite alternate-reverse;
        }

        @keyframes floatBg {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(3%, 5%) scale(1.05); }
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 44px;
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03),
                0 32px 80px rgba(0,0,0,0.5),
                0 0 60px var(--accent-glow);
            animation: slideUp 0.5s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Decorative top line */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            border-radius: 999px;
        }

        .card-header {
            margin-bottom: 36px;
            text-align: center;
        }

        .icon-wrap {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px var(--accent-glow);
        }

        .icon-wrap svg {
            width: 26px;
            height: 26px;
            fill: white;
        }

        h2 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        .subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Alert messages */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 28px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(34,197,94,0.25);
            color: var(--success);
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid rgba(248,113,113,0.25);
            color: var(--error);
        }

        .alert svg { flex-shrink: 0; }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            transition: color var(--transition);
        }

        input, select {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
            appearance: none;
            -webkit-appearance: none;
        }

        input::placeholder { color: var(--text-muted); }

        input:focus, select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input:focus + .input-icon,
        select:focus + .input-icon {
            color: var(--accent-light);
        }

        /* Select arrow */
        .select-wrap::after {
            content: '';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 0; height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid var(--text-muted);
            pointer-events: none;
        }

        select option {
            background: var(--surface-2);
        }

        /* Role badges */
        .role-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .role-option {
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .role-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 14px 10px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all var(--transition);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            user-select: none;
        }

        .role-label:hover {
            border-color: rgba(108,99,255,0.4);
            color: var(--text);
        }

        .role-option input:checked + .role-label {
            background: rgba(108,99,255,0.15);
            border-color: var(--accent);
            color: var(--accent-light);
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .role-label .role-icon {
            font-size: 1.3rem;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent) 0%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 28px;
            position: relative;
            overflow: hidden;
            transition: transform var(--transition), box-shadow var(--transition), opacity var(--transition);
            box-shadow: 0 4px 20px var(--accent-glow);
            letter-spacing: 0.02em;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 100%);
            opacity: 0;
            transition: opacity var(--transition);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }

        .btn-submit:hover::after { opacity: 1; }
        .btn-submit:active { transform: translateY(0); }

        .btn-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0 0;
        }

        .footer-note {
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 16px;
        }

        .footer-note span {
            color: var(--accent-light);
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <h2>ເພຶ່ມຜູ້ໃຊ້ງານ</h2>
        <p class="subtitle">ຕື່ມຂໍ້ມູນດ້ານລຸ່ມເພື່ອສ້າງບັນຊີໃໝ່</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <?php if ($message_type === 'success'): ?>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php else: ?>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <?php endif; ?>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>

        <div class="form-group">
            <label for="name">ຊື່ - ນາມສະກຸນ</label>
            <div class="input-wrap">
                <input type="text" id="name" name="name"
                       placeholder="ກະລຸນາໃສ່ຊື່ຂອງທ່ານ"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required>
                <svg class="input-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>

        <div class="form-group">
            <label for="email">ອີເມວ</label>
            <div class="input-wrap">
                <input type="email" id="email" name="email"
                       placeholder="example@domain.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
                <svg class="input-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <div class="form-group">
            <label for="password">ລະຫັດຜ່ານ</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password"
                       placeholder="ຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ"
                       minlength="8"
                       required>
                <svg class="input-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>

        <div class="form-group">
            <label>ສິດເຂົ້າໃຊ້ງານ</label>
            <div class="role-grid">
                <div class="role-option">
                    <input type="radio" id="role_user" name="role" value="user"
                           <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : '' ?>>
                    <label for="role_user" class="role-label">
                        <span class="role-icon">👤</span>
                        User
                    </label>
                </div>
                <div class="role-option">
                    <input type="radio" id="role_staff" name="role" value="staff"
                           <?= (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'checked' : '' ?>>
                    <label for="role_staff" class="role-label">
                        <span class="role-icon">🛠️</span>
                        Staff
                    </label>
                </div>
                <div class="role-option">
                    <input type="radio" id="role_admin" name="role" value="admin"
                           <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : '' ?>>
                    <label for="role_admin" class="role-label">
                        <span class="role-icon">⚡</span>
                        Admin
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <span class="btn-inner">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                ບັນທຶກຜູ້ໃຊ້ງານ
            </span>
        </button>

    </form>

    <div class="divider"></div>
    <p class="footer-note">ຂໍ້ມູນທັງໝົດຖືກເຂົ້າລະຫັດດ້ວຍ <span>bcrypt</span> ຢ່າງປອດໄພ</p>
</div>

</body>
</html>