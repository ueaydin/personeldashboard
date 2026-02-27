<?php
/**
 * Kullanıcı Ayarları API
 */

require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
requireApiLogin();

header('Content-Type: application/json; charset=utf-8');

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();

    switch ($method) {
        case 'GET':
            // Tüm ayarları getir
            $settings = getUserSettings($userId);
            jsonResponse(['settings' => $settings]);
            break;

        case 'POST':
        case 'PUT':
            $data = json_decode(getRawInput(), true);

            if (empty($data)) {
                jsonResponse(['error' => 'Veri bulunamadı'], 400);
            }

            // Hava durumu ayarları
            if (isset($data['weather_city'])) {
                updateUserSetting('weather_city', sanitizeInput($data['weather_city']), $userId);
            }

            if (isset($data['weather_country'])) {
                updateUserSetting('weather_country', strtoupper(sanitizeInput($data['weather_country'])), $userId);
            }

            // Tema ayarı
            if (isset($data['theme'])) {
                updateUserSetting('theme', sanitizeInput($data['theme']), $userId);
            }

            // Dil ayarı
            if (isset($data['language'])) {
                updateUserSetting('language', sanitizeInput($data['language']), $userId);
            }

            // Şifre değiştirme
            if (!empty($data['current_password']) && !empty($data['new_password'])) {
                if (strlen($data['new_password']) < 6) {
                    jsonResponse(['error' => 'Yeni şifre en az 6 karakter olmalıdır'], 400);
                }

                if (!changePassword($data['current_password'], $data['new_password'])) {
                    jsonResponse(['error' => 'Mevcut şifre yanlış'], 400);
                }
            }

            jsonResponse(['success' => true, 'message' => 'Ayarlar güncellendi']);
            break;

        default:
            jsonResponse(['error' => 'Desteklenmeyen HTTP metodu'], 405);
    }

} catch (Exception $e) {
    error_log("Settings API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Bir hata oluştu'], 500);
}
