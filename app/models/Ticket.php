<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Ticket extends Model
{
    public function getDemoTickets(): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.kode_tiket, t.nama_pemegang, t.status_checkin, t.waktu_checkin,
                    e.nama AS event_nama, tc.nama AS kategori_tiket
             FROM tickets t
             JOIN events e ON e.id = t.event_id
             JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             WHERE t.kode_tiket IN (:c1, :c2, :c3)
             ORDER BY FIELD(t.kode_tiket, :f1, :f2, :f3)'
        );
        $stmt->execute([
            ':c1' => 'TKL-A1B2C3',
            ':c2' => 'TKL-D4E5F6',
            ':c3' => 'TKL-USED99',
            ':f1' => 'TKL-A1B2C3',
            ':f2' => 'TKL-D4E5F6',
            ':f3' => 'TKL-USED99',
        ]);
        return $stmt->fetchAll();
    }
}
