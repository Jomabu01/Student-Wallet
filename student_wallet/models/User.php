<?php

require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected string $table = 'Users';
    protected string $primaryKey = 'user_id';

    public function create(array $data): int
    {
        $sql = "INSERT INTO Users (first_name, last_name, email, password, phone_number)
                VALUES (:first_name, :last_name, :email, :password, :phone_number)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'password'     => password_hash($data['password'], PASSWORD_BCRYPT),
            'phone_number' => $data['phone_number'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE user_id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Users
                SET first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone_number = :phone_number
                WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'],
            'id'           => $id,
        ]);
    }

    public function login(string $email, string $plainPassword): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($plainPassword, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return null;
    }
}
