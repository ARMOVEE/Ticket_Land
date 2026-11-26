<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Event extends Model
{
    private function attachTicketCategories(array $events): array
    {
        if (empty($events)) {
            return [];
        }

        try {
            (new Order())->releaseExpiredHolds();
        } catch (\Throwable) {
        }

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

        // Count sold tickets per event
        $soldStmt = $this->db->query(
            'SELECT event_id, COUNT(*) AS terjual
             FROM tickets
             WHERE status_checkin != "Dibatalkan"
             GROUP BY event_id'
        );
        $soldByEvent = [];
        foreach ($soldStmt->fetchAll() as $s) {
            $soldByEvent[(int) $s['event_id']] = (int) $s['terjual'];
        }

        foreach ($events as &$ev) {
            $eid = (int) $ev['id'];
            $ev['id'] = $eid;
            $ev['category_id'] = (int) $ev['category_id'];
            $ev['harga_mulai'] = (int) ($ev['harga_mulai'] ?? 0);
            $ev['total_stok'] = (int) ($ev['total_stok'] ?? 0);
            $ev['total_kuota'] = (int) ($ev['total_kuota'] ?? 0);
            $ev['tiket_terjual'] = $soldByEvent[$eid] ?? 0;
            $ev['is_featured'] = (int) ($ev['is_featured'] ?? 0);
            $ev['is_hero'] = (int) ($ev['is_hero'] ?? 0);
            $ev['ticket_categories'] = $tcByEvent[$eid] ?? [];
        }
        unset($ev);

        return $events;
    }

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
        return $this->attachTicketCategories($stmt->fetchAll());
    }

    public function getAllTrashed(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, c.nama AS kategori_nama,
                    COALESCE(MIN(tc.harga), 0) AS harga_mulai,
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok,
                    COALESCE(SUM(tc.kuota_awal), 0) AS total_kuota
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.deleted_at IS NOT NULL
             GROUP BY e.id
             ORDER BY e.deleted_at DESC, e.id DESC'
        );
        return $this->attachTicketCategories($stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, c.nama AS kategori_nama,
                    COALESCE(MIN(tc.harga), 0) AS harga_mulai,
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok,
                    COALESCE(SUM(tc.kuota_awal), 0) AS total_kuota
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.id = :id
             GROUP BY e.id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $list = $this->attachTicketCategories([$row]);
        return $list[0] ?? null;
    }

    public function findActiveById(int $id): ?array
    {
        $ev = $this->findById($id);
        if ($ev === null || $ev['deleted_at'] !== null) {
            return null;
        }
        return $ev;
    }

    public function generateNextKode(): string
    {
        $stmt = $this->db->query('SELECT kode FROM events ORDER BY id DESC LIMIT 1');
        $last = (string) $stmt->fetchColumn();
        if (preg_match('/EVT(\d+)/', $last, $m)) {
            $nextNum = (int) $m[1] + 1;
        } else {
            $nextNum = 1;
        }
        while (true) {
            $candidate = sprintf('EVT%03d', $nextNum);
            $chk = $this->db->prepare('SELECT COUNT(*) FROM events WHERE kode = :k');
            $chk->execute([':k' => $candidate]);
            if ((int) $chk->fetchColumn() === 0) {
                return $candidate;
            }
            $nextNum++;
        }
    }

    public function saveEvent(array $data, string $pelaku): array
    {
        $id = !empty($data['id']) ? (int) $data['id'] : 0;
        $nama = trim((string) ($data['nama'] ?? ''));
        $categoryId = (int) ($data['category_id'] ?? 0);
        $tanggalMulai = trim((string) ($data['tanggal_mulai'] ?? ''));
        $tanggalLabel = trim((string) ($data['tanggal_label'] ?? ''));
        $jamLabel = trim((string) ($data['jam_label'] ?? '19:00 - 22:00 WIB'));
        $venue = trim((string) ($data['venue'] ?? ''));
        $kota = trim((string) ($data['kota'] ?? ''));
        $penyelenggara = trim((string) ($data['penyelenggara'] ?? ''));
        $deskripsi = trim((string) ($data['deskripsi'] ?? ''));
        $snk = trim((string) ($data['syarat_ketentuan'] ?? ''));
        $hargaReguler = (int) ($data['harga_reguler'] ?? 0);
        $stokReguler = (int) ($data['stok_reguler'] ?? 0);
        $namaKategoriTiket = trim((string) ($data['nama_kategori_tiket'] ?? 'Reguler'));
        if ($namaKategoriTiket === '') {
            $namaKategoriTiket = 'Reguler';
        }

        if ($nama === '' || $categoryId <= 0 || $tanggalMulai === '' || $venue === '' || $kota === '' || $penyelenggara === '') {
            throw new RuntimeException('Nama event, kategori, tanggal, venue, kota, dan penyelenggara wajib diisi (FR-A-10).');
        }
        if ($hargaReguler < 0 || $stokReguler < 0) {
            throw new RuntimeException('Harga tiket dan kuota/stok tidak boleh bernilai negatif (FR-A-10).');
        }

        // Verify category exists (DFD 1.2)
        $chkCat = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE id = :cid');
        $chkCat->execute([':cid' => $categoryId]);
        if ((int) $chkCat->fetchColumn() === 0) {
            throw new RuntimeException('Kategori event tidak valid.');
        }

        if ($tanggalLabel === '') {
            $ts = strtotime($tanggalMulai);
            $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            if ($ts !== false) {
                $tanggalLabel = date('j', $ts) . ' ' . ($bulan[(int) date('n', $ts)] ?? date('M', $ts)) . ' ' . date('Y', $ts);
            } else {
                $tanggalLabel = $tanggalMulai;
            }
        }
        if ($deskripsi === '') {
            $deskripsi = "Event {$nama} diselenggarakan oleh {$penyelenggara} di {$venue}, {$kota}.";
        }
        if ($snk === '') {
            $snk = "1. Tiket resmi hanya dijual melalui Ticket Land.\n2. Satu tiket berlaku untuk satu orang.\n3. Wajib membawa KTP sesuai nama pemegang tiket saat check-in.";
        }

        $poster = array_key_exists('poster', $data)
            ? (trim((string) $data['poster']) !== '' ? trim((string) $data['poster']) : null)
            : null;

        $this->db->beginTransaction();
        try {
            if ($id <= 0) {
                $kode = $this->generateNextKode();
                $stmt = $this->db->prepare(
                    'INSERT INTO events (
                        kode, nama, category_id, tanggal_mulai, tanggal_selesai, tanggal_label,
                        jam_label, venue, kota, penyelenggara, deskripsi, syarat_ketentuan,
                        poster, status, created_at
                     ) VALUES (
                        :kode, :nama, :cid, :tm, :ts, :tl,
                        :jl, :venue, :kota, :org, :desc, :snk,
                        :poster, "aktif", NOW()
                     )'
                );
                $stmt->execute([
                    ':kode'   => $kode,
                    ':nama'   => $nama,
                    ':cid'    => $categoryId,
                    ':tm'     => $tanggalMulai,
                    ':ts'     => $tanggalMulai,
                    ':tl'     => $tanggalLabel,
                    ':jl'     => $jamLabel,
                    ':venue'  => $venue,
                    ':kota'   => $kota,
                    ':org'    => $penyelenggara,
                    ':desc'   => $deskripsi,
                    ':snk'    => $snk,
                    ':poster' => $poster,
                ]);
                $eventId = (int) $this->db->lastInsertId();

                $statusTc = $stokReguler >= 1 ? 'On Sale' : 'Habis';
                $stmtTc = $this->db->prepare(
                    'INSERT INTO ticket_categories (event_id, nama, harga, kuota_awal, stok_sisa, status, created_at)
                     VALUES (:eid, :nama, :harga, :kuota, :stok, :status, NOW())'
                );
                $stmtTc->execute([
                    ':eid'    => $eventId,
                    ':nama'   => $namaKategoriTiket,
                    ':harga'  => $hargaReguler,
                    ':kuota'  => $stokReguler,
                    ':stok'   => $stokReguler,
                    ':status' => $statusTc,
                ]);
                $tcId = (int) $this->db->lastInsertId();

                if ($stokReguler > 0) {
                    $stmtSt = $this->db->prepare(
                        'INSERT INTO stock_transactions (event_id, ticket_category_id, jenis, jumlah, sisa_sesudah, pelaku, catatan, waktu)
                         VALUES (:eid, :tcid, "IN", :jml, :sisa, :pelaku, "Kuota tiket awal", NOW())'
                    );
                    $stmtSt->execute([
                        ':eid'    => $eventId,
                        ':tcid'   => $tcId,
                        ':jml'    => $stokReguler,
                        ':sisa'   => $stokReguler,
                        ':pelaku' => $pelaku,
                    ]);
                }

                $this->db->commit();
                return $this->findById($eventId) ?? [];
            }

            // Update existing event
            $existing = $this->findById($id);
            if ($existing === null) {
                throw new RuntimeException('Event tidak ditemukan.');
            }

            $posterToSave = array_key_exists('poster', $data)
                ? (trim((string) $data['poster']) !== '' ? trim((string) $data['poster']) : null)
                : ($existing['poster'] ?? null);

            $stmt = $this->db->prepare(
                'UPDATE events
                 SET nama = :nama, category_id = :cid, tanggal_mulai = :tm, tanggal_selesai = :ts,
                     tanggal_label = :tl, jam_label = :jl, venue = :venue, kota = :kota,
                     penyelenggara = :org, deskripsi = :desc, syarat_ketentuan = :snk,
                     poster = :poster
                 WHERE id = :id'
            );
            $stmt->execute([
                ':nama'   => $nama,
                ':cid'    => $categoryId,
                ':tm'     => $tanggalMulai,
                ':ts'     => $tanggalMulai,
                ':tl'     => $tanggalLabel,
                ':jl'     => $jamLabel,
                ':venue'  => $venue,
                ':kota'   => $kota,
                ':org'    => $penyelenggara,
                ':desc'   => $deskripsi,
                ':snk'    => $snk,
                ':poster' => $posterToSave,
                ':id'     => $id,
            ]);

            // Update primary ticket category price & stock
            $firstTc = $existing['ticket_categories'][0] ?? null;
            if ($firstTc !== null) {
                $tcId = (int) $firstTc['id'];
                $oldStok = (int) $firstTc['stok_sisa'];
                $statusTc = $stokReguler >= 1 ? 'On Sale' : 'Habis';
                $newKuota = max((int) $firstTc['kuota_awal'], $stokReguler);

                $stmtUpTc = $this->db->prepare(
                    'UPDATE ticket_categories
                     SET nama = :nama, harga = :harga, kuota_awal = :kuota, stok_sisa = :stok, status = :status
                     WHERE id = :id'
                );
                $stmtUpTc->execute([
                    ':nama'   => $namaKategoriTiket,
                    ':harga'  => $hargaReguler,
                    ':kuota'  => $newKuota,
                    ':stok'   => $stokReguler,
                    ':status' => $statusTc,
                    ':id'     => $tcId,
                ]);

                $diff = $stokReguler - $oldStok;
                if ($diff !== 0) {
                    $jenis = $diff > 0 ? 'IN' : 'OUT';
                    $stmtSt = $this->db->prepare(
                        'INSERT INTO stock_transactions (event_id, ticket_category_id, jenis, jumlah, sisa_sesudah, pelaku, catatan, waktu)
                         VALUES (:eid, :tcid, :jenis, :jml, :sisa, :pelaku, :catatan, NOW())'
                    );
                    $stmtSt->execute([
                        ':eid'     => $id,
                        ':tcid'    => $tcId,
                        ':jenis'   => $jenis,
                        ':jml'     => abs($diff),
                        ':sisa'    => $stokReguler,
                        ':pelaku'  => $pelaku,
                        ':catatan' => $diff > 0 ? 'Penyesuaian tambah kuota' : 'Penyesuaian kurangi kuota',
                    ]);
                }
            }

            $this->db->commit();
            return $this->findById($id) ?? [];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function softDelete(int $id, string $pelaku): array
    {
        $ev = $this->findById($id);
        if ($ev === null) {
            throw new RuntimeException('Event tidak ditemukan.');
        }

        $stmt = $this->db->prepare(
            'UPDATE events SET deleted_at = NOW(), deleted_by = :by WHERE id = :id'
        );
        $stmt->execute([':by' => $pelaku, ':id' => $id]);
        return $ev;
    }

    public function restore(int $id): array
    {
        $ev = $this->findById($id);
        if ($ev === null) {
            throw new RuntimeException('Event tidak ditemukan.');
        }

        $stmt = $this->db->prepare(
            'UPDATE events SET deleted_at = NULL, deleted_by = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $this->findById($id) ?? $ev;
    }
}
