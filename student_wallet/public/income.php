<?php
require_once __DIR__ . '/../includes/auth.php';
$currentUser = require_auth();
$userId = (int) $currentUser['user_id'];

$walletModel = new Wallet();
$incomeModel = new Income();

$wallet   = $walletModel->findByUserId($userId)[0];
$walletId = (int) $wallet['wallet_id'];

$message = '';
$editingIncome = null;

if (isset($_GET['delete'])) {
    $income = $incomeModel->findById((int) $_GET['delete']);
    if ($income && (int) $income['wallet_id'] === $walletId) {
        $incomeModel->delete((int) $_GET['delete']);
    }
    header('Location: income.php');
    exit;
}

if (isset($_GET['edit'])) {
    $editingIncome = $incomeModel->findById((int) $_GET['edit']);
    if (!$editingIncome || (int) $editingIncome['wallet_id'] !== $walletId) {
        $editingIncome = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $source = trim($_POST['source'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0 && $source && $description) {
        if (isset($_POST['income_id'])) {
            $income = $incomeModel->findById((int) $_POST['income_id']);
            if ($income && (int) $income['wallet_id'] === $walletId) {
                $incomeModel->update((int) $_POST['income_id'], [
                    'amount'      => $amount,
                    'source'      => $source,
                    'description' => $description,
                    'income_date' => $income['income_date'],
                ]);
            }
        } else {
            $incomeModel->create([
                'amount'      => $amount,
                'source'      => $source,
                'description' => $description,
                'wallet_id'   => $walletId,
            ]);
        }
        header('Location: income.php');
        exit;
    } else {
        $message = 'Fill in amount, source, and description.';
    }
}

$incomes = $incomeModel->findByWalletId($walletId);

$pageTitle = 'Income';
$active = 'income';
include __DIR__ . '/../includes/header.php';
?>

<div class="section-head">
    <h1>Income</h1>
</div>

<?php if ($message): ?><div class="error-msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="section">
    <div class="card">
        <h2 style="margin-bottom:1rem;"><?= $editingIncome ? 'Edit income' : 'Record income' ?></h2>
        <form method="POST">
            <?php if ($editingIncome): ?><input type="hidden" name="income_id" value="<?= $editingIncome['income_id'] ?>"><?php endif; ?>
            <div class="grid-2">
                <div class="field">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= htmlspecialchars($editingIncome['amount'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="source">Source</label>
                    <input type="text" id="source" name="source" value="<?= htmlspecialchars($editingIncome['source'] ?? '') ?>" placeholder="e.g. Parents, Part-time job" required>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($editingIncome['description'] ?? '') ?>" placeholder="e.g. Weekly allowance" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;"><?= $editingIncome ? 'Update income' : 'Add income' ?></button>
            <?php if ($editingIncome): ?> <a href="income.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="section">
    <div class="card">
        <?php if (empty($incomes)): ?>
            <div class="empty-state">No income recorded yet.</div>
        <?php else: ?>
        <table class="ledger">
            <thead><tr><th>Source</th><th>Description</th><th>Date</th><th style="text-align:right;">Amount</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($incomes as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['source']) ?></td>
                    <td><?= htmlspecialchars($i['description']) ?></td>
                    <td><?= date('M j, Y', strtotime($i['income_date'])) ?></td>
                    <td style="text-align:right;"><span class="amount positive">+₱<?= number_format($i['amount'], 2) ?></span></td>
                    <td class="actions"><a href="?edit=<?= $i['income_id'] ?>">Edit</a><a href="?delete=<?= $i['income_id'] ?>" class="del" onclick="return confirm('Delete this income?');">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
