<?php
/**
 * Kimlik Doğrulama İşlemleri
 */

require_once __DIR__ . '/../config.php';

// Oturum başlat
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Kullanıcı giriş yap
function loginUser($username, $password) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, avatar FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Oturum bilgilerini ayarla
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Son giriş zamanını güncelle
        $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        return true;
    }

    return false;
}

// Kullanıcı çıkış yap
function logoutUser() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

// Kullanıcı giriş yapmış mı kontrol et
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Oturum süresini kontrol et
function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
            logoutUser();
            return false;
        }
        // Oturum süresini yenile
        $_SESSION['login_time'] = time();
    }
    return true;
}

// Giriş gerektiren sayfa kontrolü
function requireLogin() {
    startSecureSession();

    if (!isLoggedIn() || !checkSessionTimeout()) {
        header('Location: index.php');
        exit;
    }
}

// API için giriş kontrolü
function requireApiLogin() {
    startSecureSession();

    if (!isLoggedIn() || !checkSessionTimeout()) {
        jsonResponse(['error' => 'Oturum açmanız gerekiyor'], 401);
    }
}

// Mevcut kullanıcı ID'sini al
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Mevcut kullanıcı bilgilerini al
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'avatar' => $_SESSION['avatar']
    ];
}

// Kullanıcı ayarlarını al
function getUserSettings($userId = null) {
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) return [];

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);

    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

// Kullanıcı ayarını güncelle
function updateUserSetting($key, $value, $userId = null) {
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) return false;

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    return $stmt->execute([$userId, $key, $value]);
}

// Şifre değiştir
function changePassword($currentPassword, $newPassword) {
    $userId = getCurrentUserId();
    if (!$userId) return false;

    $pdo = getDBConnection();

    // Mevcut şifreyi doğrula
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return false;
    }

    // Yeni şifreyi hashle ve güncelle
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

    return $updateStmt->execute([$hashedPassword, $userId]);
}

// Yeni kullanıcı kaydet
function registerUser($username, $email, $password, $fullName = '') {
    $pdo = getDBConnection();

    // Kullanıcı adı veya email var mı kontrol et
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->execute([$username, $email]);

    if ($checkStmt->fetch()) {
        return ['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor'];
    }

    // Şifreyi hashle
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Kullanıcıyı ekle
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");

    try {
        $stmt->execute([$username, $email, $hashedPassword, $fullName]);
        $userId = $pdo->lastInsertId();

        // Varsayılan ayarları ekle
        $settingsStmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)");
        $settingsStmt->execute([$userId, 'weather_city', 'Istanbul']);
        $settingsStmt->execute([$userId, 'weather_country', 'TR']);
        $settingsStmt->execute([$userId, 'theme', 'dark']);
        $settingsStmt->execute([$userId, 'language', 'tr']);

        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        error_log("Kayıt hatası: " . $e->getMessage());
        return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu'];
    }
}
