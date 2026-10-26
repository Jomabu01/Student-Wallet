<?php

require_once __DIR__ . '/Model.php';

class Category extends Model
{
    protected string $table = 'Category';
    protected string $primaryKey = 'category_id';

    public function create(array $data): int
    {
        $sql = "INSERT INTO Category (category_name, category_icon)
                VALUES (:category_name, :category_icon)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'category_name' => $data['category_name'],
            'category_icon' => $data['category_icon'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Category WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Category SET category_name = :category_name, category_icon = :category_icon
                WHERE category_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'category_name' => $data['category_name'],
            'category_icon' => $data['category_icon'],
            'id'             => $id,
        ]);
    }
}
