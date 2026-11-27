<?php
declare(strict_types=1);

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

$seedFile = dirname(__DIR__) . '/database/seed.php';
$content = file_get_contents($seedFile);
if ($content === false) {
    throw new RuntimeException("Could not read {$seedFile}");
}

// Add 'poster' => 'filename' to each event in $events array
foreach ($mapping as $kode => $poster) {
    // Match block for this event
    $pattern = "/('kode'\s*=>\s*'" . preg_quote($kode, '/') . "',[\s\S]*?'syarat_ketentuan'\s*=>\s*\\\$defaultSnk,)/";
    if (preg_match($pattern, $content, $m)) {
        if (!str_contains($m[1], "'poster'")) {
            $replacement = $m[1] . "\n        'poster'           => '{$poster}',";
            $content = str_replace($m[1], $replacement, $content);
            echo "Added poster to {$kode} in seed.php\n";
        }
    } else {
        echo "Pattern not matched for {$kode}\n";
    }
}

// Also handle EVT011 and EVT012 if they have null poster
foreach (['EVT011', 'EVT012'] as $delKode) {
    $pattern = "/('kode'\s*=>\s*'" . preg_quote($delKode, '/') . "',[\s\S]*?'syarat_ketentuan'\s*=>\s*\\\$defaultSnk,)/";
    if (preg_match($pattern, $content, $m)) {
        if (!str_contains($m[1], "'poster'")) {
            $replacement = $m[1] . "\n        'poster'           => null,";
            $content = str_replace($m[1], $replacement, $content);
            echo "Added null poster to {$delKode} in seed.php\n";
        }
    }
}

// Update $stmtEvent in seed.php
$oldStmt = "    'INSERT INTO events (
        id, kode, nama, category_id, tanggal_mulai, tanggal_selesai, tanggal_label,
        jam_label, venue, kota, penyelenggara, label_sesi, deskripsi, syarat_ketentuan,
        is_featured, is_hero, deleted_at, deleted_by
     ) VALUES (
        :id, :kode, :nama, :category_id, :tanggal_mulai, :tanggal_selesai, :tanggal_label,
        :jam_label, :venue, :kota, :penyelenggara, :label_sesi, :deskripsi, :syarat_ketentuan,
        :is_featured, :is_hero, :deleted_at, :deleted_by
     ) ON DUPLICATE KEY UPDATE
        nama = VALUES(nama),
        category_id = VALUES(category_id),
        tanggal_mulai = VALUES(tanggal_mulai),
        tanggal_selesai = VALUES(tanggal_selesai),
        tanggal_label = VALUES(tanggal_label),
        jam_label = VALUES(jam_label),
        venue = VALUES(venue),
        kota = VALUES(kota),
        penyelenggara = VALUES(penyelenggara),
        label_sesi = VALUES(label_sesi),
        deskripsi = VALUES(deskripsi),
        syarat_ketentuan = VALUES(syarat_ketentuan),
        is_featured = VALUES(is_featured),
        is_hero = VALUES(is_hero)'";

$newStmt = "    'INSERT INTO events (
        id, kode, nama, category_id, tanggal_mulai, tanggal_selesai, tanggal_label,
        jam_label, venue, kota, penyelenggara, label_sesi, deskripsi, syarat_ketentuan,
        poster, is_featured, is_hero, deleted_at, deleted_by
     ) VALUES (
        :id, :kode, :nama, :category_id, :tanggal_mulai, :tanggal_selesai, :tanggal_label,
        :jam_label, :venue, :kota, :penyelenggara, :label_sesi, :deskripsi, :syarat_ketentuan,
        :poster, :is_featured, :is_hero, :deleted_at, :deleted_by
     ) ON DUPLICATE KEY UPDATE
        nama = VALUES(nama),
        category_id = VALUES(category_id),
        tanggal_mulai = VALUES(tanggal_mulai),
        tanggal_selesai = VALUES(tanggal_selesai),
        tanggal_label = VALUES(tanggal_label),
        jam_label = VALUES(jam_label),
        venue = VALUES(venue),
        kota = VALUES(kota),
        penyelenggara = VALUES(penyelenggara),
        label_sesi = VALUES(label_sesi),
        deskripsi = VALUES(deskripsi),
        syarat_ketentuan = VALUES(syarat_ketentuan),
        poster = VALUES(poster),
        is_featured = VALUES(is_featured),
        is_hero = VALUES(is_hero)'";

if (str_contains($content, $oldStmt)) {
    $content = str_replace($oldStmt, $newStmt, $content);
    echo "Updated stmtEvent in seed.php\n";
} else {
    echo "stmtEvent already updated or slightly different\n";
}

file_put_contents($seedFile, $content);
echo "seed.php successfully updated!\n";
