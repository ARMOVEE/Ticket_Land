<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM akun_user WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => trim($email)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $email, string $nama, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO akun_user (email, nama, password_hash, created_at)
             VALUES (:email, :nama, :hash, NOW())'
        );
        $stmt->execute([
            ':email' => strtolower(trim($email)),
            ':nama'  => trim($nama),
            ':hash'  => $hash,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
