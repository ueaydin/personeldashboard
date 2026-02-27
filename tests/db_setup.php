<?php
/**
 * SQLite Database Setup for Testing
 */

function setupTestDb($schemaFile, $dbFile) {
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    try {
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = file_get_contents($schemaFile);

        // Basic MySQL to SQLite translation
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
        $sql = preg_replace('/USE .*/i', '', $sql);
        $sql = preg_replace('/INT PRIMARY KEY AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/ENUM\(.*?\)/i', 'TEXT', $sql);
        $sql = preg_replace('/BOOLEAN/i', 'INTEGER', $sql);
        $sql = preg_replace('/DATETIME/i', 'TEXT', $sql);
        $sql = preg_replace('/TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP/i', 'TEXT DEFAULT CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace('/TIMESTAMP DEFAULT CURRENT_TIMESTAMP/i', 'TEXT DEFAULT CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace('/CHARACTER SET \w+/i', '', $sql);
        $sql = preg_replace('/COLLATE \w+/i', '', $sql);
        $sql = preg_replace('/ENGINE=InnoDB/i', '', $sql);

        // Remove foreign key constraints and their references for simplicity in SQLite testing
        $sql = preg_replace('/,?\s*FOREIGN KEY.*?\)/is', '', $sql);

        $sql = preg_replace('/DEFAULT NULL/i', 'NULL', $sql);
        $sql = preg_replace('/UNIQUE KEY \w+ \((.*?)\)/i', 'UNIQUE($1)', $sql);
        $sql = preg_replace('/INDEX \w+ \((.*?)\)/i', '', $sql); // Remove inline indices
        $sql = preg_replace('/CREATE INDEX .*/i', '', $sql); // Remove separate index creation
        $sql = preg_replace('/DECIMAL\(.*?\)/i', 'REAL', $sql);

        // Remove trailing commas that might be left after removing FOREIGN KEYs
        $sql = preg_replace('/,\s*\)/m', "\n)", $sql);

        // Handle multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        // echo "Test database initialized successfully at $dbFile\n";
        return $pdo;
    } catch (PDOException $e) {
        die("Error setting up test database: " . $e->getMessage() . "\n");
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    setupTestDb(__DIR__ . '/../database/schema.sql', __DIR__ . '/test_db.sqlite');
}
