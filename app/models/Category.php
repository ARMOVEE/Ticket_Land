<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Category extends Model
{
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT c.id, c.nama, c.deskripsi,
                    COUNT(CASE WHEN e.deleted_at IS NULL THEN e.id END) AS jumlah_event,
                    COUNT(e.id) AS total_event_semua
             FROM categories c
             LEFT JOIN events e ON e.category_id = c.id
             GROUP BY c.id, c.nama, c.deskripsi
             ORDER BY c.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.nama, c.deskripsi,
                    COUNT(e.id) AS total_event_semua
             FROM categories c
             LEFT JOIN events e ON e.category_id = c.id
             WHERE c.id = :id
             GROUP BY c.id, c.nama, c.deskripsi
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nama, string $deskripsi): int
    {
        $cleanNama = trim($nama);
        if ($cleanNama === '') {
            throw new RuntimeException('Nama kategori wajib diisi.');
        }

        $stmtCheck = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(nama) = LOWER(:nama)');
        $stmtCheck->execute([':nama' => $cleanNama]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            throw new RuntimeException('Nama kategori tersebut sudah ada.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO categories (nama, deskripsi, created_at)
             VALUES (:nama, :deskripsi, NOW())'
        );
        $stmt->execute([
            ':nama'      => $cleanNama,
            ':deskripsi' => trim($deskripsi),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nama, string $deskripsi): void
    {
        $cleanNama = trim($nama);
        if ($cleanNama === '') {
            throw new RuntimeException('Nama kategori wajib diisi.');
        }

        $stmtCheck = $this->db->prepare(
            'SELECT COUNT(*) FROM categories WHERE LOWER(nama) = LOWER(:nama) AND id != :id'
        );
        $stmtCheck->execute([':nama' => $cleanNama, ':id' => $id]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            throw new RuntimeException('Nama kategori tersebut sudah dipakai kategori lain.');
        }

        $stmt = $this->db->prepare(
            'UPDATE categories SET nama = :nama, deskripsi = :deskripsi WHERE id = :id'
        );
        $stmt->execute([
            ':nama'      => $cleanNama,
            ':deskripsi' => trim($deskripsi),
            ':id'        => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $cat = $this->findById($id);
        if ($cat === null) {
            throw new RuntimeException('Kategori tidak ditemukan.');
        }
        if ((int) $cat['total_event_semua'] > 0) {
            throw new RuntimeException(
                'Kategori "' . $cat['nama'] . '" masih dipakai oleh ' . $cat['total_event_semua'] . ' event dan tidak dapat dihapus (FR-A-14).'
            );
        }

        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
