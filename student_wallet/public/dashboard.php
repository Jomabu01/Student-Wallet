<?php
require_once __DIR__ . '/../includes/auth.php';
$currentUser = require_auth();
$userId = (int) $currentUser['user_id'];

$walletModel   = new Wallet();
$incomeModel   = new Income();
$expenseModel  = new Expense();
$budgetModel   = new Budget();
$categoryModel = new Category();

$wallets = $walletModel->findByUserId($userId);
$wallet  = $wallets[0] ?? null;

if (!$wallet) {
    $walletId = $walletModel->create(['current_balance' => 0.00, 'user_id' => $userId]);
    $wallet = $walletModel->findById($walletId);
}

$balance = (float) $wallet['current_balance'];
$walletId = (int) $wallet['wallet_id'];

$incomes  = $incomeModel->findByWalletId($walletId);
$expenses = $expenseModel->findByWalletId($walletId);

$activity = [];
foreach ($incomes as $i) {
    $activity[] = ['type' => 'income', 'label' => $i['source'], 'amount' => $i['amount'], 'date' => $i['income_date']];
}
foreach ($expenses as $e) {
    $activity[] = ['type' => 'expense', 'label' => $e['description'], 'amount' => $e['amount'], 'date' => $e['expense_date']];
}
usort($activity, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
$activity = array_slice($activity, 0, 6);

$budgets = $budgetModel->findByUserId($userId);
$categories = $categoryModel->findAll();
$categoryNames = array_column($categories, 'category_name', 'category_id');

$pageTitle = 'Dashboard';
$active = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="receipt">
    <div class="label">Available balance</div>
    <div class="balance amount <?= $balance < 0 ? 'negative' : '' ?>">₱<?= number_format($balance, 2) ?></div>
    <div class="receipt-line">
        <span>Opened <?= date('M j, Y', strtotime($wallet['created_date'])) ?></span>
    </div>
</div>

<div class="section">
    <div class="section-head">
        <h2>Current budgets</h2>
        <a href="budget.php">Manage budgets →</a>
    </div>
    <div class="card">
        <?php if (empty($budgets)): ?>
            <div class="empty-state">No budgets set yet. Set one to catch overspending before it happens.</div>
        <?php else: ?>
            <?php foreach ($budgets as $b):
                $periodType = $b['period_type'] ?? 'monthly';
                $periodStart = $b['period_start'] ?? sprintf('%04d-%02d-01', $b['year'], $b['month']);
                if (!Budget::isCurrentPeriod($b)) continue;
                $status = $budgetModel->checkStatus($userId, (int)$b['category_id'], $periodType, $periodStart);
                $pct = min(100, $status['percent_used'] ?? 0);
                $fillClass = $status['status'] === 'exceeded' ? 'exceeded' : ($status['status'] === 'near_limit' ? 'near' : 'ok');
            ?>
            <div class="budget-item">
                <div class="row">
                    <span><?= htmlspecialchars($categoryNames[$b['category_id']] ?? 'Category') ?></span>
                    <span class="amount">₱<?= number_format($status['spent'] ?? 0, 2) ?> / ₱<?= number_format($b['budget_limit'], 2) ?></span>
                </div>
                <div class="track"><div class="fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="section-head">
        <h2>Recent activity</h2>
        <a href="expenses.php">Add expense →</a>
    </div>
    <div class="card">
        <?php if (empty($activity)): ?>
            <div class="empty-state">Nothing recorded yet. Log your allowance in Income to get started.</div>
        <?php else: ?>
        <table class="ledger">
            <thead><tr><th>Description</th><th>Date</th><th style="text-align:right;">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($activity as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['label']) ?></td>
                    <td><?= date('M j, Y', strtotime($a['date'])) ?></td>
                    <td style="text-align:right;">
                        <span class="amount <?= $a['type'] === 'income' ? 'positive' : 'negative' ?>">
                            <?= $a['type'] === 'income' ? '+' : '-' ?>₱<?= number_format($a['amount'], 2) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
