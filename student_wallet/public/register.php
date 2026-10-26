<?php
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$firstName = '';
$lastName = '';
$email = '';
$phoneNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim(is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '');
    $lastName = trim(is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '');
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $phoneNumber = trim(is_string($_POST['phone_number'] ?? null) ? $_POST['phone_number'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $passwordConfirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } elseif (
        $firstName === ''
        || $lastName === ''
        || strlen($firstName) > 255
        || strlen($lastName) > 255
    ) {
        $errors[] = 'Enter a first and last name (up to 255 characters each).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Enter a valid email address (up to 255 characters).';
    }

    if (!preg_match('/^[0-9+(). -]{7,30}$/D', $phoneNumber)) {
        $errors[] = 'Enter a phone number between 7 and 30 characters.';
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Choose a password between 8 and 72 characters.';
    } elseif ($password !== $passwordConfirmation) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $errors[] = 'An account with that email address already exists.';
        } else {
            $db = Database::getInstance()->getConnection();
            try {
                $db->beginTransaction();
                $userId = $userModel->create([
                    'first_name'   => $firstName,
                    'last_name'    => $lastName,
                    'email'        => $email,
                    'password'     => $password,
                    'phone_number' => $phoneNumber,
                ]);
                (new Wallet())->create(['current_balance' => 0.00, 'user_id' => $userId]);
                $db->commit();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                unset($_SESSION['csrf_token']);
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                if ((string) $exception->getCode() === '23000') {
                    $errors[] = 'An account with that email address already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account · Student Wallet</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=20261021-1">
</head>
<body>
<main class="auth-wrap">
    <section class="auth-card auth-card-wide">
        <div class="brand">Student<span>Wallet</span></div>
        <p class="eyebrow">Get started</p>
        <h1>Create your account</h1>
        <p class="lead">Your account and personal wallet will be ready after sign-up.</p>

        <?php if ($errors): ?>
            <div class="error-msg" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="grid-2">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" maxlength="255" autocomplete="given-name" required value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" maxlength="255" autocomplete="family-name" required value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="phone_number">Phone number</label>
                <input id="phone_number" name="phone_number" type="tel" maxlength="30" autocomplete="tel" required value="<?= htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="password">Password (8–72 characters)</label>
                    <div class="password-control">
                        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/></svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-control">
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/></svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-shape" d="M1 12s3.8-7 11-7 11 7 11 7-3.8 7-11 7S1 12 1 12Z"/><circle class="eye-iris" cx="12" cy="12" r="5"/><circle class="eye-pupil" cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Create account</button>
        </form>
        <div class="foot-link">Already have an account? <a href="login.php">Log in</a></div>
    </section>
</main>
<script src="../assets/js/password-toggle.js?v=20261021-1" defer></script>
</body>
</html>
