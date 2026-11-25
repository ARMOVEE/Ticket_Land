<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContactMessage extends Model
{
    public function create(string $nama, string $email, string $subject, string $pesan): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (nama, email, subject, pesan, created_at)
             VALUES (:nama, :email, :subject, :pesan, NOW())'
        );
        $stmt->execute([
            ':nama'    => trim($nama),
            ':email'   => trim($email),
            ':subject' => trim($subject),
            ':pesan'   => trim($pesan),
        ]);
        return (int) $this->db->lastInsertId();
    }
}
