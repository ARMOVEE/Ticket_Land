<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT c.id, c.nama, c.deskripsi,
                    COUNT(CASE WHEN e.deleted_at IS NULL THEN e.id END) AS jumlah_event
             FROM categories c
             LEFT JOIN events e ON e.category_id = c.id
             GROUP BY c.id, c.nama, c.deskripsi
             ORDER BY c.id ASC'
        );
        return $stmt->fetchAll();
    }
}
