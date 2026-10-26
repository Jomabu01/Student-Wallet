<?php
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $user = (new User())->login($email, $password);
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            unset($_SESSION['csrf_token']);
            header('Location: dashboard.php');
            exit;
        }

        $error = 'The email or password is incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in · Student Wallet</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=20261021-1">
</head>
<body>
<main class="auth-wrap">
    <section class="auth-card">
        <div class="brand">Student<span>Wallet</span></div>
        <p class="eyebrow">Welcome back</p>
        <h1>Log in</h1>
        <p class="lead">Sign in to manage your student wallet.</p>

        <?php if ($error !== ''): ?>
            <div class="error-msg" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-control">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                        <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Log in</button>
        </form>
        <div class="foot-link">New to Student Wallet? <a href="register.php">Create an account</a></div>
    </section>
</main>
<script src="../assets/js/password-toggle.js?v=20261021-1" defer></script>
</body>
</html>
