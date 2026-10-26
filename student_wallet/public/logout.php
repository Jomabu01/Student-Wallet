<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid logout request.');
}

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: login.php');
exit;
