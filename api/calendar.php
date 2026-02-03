<?php
/**
 * Takvim Etkinlikleri API
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
            $id = $_GET['id'] ?? null;

            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                $event = $stmt->fetch();

                if ($event) {
                    jsonResponse($event);
                } else {
                    jsonResponse(['error' => 'Etkinlik bulunamadı'], 404);
                }
            } else {
                // Tarih aralığı filtreleme
                $startDate = $_GET['start'] ?? date('Y-m-01');
                $endDate = $_GET['end'] ?? date('Y-m-t');
                $upcoming = $_GET['upcoming'] ?? null;

                if ($upcoming) {
                    // Yaklaşan etkinlikler (önümüzdeki 30 gün)
                    $stmt = $pdo->prepare("
                        SELECT * FROM calendar_events
                        WHERE user_id = ?
                        AND event_date >= CURDATE()
                        AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY event_date ASC, event_time ASC
                        LIMIT 10
                    ");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT * FROM calendar_events
                        WHERE user_id = ?
                        AND event_date BETWEEN ? AND ?
                        ORDER BY event_date ASC, event_time ASC
                    ");
                    $stmt->execute([$userId, $startDate, $endDate]);
                }

                $events = $stmt->fetchAll();

                // Etkinlik sayısını al
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*) as total,
                           COUNT(CASE WHEN event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as this_week
                    FROM calendar_events WHERE user_id = ?
                ");
                $countStmt->execute([$userId]);
                $stats = $countStmt->fetch();

                jsonResponse([
                    'events' => $events,
                    'stats' => $stats
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['title']) || empty($data['event_date'])) {
                jsonResponse(['error' => 'Başlık ve tarih gereklidir'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO calendar_events (user_id, title, description, event_date, event_time, event_type, color, is_recurring, recurring_period)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                sanitizeInput($data['title']),
                $data['description'] ?? null,
                $data['event_date'],
                $data['event_time'] ?? null,
                $data['event_type'] ?? 'other',
                $data['color'] ?? '#6366f1',
                $data['is_recurring'] ?? false,
                $data['is_recurring'] ? 'yearly' : null
            ]);

            jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Etkinlik başarıyla eklendi'
            ], 201);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id'])) {
                jsonResponse(['error' => 'Etkinlik ID gereklidir'], 400);
            }

            $checkStmt = $pdo->prepare("SELECT id FROM calendar_events WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$data['id'], $userId]);

            if (!$checkStmt->fetch()) {
                jsonResponse(['error' => 'Etkinlik bulunamadı'], 404);
            }

            $updates = [];
            $params = [];

            $allowedFields = ['title', 'description', 'event_date', 'event_time', 'event_type', 'color', 'is_recurring', 'recurring_period'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $field === 'title' || $field === 'description' ? sanitizeInput($data[$field]) : $data[$field];
                }
            }

            if (empty($updates)) {
                jsonResponse(['error' => 'Güncellenecek alan belirtilmedi'], 400);
            }

            $params[] = $data['id'];
            $params[] = $userId;

            $sql = "UPDATE calendar_events SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['success' => true, 'message' => 'Etkinlik güncellendi']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;

            if (!$id) {
                jsonResponse(['error' => 'Etkinlik ID gereklidir'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Etkinlik silindi']);
            } else {
                jsonResponse(['error' => 'Etkinlik bulunamadı'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Desteklenmeyen HTTP metodu'], 405);
    }

} catch (PDOException $e) {
    error_log("Calendar API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Veritabanı hatası oluştu'], 500);
}
