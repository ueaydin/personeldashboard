<?php
/**
 * Hava Durumu API
 * OpenWeatherMap ücretsiz API'sini kullanır
 */

require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
requireApiLogin();

header('Content-Type: application/json; charset=utf-8');

$city = $_GET['city'] ?? 'Istanbul';
$country = $_GET['country'] ?? 'TR';

// API anahtarı kontrolü
if (WEATHER_API_KEY === 'YOUR_OPENWEATHERMAP_API_KEY') {
    // Demo veri döndür
    jsonResponse([
        'success' => true,
        'demo' => true,
        'data' => [
            'city' => $city,
            'country' => $country,
            'temp' => rand(15, 30),
            'feels_like' => rand(14, 32),
            'humidity' => rand(40, 80),
            'description' => ['Güneşli', 'Parçalı Bulutlu', 'Bulutlu', 'Hafif Yağmurlu'][rand(0, 3)],
            'icon' => ['01d', '02d', '03d', '10d'][rand(0, 3)],
            'wind_speed' => rand(5, 25)
        ]
    ]);
}

try {
    // OpenWeatherMap API çağrısı
    $url = WEATHER_API_URL . "?q=" . urlencode($city . "," . $country) . "&appid=" . WEATHER_API_KEY . "&units=metric&lang=tr";

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        jsonResponse(['error' => 'Hava durumu verisi alınamadı'], 500);
    }

    $data = json_decode($response, true);

    if (isset($data['cod']) && $data['cod'] != 200) {
        jsonResponse([
            'error' => $data['message'] ?? 'Hava durumu verisi alınamadı',
            'code' => $data['cod']
        ], 400);
    }

    // Veriyi formatla
    $weatherData = [
        'city' => $data['name'],
        'country' => $data['sys']['country'],
        'temp' => round($data['main']['temp']),
        'feels_like' => round($data['main']['feels_like']),
        'humidity' => $data['main']['humidity'],
        'description' => $data['weather'][0]['description'],
        'icon' => $data['weather'][0]['icon'],
        'wind_speed' => round($data['wind']['speed'] * 3.6), // m/s to km/h
        'sunrise' => date('H:i', $data['sys']['sunrise']),
        'sunset' => date('H:i', $data['sys']['sunset'])
    ];

    jsonResponse([
        'success' => true,
        'data' => $weatherData
    ]);

} catch (Exception $e) {
    error_log("Weather API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Hava durumu verisi alınırken hata oluştu'], 500);
}
