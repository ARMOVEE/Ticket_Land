<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$db = \App\Core\Database::getInstance();
$stmt = $db->query('SELECT id, kode, nama, tanggal_mulai FROM events WHERE deleted_at IS NULL ORDER BY tanggal_mulai ASC');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "{$row['kode']} | {$row['tanggal_mulai']} | {$row['nama']}\n";
}
