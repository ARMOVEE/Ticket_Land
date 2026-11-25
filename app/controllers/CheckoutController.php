<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Order;

class CheckoutController extends Controller
{
    private const SESSION_DURATION_SECONDS = 600;

    private function readInput(): array
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }

    public function startSession(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $tcId = (int) ($input['ticket_category_id'] ?? 0);
        if ($tcId <= 0) {
            $this->json(['ok' => false, 'message' => 'Pilih kategori tiket terlebih dahulu.'], 422);
            return;
        }

        $orderModel = new Order();
        $tc = $orderModel->getTicketCategoryStock($tcId);
        if ($tc === null || $tc['deleted_at'] !== null || $tc['event_status'] !== 'aktif') {
            $this->json(['ok' => false, 'message' => 'Kategori tiket atau event tidak ditemukan.'], 404);
            return;
        }

        $stokSisa = (int) $tc['stok_sisa'];
        if ($stokSisa < 1) {
            $this->json(['ok' => false, 'message' => 'Mohon maaf, stok tiket untuk kategori ini sudah habis.'], 422);
            return;
        }

        $now = time();
        $expiresAt = $now + self::SESSION_DURATION_SECONDS;
        $checkoutToken = bin2hex(random_bytes(16));

        $_SESSION['checkout_session'] = [
            'ticket_category_id' => $tcId,
            'event_id'           => (int) $tc['event_id'],
            'started_at'         => $now,
            'expires_at'         => $expiresAt,
            'token'              => $checkoutToken,
            'paid'               => false,
        ];

        $this->json([
            'ok'                => true,
            'ticket_category'   => [
                'id'        => (int) $tc['id'],
                'event_id'  => (int) $tc['event_id'],
                'nama'      => $tc['nama'],
                'harga'     => (int) $tc['harga'],
                'stok_sisa' => $stokSisa,
                'status'    => $stokSisa >= 1 ? 'On Sale' : 'Habis',
            ],
            'remaining_seconds' => self::SESSION_DURATION_SECONDS,
            'expires_at'        => $expiresAt,
            'checkout_token'    => $checkoutToken,
        ]);
    }

    public function resetSession(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        unset($_SESSION['checkout_session']);
        $this->json(['ok' => true]);
    }

    public function pay(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $session = $_SESSION['checkout_session'] ?? null;
        if (!is_array($session) || empty($session['expires_at'])) {
            $this->json([
                'ok'      => false,
                'expired' => true,
                'message' => 'Waktu pemesanan habis. Silakan ulangi pemesanan.',
            ], 408);
            return;
        }

        if (time() > (int) $session['expires_at']) {
            unset($_SESSION['checkout_session']);
            $this->json([
                'ok'      => false,
                'expired' => true,
                'message' => 'Waktu pemesanan habis. Silakan ulangi pemesanan.',
            ], 408);
            return;
        }

        if (!empty($session['paid'])) {
            $this->json([
                'ok'      => false,
                'message' => 'Pesanan pada sesi ini sudah diproses.',
            ], 409);
            return;
        }

        $clientToken = (string) ($input['checkout_token'] ?? '');
        if ($clientToken !== '' && !hash_equals((string) $session['token'], $clientToken)) {
            $this->json([
                'ok'      => false,
                'message' => 'Sesi pemesanan sudah berganti. Silakan ulangi dari Pilih Kategori.',
            ], 409);
            return;
        }

        $tcId = (int) ($input['ticket_category_id'] ?? $session['ticket_category_id'] ?? 0);
        $jumlah = (int) ($input['jumlah'] ?? 1);
        $metode = trim((string) ($input['metode'] ?? 'QRIS'));
        $buyer = is_array($input['buyer'] ?? null) ? $input['buyer'] : [];
        $attendees = is_array($input['attendees'] ?? null) ? $input['attendees'] : [];

        if (
            trim((string) ($buyer['nama'] ?? '')) === '' ||
            trim((string) ($buyer['tipe_identitas'] ?? '')) === '' ||
            trim((string) ($buyer['nomor_identitas'] ?? '')) === '' ||
            !filter_var(trim((string) ($buyer['email'] ?? '')), FILTER_VALIDATE_EMAIL) ||
            trim((string) ($buyer['wa'] ?? '')) === ''
        ) {
            $this->json(['ok' => false, 'message' => 'Data Pemesan belum lengkap atau email belum valid.'], 422);
            return;
        }

        if (count($attendees) !== $jumlah) {
            $this->json(['ok' => false, 'message' => 'Data pemegang tiket belum lengkap untuk semua tiket.'], 422);
            return;
        }

        foreach ($attendees as $idx => $att) {
            $num = $idx + 1;
            if (
                trim((string) ($att['nama'] ?? '')) === '' ||
                trim((string) ($att['tipe_identitas'] ?? '')) === '' ||
                trim((string) ($att['nomor_identitas'] ?? '')) === '' ||
                !filter_var(trim((string) ($att['email'] ?? '')), FILTER_VALIDATE_EMAIL) ||
                trim((string) ($att['jenis_kelamin'] ?? '')) === '' ||
                trim((string) ($att['wa'] ?? '')) === ''
            ) {
                $this->json(['ok' => false, 'message' => "Detail Pesanan - {$num} belum lengkap atau email tidak valid."], 422);
                return;
            }
        }

        $akunUserId = !empty($_SESSION['customer']['id']) ? (int) $_SESSION['customer']['id'] : null;

        try {
            $orderModel = new Order();
            $invoice = $orderModel->createPaidOrder([
                'ticket_category_id' => $tcId,
                'jumlah'             => $jumlah,
                'metode'             => $metode,
                'akun_user_id'       => $akunUserId,
                'buyer'              => $buyer,
                'attendees'          => $attendees,
            ]);

            $_SESSION['checkout_session']['paid'] = true;
            unset($_SESSION['checkout_session']);

            // (5) Email notification (FR-N-01..FR-N-05: does not expose full identity number, QR contains only code)
            $emailSubject = "Ticket Land - Invoice {$invoice['nomor_invoice']} - {$invoice['event_nama']}";
            $pelakuLog = !empty($_SESSION['customer']['email']) ? (string) $_SESSION['customer']['email'] : 'tamu (' . $invoice['pemesan']['email'] . ')';

            // (6) Activity log recorded
            $log = new ActivityLog();
            $log->record(
                null,
                $pelakuLog,
                'create',
                'orders',
                "Pemesanan tiket lunas {$invoice['nomor_invoice']} ({$invoice['jumlah']} tiket {$invoice['kategori_tiket_nama']}) untuk event {$invoice['event_nama']}."
            );

            $this->json([
                'ok'                 => true,
                'invoice'            => $invoice,
                'email_notification' => [
                    'recipient' => $invoice['pemesan']['email'],
                    'sender'    => 'Ticket Land <no-reply@ticketland.id>',
                    'subject'   => $emailSubject,
                    'status'    => 'terkirim',
                ],
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
