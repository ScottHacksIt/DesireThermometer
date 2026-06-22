<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';

$db      = get_db();
$user_id = (int) $_SESSION['user_id'];

// Load user's desire scale
$scale_rows = $db->prepare('SELECT level, name FROM desire_scale WHERE user_id = ? ORDER BY level');
$scale_rows->execute([$user_id]);
$scale = [];
foreach ($scale_rows->fetchAll() as $row) {
    $scale[(int)$row['level']] = $row['name'];
}

// Current desire level (most recent history entry)
$cur_stmt = $db->prepare('SELECT level FROM desire_history WHERE user_id = ? ORDER BY set_at DESC LIMIT 1');
$cur_stmt->execute([$user_id]);
$cur_row    = $cur_stmt->fetch();
$current_level = $cur_row ? (int)$cur_row['level'] : 5;

// Find accepted partnership (user may be requester or receiver)
$partner_stmt = $db->prepare(
    'SELECT p.id, p.requester_id, p.receiver_id,
            u.first_name AS partner_name,
            u.id         AS partner_id
     FROM partnerships p
     JOIN users u ON u.id = IF(p.requester_id = ?, p.receiver_id, p.requester_id)
     WHERE (p.requester_id = ? OR p.receiver_id = ?)
       AND p.status = "accepted"
     LIMIT 1'
);
$partner_stmt->execute([$user_id, $user_id, $user_id]);
$partner = $partner_stmt->fetch();

// Pending incoming partner requests
$pending_stmt = $db->prepare(
    'SELECT p.id, u.first_name, u.id AS requester_id
     FROM partnerships p
     JOIN users u ON u.id = p.requester_id
     WHERE p.receiver_id = ? AND p.status = "pending"'
);
$pending_stmt->execute([$user_id]);
$pending_requests = $pending_stmt->fetchAll();

// Handle accept / decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'] ?? '';
    $partnership_id = (int)($_POST['partnership_id'] ?? 0);

    if (in_array($action, ['accept', 'decline']) && $partnership_id > 0) {
        $status = $action === 'accept' ? 'accepted' : 'declined';
        $upd    = $db->prepare(
            'UPDATE partnerships SET status = ? WHERE id = ? AND receiver_id = ?'
        );
        $upd->execute([$status, $partnership_id, $user_id]);
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Desire Thermometer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">

<header class="site-header">
    <div class="header-left">
        <span class="logo-small">🌡️ My Desire <span class="script">Thermometer</span></span>
        <span class="user-info">Hi, <strong><?= htmlspecialchars($_SESSION['first_name']) ?></strong> &nbsp;|&nbsp; ID: <strong><?= $user_id ?></strong></span>
    </div>
    <nav class="header-nav">
        <a href="partner.php">Partner</a>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="dashboard-main">

    <?php if ($pending_requests): ?>
        <div class="notifications">
            <?php foreach ($pending_requests as $req): ?>
                <div class="notification-card">
                    <span><strong><?= htmlspecialchars($req['first_name']) ?></strong> (ID: <?= $req['requester_id'] ?>) wants to partner with you.</span>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="partnership_id" value="<?= $req['id'] ?>">
                        <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                        <button type="submit" name="action" value="decline" class="btn btn-sm btn-danger">Decline</button>
                    </form>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <section class="thermometer-section">
        <div class="thermometer-wrap">

            <!-- Scale labels on the left -->
            <div class="scale-labels">
                <?php for ($i = 10; $i >= 1; $i--): ?>
                    <div class="scale-label <?= $i === $current_level ? 'active' : '' ?>" data-level="<?= $i ?>">
                        <span class="scale-num level-color-<?= $i ?>"><?= $i ?></span>
                        <span class="scale-name"><?= htmlspecialchars($scale[$i] ?? '') ?></span>
                    </div>
                <?php endfor ?>
            </div>

            <!-- Thermometer graphic -->
            <div class="thermometer-container" id="thermometer" title="Click or drag to set your desire level">
                <div class="thermo-tube">
                    <div class="thermo-fill" id="thermoFill"></div>
                    <div class="thermo-indicator" id="thermoIndicator"></div>
                    <div class="thermo-tick-marks">
                        <?php for ($i = 10; $i >= 1; $i--): ?>
                            <div class="thermo-tick" data-level="<?= $i ?>"></div>
                        <?php endfor ?>
                    </div>
                </div>
                <div class="thermo-bulb">
                    <span class="thermo-bulb-icon">❤️</span>
                </div>
            </div>

            <!-- Current level display + partner button -->
            <div class="current-level-panel">
                <div class="current-level-badge" id="currentLevelBadge">
                    <span class="current-level-num" id="currentLevelNum"><?= $current_level ?></span>
                    <span class="current-level-name" id="currentLevelName"><?= htmlspecialchars($scale[$current_level] ?? '') ?></span>
                </div>
                <p class="level-saved-msg" id="savedMsg" style="display:none">✓ Saved</p>

                <?php if ($partner): ?>
                    <button class="btn btn-partner" id="partnerBtn" data-partner-id="<?= $partner['partner_id'] ?>">
                        💞 See <?= htmlspecialchars($partner['partner_name']) ?>'s Level
                    </button>
                    <div class="partner-reveal" id="partnerReveal" style="display:none">
                        <h3><?= htmlspecialchars($partner['partner_name']) ?>'s desire right now:</h3>
                        <div class="partner-level-badge">
                            <span id="partnerLevelNum">—</span>
                            <span id="partnerLevelName">—</span>
                        </div>
                        <button class="btn btn-sm" id="closePartnerBtn">Close</button>
                    </div>
                <?php else: ?>
                    <p class="no-partner-hint"><a href="partner.php">Partner with someone</a> to see their level.</p>
                <?php endif ?>
            </div>

        </div>
    </section>
</main>

<script>
// Pass PHP data to JS
const SCALE      = <?= json_encode($scale) ?>;
const INIT_LEVEL = <?= $current_level ?>;
</script>
<script src="assets/js/thermometer.js"></script>
</body>
</html>
