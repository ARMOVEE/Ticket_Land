<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Order;

class CheckoutController extends Controller
{
    private const SESSION_STEP1_2_SECONDS = 900; // 15 menit (Langkah Pilih Kategori + Detail Pesanan)
    private const SESSION_STEP3_SECONDS = 600;   // 10 menit (Langkah Metode Pembayaran)

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

        $jumlah = (int) ($input['jumlah'] ?? 1);
        if ($jumlah < 1 || $jumlah > 4) {
            $this->json([
                'ok'      => false,
                'message' => 'Maksimal pembelian adalah 4 tiket dalam 1 kali transaksi.',
                'sisa'    => 0,
            ], 422);
            return;
        }

        $orderModel = new Order();

        // Release any existing hold for this session
        if (!empty($_SESSION['checkout_session']['token'])) {
            $orderModel->releaseHoldByToken((string) $_SESSION['checkout_session']['token']);
            unset($_SESSION['checkout_session']);
        }

        $checkoutToken = bin2hex(random_bytes(16));
        $hold = $orderModel->holdStock($tcId, $jumlah, $checkoutToken, self::SESSION_STEP1_2_SECONDS);

        if (!$hold['ok']) {
            $this->json($hold, 422);
            return;
        }

        $_SESSION['checkout_session'] = [
            'ticket_category_id' => $tcId,
            'event_id'           => (int) $hold['ticket_category']['event_id'],
            'jumlah'             => $jumlah,
            'started_at'         => time(),
            'expires_at'         => time() + self::SESSION_STEP1_2_SECONDS,
            'token'              => $checkoutToken,
            'paid'               => false,
        ];

        $this->json([
            'ok'                => true,
            'ticket_category'   => $hold['ticket_category'],
            'jumlah'            => $jumlah,
            'remaining_seconds' => self::SESSION_STEP1_2_SECONDS,
            'expires_at'        => time() + self::SESSION_STEP1_2_SECONDS,
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

        $session = $_SESSION['checkout_session'] ?? null;
        if (is_array($session) && !empty($session['token'])) {
            $orderModel = new Order();
            $orderModel->releaseHoldByToken((string) $session['token']);
        }

        unset($_SESSION['checkout_session']);
        $this->json(['ok' => true]);
    }

    public function updateQty(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $session = $_SESSION['checkout_session'] ?? null;
        if (!is_array($session) || empty($session['token'])) {
            $this->json(['ok' => false, 'message' => 'Sesi pemesanan tidak ditemukan.'], 404);
            return;
        }

        $token = (string) $session['token'];
        $newQty = (int) ($input['jumlah'] ?? 1);
        if ($newQty < 1 || $newQty > 4) {
            $this->json(['ok' => false, 'message' => 'Maksimal pembelian adalah 4 tiket dalam 1 kali transaksi.'], 422);
            return;
        }

        $orderModel = new Order();
        $res = $orderModel->updateHoldQuantity($token, $newQty);

        if (!$res['ok']) {
            $this->json($res, 422);
            return;
        }

        $_SESSION['checkout_session']['jumlah'] = $newQty;
        $this->json([
            'ok'     => true,
            'jumlah' => $newQty,
        ]);
    }

    public function extendPaymentSession(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $session = $_SESSION['checkout_session'] ?? null;
        if (!is_array($session) || empty($session['token'])) {
            $this->json(['ok' => false, 'message' => 'Sesi pemesanan tidak ditemukan.'], 408);
            return;
        }

        $orderModel = new Order();
        $orderModel->extendHoldByToken((string) $session['token'], self::SESSION_STEP3_SECONDS);

        $newExpiresAt = time() + self::SESSION_STEP3_SECONDS;
        $_SESSION['checkout_session']['expires_at'] = $newExpiresAt;

        $this->json([
            'ok'                => true,
            'remaining_seconds' => self::SESSION_STEP3_SECONDS,
            'expires_at'        => $newExpiresAt,
        ]);
    }

    private function validatePerson(array $data, string $label): ?string
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        $nik = trim((string) ($data['nomor_identitas'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $wa = trim((string) ($data['wa'] ?? ''));
        $waDigits = preg_replace('/^\+?62/', '', $wa);
        $gender = trim((string) ($data['jenis_kelamin'] ?? ''));

        if ($nama === '') {
            return "{$label}: Nama Lengkap wajib diisi.";
        }
        if (!preg_match('/^[0-9]+$/', $nik)) {
            return "{$label}: Nomor Identitas (KTP/NIK) hanya boleh berisi angka (0-9).";
        }
        if (strlen($nik) !== 16) {
            return "{$label}: Nomor Identitas (KTP/NIK) harus tepat 16 digit, tidak boleh kurang atau lebih.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
            return "{$label}: Email harus menggunakan alamat @gmail.com.";
        }
        if (!preg_match('/^[0-9]+$/', $waDigits)) {
            return "{$label}: No. WhatsApp hanya boleh berisi angka (0-9).";
        }
        if (strlen($waDigits) < 9) {
            return "{$label}: No. WhatsApp minimal 9 digit angka.";
        }
        if (!in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
            return "{$label}: Jenis Kelamin wajib dipilih (Laki-laki / Perempuan).";
        }
        return null;
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

        $orderModel = new Order();

        if (time() > (int) $session['expires_at']) {
            if (!empty($session['token'])) {
                $orderModel->releaseHoldByToken((string) $session['token']);
            }
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
        $jumlah = (int) ($input['jumlah'] ?? $session['jumlah'] ?? 1);
        $metode = trim((string) ($input['metode'] ?? 'QRIS'));
        $buyer = is_array($input['buyer'] ?? null) ? $input['buyer'] : [];
        $attendees = is_array($input['attendees'] ?? null) ? $input['attendees'] : [];

        if ($jumlah < 1 || $jumlah > 4) {
            $this->json(['ok' => false, 'message' => 'Maksimal pembelian adalah 4 tiket dalam 1 kali transaksi.'], 422);
            return;
        }

        $buyerErr = $this->validatePerson($buyer, 'Data Pemesan');
        if ($buyerErr !== null) {
            $this->json(['ok' => false, 'message' => $buyerErr], 422);
            return;
        }

        if (count($attendees) !== $jumlah) {
            $this->json(['ok' => false, 'message' => 'Data pemegang tiket belum lengkap untuk semua tiket.'], 422);
            return;
        }

        foreach ($attendees as $idx => $att) {
            $num = $idx + 1;
            $attErr = $this->validatePerson($att, "Data Pemegang Tiket - {$num}");
            if ($attErr !== null) {
                $this->json(['ok' => false, 'message' => $attErr], 422);
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
                'checkout_token'     => (string) ($session['token'] ?? ''),
                'buyer'              => $buyer,
                'attendees'          => $attendees,
            ]);

            $_SESSION['checkout_session']['paid'] = true;
            unset($_SESSION['checkout_session']);

            // (5) Kirim email faktur / invoice resmi lewat Mailer (PHPMailer SMTP / fallback log)
            \App\Core\Mailer::sendInvoiceEmail($invoice);

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

            // Kode unik dan link pembatalan TIDAK BOLEH muncul di halaman web mana pun
            $webInvoice = $invoice;
            unset($webInvoice['cancel_code']);

            $this->json([
                'ok'                 => true,
                'invoice'            => $webInvoice,
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
