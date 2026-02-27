<?php
/**
 * Yapılacaklar Listesi API
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
                $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                $todo = $stmt->fetch();

                if ($todo) {
                    jsonResponse($todo);
                } else {
                    jsonResponse(['error' => 'Görev bulunamadı'], 404);
                }
            } else {
                // Filtreleme
                $status = $_GET['status'] ?? null;
                $priority = $_GET['priority'] ?? null;

                $sql = "SELECT * FROM todos WHERE user_id = ?";
                $params = [$userId];

                if ($status) {
                    $sql .= " AND status = ?";
                    $params[] = $status;
                }

                if ($priority) {
                    $sql .= " AND priority = ?";
                    $params[] = $priority;
                }

                // Sıralama: Öncelik > Durum > Tarih
                $sql .= " ORDER BY
                    CASE status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END,
                    CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                    (due_date IS NULL) ASC, due_date ASC,
                    created_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $todos = $stmt->fetchAll();

                // İstatistikler
                $statsStmt = $pdo->prepare("
                    SELECT
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                        COUNT(CASE WHEN priority = 'high' AND status != 'completed' THEN 1 END) as high_priority
                    FROM todos WHERE user_id = ?
                ");
                $statsStmt->execute([$userId]);
                $stats = $statsStmt->fetch();

                jsonResponse([
                    'todos' => $todos,
                    'stats' => $stats
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(getRawInput(), true);

            if (empty($data['title'])) {
                jsonResponse(['error' => 'Görev başlığı gereklidir'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO todos (user_id, title, description, priority, status, due_date, category)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                sanitizeInput($data['title']),
                $data['description'] ?? null,
                $data['priority'] ?? 'medium',
                'pending',
                $data['due_date'] ?? null,
                $data['category'] ?? null
            ]);

            jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Görev başarıyla eklendi'
            ], 201);
            break;

        case 'PUT':
            $data = json_decode(getRawInput(), true);

            if (empty($data['id'])) {
                jsonResponse(['error' => 'Görev ID gereklidir'], 400);
            }

            $checkStmt = $pdo->prepare("SELECT id, status FROM todos WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$data['id'], $userId]);
            $existingTodo = $checkStmt->fetch();

            if (!$existingTodo) {
                jsonResponse(['error' => 'Görev bulunamadı'], 404);
            }

            $updates = [];
            $params = [];

            $allowedFields = ['title', 'description', 'priority', 'status', 'due_date', 'category'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $field === 'title' || $field === 'description' ? sanitizeInput($data[$field]) : $data[$field];
                }
            }

            // Eğer durum "completed" yapılıyorsa completed_at'ı ayarla
            if (isset($data['status']) && $data['status'] === 'completed' && $existingTodo['status'] !== 'completed') {
                $updates[] = "completed_at = NOW()";
            } elseif (isset($data['status']) && $data['status'] !== 'completed') {
                $updates[] = "completed_at = NULL";
            }

            if (empty($updates)) {
                jsonResponse(['error' => 'Güncellenecek alan belirtilmedi'], 400);
            }

            $params[] = $data['id'];
            $params[] = $userId;

            $sql = "UPDATE todos SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['success' => true, 'message' => 'Görev güncellendi']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;

            if (!$id) {
                jsonResponse(['error' => 'Görev ID gereklidir'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Görev silindi']);
            } else {
                jsonResponse(['error' => 'Görev bulunamadı'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Desteklenmeyen HTTP metodu'], 405);
    }

} catch (PDOException $e) {
    error_log("Todos API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Veritabanı hatası oluştu'], 500);
}
