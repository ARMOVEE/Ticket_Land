<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;

class StaffController extends Controller
{
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

    private function currentStaff(): ?array
    {
        $s = $_SESSION['staff_user'] ?? null;
        if (!is_array($s) || empty($s['id'])) {
            return null;
        }
        return $s;
    }

    public function loginPage(): void
    {
        $staff = $this->currentStaff();
        if ($staff !== null) {
            header('Location: /staff');
            exit;
        }

        $this->view('staff/login', [
            'csrfToken' => self::csrfToken(),
        ]);
    }

    public function login(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $identifier = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $this->json(['ok' => false, 'message' => 'Username dan password wajib diisi.'], 422);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByIdentifier($identifier);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->json(['ok' => false, 'message' => 'Username atau password salah.'], 401);
            return;
        }

        // Staff, Manager, and Admin can all operate the check-in scanner
        session_regenerate_id(true);
        $_SESSION['staff_user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama'],
            'peran'    => $user['peran'],
        ];

        $logModel = new ActivityLog();
        $logModel->record(
            (int) $user['id'],
            $user['username'],
            'LOGIN',
            'Sesi Staff',
            'Petugas login ke modul check-in pemindai QR'
        );

        $this->json([
            'ok'       => true,
            'message'  => 'Login staff berhasil.',
            'redirect' => '/staff',
            'staff'    => $_SESSION['staff_user'],
        ]);
    }

    public function logout(): void
    {
        $staff = $this->currentStaff();
        if ($staff) {
            $logModel = new ActivityLog();
            $logModel->record(
                (int) $staff['id'],
                $staff['username'],
                'LOGOUT',
                'Sesi Staff',
                'Petugas keluar dari sistem check-in'
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['staff_user']);
            $_SESSION = [];
            session_destroy();
        }

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->json(['ok' => true, 'redirect' => '/']);
            return;
        }

        header('Location: /');
        exit;
    }

    public function index(): void
    {
        $staff = $this->currentStaff();
        if ($staff === null) {
            header('Location: /staff/login');
            exit;
        }

        $ticketModel = new Ticket();
        $todayCount = $ticketModel->getTodayCheckinCount();
        $activeEvent = $ticketModel->getActiveFeaturedEvent();
        $recentLogs = $ticketModel->getRecentCheckinLogs(15);

        $this->view('staff/scan', [
            'staff'       => $staff,
            'todayCount'  => $todayCount,
            'activeEvent' => $activeEvent,
            'recentLogs'  => $recentLogs,
            'csrfToken'   => self::csrfToken(),
        ]);
    }

    public function checkTicket(): void
    {
        $staff = $this->currentStaff();
        if ($staff === null) {
            $this->json(['ok' => false, 'message' => 'Sesi petugas berakhir. Silakan login kembali.'], 401);
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $kode = strtoupper(trim((string) ($input['kode_tiket'] ?? '')));
        if ($kode === '') {
            $this->json(['ok' => false, 'message' => 'Kode tiket wajib diisi atau dipindai.'], 422);
            return;
        }

        $ticketModel = new Ticket();
        $ticket = $ticketModel->findByCode($kode);

        // Kasus 1: Kode tidak dikenal (AC-15)
        if (!$ticket) {
            $ticketModel->logRejection(
                null,
                $kode,
                '',
                '',
                'Kode tidak terdaftar di sistem',
                $staff['id'],
                $staff['nama']
            );
            $this->json([
                'ok'      => false,
                'status'  => 'NOT_FOUND',
                'message' => 'Kode tiket ' . $kode . ' tidak terdaftar di sistem Ticket Land. Arahkan pemesan ke Admin/Customer Service.',
            ], 404);
            return;
        }

        // Kasus 2: Sudah check-in sebelumnya (AC-14)
        if ($ticket['status_checkin'] === 'Sudah Check-in') {
            $usedTime = $ticket['waktu_checkin'] ? date('d/m/Y H:i', strtotime($ticket['waktu_checkin'])) : '-';
            $ticketModel->logRejection(
                (int) $ticket['id'],
                $ticket['kode_tiket'],
                $ticket['nama_pemegang'],
                $ticket['event_nama'],
                'Tiket sudah dipakai sebelumnya pada ' . $usedTime . ' WIB',
                $staff['id'],
                $staff['nama']
            );
            $this->json([
                'ok'      => false,
                'status'  => 'ALREADY_USED',
                'message' => 'Tiket ini sudah dipakai pada ' . $usedTime . ' WIB. Pemesan diarahkan ke petugas/Admin sesuai SOP.',
                'data'    => [
                    'nama'    => $ticket['nama_pemegang'],
                    'kode'    => $ticket['kode_tiket'],
                    'event'   => $ticket['event_nama'],
                    'used_at' => $usedTime,
                ],
            ]);
            return;
        }

        // Kasus 3: Status pembayaran tidak lunas atau dibatalkan
        if ($ticket['status_pembayaran'] !== 'Lunas' || $ticket['status_checkin'] === 'Dibatalkan') {
            $ticketModel->logRejection(
                (int) $ticket['id'],
                $ticket['kode_tiket'],
                $ticket['nama_pemegang'],
                $ticket['event_nama'],
                'Status pembayaran belum Lunas (' . $ticket['status_pembayaran'] . ')',
                $staff['id'],
                $staff['nama']
            );
            $this->json([
                'ok'      => false,
                'status'  => 'INVALID',
                'message' => 'Status pemesanan tiket belum Lunas atau telah dibatalkan.',
            ], 422);
            return;
        }

        // Kasus 4: Tiket VALID (AC-13)
        $tglFormatted = date('d/m/Y', strtotime($ticket['event_tanggal'])) . ' ' . substr((string) $ticket['event_waktu'], 0, 5) . ' WIB';
        $this->json([
            'ok'     => true,
            'status' => 'VALID',
            'data'   => [
                'id'        => (int) $ticket['id'],
                'kode'      => $ticket['kode_tiket'],
                'nama'      => $ticket['nama_pemegang'],
                'email'     => $ticket['email'],
                'identitas' => $ticket['tipe_identitas'] . ' ' . $ticket['nomor_identitas'],
                'event'     => $ticket['event_nama'],
                'venue'     => $ticket['event_venue'] . ($ticket['event_kota'] ? ', ' . $ticket['event_kota'] : ''),
                'tanggal'   => $tglFormatted,
                'kategori'  => $ticket['kategori_tiket'],
                'wa'        => $ticket['wa'],
            ],
        ]);
    }

    public function confirmCheckin(): void
    {
        $staff = $this->currentStaff();
        if ($staff === null) {
            $this->json(['ok' => false, 'message' => 'Sesi petugas berakhir. Silakan login kembali.'], 401);
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $ticketId = (int) ($input['ticket_id'] ?? 0);
        $kode = strtoupper(trim((string) ($input['kode_tiket'] ?? '')));
        $nama = trim((string) ($input['nama_pemesan'] ?? ''));
        $event = trim((string) ($input['nama_event'] ?? ''));

        if ($ticketId <= 0 || $kode === '') {
            $this->json(['ok' => false, 'message' => 'Data tiket tidak lengkap.'], 422);
            return;
        }

        $ticketModel = new Ticket();
        $ok = $ticketModel->confirmCheckin($ticketId, $kode, $nama, $event, $staff['id'], $staff['nama']);

        if (!$ok) {
            $this->json(['ok' => false, 'message' => 'Gagal check-in: Tiket telah dipakai atau tidak ditemukan.'], 409);
            return;
        }

        $newCount = $ticketModel->getTodayCheckinCount();
        $recentLogs = $ticketModel->getRecentCheckinLogs(15);

        $this->json([
            'ok'         => true,
            'message'    => 'Check-in berhasil untuk ' . $nama . ' (Kode: ' . $kode . ').',
            'todayCount' => $newCount,
            'recentLogs' => $recentLogs,
            'data'       => [
                'kode'  => $kode,
                'nama'  => $nama,
                'event' => $event,
                'waktu' => date('H:i'),
            ],
        ]);
    }

    public function rejectKtp(): void
    {
        $staff = $this->currentStaff();
        if ($staff === null) {
            $this->json(['ok' => false, 'message' => 'Sesi petugas berakhir.'], 401);
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan tidak valid.'], 403);
            return;
        }

        $ticketId = !empty($input['ticket_id']) ? (int) $input['ticket_id'] : null;
        $kode = strtoupper(trim((string) ($input['kode_tiket'] ?? '')));
        $nama = trim((string) ($input['nama_pemesan'] ?? ''));
        $event = trim((string) ($input['nama_event'] ?? ''));

        $ticketModel = new Ticket();
        $ticketModel->logRejection(
            $ticketId,
            $kode,
            $nama,
            $event,
            'KTP tidak cocok dengan nama pemesan (Batal oleh petugas)',
            $staff['id'],
            $staff['nama']
        );

        $recentLogs = $ticketModel->getRecentCheckinLogs(15);

        $this->json([
            'ok'         => true,
            'message'    => 'Check-in dibatalkan karena verifikasi KTP tidak cocok. Tiket tetap belum dipakai.',
            'recentLogs' => $recentLogs,
        ]);
    }
}
