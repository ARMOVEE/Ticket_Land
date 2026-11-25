<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Ticket extends Model
{
    public function findByCode(string $code): ?array
    {
        $clean = strtoupper(trim($code));
        $stmt = $this->db->prepare(
            'SELECT 
                t.id,
                t.order_id,
                t.event_id,
                t.ticket_category_id,
                t.kode_tiket,
                t.nama_pemegang,
                t.tipe_identitas,
                t.nomor_identitas,
                t.email,
                t.jenis_kelamin,
                t.wa,
                t.status_checkin,
                t.waktu_checkin,
                t.checkin_staff_id,
                t.created_at,
                e.kode AS event_kode,
                e.nama AS event_nama,
                e.venue AS event_venue,
                e.kota AS event_kota,
                e.tanggal_mulai AS event_tanggal,
                e.jam_label AS event_waktu,
                tc.nama AS kategori_tiket,
                tc.harga AS harga_tiket,
                o.nomor_invoice,
                o.status AS status_pembayaran
             FROM tickets t
             INNER JOIN events e ON e.id = t.event_id
             INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             INNER JOIN orders o ON o.id = t.order_id
             WHERE UPPER(t.kode_tiket) = :kode
             LIMIT 1'
        );
        $stmt->execute([':kode' => $clean]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getTodayCheckinCount(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM tickets 
             WHERE status_checkin = 'Sudah Check-in' 
               AND waktu_checkin IS NOT NULL 
               AND DATE(waktu_checkin) = CURRENT_DATE()"
        );
        $cnt = (int) $stmt->fetchColumn();

        // Also check checkin_logs if count is zero (e.g. for seeded demo logs today)
        if ($cnt === 0) {
            $stmtLogs = $this->db->query(
                "SELECT COUNT(*) FROM checkin_logs 
                 WHERE hasil = 'Check-in' 
                   AND DATE(waktu) = CURRENT_DATE()"
            );
            $cntLogs = (int) $stmtLogs->fetchColumn();
            if ($cntLogs > 0) {
                return $cntLogs;
            }
        }
        return $cnt;
    }

    public function getRecentCheckinLogs(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT 
                cl.id,
                cl.ticket_id,
                cl.kode_dipindai,
                cl.nama_pemesan,
                cl.nama_event,
                cl.hasil,
                cl.alasan,
                cl.staff_id,
                cl.waktu,
                u.nama AS staff_nama
             FROM checkin_logs cl
             LEFT JOIN users u ON u.id = cl.staff_id
             ORDER BY cl.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActiveFeaturedEvent(): ?array
    {
        $stmt = $this->db->query(
            "SELECT id, kode, nama, venue, kota, tanggal_mulai, jam_label 
             FROM events 
             WHERE deleted_at IS NULL 
             ORDER BY id ASC 
             LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function confirmCheckin(int $ticketId, string $kodeTiket, string $namaPemesan, string $namaEvent, int $staffId, string $staffNama): bool
    {
        $this->db->beginTransaction();
        try {
            $stmtLock = $this->db->prepare(
                'SELECT id, status_checkin FROM tickets WHERE id = :id FOR UPDATE'
            );
            $stmtLock->execute([':id' => $ticketId]);
            $ticket = $stmtLock->fetch();

            if (!$ticket) {
                $this->db->rollBack();
                return false;
            }

            if ($ticket['status_checkin'] === 'Sudah Check-in') {
                $this->db->rollBack();
                return false;
            }

            // Update ticket status
            $stmtUp = $this->db->prepare(
                "UPDATE tickets 
                 SET status_checkin = 'Sudah Check-in',
                     waktu_checkin = NOW(),
                     checkin_staff_id = :sid
                 WHERE id = :id"
            );
            $stmtUp->execute([
                ':sid' => $staffId,
                ':id'  => $ticketId,
            ]);

            // Insert into checkin_logs
            $stmtLog = $this->db->prepare(
                'INSERT INTO checkin_logs (ticket_id, kode_dipindai, nama_pemesan, nama_event, hasil, alasan, staff_id, waktu)
                 VALUES (:tid, :kode, :nama, :event, :hasil, :alasan, :sid, NOW())'
            );
            $stmtLog->execute([
                ':tid'    => $ticketId,
                ':kode'   => $kodeTiket,
                ':nama'   => $namaPemesan,
                ':event'  => $namaEvent,
                ':hasil'  => 'Check-in',
                ':alasan' => 'KTP sesuai',
                ':sid'    => $staffId,
            ]);

            // Insert into activity_logs
            (new ActivityLog())->record(
                $staffId,
                $staffNama,
                'CHECKIN',
                'Tiket ' . $kodeTiket,
                'Check-in berhasil: ' . $namaPemesan . ', Event: ' . $namaEvent . ' (KTP cocok)'
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function logRejection(?int $ticketId, string $kodeTiket, string $namaPemesan, string $namaEvent, string $alasan, int $staffId, string $staffNama): void
    {
        $stmtLog = $this->db->prepare(
            'INSERT INTO checkin_logs (ticket_id, kode_dipindai, nama_pemesan, nama_event, hasil, alasan, staff_id, waktu)
             VALUES (:tid, :kode, :nama, :event, :hasil, :alasan, :sid, NOW())'
        );
        $stmtLog->execute([
            ':tid'    => $ticketId,
            ':kode'   => $kodeTiket,
            ':nama'   => $namaPemesan !== '' ? $namaPemesan : '-',
            ':event'  => $namaEvent !== '' ? $namaEvent : '-',
            ':hasil'  => 'Ditolak',
            ':alasan' => $alasan,
            ':sid'    => $staffId,
        ]);

        (new ActivityLog())->record(
            $staffId,
            $staffNama,
            'REJECT_CHECKIN',
            'Tiket ' . $kodeTiket,
            'Penolakan tiket: ' . $alasan . ($namaPemesan !== '' ? ' (Pemesan: ' . $namaPemesan . ')' : '')
        );
    }
}
