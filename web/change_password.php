<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';

$errors  = [];
$success = false;
$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $db   = get_db();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user_id]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — Desire Thermometer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1 class="logo-title">My Desire<br><span class="script">Thermometer</span> 🌡️</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">Password changed successfully.</div>
        <p style="text-align:center"><a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a></p>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>

        <form method="post" novalidate>
            <h2>Change Password</h2>
            <label>Current Password
                <input type="password" name="current_password" required autofocus>
            </label>
            <label>New Password
                <input type="password" name="new_password" required>
            </label>
            <label>Confirm New Password
                <input type="password" name="confirm_password" required>
            </label>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
        <p class="auth-link"><a href="dashboard.php">← Back to Dashboard</a></p>
    <?php endif ?>
</div>
</body>
</html>
