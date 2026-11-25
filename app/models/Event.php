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
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok,
                    COALESCE(SUM(tc.kuota_awal), 0) AS total_kuota
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.deleted_at IS NULL AND e.status = "aktif"
             GROUP BY e.id
             ORDER BY e.id ASC'
        );
        $events = $stmt->fetchAll();

        // Attach ticket_categories for each event
        $tcStmt = $this->db->query(
            'SELECT id, event_id, nama, harga, kuota_awal, stok_sisa,
                    CASE WHEN stok_sisa >= 1 THEN "On Sale" ELSE "Habis" END AS status
             FROM ticket_categories
             ORDER BY harga ASC, id ASC'
        );
        $allTc = $tcStmt->fetchAll();
        $tcByEvent = [];
        foreach ($allTc as $tc) {
            $eid = (int) $tc['event_id'];
            $tcByEvent[$eid][] = [
                'id'         => (int) $tc['id'],
                'event_id'   => $eid,
                'nama'       => $tc['nama'],
                'harga'      => (int) $tc['harga'],
                'kuota_awal' => (int) $tc['kuota_awal'],
                'stok_sisa'  => (int) $tc['stok_sisa'],
                'status'     => ((int) $tc['stok_sisa'] >= 1) ? 'On Sale' : 'Habis',
            ];
        }

        foreach ($events as &$ev) {
            $eid = (int) $ev['id'];
            $ev['id'] = $eid;
            $ev['category_id'] = (int) $ev['category_id'];
            $ev['harga_mulai'] = (int) $ev['harga_mulai'];
            $ev['total_stok'] = (int) $ev['total_stok'];
            $ev['total_kuota'] = (int) $ev['total_kuota'];
            $ev['is_featured'] = (int) $ev['is_featured'];
            $ev['is_hero'] = (int) $ev['is_hero'];
            $ev['ticket_categories'] = $tcByEvent[$eid] ?? [];
        }
        unset($ev);

        return $events;
    }

    public function findActiveById(int $id): ?array
    {
        $all = $this->getAllActive();
        foreach ($all as $ev) {
            if ((int) $ev['id'] === $id) {
                return $ev;
            }
        }
        return null;
    }
}
