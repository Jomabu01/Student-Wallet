<?php

require_once __DIR__ . '/Model.php';

class Wallet extends Model
{
    protected string $table = 'Wallet';
    protected string $primaryKey = 'wallet_id';

    public function create(array $data): int
    {
        $sql = "INSERT INTO Wallet (current_balance, created_date, user_id)
                VALUES (:current_balance, :created_date, :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'current_balance' => $data['current_balance'] ?? 0.00,
            'created_date'    => $data['created_date'] ?? date('Y-m-d H:i:s'),
            'user_id'         => $data['user_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Wallet WHERE wallet_id = :id");
        $stmt->execute(['id' => $id]);
        $wallet = $stmt->fetch();
        return $wallet ?: null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM Wallet WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Wallet SET current_balance = :current_balance WHERE wallet_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'current_balance' => $data['current_balance'],
            'id'               => $id,
        ]);
    }

    
    public function credit(int $walletId, float $amount): bool
    {
        $sql = "UPDATE Wallet SET current_balance = current_balance + :amount WHERE wallet_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['amount' => $amount, 'id' => $walletId]);
    }

    
    public function debit(int $walletId, float $amount): bool
    {
        $sql = "UPDATE Wallet SET current_balance = current_balance - :amount WHERE wallet_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['amount' => $amount, 'id' => $walletId]);
    }
}
