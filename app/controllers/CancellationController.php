<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Models\Order;
use App\Models\ActivityLog;

class CancellationController extends Controller
{
    /**
     * Tampilkan halaman formulir pembatalan pesanan (/batalkan)
     */
    public function index(): void
    {
        $cancelCode = strtoupper(trim((string) ($_GET['kode'] ?? '')));
        $orderModel = new Order();
        $db = \App\Core\Database::getInstance();

        // Ambil daftar event aktif untuk dropdown
        $stmtEvents = $db->query(
            'SELECT id, kode, nama, venue, kota
             FROM events
             WHERE deleted_at IS NULL
             ORDER BY tanggal_mulai ASC, nama ASC'
        );
        $events = $stmtEvents->fetchAll() ?: [];

        $prefill = [
            'cancel_code'   => $cancelCode,
            'nomor_invoice' => '',
            'pemesan_nama'  => '',
            'pemesan_email' => '',
            'event_id'      => 0,
            'is_from_link'  => false,
            'order'         => null,
        ];

        // Jika ada kode di query string, cari data pesanan untuk auto-fill
        if ($cancelCode !== '') {
            $order = $orderModel->findOrderByCancelCode($cancelCode);
            if ($order) {
                $prefill['nomor_invoice'] = (string) $order['nomor_invoice'];
                $prefill['pemesan_nama']  = (string) $order['pemesan_nama'];
                $prefill['pemesan_email'] = (string) $order['pemesan_email'];
                $prefill['event_id']      = (int) $order['event_id'];
                $prefill['is_from_link']  = true;
                $prefill['order']         = $order;
            }
        }

        $flashError = $_SESSION['cancel_flash_error'] ?? null;
        unset($_SESSION['cancel_flash_error']);

        $flashSuccess = $_SESSION['cancel_flash_success'] ?? null;
        unset($_SESSION['cancel_flash_success']);

        $this->view('cancellation/index', [
            'title'        => 'Batalkan Pesanan - Ticket Land',
            'events'       => $events,
            'prefill'      => $prefill,
            'csrf_token'   => self::csrfToken(),
            'flashError'   => $flashError,
            'flashSuccess' => $flashSuccess,
        ]);
    }

    /**
     * Memproses pengajuan pembatalan (POST /batalkan)
     */
    public function submit(): void
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'))
            || (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

        $input = $_POST;
        if (empty($input) && $isAjax) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }

        // Verifikasi CSRF
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $msg = 'Token keamanan (CSRF) tidak valid atau sesi berakhir. Silakan muat ulang halaman.';
            if ($isAjax) {
                $this->json(['ok' => false, 'message' => $msg], 403);
                return;
            }
            $_SESSION['cancel_flash_error'] = $msg;
            $this->redirect('/batalkan' . (!empty($input['cancel_code']) ? '?kode=' . urlencode((string)$input['cancel_code']) : ''));
            return;
        }

        try {
            $orderModel = new Order();
            $result = $orderModel->cancelOrder([
                'cancel_code'    => (string) ($input['cancel_code'] ?? ''),
                'nomor_invoice'  => (string) ($input['nomor_invoice'] ?? ''),
                'pemesan_nama'   => (string) ($input['pemesan_nama'] ?? ''),
                'pemesan_email'  => (string) ($input['pemesan_email'] ?? ''),
                'event_id'       => (int) ($input['event_id'] ?? 0),
                'alasan'         => (string) ($input['alasan'] ?? ''),
            ]);

            // Kirim email konfirmasi pembatalan lewat Mailer
            Mailer::sendCancellationConfirmation($result);

            // Catat log aktivitas audit
            try {
                $log = new ActivityLog();
                $pelaku = $result['pemesan_email'] . ' (Customer Self-Service)';
                $log->record(
                    null,
                    $pelaku,
                    'cancel',
                    'orders',
                    "Pembatalan pesanan {$result['nomor_invoice']} (Ref: {$result['cancellation_ref']}). {$result['jumlah']} tiket {$result['kategori_tiket_nama']} dikembalikan ke stok."
                );
            } catch (\Throwable) {
                // Jangan gagalkan alur utama bila audit log gagal
            }

            if ($isAjax) {
                $this->json([
                    'ok'     => true,
                    'result' => $result,
                ]);
                return;
            }

            $_SESSION['cancel_flash_success'] = $result;
            $this->redirect('/batalkan');
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            if ($isAjax) {
                $this->json([
                    'ok'      => false,
                    'message' => $errorMsg,
                ], 422);
                return;
            }

            $_SESSION['cancel_flash_error'] = $errorMsg;
            $redirectUrl = '/batalkan';
            if (!empty($input['cancel_code'])) {
                $redirectUrl .= '?kode=' . urlencode((string) $input['cancel_code']);
            }
            $this->redirect($redirectUrl);
        }
    }
}
