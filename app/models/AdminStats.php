<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class AdminStats extends Model
{
    public function getDashboardSummary(): array
    {
        $totalEvents = (int) $this->db->query(
            'SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = "aktif"'
        )->fetchColumn();

        $totalTickets = (int) $this->db->query(
            'SELECT COALESCE(SUM(tc.stok_sisa), 0)
             FROM ticket_categories tc
             JOIN events e ON e.id = tc.event_id
             WHERE e.deleted_at IS NULL AND e.status = "aktif"'
        )->fetchColumn();

        $salesValue = (int) $this->db->query(
            'SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE status = "Lunas"'
        )->fetchColumn();

        // Low stock events: active events with total remaining stock between 1 and 5 (FR-A-03, BR-10)
        $lowStockRows = $this->db->query(
            'SELECT e.id, e.kode, e.nama, c.nama AS kategori_nama,
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.deleted_at IS NULL AND e.status = "aktif"
             GROUP BY e.id, e.kode, e.nama, c.nama
             HAVING total_stok >= 1 AND total_stok <= 5
             ORDER BY total_stok ASC, e.id ASC'
        )->fetchAll();

        $todayTx = (int) $this->db->query(
            'SELECT COUNT(*) FROM stock_transactions WHERE DATE(waktu) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
        )->fetchColumn();

        // Top 5 events by remaining tickets (FR-A-05)
        $topEvents = $this->db->query(
            'SELECT e.id, e.kode, e.nama, c.nama AS kategori_nama,
                    COALESCE(SUM(tc.stok_sisa), 0) AS total_stok
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             WHERE e.deleted_at IS NULL AND e.status = "aktif"
             GROUP BY e.id, e.kode, e.nama, c.nama
             ORDER BY total_stok DESC, e.id ASC
             LIMIT 5'
        )->fetchAll();

        // Latest stock transactions (FR-A-05)
        $recentTx = $this->db->query(
            'SELECT st.*, e.kode AS event_kode, e.nama AS event_nama, tc.nama AS kategori_tiket_nama
             FROM stock_transactions st
             JOIN events e ON e.id = st.event_id
             JOIN ticket_categories tc ON tc.id = st.ticket_category_id
             ORDER BY st.waktu DESC, st.id DESC
             LIMIT 6'
        )->fetchAll();

        // 7-day chart movement (FR-A-04)
        $chartRows = $this->db->query(
            'SELECT DATE(waktu) AS tgl,
                    SUM(CASE WHEN jenis = "IN" THEN jumlah ELSE 0 END) AS total_in,
                    SUM(CASE WHEN jenis = "OUT" THEN jumlah ELSE 0 END) AS total_out
             FROM stock_transactions
             WHERE waktu >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(waktu)
             ORDER BY tgl ASC'
        )->fetchAll();

        $mapByDate = [];
        foreach ($chartRows as $cr) {
            $mapByDate[(string) $cr['tgl']] = [
                'in'  => (int) $cr['total_in'],
                'out' => (int) $cr['total_out'],
            ];
        }

        $bulanPendek = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartLabels = [];
        $chartIn = [];
        $chartOut = [];
        for ($i = 6; $i >= 0; $i--) {
            $ts = strtotime("-{$i} days");
            $dKey = date('Y-m-d', $ts);
            $label = date('d', $ts) . ' ' . ($bulanPendek[(int) date('n', $ts)] ?? date('M', $ts));
            $chartLabels[] = $label;
            $chartIn[] = $mapByDate[$dKey]['in'] ?? 0;
            $chartOut[] = $mapByDate[$dKey]['out'] ?? 0;
        }

        return [
            'total_events'  => $totalEvents,
            'total_tickets' => $totalTickets,
            'sales_value'   => $salesValue,
            'low_stock_cnt' => count($lowStockRows),
            'today_tx_cnt'  => $todayTx,
            'low_stock'     => $lowStockRows,
            'top_events'    => $topEvents,
            'recent_tx'     => $recentTx,
            'chart'         => [
                'labels' => $chartLabels,
                'in'     => $chartIn,
                'out'    => $chartOut,
            ],
        ];
    }

    public function getAllStockTransactions(): array
    {
        return $this->db->query(
            'SELECT st.*, e.kode AS event_kode, e.nama AS event_nama, tc.nama AS kategori_tiket_nama
             FROM stock_transactions st
             JOIN events e ON e.id = st.event_id
             JOIN ticket_categories tc ON tc.id = st.ticket_category_id
             ORDER BY st.waktu DESC, st.id DESC'
        )->fetchAll();
    }

    public function getAllOrders(): array
    {
        return $this->db->query(
            'SELECT o.*, e.kode AS event_kode, e.nama AS event_nama, tc.nama AS kategori_tiket_nama
             FROM orders o
             JOIN events e ON e.id = o.event_id
             JOIN ticket_categories tc ON tc.id = o.ticket_category_id
             ORDER BY o.created_at DESC, o.id DESC'
        )->fetchAll();
    }

    public function createManualStockTransaction(int $ticketCategoryId, string $jenis, int $jumlah, string $catatan, string $pelaku): array
    {
        $jenis = strtoupper(trim($jenis));
        if (!in_array($jenis, ['IN', 'OUT'], true)) {
            throw new RuntimeException('Jenis transaksi harus Masuk (IN) atau Keluar (OUT).');
        }
        if ($jumlah < 1) {
            throw new RuntimeException('Jumlah tiket minimal 1.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT tc.*, e.kode AS event_kode, e.nama AS event_nama
                 FROM ticket_categories tc
                 JOIN events e ON e.id = tc.event_id
                 WHERE tc.id = :id
                 FOR UPDATE'
            );
            $stmt->execute([':id' => $ticketCategoryId]);
            $tc = $stmt->fetch();
            if (!$tc) {
                throw new RuntimeException('Kategori tiket tidak ditemukan.');
            }

            $stokLama = (int) $tc['stok_sisa'];
            if ($jenis === 'OUT' && $jumlah > $stokLama) {
                throw new RuntimeException("Penjualan/pengurangan melebihi sisa stok ({$stokLama} tiket). Transaksi ditolak dan stok tidak berubah (FR-A-17).");
            }

            $stokBaru = $jenis === 'IN' ? ($stokLama + $jumlah) : ($stokLama - $jumlah);
            $statusBaru = $stokBaru >= 1 ? 'On Sale' : 'Habis';
            $kuotaBaru = max((int) $tc['kuota_awal'], $stokBaru);

            $stmtUp = $this->db->prepare(
                'UPDATE ticket_categories
                 SET stok_sisa = :stok, kuota_awal = :kuota, status = :status
                 WHERE id = :id'
            );
            $stmtUp->execute([
                ':stok'   => $stokBaru,
                ':kuota'  => $kuotaBaru,
                ':status' => $statusBaru,
                ':id'     => $ticketCategoryId,
            ]);

            $cleanCatatan = trim($catatan);
            if ($cleanCatatan === '') {
                $cleanCatatan = $jenis === 'IN' ? 'Penambahan kuota tiket' : 'Pengurangan kuota manual';
            }

            $stmtIns = $this->db->prepare(
                'INSERT INTO stock_transactions (event_id, ticket_category_id, jenis, jumlah, sisa_sesudah, pelaku, catatan, waktu)
                 VALUES (:eid, :tcid, :jenis, :jml, :sisa, :pelaku, :catatan, NOW())'
            );
            $stmtIns->execute([
                ':eid'     => (int) $tc['event_id'],
                ':tcid'    => $ticketCategoryId,
                ':jenis'   => $jenis,
                ':jml'     => $jumlah,
                ':sisa'    => $stokBaru,
                ':pelaku'  => $pelaku,
                ':catatan' => $cleanCatatan,
            ]);

            $this->db->commit();

            return [
                'event_id'           => (int) $tc['event_id'],
                'event_kode'         => $tc['event_kode'],
                'event_nama'         => $tc['event_nama'],
                'ticket_category_id' => $ticketCategoryId,
                'kategori_tiket'     => $tc['nama'],
                'jenis'              => $jenis,
                'jumlah'             => $jumlah,
                'stok_sisa_baru'     => $stokBaru,
                'status_baru'        => $statusBaru,
                'catatan'            => $cleanCatatan,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getSalesReport(): array
    {
        $rows = $this->db->query(
            'SELECT e.id, e.kode, e.nama, c.nama AS kategori_nama,
                    COALESCE(MIN(tc.harga), 0) AS harga_mulai,
                    COUNT(CASE WHEN o.status = "Lunas" THEN t.id END) AS tiket_terjual,
                    COUNT(CASE WHEN o.status = "Dibatalkan" OR t.status_checkin = "Dibatalkan" THEN t.id END) AS tiket_dibatalkan
             FROM events e
             JOIN categories c ON c.id = e.category_id
             LEFT JOIN ticket_categories tc ON tc.event_id = e.id
             LEFT JOIN tickets t ON t.event_id = e.id
             LEFT JOIN orders o ON o.id = t.order_id
             WHERE e.deleted_at IS NULL
             GROUP BY e.id, e.kode, e.nama, c.nama
             ORDER BY e.id ASC'
        )->fetchAll();

        // Also compute exact revenue per event from Lunas orders
        $revRows = $this->db->query(
            'SELECT event_id, COALESCE(SUM(jumlah), 0) AS jml_terjual, COALESCE(SUM(subtotal), 0) AS total_penjualan
             FROM orders
             WHERE status = "Lunas"
             GROUP BY event_id'
        )->fetchAll();
        $revMap = [];
        foreach ($revRows as $r) {
            $revMap[(int) $r['event_id']] = [
                'jml'   => (int) $r['jml_terjual'],
                'total' => (int) $r['total_penjualan'],
            ];
        }

        $report = [];
        $grandSold = 0;
        $grandCanceled = 0;
        $grandRevenue = 0;

        foreach ($rows as $row) {
            $eid = (int) $row['id'];
            $sold = $revMap[$eid]['jml'] ?? 0;
            $revenue = $revMap[$eid]['total'] ?? 0;
            $canceled = (int) $row['tiket_dibatalkan'];

            $grandSold += $sold;
            $grandCanceled += $canceled;
            $grandRevenue += $revenue;

            $report[] = [
                'id'               => $eid,
                'kode'             => $row['kode'],
                'nama'             => $row['nama'],
                'kategori_nama'    => $row['kategori_nama'],
                'harga_mulai'      => (int) $row['harga_mulai'],
                'tiket_terjual'    => $sold,
                'tiket_dibatalkan' => $canceled,
                'total_penjualan'  => $revenue,
            ];
        }

        return [
            'rows'           => $report,
            'total_terjual'  => $grandSold,
            'total_batal'    => $grandCanceled,
            'total_penjualan'=> $grandRevenue,
        ];
    }

    public function getAllPassengers(): array
    {
        return $this->db->query(
            'SELECT t.id, t.kode_tiket, t.nama_pemegang, t.email, t.wa, t.status_checkin, t.waktu_checkin,
                    e.id AS event_id, e.kode AS event_kode, e.nama AS event_nama,
                    tc.nama AS kategori_tiket_nama, o.nomor_invoice
             FROM tickets t
             JOIN events e ON e.id = t.event_id
             JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             JOIN orders o ON o.id = t.order_id
             ORDER BY t.id ASC'
        )->fetchAll();
    }
}
