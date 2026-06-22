<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$level   = (int)($_POST['level'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($level < 1 || $level > 10) {
    echo json_encode(['ok' => false, 'error' => 'Invalid level']);
    exit;
}

$db = get_db();
$db->prepare('INSERT INTO desire_history (user_id, level) VALUES (?, ?)')->execute([$user_id, $level]);

echo json_encode(['ok' => true]);
