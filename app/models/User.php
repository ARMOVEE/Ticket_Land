<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users
             WHERE (LOWER(username) = LOWER(:u1) OR LOWER(email) = LOWER(:u2))
               AND status_aktif = 1
             LIMIT 1'
        );
        $clean = trim($identifier);
        $stmt->execute([':u1' => $clean, ':u2' => $clean]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, username, email, nama, peran, status_aktif, created_at
             FROM users
             ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public function existsUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users
             WHERE LOWER(username) = LOWER(:u) OR LOWER(email) = LOWER(:e)'
        );
        $stmt->execute([
            ':u' => trim($username),
            ':e' => trim($email),
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $username, string $email, string $nama, string $password, string $peran): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $role = strtolower(trim($peran));
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            $role = 'staff';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, nama, password_hash, peran, status_aktif, created_at)
             VALUES (:username, :email, :nama, :hash, :peran, 1, NOW())'
        );
        $stmt->execute([
            ':username' => trim($username),
            ':email'    => strtolower(trim($email)),
            ':nama'     => trim($nama),
            ':hash'     => $hash,
            ':peran'    => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
