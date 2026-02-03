<?php
/**
 * Kişisel Dashboard - Giriş Sayfası
 */

// Kurulum kontrolü - install.lock yoksa kuruluma yönlendir
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'includes/auth.php';

startSecureSession();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre gereklidir';
        } elseif (loginUser($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre';
        }
    } elseif ($action === 'register') {
        $username = sanitizeInput($_POST['reg_username'] ?? '');
        $email = sanitizeInput($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $fullName = sanitizeInput($_POST['reg_fullname'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Tüm alanları doldurun';
        } elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır';
        } else {
            $result = registerUser($username, $email, $password, $fullName);
            if ($result['success']) {
                $success = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f59e0b;
            --bg-dark: #0f0f1a;
            --bg-card: rgba(255, 255, 255, 0.05);
            --bg-card-hover: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border: rgba(255, 255, 255, 0.1);
            --success: #10b981;
            --error: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 70%);
            animation: gradientMove 15s ease infinite;
            z-index: 0;
        }

        @keyframes gradientMove {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }

        /* Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--primary), transparent);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, var(--secondary), transparent);
            bottom: -50px;
            right: -50px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, var(--success), transparent);
            top: 50%;
            left: 50%;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -50px) scale(1.1); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(-50px, -30px) scale(1.05); }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 48px;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                inset 0 1px 1px rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 35px 60px -15px rgba(0, 0, 0, 0.6),
                inset 0 1px 1px rgba(255, 255, 255, 0.08);
        }

        /* Logo & Title */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.5);
            position: relative;
            overflow: hidden;
        }

        .logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .logo svg {
            width: 36px;
            height: 36px;
            fill: white;
            position: relative;
            z-index: 1;
        }

        .login-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 32px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 4px;
        }

        .tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px -5px rgba(99, 102, 241, 0.5);
        }

        .tab:not(.active):hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        /* Form */
        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.6);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Messages */
        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .message-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .message svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .login-footer p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 520px) {
            .login-card {
                padding: 32px 24px;
            }

            .login-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M3 9h18"/>
                        <path d="M9 21V9"/>
                    </svg>
                </div>
                <h1 class="login-title"><?php echo APP_NAME; ?></h1>
                <p class="login-subtitle">Hayatınızı organize edin</p>
            </div>

            <?php if ($error): ?>
            <div class="message message-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="message message-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('login')">Giriş Yap</button>
                <button class="tab" onclick="switchTab('register')">Kayıt Ol</button>
            </div>

            <!-- Login Form -->
            <div class="form-panel active" id="login-panel">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı veya E-posta</label>
                        <input type="text" name="username" class="form-input" placeholder="kullanici@ornek.com" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn-submit">Giriş Yap</button>
                </form>
            </div>

            <!-- Register Form -->
            <div class="form-panel" id="register-panel">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="reg_fullname" class="form-input" placeholder="Ad Soyad">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="reg_username" class="form-input" placeholder="kullaniciadi" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="reg_email" class="form-input" placeholder="kullanici@ornek.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="reg_password" class="form-input" placeholder="En az 6 karakter" required minlength="6">
                    </div>

                    <button type="submit" class="btn-submit">Kayıt Ol</button>
                </form>
            </div>

            <div class="login-footer">
                <p>&copy; 2024 <?php echo APP_NAME; ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tab + '-panel').classList.add('active');
        }
    </script>
</body>
</html>
