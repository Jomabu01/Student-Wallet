<?php
require_once __DIR__ . '/../includes/auth.php';
$currentUser = require_auth();
$userId = (int) $currentUser['user_id'];

$budgetModel   = new Budget();
$categoryModel = new Category();

$categories = $categoryModel->findAll();
$categoryNames = array_column($categories, 'category_name', 'category_id');

$message = '';
$editingBudget = null;

if (isset($_GET['delete'])) {
    $budget = $budgetModel->findById((int) $_GET['delete']);
    if ($budget && (int) $budget['user_id'] === $userId) {
        $budgetModel->delete((int) $_GET['delete']);
    }
    header('Location: budget.php');
    exit;
}

if (isset($_GET['edit'])) {
    $editingBudget = $budgetModel->findById((int) $_GET['edit']);
    if (!$editingBudget || (int) $editingBudget['user_id'] !== $userId) {
        $editingBudget = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $limit = (float) ($_POST['budget_limit'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $periodType = is_string($_POST['period_type'] ?? null) ? $_POST['period_type'] : '';
    $periodDate = is_string($_POST['period_date'] ?? null) ? $_POST['period_date'] : '';
    $periodStart = Budget::normalizePeriodStart($periodType, $periodDate);

    if ($limit > 0 && $categoryId && $periodStart !== null) {
        if (isset($_POST['budget_id'])) {
            $budget = $budgetModel->findById((int) $_POST['budget_id']);
            if ($budget && (int) $budget['user_id'] === $userId) {
                $budgetModel->update((int) $_POST['budget_id'], [
                    'budget_limit' => $limit,
                    'category_id'  => $categoryId,
                    'period_type'  => $periodType,
                    'period_start' => $periodStart,
                ]);
            }
        } else {
            $budgetModel->create([
                'budget_limit' => $limit,
                'category_id'  => $categoryId,
                'user_id'      => $userId,
                'period_type'  => $periodType,
                'period_start' => $periodStart,
            ]);
        }
        header('Location: budget.php');
        exit;
    } else {
        $message = 'Set a valid amount, category, and budget period.';
    }
}

$budgets = $budgetModel->findByUserId($userId);
$periodTypes = ['monthly' => 'Monthly', 'weekly' => 'Weekly', 'daily' => 'Daily'];
$editingPeriodType = $editingBudget['period_type'] ?? 'monthly';
$editingPeriodStart = $editingBudget['period_start'] ?? date('Y-m-d');

$pageTitle = 'Budget';
$active = 'budget';
include __DIR__ . '/../includes/header.php';
?>

<div class="section-head">
    <h1>Budget planner</h1>
</div>

<?php if ($message): ?><div class="error-msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="section">
    <div class="card">
        <h2 style="margin-bottom:1rem;"><?= $editingBudget ? 'Edit spending limit' : 'Set a spending limit' ?></h2>
        <form method="POST">
            <?php if ($editingBudget): ?><input type="hidden" name="budget_id" value="<?= $editingBudget['budget_id'] ?>"><?php endif; ?>
            <div class="grid-2">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['category_id'] ?>" <?= isset($editingBudget['category_id']) && (int) $editingBudget['category_id'] === (int) $c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="budget_limit">Limit (₱)</label>
                    <input type="number" step="0.01" min="0.01" id="budget_limit" name="budget_limit" value="<?= htmlspecialchars($editingBudget['budget_limit'] ?? '') ?>" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="period_type">Budget period</label>
                    <select id="period_type" name="period_type" required>
                        <?php foreach ($periodTypes as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $editingPeriodType === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="period_date">Date in period</label>
                    <input type="date" id="period_date" name="period_date" value="<?= htmlspecialchars($editingPeriodStart, ENT_QUOTES, 'UTF-8') ?>" required>
                    <small>Weekly budgets start on Monday; monthly budgets cover the selected month.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;"><?= $editingBudget ? 'Update budget' : 'Set budget' ?></button>
            <?php if ($editingBudget): ?> <a href="budget.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="section">
    <div class="card">
        <?php if (empty($budgets)): ?>
            <div class="empty-state">No budgets yet. Set a limit above to start tracking against it.</div>
        <?php else: ?>
            <?php foreach ($budgets as $b):
                $periodType = $b['period_type'] ?? 'monthly';
                $periodStart = $b['period_start'] ?? sprintf('%04d-%02d-01', $b['year'], $b['month']);
                $status = $budgetModel->checkStatus($userId, (int)$b['category_id'], $periodType, $periodStart);
                $pct = min(100, $status['percent_used'] ?? 0);
                $fillClass = $status['status'] === 'exceeded' ? 'exceeded' : ($status['status'] === 'near_limit' ? 'near' : 'ok');
                $periodDate = new DateTimeImmutable($periodStart);
                $periodLabel = match ($periodType) {
                    'weekly' => 'Week of ' . $periodDate->format('M j, Y'),
                    'daily' => $periodDate->format('M j, Y'),
                    default => $periodDate->format('F Y'),
                };
            ?>
            <div class="budget-item">
                <div class="row">
                    <span><?= htmlspecialchars($categoryNames[$b['category_id']] ?? 'Category') ?> — <?= $periodLabel ?> (<?= ucfirst($periodType) ?>)</span>
                    <span class="amount">₱<?= number_format($status['spent'] ?? 0, 2) ?> / ₱<?= number_format($b['budget_limit'], 2) ?></span>
                </div>
                <div class="track"><div class="fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div></div>
                <div class="actions"><a href="?edit=<?= $b['budget_id'] ?>">Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="?delete=<?= $b['budget_id'] ?>" class="del" onclick="return confirm('Delete this budget?');">Delete</a></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
