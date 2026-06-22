<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = trim($_POST['account_id'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!ctype_digit($account_id) || (int)$account_id < 1) {
        $errors[] = 'Please enter a valid Account ID.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $db   = get_db();
        $stmt = $db->prepare('SELECT id, first_name, password_hash FROM users WHERE id = ?');
        $stmt->execute([(int)$account_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid Account ID or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Desire Thermometer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1 class="logo-title">My Desire<br><span class="script">Thermometer</span> 🌡️</h1>
    <p class="tagline">Desire exists on a spectrum. There is no right or wrong place to be.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul>
        </div>
    <?php endif ?>

    <form method="post" novalidate>
        <h2>Sign In</h2>
        <label>Account ID
            <input type="number" name="account_id" value="<?= htmlspecialchars($_POST['account_id'] ?? '') ?>" min="1" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Sign In</button>
    </form>

    <p class="auth-link">New here? <a href="register.php">Create an account</a></p>
</div>
</body>
</html>
