<?php
/**
 * Ödemeler API
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
            // Tüm ödemeleri getir veya belirli bir ödeme
            $id = $_GET['id'] ?? null;

            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                $payment = $stmt->fetch();

                if ($payment) {
                    jsonResponse($payment);
                } else {
                    jsonResponse(['error' => 'Ödeme bulunamadı'], 404);
                }
            } else {
                // Filtreleme seçenekleri
                $status = $_GET['status'] ?? null;
                $month = $_GET['month'] ?? null;

                $sql = "SELECT * FROM payments WHERE user_id = ?";
                $params = [$userId];

                if ($status) {
                    $sql .= " AND status = ?";
                    $params[] = $status;
                }

                if ($month) {
                    $sql .= " AND DATE_FORMAT(due_date, '%Y-%m') = ?";
                    $params[] = $month;
                }

                $sql .= " ORDER BY due_date ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $payments = $stmt->fetchAll();

                // İstatistikler
                $statsStmt = $pdo->prepare("
                    SELECT
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total,
                        SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_total
                    FROM payments
                    WHERE user_id = ?
                ");
                $statsStmt->execute([$userId]);
                $stats = $statsStmt->fetch();

                jsonResponse([
                    'payments' => $payments,
                    'stats' => $stats
                ]);
            }
            break;

        case 'POST':
            // Yeni ödeme ekle
            $data = json_decode(getRawInput(), true);

            if (empty($data['title']) || empty($data['amount']) || empty($data['due_date'])) {
                jsonResponse(['error' => 'Başlık, tutar ve vade tarihi gereklidir'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, title, amount, currency, due_date, category, status, is_recurring, recurring_period, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                sanitizeInput($data['title']),
                floatval($data['amount']),
                $data['currency'] ?? 'TRY',
                $data['due_date'],
                $data['category'] ?? 'diger',
                'pending',
                $data['is_recurring'] ?? false,
                $data['recurring_period'] ?? null,
                $data['notes'] ?? null
            ]);

            jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Ödeme başarıyla eklendi'
            ], 201);
            break;

        case 'PUT':
            // Ödeme güncelle
            $data = json_decode(getRawInput(), true);

            if (empty($data['id'])) {
                jsonResponse(['error' => 'Ödeme ID gereklidir'], 400);
            }

            // Ödemenin kullanıcıya ait olduğunu doğrula
            $checkStmt = $pdo->prepare("SELECT id FROM payments WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$data['id'], $userId]);

            if (!$checkStmt->fetch()) {
                jsonResponse(['error' => 'Ödeme bulunamadı'], 404);
            }

            $updates = [];
            $params = [];

            $allowedFields = ['title', 'amount', 'currency', 'due_date', 'category', 'status', 'is_recurring', 'recurring_period', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $field === 'title' ? sanitizeInput($data[$field]) : $data[$field];
                }
            }

            if (empty($updates)) {
                jsonResponse(['error' => 'Güncellenecek alan belirtilmedi'], 400);
            }

            $params[] = $data['id'];
            $params[] = $userId;

            $sql = "UPDATE payments SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['success' => true, 'message' => 'Ödeme güncellendi']);
            break;

        case 'DELETE':
            // Ödeme sil
            $id = $_GET['id'] ?? null;

            if (!$id) {
                jsonResponse(['error' => 'Ödeme ID gereklidir'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Ödeme silindi']);
            } else {
                jsonResponse(['error' => 'Ödeme bulunamadı'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Desteklenmeyen HTTP metodu'], 405);
    }

} catch (PDOException $e) {
    error_log("Payments API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Veritabanı hatası oluştu'], 500);
}
