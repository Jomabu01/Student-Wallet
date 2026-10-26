<?php
require_once __DIR__ . '/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_auth(): array
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user = (new User())->findById((int) $_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id']);
        header('Location: login.php');
        exit;
    }

    return $user;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_token_is_valid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
