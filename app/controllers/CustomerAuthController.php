<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;

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

        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $pass = (string) ($input['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'message' => 'Masukkan alamat email yang valid.'], 422);
            return;
        }
        if ($pass === '') {
            $this->json(['ok' => false, 'message' => 'Kata sandi belum diisi.'], 422);
            return;
        }

        $customerModel = new Customer();
        $user = $customerModel->findByEmail($email);
        if ($user === null || !password_verify($pass, (string) $user['password_hash'])) {
            $this->json(['ok' => false, 'message' => 'Email atau kata sandi tidak sesuai.'], 401);
            return;
        }

        $_SESSION['customer'] = [
            'id'    => (int) $user['id'],
            'email' => (string) $user['email'],
            'nama'  => (string) $user['nama'],
        ];

        $log = new ActivityLog();
        $log->record(null, (string) $user['email'], 'login', 'akun_user', "Customer {$user['email']} berhasil login.");

        $this->json([
            'ok'       => true,
            'customer' => $_SESSION['customer'],
        ]);
    }

    public function logout(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid.'], 403);
            return;
        }

        if (!empty($_SESSION['customer']['email'])) {
            $email = (string) $_SESSION['customer']['email'];
            $log = new ActivityLog();
            $log->record(null, $email, 'logout', 'akun_user', "Customer {$email} keluar dari sesi.");
        }

        unset($_SESSION['customer']);
        $this->json(['ok' => true]);
    }
}
