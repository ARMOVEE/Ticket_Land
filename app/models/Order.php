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
        $this->releaseExpiredHolds($ticketCategoryId);

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
     * Lazy-releases holds that have expired past expires_at without payment.
     * Restores stock atomically to ticket_categories and sets status_hold = 'RELEASED'.
     */
    public function releaseExpiredHolds(?int $ticketCategoryId = null): int
    {
        $sql = 'SELECT id, event_id, ticket_category_id, jumlah, catatan
                FROM stock_transactions
                WHERE status_hold = "HOLD" AND expires_at <= NOW()';
        $params = [];
        if ($ticketCategoryId !== null) {
            $sql .= ' AND ticket_category_id = :tcid';
            $params[':tcid'] = $ticketCategoryId;
        }
        $sql .= ' FOR UPDATE';

        $inTrans = $this->db->inTransaction();
        if (!$inTrans) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $expiredRows = $stmt->fetchAll();

            $releasedCount = 0;
            if (!empty($expiredRows)) {
                $stmtReturnStock = $this->db->prepare(
                    'UPDATE ticket_categories
                     SET stok_sisa = stok_sisa + :jml1,
                         status = CASE WHEN (stok_sisa + :jml2) >= 1 THEN "On Sale" ELSE "Habis" END
                     WHERE id = :tcid'
                );

                $stmtUpdateHold = $this->db->prepare(
                    'UPDATE stock_transactions
                     SET status_hold = "RELEASED",
                         catatan = CONCAT(catatan, " [Expired auto-released]")
                     WHERE id = :id'
                );

                foreach ($expiredRows as $row) {
                    $stmtReturnStock->execute([
                        ':jml1' => (int) $row['jumlah'],
                        ':jml2' => (int) $row['jumlah'],
                        ':tcid' => (int) $row['ticket_category_id'],
                    ]);

                    $stmtUpdateHold->execute([
                        ':id' => (int) $row['id'],
                    ]);
                    $releasedCount++;
                }
            }

            if (!$inTrans) {
                $this->db->commit();
            }
            return $releasedCount;
        } catch (\Throwable $e) {
            if (!$inTrans && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Atomically holds stock for a checkout session.
     * Decrements stok_sisa in ONE atomic statement:
     * UPDATE ticket_categories SET stok_sisa = stok_sisa - :jumlah WHERE id = :id AND stok_sisa >= :jumlah;
     * If 0 rows affected, checks available stock and returns clear rejection message.
     */
    public function holdStock(int $ticketCategoryId, int $jumlah, string $checkoutToken, int $durationSeconds = 600): array
    {
        if ($jumlah < 1 || $jumlah > 5) {
            return [
                'ok'      => false,
                'message' => 'Jumlah tiket harus antara 1 sampai 5 tiket per transaksi (BR-02).',
                'sisa'    => 0,
            ];
        }

        $this->db->beginTransaction();
        try {
            // 1. Release expired holds first (lazy release)
            $this->releaseExpiredHolds($ticketCategoryId);

            // 2. Atomic hold decrement with single query (anti-race condition)
            $stmtUpdate = $this->db->prepare(
                'UPDATE ticket_categories
                 SET stok_sisa = stok_sisa - :jml1,
                     status = CASE WHEN (stok_sisa - :jml2) >= 1 THEN "On Sale" ELSE "Habis" END
                 WHERE id = :id AND stok_sisa >= :jml3'
            );
            $stmtUpdate->execute([
                ':jml1' => $jumlah,
                ':jml2' => $jumlah,
                ':id'   => $ticketCategoryId,
                ':jml3' => $jumlah,
            ]);

            if ($stmtUpdate->rowCount() === 0) {
                // Stock is insufficient! Check current available stock
                $stmtCheck = $this->db->prepare(
                    'SELECT tc.stok_sisa, e.deleted_at, e.status AS event_status
                     FROM ticket_categories tc
                     JOIN events e ON e.id = tc.event_id
                     WHERE tc.id = :id'
                );
                $stmtCheck->execute([':id' => $ticketCategoryId]);
                $cur = $stmtCheck->fetch();
                $this->db->rollBack();

                if (!$cur || $cur['deleted_at'] !== null || $cur['event_status'] !== 'aktif') {
                    return [
                        'ok'      => false,
                        'message' => 'Kategori tiket atau event tidak ditemukan.',
                        'sisa'    => 0,
                    ];
                }

                $sisa = (int) $cur['stok_sisa'];
                if ($sisa <= 0) {
                    return [
                        'ok'      => false,
                        'message' => 'Maaf, tiket sudah habis.',
                        'sisa'    => 0,
                    ];
                }

                return [
                    'ok'      => false,
                    'message' => "Stok tersisa {$sisa} tiket.",
                    'sisa'    => $sisa,
                ];
            }

            // Fetch updated ticket category and event details
            $stmtTc = $this->db->prepare(
                'SELECT tc.*, e.nama AS event_nama, e.kode AS event_kode, e.tanggal_label, e.jam_label,
                        e.venue, e.kota, e.deleted_at, e.status AS event_status
                 FROM ticket_categories tc
                 JOIN events e ON e.id = tc.event_id
                 WHERE tc.id = :id
                 LIMIT 1'
            );
            $stmtTc->execute([':id' => $ticketCategoryId]);
            $tc = $stmtTc->fetch();

            $eventId = (int) $tc['event_id'];
            $stokSisaBaru = (int) $tc['stok_sisa'];
            $expiresAt = date('Y-m-d H:i:s', time() + $durationSeconds);

            // 3. Record hold in stock_transactions
            $stmtHold = $this->db->prepare(
                'INSERT INTO stock_transactions (
                    event_id, ticket_category_id, jenis, jumlah, sisa_sesudah,
                    pelaku, catatan, order_id, checkout_token, status_hold, expires_at, waktu
                 ) VALUES (
                    :eid, :tcid, "HOLD", :jml, :sisa,
                    "sistem", :catatan, NULL, :token, "HOLD", :exp, NOW()
                 )'
            );
            $stmtHold->execute([
                ':eid'     => $eventId,
                ':tcid'    => $ticketCategoryId,
                ':jml'     => $jumlah,
                ':sisa'    => $stokSisaBaru,
                ':catatan' => 'Hold checkout (' . substr($checkoutToken, 0, 8) . '...)',
                ':token'   => $checkoutToken,
                ':exp'     => $expiresAt,
            ]);
            $holdId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'ok'                => true,
                'hold_id'           => $holdId,
                'ticket_category'   => [
                    'id'        => (int) $tc['id'],
                    'event_id'  => $eventId,
                    'nama'      => $tc['nama'],
                    'harga'     => (int) $tc['harga'],
                    'stok_sisa' => $stokSisaBaru,
                    'status'    => $stokSisaBaru >= 1 ? 'On Sale' : 'Habis',
                ],
                'jumlah'            => $jumlah,
                'checkout_token'    => $checkoutToken,
                'remaining_seconds' => $durationSeconds,
                'expires_at'        => strtotime($expiresAt),
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Releases an active hold by checkout token (e.g. when cancelled or session reset).
     */
    public function releaseHoldByToken(string $checkoutToken): bool
    {
        if (trim($checkoutToken) === '') {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT id, ticket_category_id, jumlah
                 FROM stock_transactions
                 WHERE checkout_token = :token AND status_hold = "HOLD"
                 FOR UPDATE'
            );
            $stmt->execute([':token' => $checkoutToken]);
            $hold = $stmt->fetch();

            if (!$hold) {
                $this->db->commit();
                return false;
            }

            $jml = (int) $hold['jumlah'];
            $tcid = (int) $hold['ticket_category_id'];
            $holdId = (int) $hold['id'];

            $stmtReturn = $this->db->prepare(
                'UPDATE ticket_categories
                 SET stok_sisa = stok_sisa + :jml1,
                     status = CASE WHEN (stok_sisa + :jml2) >= 1 THEN "On Sale" ELSE "Habis" END
                 WHERE id = :tcid'
            );
            $stmtReturn->execute([':jml1' => $jml, ':jml2' => $jml, ':tcid' => $tcid]);

            $stmtUpdateHold = $this->db->prepare(
                'UPDATE stock_transactions
                 SET status_hold = "RELEASED",
                     catatan = CONCAT(catatan, " [Released by user]")
                 WHERE id = :id'
            );
            $stmtUpdateHold->execute([':id' => $holdId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates held quantity atomically when user changes quantity in stepper.
     */
    public function updateHoldQuantity(string $checkoutToken, int $newQuantity): array
    {
        if ($newQuantity < 1 || $newQuantity > 5) {
            return [
                'ok'      => false,
                'message' => 'Jumlah tiket harus antara 1 sampai 5 tiket per transaksi (BR-02).',
            ];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT id, ticket_category_id, jumlah, expires_at
                 FROM stock_transactions
                 WHERE checkout_token = :token AND status_hold = "HOLD"
                 FOR UPDATE'
            );
            $stmt->execute([':token' => $checkoutToken]);
            $hold = $stmt->fetch();

            if (!$hold) {
                $this->db->rollBack();
                return [
                    'ok'      => false,
                    'message' => 'Sesi hold tidak ditemukan atau sudah berakhir.',
                ];
            }

            if (strtotime($hold['expires_at']) <= time()) {
                $this->db->rollBack();
                $this->releaseExpiredHolds((int) $hold['ticket_category_id']);
                return [
                    'ok'      => false,
                    'expired' => true,
                    'message' => 'Waktu pemesanan habis. Silakan ulangi pemesanan.',
                ];
            }

            $currentHoldQty = (int) $hold['jumlah'];
            $diff = $newQuantity - $currentHoldQty;
            $tcId = (int) $hold['ticket_category_id'];
            $holdId = (int) $hold['id'];

            if ($diff === 0) {
                $this->db->commit();
                return ['ok' => true, 'jumlah' => $newQuantity];
            }

            if ($diff > 0) {
                // Needs more tickets: check atomic decrement of $diff
                $this->releaseExpiredHolds($tcId);

                $stmtUpdate = $this->db->prepare(
                    'UPDATE ticket_categories
                     SET stok_sisa = stok_sisa - :diff1,
                         status = CASE WHEN (stok_sisa - :diff2) >= 1 THEN "On Sale" ELSE "Habis" END
                     WHERE id = :id AND stok_sisa >= :diff3'
                );
                $stmtUpdate->execute([
                    ':diff1' => $diff,
                    ':diff2' => $diff,
                    ':id'    => $tcId,
                    ':diff3' => $diff,
                ]);

                if ($stmtUpdate->rowCount() === 0) {
                    $stmtCheck = $this->db->prepare('SELECT stok_sisa FROM ticket_categories WHERE id = :id');
                    $stmtCheck->execute([':id' => $tcId]);
                    $availableAdditional = (int) $stmtCheck->fetchColumn();
                    $totalMax = $currentHoldQty + $availableAdditional;
                    $this->db->rollBack();

                    if ($availableAdditional <= 0) {
                        return [
                            'ok'      => false,
                            'message' => 'Maaf, sisa tiket tidak mencukupi untuk penambahan.',
                            'sisa'    => $currentHoldQty,
                        ];
                    }
                    return [
                        'ok'      => false,
                        'message' => "Stok tersisa {$totalMax} tiket.",
                        'sisa'    => $totalMax,
                    ];
                }
            } else {
                // Reducing quantity: return excess tickets to stock
                $excess = abs($diff);
                $stmtReturn = $this->db->prepare(
                    'UPDATE ticket_categories
                     SET stok_sisa = stok_sisa + :exc1,
                         status = CASE WHEN (stok_sisa + :exc2) >= 1 THEN "On Sale" ELSE "Habis" END
                     WHERE id = :id'
                );
                $stmtReturn->execute([
                    ':exc1' => $excess,
                    ':exc2' => $excess,
                    ':id'   => $tcId,
                ]);
            }

            // Update hold transaction
            $stmtUpdateHold = $this->db->prepare(
                'UPDATE stock_transactions
                 SET jumlah = :newQty,
                     sisa_sesudah = (SELECT stok_sisa FROM ticket_categories WHERE id = :tcid)
                 WHERE id = :id'
            );
            $stmtUpdateHold->execute([':newQty' => $newQuantity, ':tcid' => $tcId, ':id' => $holdId]);

            $this->db->commit();
            return [
                'ok'     => true,
                'jumlah' => $newQuantity,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
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
        $checkoutToken = trim((string) ($payload['checkout_token'] ?? ''));

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

            // Check if there is an active hold for this checkout_token
            $activeHold = null;
            if ($checkoutToken !== '') {
                $stmtHold = $this->db->prepare(
                    'SELECT id, jumlah, status_hold, expires_at
                     FROM stock_transactions
                     WHERE checkout_token = :token AND ticket_category_id = :tcid AND status_hold = "HOLD"
                     ORDER BY id DESC LIMIT 1
                     FOR UPDATE'
                );
                $stmtHold->execute([':token' => $checkoutToken, ':tcid' => $ticketCategoryId]);
                $activeHold = $stmtHold->fetch();
            }

            $stokSisa = (int) $tc['stok_sisa'];
            $isHoldingStock = false;

            if ($activeHold) {
                if (strtotime((string) $activeHold['expires_at']) <= time()) {
                    throw new RuntimeException('Waktu pemesanan habis. Silakan ulangi pemesanan.');
                }
                $isHoldingStock = true;
            } else {
                if ($stokSisa < 1) {
                    throw new RuntimeException('Mohon maaf, tiket kategori ini sudah habis (BR-06).');
                }
                if ($jumlah > $stokSisa) {
                    throw new RuntimeException("Stok tiket tidak mencukupi. Sisa stok saat ini: {$stokSisa} tiket.");
                }
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

            if ($isHoldingStock) {
                // (3) & (4): Stok sudah berkurang saat hold (holdStock). JANGAN kurangi stok_sisa lagi!
                $sisaBaru = $stokSisa; // stok_sisa di DB sudah terpotong saat hold
                $statusBaru = $sisaBaru >= 1 ? 'On Sale' : 'Habis';

                $stmtUpdateTc = $this->db->prepare(
                    'UPDATE ticket_categories SET status = :status WHERE id = :id'
                );
                $stmtUpdateTc->execute([
                    ':status' => $statusBaru,
                    ':id'     => $ticketCategoryId,
                ]);

                // Ubah status hold di stock_transactions menjadi 'PAID' dan jenis 'OUT'
                $stmtUpdateHold = $this->db->prepare(
                    'UPDATE stock_transactions
                     SET jenis = "OUT",
                         status_hold = "PAID",
                         order_id = :oid,
                         sisa_sesudah = :sisa,
                         catatan = :catatan
                     WHERE id = :hid'
                );
                $stmtUpdateHold->execute([
                    ':oid'     => $orderId,
                    ':sisa'    => $sisaBaru,
                    ':catatan' => 'Penjualan online (' . $nomorInvoice . ')',
                    ':hid'     => (int) $activeHold['id'],
                ]);
            } else {
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
                        pelaku, catatan, order_id, status_hold, waktu
                     ) VALUES (
                        :eid, :tcid, "OUT", :jml, :sisa,
                        "sistem", :catatan, :oid, "PAID", NOW()
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
            }

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
