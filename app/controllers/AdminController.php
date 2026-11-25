<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\AdminStats;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;

class AdminController extends Controller
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

    private function currentAdmin(): ?array
    {
        $u = $_SESSION['admin_user'] ?? null;
        if (!is_array($u) || empty($u['id'])) {
            return null;
        }
        return $u;
    }

    private function requireAdminJson(): ?array
    {
        // RBAC check: if logged in ONLY as staff, deny access (FR-A-23, AC-18)
        $admin = $this->currentAdmin();
        if ($admin === null) {
            if (!empty($_SESSION['staff_user'])) {
                $this->json(['ok' => false, 'message' => 'Akses ditolak: Akun Staff tidak diizinkan mengakses halaman Admin (AC-18).'], 403);
                return null;
            }
            $this->json(['ok' => false, 'message' => 'Sesi login Admin berakhir. Silakan login kembali.'], 401);
            return null;
        }
        if (!in_array($admin['peran'], ['admin', 'manager'], true)) {
            $this->json(['ok' => false, 'message' => 'Akses ditolak: Peran Anda tidak memiliki hak akses Admin.'], 403);
            return null;
        }
        return $admin;
    }

    private function buildFullState(): array
    {
        $statsModel = new AdminStats();
        $eventModel = new Event();
        $catModel = new Category();
        $userModel = new User();
        $logModel = new ActivityLog();

        return [
            'summary'            => $statsModel->getDashboardSummary(),
            'events'             => $eventModel->getAllActive(),
            'trashed_events'     => $eventModel->getAllTrashed(),
            'categories'         => $catModel->getAll(),
            'stock_transactions' => $statsModel->getAllStockTransactions(),
            'orders'             => $statsModel->getAllOrders(),
            'sales_report'       => $statsModel->getSalesReport(),
            'passengers'         => $statsModel->getAllPassengers(),
            'users'              => $userModel->getAll(),
            'activity_logs'      => $logModel->getAll(100),
        ];
    }

    public function index(string $section = 'dashboard'): void
    {
        $admin = $this->currentAdmin();

        // Enforce server-side RBAC when a Staff user tries to access /admin directly (FR-A-23, AC-18)
        if ($admin === null && !empty($_SESSION['staff_user'])) {
            http_response_code(403);
            echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>403 Akses Ditolak - Ticket Land</title>'
                . '<link href="/assets/css/bootstrap.min.css" rel="stylesheet"></head>'
                . '<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">'
                . '<div class="card border-0 shadow-sm p-4 text-center" style="max-width:460px">'
                . '<h1 class="h4 text-danger fw-bold mb-2">403 - Akses Ditolak</h1>'
                . '<p class="text-muted mb-3">Akun Staff hanya diizinkan mengakses halaman Check-in Staff (Scan QR) dan tidak memiliki akses ke Dashboard Admin (FR-A-23 / AC-18).</p>'
                . '<a href="/staff" class="btn btn-success" style="background:#0B5D3B;border-color:#0B5D3B">Kembali ke Halaman Staff</a>'
                . '</div></body></html>';
            return;
        }

        $allowedSections = ['dashboard', 'items', 'item-detail', 'categories', 'transactions', 'reports', 'users', 'activity'];
        $mapSection = [
            'dashboard'    => 'dashboard',
            'events'       => 'items',
            'items'        => 'items',
            'categories'   => 'categories',
            'transactions' => 'transactions',
            'reports'      => 'reports',
            'users'        => 'users',
            'logs'         => 'activity',
            'activity'     => 'activity',
        ];
        $initialPage = $mapSection[strtolower($section)] ?? 'dashboard';
        if (!in_array($initialPage, $allowedSections, true)) {
            $initialPage = 'dashboard';
        }

        $state = $admin !== null ? $this->buildFullState() : null;

        $this->view('admin/index', [
            'admin'       => $admin,
            'initialPage' => $admin ? $initialPage : 'login',
            'state'       => $state,
            'csrfToken'   => self::csrfToken(),
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
            $this->json(['ok' => false, 'message' => 'Username/email dan password wajib diisi.'], 422);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByIdentifier($identifier);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->json(['ok' => false, 'message' => 'Username atau password tidak sesuai.'], 401);
            return;
        }

        if (!in_array($user['peran'], ['admin', 'manager'], true)) {
            $this->json([
                'ok'      => false,
                'message' => 'Akses ditolak: Akun Staff hanya dapat masuk melalui halaman Login Staff Check-in.',
            ], 403);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id'       => (int) $user['id'],
            'username' => (string) $user['username'],
            'email'    => (string) $user['email'],
            'nama'     => (string) $user['nama'],
            'peran'    => (string) $user['peran'],
        ];

        $log = new ActivityLog();
        $log->record(
            (int) $user['id'],
            (string) $user['username'],
            'login',
            'auth',
            "Pengguna {$user['username']} berhasil login ke sistem."
        );

        $this->json([
            'ok'    => true,
            'user'  => $_SESSION['admin_user'],
            'state' => $this->buildFullState(),
        ]);
    }

    public function logout(): void
    {
        $admin = $this->currentAdmin();
        if ($admin !== null) {
            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                'LOGOUT',
                'users',
                "Pengguna {$admin['username']} keluar dari Dashboard Admin."
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['admin_user']);
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

    public function state(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }
        $this->json([
            'ok'    => true,
            'user'  => $admin,
            'state' => $this->buildFullState(),
        ]);
    }

    public function saveEvent(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        try {
            $isUpdate = !empty($input['id']) && (int) $input['id'] > 0;
            $eventModel = new Event();
            $saved = $eventModel->saveEvent($input, (string) $admin['username']);

            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                $isUpdate ? 'update' : 'create',
                'events',
                ($isUpdate ? 'Memperbarui data event ' : 'Menambahkan event baru ') . ($saved['nama'] ?? '') . ' (' . ($saved['kode'] ?? '') . ').'
            );

            $this->json([
                'ok'      => true,
                'event'   => $saved,
                'message' => $isUpdate ? 'Event berhasil diperbarui.' : 'Event baru berhasil ditambahkan.',
                'state'   => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteEvent(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $id = (int) ($input['id'] ?? 0);
        try {
            $eventModel = new Event();
            $ev = $eventModel->softDelete($id, (string) $admin['username']);

            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                'delete',
                'events',
                "Menghapus event {$ev['nama']} ({$ev['kode']}) melalui soft delete ke Sampah."
            );

            $this->json([
                'ok'      => true,
                'message' => "Event {$ev['nama']} berhasil dipindahkan ke Sampah.",
                'state'   => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function restoreEvent(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $id = (int) ($input['id'] ?? 0);
        try {
            $eventModel = new Event();
            $ev = $eventModel->restore($id);

            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                'restore',
                'events',
                "Memulihkan event {$ev['nama']} ({$ev['kode']}) dari Sampah."
            );

            $this->json([
                'ok'      => true,
                'message' => "Event {$ev['nama']} berhasil dipulihkan dari Sampah.",
                'state'   => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function saveCategory(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $id = (int) ($input['id'] ?? 0);
        $nama = trim((string) ($input['nama'] ?? ''));
        $deskripsi = trim((string) ($input['deskripsi'] ?? ''));

        try {
            $catModel = new Category();
            if ($id > 0) {
                $catModel->update($id, $nama, $deskripsi);
                $aksi = 'update';
                $msg = "Kategori {$nama} berhasil diperbarui.";
                $descLog = "Memperbarui kategori event: {$nama}.";
            } else {
                $catModel->create($nama, $deskripsi);
                $aksi = 'create';
                $msg = "Kategori {$nama} berhasil ditambahkan.";
                $descLog = "Menambahkan kategori event baru: {$nama}.";
            }

            $log = new ActivityLog();
            $log->record((int) $admin['id'], (string) $admin['username'], $aksi, 'categories', $descLog);

            $this->json([
                'ok'      => true,
                'message' => $msg,
                'state'   => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteCategory(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $id = (int) ($input['id'] ?? 0);
        try {
            $catModel = new Category();
            $cat = $catModel->findById($id);
            $catModel->delete($id);

            $nama = $cat['nama'] ?? ('#' . $id);
            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                'delete',
                'categories',
                "Menghapus kategori event: {$nama}."
            );

            $this->json([
                'ok'      => true,
                'message' => "Kategori {$nama} berhasil dihapus.",
                'state'   => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createTransaction(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $tcId = (int) ($input['ticket_category_id'] ?? 0);
        $jenis = (string) ($input['jenis'] ?? 'IN');
        $jumlah = (int) ($input['jumlah'] ?? 0);
        $catatan = (string) ($input['catatan'] ?? '');

        try {
            $statsModel = new AdminStats();
            $res = $statsModel->createManualStockTransaction(
                $tcId,
                $jenis,
                $jumlah,
                $catatan,
                (string) $admin['username']
            );

            $verb = $res['jenis'] === 'IN' ? 'masuk' : 'keluar';
            $log = new ActivityLog();
            $log->record(
                (int) $admin['id'],
                (string) $admin['username'],
                'update',
                'transactions',
                "Mencatat transaksi {$verb} {$res['jumlah']} tiket {$res['event_nama']} ({$res['event_kode']} - {$res['kategori_tiket']}), sisa menjadi {$res['stok_sisa_baru']}."
            );

            $this->json([
                'ok'          => true,
                'transaction' => $res,
                'message'     => "Transaksi stok ({$res['jenis']} {$res['jumlah']} tiket) berhasil disimpan.",
                'state'       => $this->buildFullState(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createUser(): void
    {
        $admin = $this->requireAdminJson();
        if ($admin === null) {
            return;
        }

        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $role = trim((string) ($input['peran'] ?? 'Staff'));
        $password = (string) ($input['password'] ?? '');

        if ($nama === '' || $username === '') {
            $this->json(['ok' => false, 'message' => 'Nama lengkap dan username wajib diisi.'], 422);
            return;
        }
        if (strlen($password) < 8) {
            $this->json(['ok' => false, 'message' => 'Password pengguna minimal 8 karakter (FR-A-22).'], 422);
            return;
        }
        if ($email === '') {
            $cleanUser = preg_replace('/[^a-zA-Z0-9._-]/', '', strtolower($username));
            $email = $cleanUser . '@ticketland.id';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'message' => 'Format email tidak valid.'], 422);
            return;
        }

        $userModel = new User();
        if ($userModel->existsUsernameOrEmail($username, $email)) {
            $this->json(['ok' => false, 'message' => 'Username atau email sudah terdaftar di sistem (DFD 1.1).'], 422);
            return;
        }

        $newId = $userModel->create($username, $email, $nama, $password, $role);

        $log = new ActivityLog();
        $log->record(
            (int) $admin['id'],
            (string) $admin['username'],
            'create',
            'users',
            "Menambahkan pengguna baru {$username} ({$nama}) dengan role " . strtolower($role) . "."
        );

        $this->json([
            'ok'      => true,
            'id'      => $newId,
            'message' => "Pengguna {$username} berhasil ditambahkan dengan role {$role}.",
            'state'   => $this->buildFullState(),
        ]);
    }

    public function exportCsv(): void
    {
        $admin = $this->currentAdmin();
        if ($admin === null) {
            http_response_code(401);
            echo '401 Unauthorized - Silakan login sebagai Admin terlebih dahulu.';
            return;
        }

        $statsModel = new AdminStats();
        $report = $statsModel->getSalesReport();

        $log = new ActivityLog();
        $log->record(
            (int) $admin['id'],
            (string) $admin['username'],
            'export',
            'reports',
            'Mengekspor Laporan Pemesanan & Pembatalan ke berkas CSV.'
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan-penjualan-tiket.csv"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Kode', 'Nama Event', 'Kategori', 'Tiket Terjual', 'Harga Tiket', 'Total Penjualan']);

        foreach ($report['rows'] as $r) {
            $hargaText = $r['harga_mulai'] === 0 ? 'Rp0' : ('Rp' . number_format((int) $r['harga_mulai'], 0, ',', '.'));
            $totalText = 'Rp' . number_format((int) $r['total_penjualan'], 0, ',', '.');
            fputcsv($out, [
                $r['kode'],
                $r['nama'],
                $r['kategori_nama'],
                (string) $r['tiket_terjual'],
                $hargaText,
                $totalText,
            ]);
        }

        fputcsv($out, [
            'Total',
            '',
            '',
            (string) $report['total_terjual'],
            '',
            'Rp' . number_format((int) $report['total_penjualan'], 0, ',', '.'),
        ]);
        fclose($out);
    }
}
