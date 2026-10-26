<?php
$pageTitle = $pageTitle ?? 'Student Wallet';
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · Student Wallet</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">Student<span>Wallet</span></div>
        <nav>
            <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="income.php" class="<?= $active === 'income' ? 'active' : '' ?>">Income</a>
            <a href="expenses.php" class="<?= $active === 'expenses' ? 'active' : '' ?>">Expenses</a>
            <a href="budget.php" class="<?= $active === 'budget' ? 'active' : '' ?>">Budget</a>
        </nav>
        <div class="user-chip">
            <?= htmlspecialchars(trim($currentUser['first_name'] . ' ' . $currentUser['last_name']), ENT_QUOTES, 'UTF-8') ?><br>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="logout-link" type="submit">Log out</button>
            </form>
        </div>
    </aside>
    <main class="content">
