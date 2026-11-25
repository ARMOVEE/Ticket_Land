<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model
{
    public function record(?int $userId, string $username, string $aksi, string $entitas, string $deskripsi): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, username, aksi, entitas, deskripsi, waktu)
             VALUES (:user_id, :username, :aksi, :entitas, :deskripsi, NOW())'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':username'  => $username,
            ':aksi'      => $aksi,
            ':entitas'   => $entitas,
            ':deskripsi' => $deskripsi,
        ]);
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, waktu, user_id, username, aksi, entitas, deskripsi
             FROM activity_logs
             ORDER BY waktu DESC, id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
