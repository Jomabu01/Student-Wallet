<?php
require_once __DIR__ . '/../includes/auth.php';
$currentUser = require_auth();
$userId = (int) $currentUser['user_id'];

$walletModel   = new Wallet();
$expenseModel  = new Expense();
$categoryModel = new Category();

$wallet   = $walletModel->findByUserId($userId)[0];
$walletId = (int) $wallet['wallet_id'];
$categories = $categoryModel->findAll();

$message = '';
$editingExpense = null;


if (isset($_GET['delete'])) {
    $expense = $expenseModel->findById((int) $_GET['delete']);
    if ($expense && (int) $expense['wallet_id'] === $walletId) {
        $expenseModel->delete((int) $_GET['delete']);
    }
    header('Location: expenses.php');
    exit;
}

if (isset($_GET['edit'])) {
    $editingExpense = $expenseModel->findById((int) $_GET['edit']);
    if (!$editingExpense || (int) $editingExpense['wallet_id'] !== $walletId) {
        $editingExpense = null;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);

    if ($amount > 0 && $description && $categoryId) {
        if (isset($_POST['expense_id'])) {
            $expense = $expenseModel->findById((int) $_POST['expense_id']);
            if ($expense && (int) $expense['wallet_id'] === $walletId) {
                $expenseModel->update((int) $_POST['expense_id'], [
                    'amount'       => $amount,
                    'description'  => $description,
                    'category_id'  => $categoryId,
                    'expense_date' => $expense['expense_date'],
                ]);
            }
        } else {
            $expenseModel->create([
                'amount'      => $amount,
                'description' => $description,
                'category_id' => $categoryId,
                'wallet_id'   => $walletId,
            ]);
        }
        header('Location: expenses.php');
        exit;
    } else {
        $message = 'Fill in amount, description, and category.';
    }
}

$expenses = $expenseModel->findByWalletId($walletId);
$categoryNames = array_column($categories, 'category_name', 'category_id');

$pageTitle = 'Expenses';
$active = 'expenses';
include __DIR__ . '/../includes/header.php';
?>

<div class="section-head">
    <h1>Expenses</h1>
</div>

<?php if ($message): ?><div class="error-msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="section">
    <div class="card">
        <h2 style="margin-bottom:1rem;"><?= $editingExpense ? 'Edit expense' : 'Log an expense' ?></h2>
        <form method="POST">
            <?php if ($editingExpense): ?><input type="hidden" name="expense_id" value="<?= $editingExpense['expense_id'] ?>"><?php endif; ?>
            <div class="grid-2">
                <div class="field">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= htmlspecialchars($editingExpense['amount'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['category_id'] ?>" <?= isset($editingExpense['category_id']) && (int) $editingExpense['category_id'] === (int) $c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($editingExpense['description'] ?? '') ?>" placeholder="e.g. Lunch at canteen" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;"><?= $editingExpense ? 'Update expense' : 'Add expense' ?></button>
            <?php if ($editingExpense): ?> <a href="expenses.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="section">
    <div class="card">
        <?php if (empty($expenses)): ?>
            <div class="empty-state">No expenses logged yet.</div>
        <?php else: ?>
        <table class="ledger">
            <thead><tr><th>Description</th><th>Category</th><th>Date</th><th style="text-align:right;">Amount</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td><span class="pill"><?= htmlspecialchars($categoryNames[$e['category_id']] ?? '—') ?></span></td>
                    <td><?= date('M j, Y', strtotime($e['expense_date'])) ?></td>
                    <td style="text-align:right;"><span class="amount negative">-₱<?= number_format($e['amount'], 2) ?></span></td>
                    <td class="actions"><a href="?edit=<?= $e['expense_id'] ?>">Edit</a><a href="?delete=<?= $e['expense_id'] ?>" class="del" onclick="return confirm('Delete this expense? Your balance will be refunded.');">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
