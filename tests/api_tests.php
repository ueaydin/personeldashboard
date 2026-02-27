<?php
/**
 * Integration Tests for API Endpoints
 */

define('TEST_MODE', true);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../config.php';

// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION[CSRF_TOKEN_NAME] = 'test_csrf_token';

// Re-initialize test DB
require_once __DIR__ . '/db_setup.php';
setupTestDb(__DIR__ . '/../database/schema.sql', __DIR__ . '/test_db.sqlite');

$pdo = getDBConnection();

// Create a test user if not exists
$checkUser = $pdo->query("SELECT id FROM users WHERE id = 1")->fetch();
if (!$checkUser) {
    $pdo->exec("INSERT INTO users (id, username, email, password, full_name) VALUES (1, 'testuser', 'test@example.com', 'hash', 'Test User')");
}

// Ensure the session is initialized for the tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['login_time'] = time();
$_SESSION[CSRF_TOKEN_NAME] = 'test_csrf_token';

function runApiTest($file, $method, $query = [], $input = null, $csrfToken = 'test_csrf_token') {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = $query;
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrfToken;
    // For Apache/PHP environment sometimes it's under REDIRECT_ or other names, but usually HTTP_ prefix for headers.
    // PHP's $_SERVER['HTTP_X_CSRF_TOKEN'] should match 'X-CSRF-Token' header.
    $GLOBALS['TEST_INPUT'] = $input !== null ? json_encode($input) : null;
    $GLOBALS['API_RESPONSE'] = null;

    // Use output buffering to catch any accidental echo
    ob_start();
    try {
        include __DIR__ . '/../' . $file;
    } catch (Exception $e) {
        // Log error if needed
    }
    ob_end_clean();

    return $GLOBALS['API_RESPONSE'];
}

echo "Testing Todos API...\n";

// POST
$newTodo = ['title' => 'Test Task', 'priority' => 'high'];
$res = runApiTest('api/todos.php', 'POST', [], $newTodo);
if ($res && $res['status'] === 201 && $res['data']['success']) {
    echo "PASS: Create Todo\n";
    $todoId = $res['data']['id'];
} else {
    echo "FAIL: Create Todo.\n";
    var_dump($res);
}

// GET
$res = runApiTest('api/todos.php', 'GET');
if ($res && $res['status'] === 200 && count($res['data']['todos']) > 0) {
    echo "PASS: List Todos\n";
} else {
    echo "FAIL: List Todos\n";
    var_dump($res);
}

// CSRF Test
$res = runApiTest('api/todos.php', 'POST', [], $newTodo, 'wrong_token');
if ($res && $res['status'] === 403) {
    echo "PASS: CSRF Protection working\n";
} else {
    echo "FAIL: CSRF Protection failed. Status: " . ($res['status'] ?? 'unknown') . "\n";
    echo "Response: " . json_encode($res['data']) . "\n";
}

echo "\nTesting Notes API...\n";

// POST
$newNote = ['title' => 'Test Note', 'content' => 'Test Content'];
$res = runApiTest('api/notes.php', 'POST', [], $newNote);
if ($res && $res['status'] === 201) {
    echo "PASS: Create Note\n";
    $noteId = $res['data']['id'];
} else {
    echo "FAIL: Create Note\n";
    var_dump($res);
}

// GET
$res = runApiTest('api/notes.php', 'GET');
if ($res && $res['status'] === 200 && count($res['data']['notes']) > 0) {
    echo "PASS: List Notes\n";
} else {
    echo "FAIL: List Notes\n";
}

// DELETE
$res = runApiTest('api/notes.php', 'DELETE', ['id' => $noteId]);
if ($res && $res['status'] === 200) {
    echo "PASS: Delete Note\n";
} else {
    echo "FAIL: Delete Note\n";
}
