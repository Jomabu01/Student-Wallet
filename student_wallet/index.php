<?php
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . (isset($_SESSION['user_id']) ? 'public/dashboard.php' : 'public/login.php'));
exit;
