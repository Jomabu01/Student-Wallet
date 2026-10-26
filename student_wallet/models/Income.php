<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Wallet.php';

class Income extends Model
{
    protected string $table = 'Income';
    protected string $primaryKey = 'income_id';
    private Wallet $wallet;

    public function __construct()
    {
        parent::__construct();
        $this->wallet = new Wallet();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO Income (amount, source, description, income_date, wallet_id)
                VALUES (:amount, :source, :description, :income_date, :wallet_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'amount'      => $data['amount'],
            'source'      => $data['source'],
            'description' => $data['description'],
            'income_date' => $data['income_date'] ?? date('Y-m-d H:i:s'),
            'wallet_id'   => $data['wallet_id'],
        ]);

    
        $this->wallet->credit((int) $data['wallet_id'], (float) $data['amount']);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Income WHERE income_id = :id");
        $stmt->execute(['id' => $id]);
        $income = $stmt->fetch();
        return $income ?: null;
    }

    public function findByWalletId(int $walletId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM Income WHERE wallet_id = :wallet_id ORDER BY income_date DESC"
        );
        $stmt->execute(['wallet_id' => $walletId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $old = $this->findById($id);
        $sql = "UPDATE Income
                SET amount = :amount, source = :source, description = :description, income_date = :income_date
                WHERE income_id = :id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'amount'      => $data['amount'],
            'source'      => $data['source'],
            'description' => $data['description'],
            'income_date' => $data['income_date'],
            'id'          => $id,
        ]);

        if ($success && $old) {
            $this->wallet->debit((int) $old['wallet_id'], (float) $old['amount']);
            $this->wallet->credit((int) $old['wallet_id'], (float) $data['amount']);
        }

        return $success;
    }

    public function delete(int $id): bool
    {
        $income = $this->findById($id);
        if ($income) {
            $this->wallet->debit((int) $income['wallet_id'], (float) $income['amount']);
        }
        return parent::delete($id);
    }
}
