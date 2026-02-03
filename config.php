<?php
/**
 * Kişisel Dashboard - Yapılandırma Dosyası
 */

// Hata raporlama (geliştirme için)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Oturum ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'personal_dashboard');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Uygulama ayarları
define('APP_NAME', 'Kişisel Dashboard');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/personeldashboard');

// Hava durumu API (OpenWeatherMap - ücretsiz)
// https://openweathermap.org/api adresinden ücretsiz API key alabilirsiniz
define('WEATHER_API_KEY', 'YOUR_OPENWEATHERMAP_API_KEY');
define('WEATHER_API_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Güvenlik
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 86400); // 24 saat

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
            die(json_encode(['error' => 'Veritabanı bağlantısı kurulamadı']));
        }
    }

    return $pdo;
}

// CSRF token oluştur
function generateCSRFToken() {
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Güvenli input temizleme
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Tarih formatla
function formatDate($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

// Para formatla
function formatMoney($amount, $currency = 'TRY') {
    $symbols = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'];
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2, ',', '.');
}
