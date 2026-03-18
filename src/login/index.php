<?php
session_start();

include '../../connection/conn.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "ກະລຸນາໃສ່ອີເມວ ແລະ ລະຫັດຜ່ານ";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "ຮູບແບບອີເມວບໍ່ຖືກຕ້ອງ";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"]   = $row["id"];
                $_SESSION["user_name"] = $row["name"];
                $_SESSION["user_role"] = $row["role"];
                // header("Location: dashboard.php");
                // exit;
                $message = "ເຂົ້າສູ່ລະບົບສຳເລັດ! ຍິນດີຕ້ອນຮັບ " . htmlspecialchars($row["name"]) . " 👋";
                $message_type = "success";
            } else {
                $message = "ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ ກະລຸນາລອງໃໝ່";
                $message_type = "error";
            }
        } else {
            $message = "ບໍ່ພົບບັນຊີນີ້ໃນລະບົບ";
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
    <title>ເຂົ້າສູ່ລະບົບ</title>
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
            --radius: 14px;
            --transition: 0.2s ease;
        }

        .noto-sans-lao-<uniquifier> {
            font-family: "Noto Sans Lao", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
            font-variation-settings:
                "wdth" 100;
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
            overflow-x: hidden;
        }
        
        @media (min-width: 640px) {
            body { padding: 24px; }
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

        /* Grid dots pattern */
        .grid-dots {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 20px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03),
                0 32px 80px rgba(0,0,0,0.5),
                0 0 60px var(--accent-glow);
            animation: slideUp 0.5s cubic-bezier(0.16,1,0.3,1) both;
        }
        
        @media (min-width: 480px) {
            .card {
                padding: 36px 32px;
                border-radius: 20px;
            }
        }
        
        @media (min-width: 640px) {
            .card {
                padding: 48px 44px;
                border-radius: 24px;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        /* Header */
        .card-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        @media (min-width: 480px) {
            .card-header { margin-bottom: 32px; }
        }
        
        @media (min-width: 640px) {
            .card-header { margin-bottom: 36px; }
        }

        .logo-wrap {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 8px 32px var(--accent-glow);
            position: relative;
        }
        
        @media (min-width: 640px) {
            .logo-wrap {
                width: 64px;
                height: 64px;
                border-radius: 20px;
                margin-bottom: 22px;
            }
        }

        .logo-wrap::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 21px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
        }

        .logo-wrap svg {
            width: 26px;
            height: 26px;
            fill: white;
        }
        
        @media (min-width: 640px) {
            .logo-wrap svg {
                width: 30px;
                height: 30px;
            }
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        
        @media (min-width: 480px) {
            h2 { font-size: 1.5rem; }
        }
        
        @media (min-width: 640px) {
            h2 { font-size: 1.65rem; }
        }

        .subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
        }
        
        @media (min-width: 480px) {
            .subtitle { font-size: 0.85rem; }
        }
        
        @media (min-width: 640px) {
            .subtitle { font-size: 0.875rem; }
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 24px;
            animation: fadeIn 0.3s ease;
        }
        
        @media (min-width: 480px) {
            .alert {
                gap: 10px;
                padding: 13px 16px;
                font-size: 0.85rem;
                margin-bottom: 26px;
            }
        }
        
        @media (min-width: 640px) {
            .alert {
                font-size: 0.875rem;
                margin-bottom: 28px;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
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
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.8rem;
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

        input {
            width: 100%;
            padding: 12px 40px 12px 38px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        
        @media (min-width: 480px) {
            input {
                padding: 13px 42px 13px 40px;
                font-size: 0.92rem;
            }
        }
        
        @media (min-width: 640px) {
            input {
                padding: 13px 44px 13px 42px;
                font-size: 0.95rem;
            }
        }

        input::placeholder { color: var(--text-muted); }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        /* Toggle password visibility */
        .toggle-pw {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color var(--transition);
        }

        .toggle-pw:hover { color: var(--accent-light); }

        /* Remember & Forgot */
        .form-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        @media (min-width: 400px) {
            .form-footer-row { flex-wrap: nowrap; }
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            padding: 0;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .remember-wrap span {
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        
        @media (min-width: 480px) {
            .remember-wrap span { font-size: 0.8rem; }
        }
        
        @media (min-width: 640px) {
            .remember-wrap span { font-size: 0.83rem; }
        }

        .forgot-link {
            font-size: 0.78rem;
            color: var(--accent-light);
            text-decoration: none;
            transition: color var(--transition);
            white-space: nowrap;
        }
        
        @media (min-width: 480px) {
            .forgot-link { font-size: 0.8rem; }
        }
        
        @media (min-width: 640px) {
            .forgot-link { font-size: 0.83rem; }
        }

        .forgot-link:hover { color: white; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent) 0%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 22px;
            position: relative;
            overflow: hidden;
            transition: transform var(--transition), box-shadow var(--transition);
            box-shadow: 0 4px 20px var(--accent-glow);
            letter-spacing: 0.02em;
        }
        
        @media (min-width: 640px) {
            .btn-submit {
                padding: 15px;
                font-size: 1rem;
                margin-top: 26px;
            }
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
        
        @media (hover: none) {
            .btn-submit:hover {
                transform: none;
                box-shadow: 0 4px 20px var(--accent-glow);
            }
        }

        .btn-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Divider */
        .divider-text {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 0.78rem;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Register link */
        .register-link {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        @media (min-width: 480px) {
            .register-link { font-size: 0.85rem; }
        }
        
        @media (min-width: 640px) {
            .register-link { font-size: 0.875rem; }
        }

        .register-link a {
            color: var(--accent-light);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition);
        }

        .register-link a:hover { color: white; }

        /* Shake animation on error */
        .shake {
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90%  { transform: translateX(-2px); }
            20%, 80%  { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60%  { transform: translateX(4px); }
        }
    </style>
</head>
<body>

<div class="grid-dots"></div>

<div class="card <?= $message_type === 'error' ? 'shake' : '' ?>">

    <div class="card-header">
        <div class="logo-wrap">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
            </svg>
        </div>
        <h2>ເຂົ້າສູ່ລະບົບ</h2>
        <p class="subtitle">ໃສ່ຂໍ້ມູນເພື່ອເຂົ້າໃຊ້ງານລະບົບ</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <?php if ($message_type === 'success'): ?>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php else: ?>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>

        <div class="form-group">
            <label for="email">ອີເມວ</label>
            <div class="input-wrap">
                <input type="email" id="email" name="email"
                       placeholder="example@domain.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email"
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
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required>
                <svg class="input-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <button type="button" class="toggle-pw" onclick="togglePassword()" title="ສະແດງ/ເຊື່ອງລະຫັດຜ່ານ" aria-label="Toggle password">
                    <svg id="eye-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <div class="form-footer-row" style="margin-top:10px;">
                <label class="remember-wrap">
                    <input type="checkbox" name="remember" value="1">
                    <span>ຈື່ຂ້ອຍໄວ້</span>
                </label>
                <a href="#" class="forgot-link">ລືມລະຫັດຜ່ານ?</a>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <span class="btn-inner">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                ເຂົ້າສູ່ລະບົບ
            </span>
        </button>

    </form>

    <div class="divider-text">ຫຼື</div>

    <p class="register-link">
        ຍັງບໍ່ມີບັນຊີ?
        <a href="../resigter/index.php">ສ້າງບັນຊີໃໝ່</a>
    </p>

</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        icon.innerHTML = isHidden
            ? `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`
            : `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    }
</script>

</body>
</html>