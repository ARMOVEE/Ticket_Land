<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    public function getAllActive(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, c.nama AS kategori_nama,
                    COALESCE(MIN(tc.harga), 0) AS harga_mulai,
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.deleted_at IS NULL
             GROUP BY e.id
             ORDER BY e.id ASC'
        );
        return $stmt->fetchAll();
    }
}
