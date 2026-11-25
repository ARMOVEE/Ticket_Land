<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Order extends Model
{
    private const CODE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function generateUniqueTicketCode(): string
    {
        $maxLen = strlen(self::CODE_CHARS) - 1;
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::CODE_CHARS[random_int(0, $maxLen)];
            }
            $code = 'TKL-' . $suffix;

            $stmt = $this->db->prepare('SELECT COUNT(*) FROM tickets WHERE kode_tiket = :c');
            $stmt->execute([':c' => $code]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $code;
            }
        }
        throw new RuntimeException('Gagal membuat kode tiket unik.');
    }

    private function generateUniqueInvoiceNo(): string
    {
        $datePart = date('Ymd');
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $randPart = (string) random_int(100000, 999999);
            $inv = "INV-{$datePart}-{$randPart}";

            $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE nomor_invoice = :inv');
            $stmt->execute([':inv' => $inv]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $inv;
            }
        }
        throw new RuntimeException('Gagal membuat nomor invoice unik.');
    }

    public function getTicketCategoryStock(int $ticketCategoryId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT tc.*, e.nama AS event_nama, e.kode AS event_kode, e.tanggal_label, e.jam_label, e.venue, e.kota,
                    e.deleted_at, e.status AS event_status
             FROM ticket_categories tc
             JOIN events e ON e.id = tc.event_id
             WHERE tc.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $ticketCategoryId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Atomic checkout per BR-05, BR-06, BR-07, BR-08, BR-09.
     * Steps (1) order Lunas, (2) tickets + QR issued, (3) stock transaction OUT recorded, (4) stock decremented
     * all run inside one database transaction with row-level lock FOR UPDATE.
     */
    public function createPaidOrder(array $payload): array
    {
        $ticketCategoryId = (int) ($payload['ticket_category_id'] ?? 0);
        $jumlah = (int) ($payload['jumlah'] ?? 0);
        $metode = trim((string) ($payload['metode'] ?? 'QRIS'));
        $akunUserId = !empty($payload['akun_user_id']) ? (int) $payload['akun_user_id'] : null;
        $buyer = $payload['buyer'] ?? [];
        $attendees = $payload['attendees'] ?? [];

        if ($jumlah < 1 || $jumlah > 5) {
            throw new RuntimeException('Jumlah tiket harus antara 1 sampai 5 tiket per transaksi (BR-02).');
        }
        if (count($attendees) !== $jumlah) {
            throw new RuntimeException('Jumlah data pemegang tiket harus sama dengan jumlah tiket yang dipesan (BR-03).');
        }

        $this->db->beginTransaction();
        try {
            // Lock ticket category row to prevent overselling on concurrent requests (AC-12)
            $stmtLock = $this->db->prepare(
                'SELECT tc.id, tc.event_id, tc.nama AS kategori_tiket_nama, tc.harga, tc.stok_sisa,
                        e.kode AS event_kode, e.nama AS event_nama, e.tanggal_label, e.jam_label,
                        e.venue, e.kota, e.deleted_at
                 FROM ticket_categories tc
                 JOIN events e ON e.id = tc.event_id
                 WHERE tc.id = :id
                 FOR UPDATE'
            );
            $stmtLock->execute([':id' => $ticketCategoryId]);
            $tc = $stmtLock->fetch();

            if (!$tc || $tc['deleted_at'] !== null) {
                throw new RuntimeException('Kategori tiket atau event tidak ditemukan.');
            }

            $stokSisa = (int) $tc['stok_sisa'];
            if ($stokSisa < 1) {
                throw new RuntimeException('Mohon maaf, tiket kategori ini sudah habis (BR-06).');
            }
            if ($jumlah > $stokSisa) {
                throw new RuntimeException("Stok tiket tidak mencukupi. Sisa stok saat ini: {$stokSisa} tiket.");
            }

            $hargaSatuan = (int) $tc['harga'];
            $subtotal = $hargaSatuan * $jumlah;
            $biayaLayanan = $subtotal === 0 ? 0 : (int) round($subtotal * 0.03);
            $total = $subtotal + $biayaLayanan;
            if ($subtotal === 0) {
                $metode = 'Tanpa pembayaran (GRATIS)';
            }

            $nomorInvoice = $this->generateUniqueInvoiceNo();
            $eventId = (int) $tc['event_id'];

            // (1) Pesanan Lunas
            $stmtOrder = $this->db->prepare(
                'INSERT INTO orders (
                    nomor_invoice, akun_user_id, event_id, ticket_category_id, jumlah,
                    subtotal, biaya_layanan, total, metode_pembayaran, status,
                    pemesan_nama, pemesan_tipe_identitas, pemesan_nomor_identitas, pemesan_email, pemesan_wa, created_at
                 ) VALUES (
                    :inv, :uid, :eid, :tcid, :jml,
                    :sub, :fee, :tot, :metode, "Lunas",
                    :pnama, :ptipe, :pno, :pemail, :pwa, NOW()
                 )'
            );
            $stmtOrder->execute([
                ':inv'    => $nomorInvoice,
                ':uid'    => $akunUserId,
                ':eid'    => $eventId,
                ':tcid'   => $ticketCategoryId,
                ':jml'    => $jumlah,
                ':sub'    => $subtotal,
                ':fee'    => $biayaLayanan,
                ':tot'    => $total,
                ':metode' => $metode,
                ':pnama'  => trim((string) ($buyer['nama'] ?? '')),
                ':ptipe'  => trim((string) ($buyer['tipe_identitas'] ?? 'KTP')),
                ':pno'    => trim((string) ($buyer['nomor_identitas'] ?? '')),
                ':pemail' => trim((string) ($buyer['email'] ?? '')),
                ':pwa'    => trim((string) ($buyer['wa'] ?? '')),
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $stmtPay = $this->db->prepare(
                'INSERT INTO payments (order_id, metode, referensi, status, waktu)
                 VALUES (:oid, :metode, :ref, "Berhasil", NOW())'
            );
            $stmtPay->execute([
                ':oid'    => $orderId,
                ':metode' => $metode,
                ':ref'    => 'DEMO-' . $nomorInvoice,
            ]);

            // (2) Tiket + QR terbit per pemegang
            $issuedTickets = [];
            $stmtTicket = $this->db->prepare(
                'INSERT INTO tickets (
                    order_id, event_id, ticket_category_id, kode_tiket, nama_pemegang,
                    tipe_identitas, nomor_identitas, email, jenis_kelamin, wa,
                    status_checkin, created_at
                 ) VALUES (
                    :oid, :eid, :tcid, :kode, :nama,
                    :tipe, :nomor, :email, :jk, :wa,
                    "Belum Check-in", NOW()
                 )'
            );

            foreach ($attendees as $idx => $att) {
                $kodeTiket = $this->generateUniqueTicketCode();
                $namaPemegang = trim((string) ($att['nama'] ?? ''));
                $tipeId = trim((string) ($att['tipe_identitas'] ?? 'KTP'));
                $nomorId = trim((string) ($att['nomor_identitas'] ?? ''));
                $emailAtt = trim((string) ($att['email'] ?? ''));
                $jkAtt = trim((string) ($att['jenis_kelamin'] ?? 'Laki-laki'));
                $waAtt = trim((string) ($att['wa'] ?? ''));

                $stmtTicket->execute([
                    ':oid'   => $orderId,
                    ':eid'   => $eventId,
                    ':tcid'  => $ticketCategoryId,
                    ':kode'  => $kodeTiket,
                    ':nama'  => $namaPemegang,
                    ':tipe'  => $tipeId,
                    ':nomor' => $nomorId,
                    ':email' => $emailAtt,
                    ':jk'    => $jkAtt,
                    ':wa'    => $waAtt,
                ]);

                $issuedTickets[] = [
                    'nomor_urut'     => $idx + 1,
                    'total_tiket'    => $jumlah,
                    'kode_tiket'     => $kodeTiket,
                    'nama_pemegang'  => $namaPemegang,
                    'tipe_identitas' => $tipeId,
                    'email'          => $emailAtt,
                    'jenis_kelamin'  => $jkAtt,
                    'wa'             => $waAtt,
                ];
            }

            // (3) Transaksi Keluar tercatat & (4) Stok berkurang + disable bila habis (BR-06, BR-07)
            $sisaBaru = $stokSisa - $jumlah;
            $statusBaru = $sisaBaru >= 1 ? 'On Sale' : 'Habis';

            $stmtUpdateTc = $this->db->prepare(
                'UPDATE ticket_categories
                 SET stok_sisa = :sisa, status = :status
                 WHERE id = :id'
            );
            $stmtUpdateTc->execute([
                ':sisa'   => $sisaBaru,
                ':status' => $statusBaru,
                ':id'     => $ticketCategoryId,
            ]);

            $stmtSt = $this->db->prepare(
                'INSERT INTO stock_transactions (
                    event_id, ticket_category_id, jenis, jumlah, sisa_sesudah,
                    pelaku, catatan, order_id, waktu
                 ) VALUES (
                    :eid, :tcid, "OUT", :jml, :sisa,
                    "sistem", :catatan, :oid, NOW()
                 )'
            );
            $stmtSt->execute([
                ':eid'     => $eventId,
                ':tcid'    => $ticketCategoryId,
                ':jml'     => $jumlah,
                ':sisa'    => $sisaBaru,
                ':catatan' => 'Penjualan online (' . $nomorInvoice . ')',
                ':oid'     => $orderId,
            ]);

            $this->db->commit();

            return [
                'order_id'            => $orderId,
                'nomor_invoice'       => $nomorInvoice,
                'tanggal_transaksi'   => date('j F Y • H:i') . ' WIB',
                'event_id'            => $eventId,
                'event_kode'          => $tc['event_kode'],
                'event_nama'          => $tc['event_nama'],
                'tanggal_label'       => $tc['tanggal_label'],
                'jam_label'           => $tc['jam_label'],
                'venue'               => $tc['venue'],
                'kota'                => $tc['kota'],
                'ticket_category_id'  => $ticketCategoryId,
                'kategori_tiket_nama' => $tc['kategori_tiket_nama'],
                'jumlah'              => $jumlah,
                'subtotal'            => $subtotal,
                'biaya_layanan'       => $biayaLayanan,
                'total'               => $total,
                'metode_pembayaran'   => $metode,
                'status'              => 'LUNAS',
                'stok_sisa_baru'      => $sisaBaru,
                'status_tiket_baru'   => $statusBaru,
                'pemesan'             => [
                    'nama'  => trim((string) ($buyer['nama'] ?? '')),
                    'email' => trim((string) ($buyer['email'] ?? '')),
                    'wa'    => trim((string) ($buyer['wa'] ?? '')),
                ],
                'tickets'             => $issuedTickets,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
