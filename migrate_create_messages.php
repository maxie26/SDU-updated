<?php
// migrate_create_messages.php
// Creates the `messages` table in the `sdu` database if it does not exist.
// Usage: visit in browser (while XAMPP Apache running) or run via CLI: php migrate_create_messages.php

header('Content-Type: application/json');
// load DB connection
require_once __DIR__ . '/db.php';

try {
    // quick existence check
    $res = $conn->query("SHOW TABLES LIKE 'messages'");
    if ($res === false) throw new Exception($conn->error ?: 'Unknown error');

    if ($res->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Table `messages` already exists']);
        exit;
    }

    $create = "CREATE TABLE IF NOT EXISTS `messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` int(11) NOT NULL,
      `receiver_id` int(11) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `receiver_id` (`receiver_id`),
      KEY `sender_id` (`sender_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    if ($conn->query($create) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Table `messages` created successfully']);
    } else {
        throw new Exception($conn->error ?: 'Failed to create table');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// If running in CLI, optionally print a human-friendly message
if (php_sapi_name() === 'cli') {
    // also echo, since CLI might not show JSON
    echo PHP_EOL;
}

?>
