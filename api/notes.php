<?php
/**
 * Notlar API
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
                $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                $note = $stmt->fetch();

                if ($note) {
                    jsonResponse($note);
                } else {
                    jsonResponse(['error' => 'Not bulunamadı'], 404);
                }
            } else {
                // Tüm notları getir (sabitlenmiş olanlar önce)
                $stmt = $pdo->prepare("
                    SELECT * FROM notes
                    WHERE user_id = ?
                    ORDER BY is_pinned DESC, updated_at DESC
                ");
                $stmt->execute([$userId]);
                $notes = $stmt->fetchAll();

                // İstatistikler
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*) as total, COUNT(CASE WHEN is_pinned = 1 THEN 1 END) as pinned
                    FROM notes WHERE user_id = ?
                ");
                $countStmt->execute([$userId]);
                $stats = $countStmt->fetch();

                jsonResponse([
                    'notes' => $notes,
                    'stats' => $stats
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['title'])) {
                jsonResponse(['error' => 'Başlık gereklidir'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO notes (user_id, title, content, color, is_pinned)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                sanitizeInput($data['title']),
                $data['content'] ?? '',
                $data['color'] ?? '#fbbf24',
                $data['is_pinned'] ?? false
            ]);

            jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Not başarıyla eklendi'
            ], 201);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id'])) {
                jsonResponse(['error' => 'Not ID gereklidir'], 400);
            }

            $checkStmt = $pdo->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$data['id'], $userId]);

            if (!$checkStmt->fetch()) {
                jsonResponse(['error' => 'Not bulunamadı'], 404);
            }

            $updates = [];
            $params = [];

            $allowedFields = ['title', 'content', 'color', 'is_pinned'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $field === 'title' || $field === 'content' ? sanitizeInput($data[$field]) : $data[$field];
                }
            }

            if (empty($updates)) {
                jsonResponse(['error' => 'Güncellenecek alan belirtilmedi'], 400);
            }

            $params[] = $data['id'];
            $params[] = $userId;

            $sql = "UPDATE notes SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['success' => true, 'message' => 'Not güncellendi']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;

            if (!$id) {
                jsonResponse(['error' => 'Not ID gereklidir'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Not silindi']);
            } else {
                jsonResponse(['error' => 'Not bulunamadı'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Desteklenmeyen HTTP metodu'], 405);
    }

} catch (PDOException $e) {
    error_log("Notes API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Veritabanı hatası oluştu'], 500);
}
