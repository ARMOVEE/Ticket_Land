<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}", $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Mapping eksklusif 21 event ke file 4k webp
$mapping = [
    'EVT001' => 'tulus-live-in-concert-4k.webp',
    'EVT002' => 'java-jazz-festival-4k.webp',
    'EVT003' => 'nadin-amizah-4k.webp',
    'EVT004' => 'artjog-4k.webp',
    'EVT005' => 'giias-4k.webp',
    'EVT006' => 'synchronize-fest-4k.webp',
    'EVT007' => 'iibf-4k.webp',
    'EVT008' => 'pandji-pragiwaksono-4k.webp',
    'EVT009' => 'bromo-4k.webp',
    'EVT010' => 'tech-in-asia-4k.webp',
    'EVT013' => 'sheila-on-7-4k.webp',
    'EVT014' => 'inacraft-4k.webp',
    'EVT015' => 'raditya-dika-4k.webp',
    'EVT016' => 'stand-up-fest-4k.webp',
    'EVT017' => 'abdur-arsyad-4k.webp',
    'EVT018' => 'labuan-bajo-4k.webp',
    'EVT019' => 'dieng-4k.webp',
    'EVT020' => 'derawan-4k.webp',
    'EVT021' => 'fintech-summit-4k.webp',
    'EVT022' => 'net-zero-summit-4k.webp',
    'EVT023' => 'brand-ecommerce-4k.webp',
];

$stmt = $pdo->prepare('UPDATE events SET poster = :poster WHERE kode = :kode');

$updated = 0;
foreach ($mapping as $kode => $poster) {
    $stmt->execute([
        ':poster' => $poster,
        ':kode'   => $kode,
    ]);
    $updated += $stmt->rowCount();
    echo "Updated {$kode} -> {$poster}\n";
}

echo "\nTotal updated: {$updated}\n";

// Verifikasi isi database sekarang
$events = $pdo->query('SELECT id, kode, nama, poster FROM events WHERE deleted_at IS NULL ORDER BY id ASC')->fetchAll();
echo "\n--- VERIFIKASI POSTER DATABASE (21 EVENT AKTIF) ---\n";
foreach ($events as $ev) {
    echo "{$ev['kode']} | {$ev['nama']} -> {$ev['poster']}\n";
}
