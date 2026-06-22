<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$db      = get_db();
$user_id = (int)$_SESSION['user_id'];

// Find accepted partner
$stmt = $db->prepare(
    'SELECT IF(p.requester_id = :uid1, p.receiver_id, p.requester_id) AS partner_id
     FROM partnerships p
     WHERE (p.requester_id = :uid2 OR p.receiver_id = :uid3)
       AND p.status = "accepted"
     LIMIT 1'
);
$stmt->execute([':uid1' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'No partner']);
    exit;
}

$partner_id = (int)$row['partner_id'];

// Get partner's most recent desire level
$lvl_stmt = $db->prepare(
    'SELECT level FROM desire_history WHERE user_id = ? ORDER BY set_at DESC LIMIT 1'
);
$lvl_stmt->execute([$partner_id]);
$lvl_row = $lvl_stmt->fetch();

if (!$lvl_row) {
    echo json_encode(['ok' => false, 'error' => 'Partner has not set a level yet']);
    exit;
}

$level = (int)$lvl_row['level'];

// Get partner's name for that level
$name_stmt = $db->prepare(
    'SELECT name FROM desire_scale WHERE user_id = ? AND level = ?'
);
$name_stmt->execute([$partner_id, $level]);
$name_row  = $name_stmt->fetch();
$level_name = $name_row ? $name_row['name'] : '';

echo json_encode(['ok' => true, 'level' => $level, 'level_name' => $level_name]);
