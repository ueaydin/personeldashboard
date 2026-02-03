<?php
/**
 * Kişisel Dashboard - Kurulum Sihirbazı
 * Session kullanmadan çalışır - Hostinger uyumlu
 */

// Kurulum zaten yapılmış mı kontrol et
if (file_exists(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Veritabanı bilgilerini şifrele/çöz (basit base64)
function encodeDbConfig($config) {
    return base64_encode(json_encode($config));
}

function decodeDbConfig($encoded) {
    $decoded = base64_decode($encoded);
    return $decoded ? json_decode($decoded, true) : null;
}

// POST'tan veya GET'ten db_config al
$dbConfigEncoded = $_POST['db_config'] ?? $_GET['db_config'] ?? '';
$dbConfig = $dbConfigEncoded ? decodeDbConfig($dbConfigEncoded) : null;

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'test_db') {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';
        $name = trim($_POST['db_name'] ?? '');

        if (empty($user) || empty($name)) {
            $error = 'Veritabanı kullanıcı adı ve veritabanı adı gereklidir';
        } else {
            try {
                $pdo = new PDO("mysql:host=$host", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);

                // Veritabanı var mı kontrol et, yoksa oluştur
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$name`");

                $dbConfig = [
                    'host' => $host,
                    'user' => $user,
                    'pass' => $pass,
                    'name' => $name
                ];
                $dbConfigEncoded = encodeDbConfig($dbConfig);

                header('Location: install.php?step=2&db_config=' . urlencode($dbConfigEncoded));
                exit;

            } catch (PDOException $e) {
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'Access denied') !== false) {
                    $error = 'Veritabanı erişimi reddedildi. Kullanıcı adı veya şifreyi kontrol edin.';
                } elseif (strpos($errorMsg, 'Unknown MySQL server host') !== false || strpos($errorMsg, 'Connection refused') !== false) {
                    $error = 'Veritabanı sunucusuna bağlanılamadı. Host adresini kontrol edin.';
                } else {
                    $error = 'Veritabanı bağlantısı başarısız: ' . $errorMsg;
                }
            }
        }
    }

    if ($action === 'create_tables') {
        if (!$dbConfig) {
            header('Location: install.php?step=1');
            exit;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                $dbConfig['user'],
                $dbConfig['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Tabloları tek tek oluştur
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                avatar VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                setting_key VARCHAR(50) NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_setting (user_id, setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'TRY',
                due_date DATE NOT NULL,
                category VARCHAR(50),
                status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
                is_recurring BOOLEAN DEFAULT FALSE,
                recurring_period ENUM('weekly', 'monthly', 'yearly') DEFAULT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                title VARCHAR(150) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                event_time TIME DEFAULT NULL,
                end_date DATE DEFAULT NULL,
                end_time TIME DEFAULT NULL,
                event_type ENUM('birthday', 'anniversary', 'meeting', 'reminder', 'holiday', 'other') DEFAULT 'other',
                color VARCHAR(7) DEFAULT '#6366f1',
                is_recurring BOOLEAN DEFAULT FALSE,
                recurring_period ENUM('weekly', 'monthly', 'yearly') DEFAULT NULL,
                reminder_before INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                title VARCHAR(150) NOT NULL,
                content TEXT,
                color VARCHAR(7) DEFAULT '#fbbf24',
                is_pinned BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS todos (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
                due_date DATE DEFAULT NULL,
                category VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                completed_at TIMESTAMP DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            header('Location: install.php?step=3&db_config=' . urlencode($dbConfigEncoded));
            exit;

        } catch (PDOException $e) {
            $error = 'Tablolar oluşturulurken hata: ' . $e->getMessage();
        }
    }

    if ($action === 'create_admin') {
        if (!$dbConfig) {
            header('Location: install.php?step=1');
            exit;
        }

        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminName = trim($_POST['admin_name'] ?? '');
        $weatherKey = trim($_POST['weather_key'] ?? '');
        $weatherCity = trim($_POST['weather_city'] ?? 'Istanbul');

        if (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
            $error = 'Kullanıcı adı, e-posta ve şifre gereklidir';
        } elseif (strlen($adminPass) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Geçerli bir e-posta adresi girin';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // Admin kullanıcısını oluştur
                $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminName]);
                $userId = $pdo->lastInsertId();

                // Varsayılan ayarları ekle
                $settingsStmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)");
                $settingsStmt->execute([$userId, 'weather_city', $weatherCity]);
                $settingsStmt->execute([$userId, 'weather_country', 'TR']);
                $settingsStmt->execute([$userId, 'theme', 'dark']);
                $settingsStmt->execute([$userId, 'language', 'tr']);

                // Config dosyasını oluştur
                $configContent = '<?php
/**
 * Kişisel Dashboard - Yapılandırma Dosyası
 * Bu dosya kurulum sihirbazı tarafından oluşturulmuştur.
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set(\'display_errors\', 0);
ini_set(\'log_errors\', 1);

// Zaman dilimi
date_default_timezone_set(\'Europe/Istanbul\');

// Oturum ayarları
ini_set(\'session.cookie_httponly\', 1);
ini_set(\'session.use_only_cookies\', 1);
ini_set(\'session.cookie_secure\', isset($_SERVER[\'HTTPS\']));

// Veritabanı ayarları
define(\'DB_HOST\', \'' . addslashes($dbConfig['host']) . '\');
define(\'DB_NAME\', \'' . addslashes($dbConfig['name']) . '\');
define(\'DB_USER\', \'' . addslashes($dbConfig['user']) . '\');
define(\'DB_PASS\', \'' . addslashes($dbConfig['pass']) . '\');
define(\'DB_CHARSET\', \'utf8mb4\');

// Uygulama ayarları
define(\'APP_NAME\', \'Kişisel Dashboard\');
define(\'APP_VERSION\', \'1.0.0\');
define(\'APP_URL\', \'' . addslashes((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'])) . '\');

// Hava durumu API (OpenWeatherMap)
define(\'WEATHER_API_KEY\', \'' . addslashes($weatherKey ?: 'YOUR_OPENWEATHERMAP_API_KEY') . '\');
define(\'WEATHER_API_URL\', \'https://api.openweathermap.org/data/2.5/weather\');

// Güvenlik
define(\'CSRF_TOKEN_NAME\', \'csrf_token\');
define(\'SESSION_LIFETIME\', 86400);

// PDO veritabanı bağlantısı
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
            die(json_encode([\'error\' => \'Veritabanı bağlantısı kurulamadı\']));
        }
    }

    return $pdo;
}

// CSRF token oluştur
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF token doğrula
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// JSON yanıt gönder
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header(\'Content-Type: application/json; charset=utf-8\');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Güvenli input temizleme
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map(\'sanitizeInput\', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, \'UTF-8\');
}

// Tarih formatla
function formatDate($date, $format = \'d.m.Y\') {
    return date($format, strtotime($date));
}

// Para formatla
function formatMoney($amount, $currency = \'TRY\') {
    $symbols = [\'TRY\' => \'₺\', \'USD\' => \'$\', \'EUR\' => \'€\'];
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2, \',\', \'.\');
}
';

                file_put_contents(__DIR__ . '/config.php', $configContent);

                // Kurulum kilit dosyası oluştur
                file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s') . "\nKurulum tamamlandı.\nAdmin: " . $adminUser);

                header('Location: install.php?step=4');
                exit;

            } catch (PDOException $e) {
                $error = 'Admin kullanıcısı oluşturulurken hata: ' . $e->getMessage();
            }
        }
    }
}

// step=2 veya step=3'te db_config yoksa step=1'e dön
if (($step == 2 || $step == 3) && !$dbConfig) {
    header('Location: install.php?step=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - Kişisel Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --success: #10b981;
            --error: #ef4444;
            --bg-dark: #0a0a0f;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
        }

        .bg-gradient {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(245, 158, 11, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .install-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 600px;
        }

        .install-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .install-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--primary), #f59e0b);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo svg {
            width: 36px;
            height: 36px;
            stroke: white;
        }

        .install-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .install-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 40px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            background: var(--glass-bg);
            border: 2px solid var(--glass-border);
            color: var(--text-muted);
            position: relative;
        }

        .step.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .step.completed {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 8px;
            height: 2px;
            background: var(--glass-border);
        }

        .step.completed:not(:last-child)::after {
            background: var(--success);
        }

        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .form-input::placeholder { color: var(--text-muted); }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .form-hint a {
            color: var(--primary-light);
            text-decoration: none;
        }

        .btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.6);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
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

        .complete-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .complete-icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
        }

        .info-box {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }

        .info-box h4 {
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: var(--primary-light);
        }

        .info-box p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .info-box code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }

        @media (max-width: 520px) {
            .install-card { padding: 32px 24px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>

    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M3 9h18"/>
                        <path d="M9 21V9"/>
                    </svg>
                </div>
                <h1 class="install-title">Kurulum Sihirbazı</h1>
                <p class="install-subtitle">Kişisel Dashboard kurulumuna hoş geldiniz</p>
            </div>

            <div class="steps">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
            </div>

            <?php if ($error): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; margin-bottom: 24px; text-align: center;">Veritabanı Ayarları</h2>

            <form method="POST">
                <input type="hidden" name="action" value="test_db">

                <div class="form-group">
                    <label class="form-label">Veritabanı Sunucusu</label>
                    <input type="text" name="db_host" class="form-input" value="localhost" required>
                    <p class="form-hint">Genellikle "localhost" veya hosting'inizin verdiği adres</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="db_user" class="form-input" required placeholder="root">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="db_pass" class="form-input" placeholder="Şifre">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Veritabanı Adı</label>
                    <input type="text" name="db_name" class="form-input" required placeholder="personal_dashboard">
                    <p class="form-hint">Veritabanı yoksa otomatik oluşturulur</p>
                </div>

                <button type="submit" class="btn">Bağlantıyı Test Et ve Devam Et</button>
            </form>

            <?php elseif ($step == 2): ?>
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; margin-bottom: 24px; text-align: center;">Veritabanı Tabloları</h2>

            <div class="message message-success">Veritabanı bağlantısı başarılı!</div>

            <p style="color: var(--text-secondary); margin-bottom: 24px; text-align: center;">
                Şimdi gerekli tabloları oluşturacağız. Bu işlem birkaç saniye sürebilir.
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="create_tables">
                <input type="hidden" name="db_config" value="<?php echo htmlspecialchars($dbConfigEncoded); ?>">
                <button type="submit" class="btn">Tabloları Oluştur</button>
            </form>

            <?php elseif ($step == 3): ?>
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; margin-bottom: 24px; text-align: center;">Admin Hesabı Oluştur</h2>

            <div class="message message-success">Tablolar başarıyla oluşturuldu!</div>

            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                <input type="hidden" name="db_config" value="<?php echo htmlspecialchars($dbConfigEncoded); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı *</label>
                        <input type="text" name="admin_user" class="form-input" required placeholder="admin">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="admin_name" class="form-input" placeholder="İsminiz">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">E-posta *</label>
                    <input type="email" name="admin_email" class="form-input" required placeholder="admin@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Şifre *</label>
                    <input type="password" name="admin_pass" class="form-input" required placeholder="En az 6 karakter" minlength="6">
                </div>

                <hr style="border: none; border-top: 1px solid var(--glass-border); margin: 30px 0;">

                <h3 style="font-size: 1rem; margin-bottom: 16px; color: var(--text-secondary);">Hava Durumu Ayarları (İsteğe bağlı)</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Şehir</label>
                        <input type="text" name="weather_city" class="form-input" value="Istanbul" placeholder="Istanbul">
                    </div>
                    <div class="form-group">
                        <label class="form-label">API Anahtarı</label>
                        <input type="text" name="weather_key" class="form-input" placeholder="OpenWeatherMap API Key">
                    </div>
                </div>
                <p class="form-hint">
                    Ücretsiz API anahtarı için: <a href="https://openweathermap.org/api" target="_blank">openweathermap.org/api</a>
                    <br>API anahtarı olmadan demo veriler gösterilir.
                </p>

                <button type="submit" class="btn">Kurulumu Tamamla</button>
            </form>

            <?php elseif ($step == 4): ?>
            <div style="text-align: center;">
                <div class="complete-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>

                <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; margin-bottom: 16px;">Kurulum Tamamlandı!</h2>

                <p style="color: var(--text-secondary); margin-bottom: 24px;">
                    Kişisel Dashboard başarıyla kuruldu. Artık giriş yapabilirsiniz.
                </p>

                <div class="info-box">
                    <h4>Güvenlik Uyarısı</h4>
                    <p>Güvenlik için <code>install.php</code> dosyasını silmenizi öneririz.</p>
                    <p>Bir <code>install.lock</code> dosyası oluşturuldu, bu da kurulumun tekrar çalışmasını engeller.</p>
                </div>

                <a href="index.php" class="btn btn-success" style="display: inline-block; text-decoration: none; margin-top: 30px;">
                    Giriş Sayfasına Git
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
