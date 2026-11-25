<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;

class ContactController extends Controller
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

    public function send(): void
    {
        $input = $this->readInput();
        $_POST['csrf_token'] = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!$this->verifyCsrf()) {
            $this->json(['ok' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $subject = trim((string) ($input['subject'] ?? ''));
        $pesan = trim((string) ($input['pesan'] ?? ''));

        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '') {
            $this->json([
                'ok'      => false,
                'message' => 'Nama lengkap, email yang valid, dan subject wajib diisi.',
            ], 422);
            return;
        }

        $model = new ContactMessage();
        $id = $model->create($nama, $email, $subject, $pesan);

        $log = new ActivityLog();
        $log->record(null, $email, 'create', 'contact_messages', "Pesan Hubungi Kami #{$id} dari {$nama} ({$email}) dengan topik: {$subject}.");

        $this->json([
            'ok'      => true,
            'id'      => $id,
            'message' => 'Pesan berhasil dikirim.',
        ]);
    }
}
