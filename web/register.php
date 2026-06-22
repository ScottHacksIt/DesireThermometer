<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/config/db.php';

$errors = [];
$success_id = null;

$defaults = [
    1  => 'To feel understood',
    2  => 'Quality time together',
    3  => 'Affection or cuddling',
    4  => 'Emotional closeness',
    5  => 'Longing for connection',
    6  => 'Tenderness & romantic attraction',
    7  => 'Wanting more time together',
    8  => 'Kissing & full-body touch',
    9  => 'Passionate kissing & massage',
    10 => 'Sexual intimacy',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';
    $levels     = $_POST['levels'] ?? [];

    if ($first_name === '') {
        $errors[] = 'First name is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    for ($i = 1; $i <= 10; $i++) {
        if (trim($levels[$i] ?? '') === '') {
            $errors[] = "Level $i name is required.";
        }
    }

    if (empty($errors)) {
        $db   = get_db();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $db->beginTransaction();
        $stmt = $db->prepare('INSERT INTO users (first_name, password_hash) VALUES (?, ?)');
        $stmt->execute([$first_name, $hash]);
        $user_id = (int) $db->lastInsertId();

        $ins = $db->prepare('INSERT INTO desire_scale (user_id, level, name) VALUES (?, ?, ?)');
        for ($i = 1; $i <= 10; $i++) {
            $ins->execute([$user_id, $i, trim($levels[$i])]);
        }
        $db->commit();

        $success_id = $user_id;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Desire Thermometer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1 class="logo-title">My Desire<br><span class="script">Thermometer</span> 🌡️</h1>

    <?php if ($success_id): ?>
        <div class="alert alert-success">
            <p>Account created! Your unique Account ID is:</p>
            <p class="account-id-display"><?= $success_id ?></p>
            <p>Share this ID with your partner so they can send you a partnering request.</p>
            <a href="index.php" class="btn btn-primary">Sign In</a>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>

        <form method="post" novalidate>
            <h2>Create Your Account</h2>

            <label>First Name
                <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required autofocus>
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <label>Confirm Password
                <input type="password" name="confirm" required>
            </label>

            <h3>Your Desire Scale</h3>
            <p class="hint">Name each level of desire (1 = lowest, 10 = highest). These are yours — edit them to reflect your experience.</p>

            <div class="scale-grid">
                <?php for ($i = 10; $i >= 1; $i--): ?>
                    <div class="scale-row">
                        <span class="scale-num level-color-<?= $i ?>"><?= $i ?></span>
                        <input
                            type="text"
                            name="levels[<?= $i ?>]"
                            value="<?= htmlspecialchars($_POST['levels'][$i] ?? $defaults[$i]) ?>"
                            maxlength="100"
                            required
                        >
                    </div>
                <?php endfor ?>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
        <p class="auth-link">Already have an account? <a href="index.php">Sign in</a></p>
    <?php endif ?>
</div>
</body>
</html>
