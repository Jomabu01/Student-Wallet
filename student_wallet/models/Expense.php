<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Wallet.php';

class Expense extends Model
{
    protected string $table = 'Expenses';
    protected string $primaryKey = 'expense_id';
    private Wallet $wallet;

    public function __construct()
    {
        parent::__construct();
        $this->wallet = new Wallet();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO Expenses (amount, description, expense_date, wallet_id, category_id)
                VALUES (:amount, :description, :expense_date, :wallet_id, :category_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'amount'       => $data['amount'],
            'description'  => $data['description'],
            'expense_date' => $data['expense_date'] ?? date('Y-m-d H:i:s'),
            'wallet_id'    => $data['wallet_id'],
            'category_id'  => $data['category_id'],
        ]);

        $this->wallet->debit((int) $data['wallet_id'], (float) $data['amount']);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Expenses WHERE expense_id = :id");
        $stmt->execute(['id' => $id]);
        $expense = $stmt->fetch();
        return $expense ?: null;
    }

    public function findByWalletId(int $walletId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM Expenses WHERE wallet_id = :wallet_id ORDER BY expense_date DESC"
        );
        $stmt->execute(['wallet_id' => $walletId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
       
        $old = $this->findById($id);
        if ($old) {
            $this->wallet->credit((int) $old['wallet_id'], (float) $old['amount']);
        }

        $sql = "UPDATE Expenses
                SET amount = :amount, description = :description, expense_date = :expense_date, category_id = :category_id
                WHERE expense_id = :id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'amount'       => $data['amount'],
            'description'  => $data['description'],
            'expense_date' => $data['expense_date'],
            'category_id'  => $data['category_id'],
            'id'           => $id,
        ]);

        if ($success && $old) {
            $this->wallet->debit((int) $old['wallet_id'], (float) $data['amount']);
        }

        return $success;
    }

    public function delete(int $id): bool
    {
        
        $expense = $this->findById($id);
        if ($expense) {
            $this->wallet->credit((int) $expense['wallet_id'], (float) $expense['amount']);
        }
        return parent::delete($id);
    }

    
    public function getMonthlyReport(int $walletId, int $month, int $year): array
    {
        $sql = "SELECT c.category_name, SUM(e.amount) AS total_spent
                FROM Expenses e
                JOIN Category c ON e.category_id = c.category_id
                WHERE e.wallet_id = :wallet_id
                  AND MONTH(e.expense_date) = :month
                  AND YEAR(e.expense_date) = :year
                GROUP BY c.category_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['wallet_id' => $walletId, 'month' => $month, 'year' => $year]);
        return $stmt->fetchAll();
    }
}
