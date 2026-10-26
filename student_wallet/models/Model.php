<?php

require_once __DIR__ . '/../config/Database.php';

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    abstract public function create(array $data): int;
    abstract public function findById(int $id): ?array;
    abstract public function update(int $id, array $data): bool;

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
}
