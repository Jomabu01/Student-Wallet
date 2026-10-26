<?php
require_once __DIR__ . '/Model.php';

class Budget extends Model
{
    protected string $table = 'Budget';
    protected string $primaryKey = 'budget_id';

    public function create(array $data): int
    {
        $periodDate = new DateTimeImmutable($data['period_start']);
        $sql = "INSERT INTO Budget (budget_limit, month, year, category_id, user_id, period_type, period_start)
                VALUES (:budget_limit, :month, :year, :category_id, :user_id, :period_type, :period_start)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'budget_limit' => $data['budget_limit'],
            'month'        => (int) $periodDate->format('n'),
            'year'         => (int) $periodDate->format('Y'),
            'category_id'  => $data['category_id'],
            'user_id'      => $data['user_id'],
            'period_type'  => $data['period_type'],
            'period_start' => $data['period_start'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Budget WHERE budget_id = :id");
        $stmt->execute(['id' => $id]);
        $budget = $stmt->fetch();
        return $budget ?: null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM Budget WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $periodDate = new DateTimeImmutable($data['period_start']);
        $sql = "UPDATE Budget
                SET budget_limit = :budget_limit, month = :month, year = :year, category_id = :category_id,
                    period_type = :period_type, period_start = :period_start
                WHERE budget_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'budget_limit' => $data['budget_limit'],
            'month'        => (int) $periodDate->format('n'),
            'year'         => (int) $periodDate->format('Y'),
            'category_id'  => $data['category_id'],
            'period_type'  => $data['period_type'],
            'period_start' => $data['period_start'],
            'id'           => $id,
        ]);
    }

    public static function normalizePeriodStart(string $periodType, string $date): ?string
    {
        if (!in_array($periodType, ['monthly', 'weekly', 'daily'], true)) {
            return null;
        }

        $periodDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$periodDate || $periodDate->format('Y-m-d') !== $date) {
            return null;
        }

        if ($periodType === 'monthly') {
            $periodDate = $periodDate->modify('first day of this month');
        } elseif ($periodType === 'weekly') {
            $periodDate = $periodDate->modify('monday this week');
        }

        return $periodDate->format('Y-m-d');
    }

    public static function isCurrentPeriod(array $budget): bool
    {
        $periodType = $budget['period_type'] ?? 'monthly';
        $currentStart = self::normalizePeriodStart($periodType, date('Y-m-d'));
        $budgetStart = $budget['period_start'] ?? null;
        return $currentStart !== null && $budgetStart === $currentStart;
    }

    public function checkStatus(int $userId, int $categoryId, string $periodType, string $periodStart): array
    {
        $budgetStmt = $this->db->prepare(
            "SELECT budget_limit FROM Budget
             WHERE user_id = :user_id AND category_id = :category_id
               AND period_type = :period_type AND period_start = :period_start"
        );
        $budgetStmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'period_type' => $periodType,
            'period_start' => $periodStart,
        ]);
        $budget = $budgetStmt->fetch();

        if (!$budget) {
            return ['status' => 'no_budget_set'];
        }

        $periodDate = new DateTimeImmutable($periodStart);
        $periodEnd = match ($periodType) {
            'daily' => $periodDate->modify('+1 day'),
            'weekly' => $periodDate->modify('+1 week'),
            'monthly' => $periodDate->modify('+1 month'),
            default => throw new InvalidArgumentException('Unsupported budget period.'),
        };
        $spentStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(e.amount), 0) AS total_spent
             FROM Expenses e
             JOIN Wallet w ON e.wallet_id = w.wallet_id
             WHERE w.user_id = :user_id AND e.category_id = :category_id
               AND e.expense_date >= :period_start AND e.expense_date < :period_end"
        );
        $spentStmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'period_start' => $periodDate->format('Y-m-d 00:00:00'),
            'period_end' => $periodEnd->format('Y-m-d 00:00:00'),
        ]);
        $spent = (float) $spentStmt->fetch()['total_spent'];
        $limit = (float) $budget['budget_limit'];
        $percentUsed = $limit > 0 ? round(($spent / $limit) * 100, 2) : 0;

        return [
            'status'        => $percentUsed >= 100 ? 'exceeded' : ($percentUsed >= 80 ? 'near_limit' : 'ok'),
            'budget_limit'  => $limit,
            'spent'         => $spent,
            'percent_used'  => $percentUsed,
        ];
    }
}
