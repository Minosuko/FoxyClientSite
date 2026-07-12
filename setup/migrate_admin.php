<?php
/**
 * Admin & Ban System Migration
 * Run this script once to add the required columns to existing databases.
 * Usage: php migrate_admin.php
 */

$config = require __DIR__ . '/../config/database.php';
$mysqli = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Running admin & ban system migration...\n";

$migrations = [
    "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER totp_enabled",
    "ALTER TABLE users ADD COLUMN banned TINYINT(1) NOT NULL DEFAULT 0 AFTER role",
    "ALTER TABLE users ADD COLUMN ban_reason TEXT DEFAULT NULL AFTER banned",
    "ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL AFTER ban_reason",
    "ALTER TABLE users ADD COLUMN banned_by INT DEFAULT NULL AFTER banned_at",
];

foreach ($migrations as $sql) {
    if ($mysqli->query($sql)) {
        echo "  OK: $sql\n";
    } else {
        if ($mysqli->errno === 1060) {
            echo "  SKIP (already exists): $sql\n";
        } else {
            echo "  ERROR: " . $mysqli->error . "\n";
        }
    }
}

// Set the first user as admin if no admin exists
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$adminCount = $result->fetch_assoc()['count'];

if ($adminCount === 0) {
    $result = $mysqli->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $firstUser = $result->fetch_assoc();
        $stmt = $mysqli->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->bind_param("i", $firstUser['id']);
        if ($stmt->execute()) {
            echo "  Promoted user ID {$firstUser['id']} to admin (first user).\n";
        }
    }
}

echo "Migration complete.\n";
