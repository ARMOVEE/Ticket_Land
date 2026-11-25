<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;

class CustomerAuthController extends Controller
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

    public function register(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $pass = (string) ($input['password'] ?? '');
        $pass2 = (string) ($input['password_confirm'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'message' => 'Masukkan alamat email yang valid.'], 422);
            return;
        }
        if (strlen($pass) < 6) {
            $this->json(['ok' => false, 'message' => 'Kata sandi minimal 6 karakter.'], 422);
            return;
        }
        if ($pass !== $pass2) {
            $this->json(['ok' => false, 'message' => 'Konfirmasi kata sandi tidak sama.'], 422);
            return;
        }

        $customerModel = new Customer();
        if ($customerModel->findByEmail($email) !== null) {
            $this->json(['ok' => false, 'message' => 'Email tersebut sudah terdaftar. Silakan masuk.'], 422);
            return;
        }

        $prefix = explode('@', $email)[0];
        $cleanName = trim((string) preg_replace('/[._-]+/', ' ', $prefix));
        $nama = $cleanName !== '' ? ucwords($cleanName) : 'Pengguna';

        $id = $customerModel->create($email, $nama, $pass);

        $_SESSION['customer'] = [
            'id'    => $id,
            'email' => $email,
            'nama'  => $nama,
        ];

        $log = new ActivityLog();
        $log->record(null, $email, 'create', 'akun_user', "Pendaftaran akun customer baru: {$email} ({$nama}).");

        $this->json([
            'ok'       => true,
            'customer' => $_SESSION['customer'],
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

        $identifier = trim((string) ($input['email'] ?? ($input['username'] ?? '')));
        $pass = (string) ($input['password'] ?? '');

        if ($identifier === '') {
            $this->json(['ok' => false, 'message' => 'Email atau username belum diisi.'], 422);
            return;
        }
        if ($pass === '') {
            $this->json(['ok' => false, 'message' => 'Kata sandi belum diisi.'], 422);
            return;
        }

        $log = new ActivityLog();

        // 1. Cek data tabel `users` terlebih dahulu (admin / manager / staff)
        $userModel = new User();
        $user = $userModel->findByIdentifier($identifier);

        if ($user !== null && password_verify($pass, (string) $user['password_hash'])) {
            $role = strtolower((string) $user['peran']);

            if (in_array($role, ['admin', 'manager'], true)) {
                $_SESSION['admin_user'] = [
                    'id'       => (int) $user['id'],
                    'username' => (string) $user['username'],
                    'nama'     => (string) $user['nama'],
                    'peran'    => $role,
                ];
                $log->record((int) $user['id'], (string) $user['username'], 'LOGIN', 'users', "Login {$role} berhasil dari landing page.");
                $this->json([
                    'ok'       => true,
                    'role'     => $role,
                    'redirect' => '/admin',
                    'message'  => 'Login berhasil. Mengalihkan ke Dashboard Admin...',
                ]);
                return;
            }

            if ($role === 'staff') {
                $_SESSION['staff_user'] = [
                    'id'       => (int) $user['id'],
                    'username' => (string) $user['username'],
                    'nama'     => (string) $user['nama'],
                    'peran'    => 'staff',
                ];
                $log->record((int) $user['id'], (string) $user['username'], 'LOGIN', 'users', 'Login staff berhasil dari landing page.');
                $this->json([
                    'ok'       => true,
                    'role'     => 'staff',
                    'redirect' => '/staff',
                    'message'  => 'Login berhasil. Mengalihkan ke Halaman Scan QR Staff...',
                ]);
                return;
            }
        }

        // 2. Cek data tabel `akun_user` untuk Customer
        $customerModel = new Customer();
        $customer = $customerModel->findByEmail($identifier);

        if ($customer !== null && password_verify($pass, (string) $customer['password_hash'])) {
            $_SESSION['customer'] = [
                'id'    => (int) $customer['id'],
                'email' => (string) $customer['email'],
                'nama'  => (string) $customer['nama'],
            ];

            $log->record(null, (string) $customer['email'], 'LOGIN', 'akun_user', "Customer {$customer['email']} berhasil login.");

            $this->json([
                'ok'       => true,
                'role'     => 'customer',
                'redirect' => null,
                'customer' => $_SESSION['customer'],
                'message'  => 'Login berhasil.',
            ]);
            return;
        }

        // 3. Jika akun tidak cocok di kedua tabel
        $this->json(['ok' => false, 'message' => 'Email/username atau kata sandi tidak sesuai.'], 401);
    }

    public function logout(): void
    {
        if (!empty($_SESSION['customer']['email'])) {
            $email = (string) $_SESSION['customer']['email'];
            $log = new ActivityLog();
            $log->record(null, $email, 'LOGOUT', 'akun_user', "Customer {$email} keluar dari sesi.");
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['customer']);
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
}
