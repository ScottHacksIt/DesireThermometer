<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';

$db      = get_db();
$user_id = (int)$_SESSION['user_id'];

$errors  = [];
$success = '';

// ── Handle form actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_request') {
        $target_id = (int)trim($_POST['target_id'] ?? '');

        if ($target_id < 1) {
            $errors[] = 'Please enter a valid Account ID.';
        } elseif ($target_id === $user_id) {
            $errors[] = 'You cannot partner with yourself.';
        } else {
            // Check target exists
            $chk = $db->prepare('SELECT id FROM users WHERE id = ?');
            $chk->execute([$target_id]);
            if (!$chk->fetch()) {
                $errors[] = "No account found with ID $target_id.";
            } else {
                // Check for existing partnership (either direction, any status)
                $dup = $db->prepare(
                    'SELECT id FROM partnerships
                     WHERE ((requester_id = ? AND receiver_id = ?)
                         OR (requester_id = ? AND receiver_id = ?))
                       AND status IN ("pending","accepted")'
                );
                $dup->execute([$user_id, $target_id, $target_id, $user_id]);
                if ($dup->fetch()) {
                    $errors[] = 'A partnership or pending request already exists with that account.';
                } else {
                    $ins = $db->prepare(
                        'INSERT INTO partnerships (requester_id, receiver_id) VALUES (?, ?)'
                    );
                    $ins->execute([$user_id, $target_id]);
                    $success = 'Partner request sent!';
                }
            }
        }
    }

    elseif (in_array($action, ['accept', 'decline'])) {
        $pid    = (int)($_POST['partnership_id'] ?? 0);
        $status = $action === 'accept' ? 'accepted' : 'declined';
        $upd    = $db->prepare(
            'UPDATE partnerships SET status = ? WHERE id = ? AND receiver_id = ?'
        );
        $upd->execute([$status, $pid, $user_id]);
        $success = $action === 'accept' ? 'Partnership accepted!' : 'Request declined.';
    }

    elseif ($action === 'remove_partner') {
        $pid = (int)($_POST['partnership_id'] ?? 0);
        $del = $db->prepare(
            'DELETE FROM partnerships WHERE id = ? AND (requester_id = ? OR receiver_id = ?)'
        );
        $del->execute([$pid, $user_id, $user_id]);
        $success = 'Partnership removed.';
    }
}

// ── Load current state ───────────────────────────────────────────

// Active partnership
$active_stmt = $db->prepare(
    'SELECT p.id,
            IF(p.requester_id = :uid1, p.receiver_id, p.requester_id) AS partner_id,
            u.first_name AS partner_name
     FROM partnerships p
     JOIN users u ON u.id = IF(p.requester_id = :uid2, p.receiver_id, p.requester_id)
     WHERE (p.requester_id = :uid3 OR p.receiver_id = :uid4)
       AND p.status = "accepted"
     LIMIT 1'
);
$active_stmt->execute([':uid1' => $user_id, ':uid2' => $user_id, ':uid3' => $user_id, ':uid4' => $user_id]);
$active_partner = $active_stmt->fetch();

// Pending incoming requests
$incoming_stmt = $db->prepare(
    'SELECT p.id, u.first_name, u.id AS requester_id
     FROM partnerships p
     JOIN users u ON u.id = p.requester_id
     WHERE p.receiver_id = ? AND p.status = "pending"'
);
$incoming_stmt->execute([$user_id]);
$incoming = $incoming_stmt->fetchAll();

// Pending outgoing requests
$outgoing_stmt = $db->prepare(
    'SELECT p.id, u.first_name, u.id AS receiver_id
     FROM partnerships p
     JOIN users u ON u.id = p.receiver_id
     WHERE p.requester_id = ? AND p.status = "pending"'
);
$outgoing_stmt->execute([$user_id]);
$outgoing = $outgoing_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Settings — Desire Thermometer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="partner-page">

<header class="site-header">
    <div class="header-left">
        <span class="logo-small">🌡️ My Desire <span class="script">Thermometer</span></span>
        <span class="user-info">Hi, <strong><?= htmlspecialchars($_SESSION['first_name']) ?></strong> &nbsp;|&nbsp; ID: <strong><?= $user_id ?></strong></span>
    </div>
    <nav class="header-nav">
        <a href="dashboard.php">← Dashboard</a>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="partner-main">
<div class="partner-section">

    <h2>Partner Settings</h2>
    <p style="margin-bottom:1.5rem;color:var(--text-muted);font-size:.9rem">Your Account ID is <strong><?= $user_id ?></strong> — share it so others can send you a request.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul></div>
    <?php endif ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif ?>

    <!-- Current partner -->
    <div class="partner-card">
        <h3>Current Partner</h3>
        <?php if ($active_partner): ?>
            <ul class="request-list">
                <li class="request-item current-partner">
                    <span>💞 <?= htmlspecialchars($active_partner['partner_name']) ?> (ID: <?= $active_partner['partner_id'] ?>)</span>
                    <form method="post">
                        <input type="hidden" name="action" value="remove_partner">
                        <input type="hidden" name="partnership_id" value="<?= $active_partner['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this partnership?')">Remove</button>
                    </form>
                </li>
            </ul>
        <?php else: ?>
            <p style="color:var(--text-muted);font-size:.9rem">You don't have a partner yet.</p>
        <?php endif ?>
    </div>

    <!-- Incoming requests -->
    <?php if ($incoming): ?>
    <div class="partner-card">
        <h3>Incoming Requests</h3>
        <ul class="request-list">
            <?php foreach ($incoming as $req): ?>
                <li class="request-item">
                    <span><?= htmlspecialchars($req['first_name']) ?> (ID: <?= $req['requester_id'] ?>) wants to partner with you.</span>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="partnership_id" value="<?= $req['id'] ?>">
                        <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                        <button type="submit" name="action" value="decline" class="btn btn-sm btn-danger">Decline</button>
                    </form>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <?php endif ?>

    <!-- Outgoing requests -->
    <?php if ($outgoing): ?>
    <div class="partner-card">
        <h3>Sent Requests</h3>
        <ul class="request-list">
            <?php foreach ($outgoing as $req): ?>
                <li class="request-item outgoing">
                    <span>Waiting for <strong><?= htmlspecialchars($req['first_name']) ?></strong> (ID: <?= $req['receiver_id'] ?>) to respond…</span>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <?php endif ?>

    <!-- Send new request -->
    <?php if (!$active_partner): ?>
    <div class="partner-card">
        <h3>Send a Partner Request</h3>
        <form method="post">
            <input type="hidden" name="action" value="send_request">
            <label>Partner's Account ID
                <input type="number" name="target_id" min="1" placeholder="e.g. 42" required autofocus>
            </label>
            <button type="submit" class="btn btn-primary">Send Request</button>
        </form>
    </div>
    <?php endif ?>

</div>
</main>
</body>
</html>
