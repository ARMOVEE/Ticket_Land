<?php
$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}", $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$cols = $pdo->query("SHOW COLUMNS FROM events LIKE 'poster'")->fetchAll();
echo "Column poster in events:\n";
print_r($cols);

$events = $pdo->query("SELECT id, kode, nama, poster FROM events ORDER BY id ASC")->fetchAll();
echo "Total events: " . count($events) . "\n";
foreach ($events as $e) {
    echo "{$e['kode']} | {$e['nama']} | poster: " . ($e['poster'] ?? 'NULL') . "\n";
}
