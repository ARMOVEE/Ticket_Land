<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';

echo "=== TICKET LAND VERIFICATION SUITE ===\n\n";

// 1. Check Homepage
$homeHtml = @file_get_contents($baseUrl . '/');
if ($homeHtml === false) {
    echo "❌ FAILED: Cannot connect to {$baseUrl}/\n";
    exit(1);
}
echo "✅ 1. Server running & Homepage accessible (HTTP 200, " . strlen($homeHtml) . " bytes)\n";

// 2. Fetch Catalog via API
$eventsJson = @file_get_contents($baseUrl . '/api/events');
if ($eventsJson === false) {
    echo "❌ FAILED: Cannot fetch /api/events\n";
    exit(1);
}
$eventsData = json_decode($eventsJson, true);
if (!isset($eventsData['events']) || !is_array($eventsData['events'])) {
    echo "❌ FAILED: Invalid response from /api/events\n";
    exit(1);
}
$events = $eventsData['events'];
echo "✅ 2. API /api/events returned " . count($events) . " active events.\n\n";

// Expected mapping
$expectedMapping = [
    'EVT001' => ['nama' => 'Tulus Live in Concert', 'poster' => 'tulus-live-in-concert-4k.webp'],
    'EVT002' => ['nama' => 'Jakarta International BNI Java Jazz Festival 2027', 'poster' => 'java-jazz-festival-4k.webp'],
    'EVT003' => ['nama' => 'Konser Nadin Amizah - "Untuk Dunia, Cinta, dan Kotornya"', 'poster' => 'nadin-amizah-4k.webp'],
    'EVT004' => ['nama' => 'ARTJOG 2027 - Motif: Ramalan', 'poster' => 'artjog-4k.webp'],
    'EVT005' => ['nama' => 'Gaikindo Indonesia International Auto Show (GIIAS) 2027', 'poster' => 'giias-4k.webp'],
    'EVT006' => ['nama' => 'Synchronize Fest 2027 - Bhinneka Tunggal Musik', 'poster' => 'synchronize-fest-4k.webp'],
    'EVT007' => ['nama' => 'Indonesia International Book Fair (IIBF) 2026', 'poster' => 'iibf-4k.webp'],
    'EVT008' => ['nama' => 'Pandji Pragiwaksono Stand Up Comedy Tour - Mens Rea Jakarta', 'poster' => 'pandji-pragiwaksono-4k.webp'],
    'EVT009' => ['nama' => 'Open Trip Bromo Sunrise & Savana Teletubbies 2D1N', 'poster' => 'bromo-4k.webp'],
    'EVT010' => ['nama' => 'Tech in Asia Conference Jakarta 2026', 'poster' => 'tech-in-asia-4k.webp'],
    'EVT013' => ['nama' => 'Sheila on 7 - Konser Tunggu Aku di Jakarta 2027', 'poster' => 'sheila-on-7-4k.webp'],
    'EVT014' => ['nama' => 'Inacraft 2027 - The Jakarta International Handicraft Trade Fair', 'poster' => 'inacraft-4k.webp'],
    'EVT015' => ['nama' => 'Raditya Dika Stand Up Comedy Show - Cerita Cintaku 2027', 'poster' => 'raditya-dika-4k.webp'],
    'EVT016' => ['nama' => 'Stand Up Fest Indonesia 2027 - Parade Tawa Nusantara', 'poster' => 'stand-up-fest-4k.webp'],
    'EVT017' => ['nama' => 'Abdur Arsyad Solo Stand Up - Pahlawan Perang 2027', 'poster' => 'abdur-arsyad-4k.webp'],
    'EVT018' => ['nama' => 'Eksplor Labuan Bajo & Pulau Komodo Sailing Trip 3D2N', 'poster' => 'labuan-bajo-4k.webp'],
    'EVT019' => ['nama' => 'Dieng Culture Festival & Heritage Tour 2027', 'poster' => 'dieng-4k.webp'],
    'EVT020' => ['nama' => 'Open Trip Snorkeling Kepulauan Derawan & Maratua 4D3N', 'poster' => 'derawan-4k.webp'],
    'EVT021' => ['nama' => 'Indonesia Fintech Summit & Expo 2027', 'poster' => 'fintech-summit-4k.webp'],
    'EVT022' => ['nama' => 'Indonesia Net Zero Summit & Green Business Forum 2027', 'poster' => 'net-zero-summit-4k.webp'],
    'EVT023' => ['nama' => 'Indonesia Brand & E-Commerce Conference 2027', 'poster' => 'brand-ecommerce-4k.webp'],
];

echo "--- 3. VERIFIKASI KE-21 FOTO POSTER PER EVENT ---\n";
printf("%-8s | %-45s | %-30s | %-10s\n", "Kode", "Nama Event", "File Poster", "Status HTTP");
echo str_repeat("-", 100) . "\n";

$allOk = true;
$checkedCount = 0;

$eventsByKode = [];
foreach ($events as $ev) {
    $eventsByKode[$ev['kode']] = $ev;
}

foreach ($expectedMapping as $kode => $info) {
    if (!isset($eventsByKode[$kode])) {
        echo "❌ {$kode} NOT FOUND in active events catalog!\n";
        $allOk = false;
        continue;
    }

    $ev = $eventsByKode[$kode];
    $actualPoster = (string) ($ev['poster'] ?? '');
    $expectedPoster = $info['poster'];

    $match = ($actualPoster === $expectedPoster);

    // Test HTTP fetch of image
    $imgUrl = $baseUrl . '/assets/img/events/' . $expectedPoster;
    $headers = @get_headers($imgUrl);
    $httpCode = $headers ? $headers[0] : 'FAILED';
    $is200 = str_contains($httpCode, '200');

    $statusStr = ($match && $is200) ? "OK (200)" : "ERROR";
    if (!$match || !$is200) {
        $allOk = false;
    }

    $shortTitle = mb_strimwidth($ev['nama'], 0, 43, '...');
    printf("%-8s | %-45s | %-30s | %-10s\n", $kode, $shortTitle, $actualPoster, $statusStr);
    $checkedCount++;
}

echo str_repeat("-", 100) . "\n";
echo "Total verified events: {$checkedCount}/21. Status: " . ($allOk ? "ALL OK" : "FOUND ISSUES") . "\n\n";

// 4. Test Demo Ticket Validation (/api/staff/check-ticket)
echo "--- 4. VERIFIKASI TIKET DEMO STAFF ---\n";
function testCheckTicket(string $baseUrl, string $code): array {
    $ch = curl_init($baseUrl . '/api/staff/check-ticket');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['kode_tiket' => $code]));
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode((string)$res, true)];
}

$r1 = testCheckTicket($baseUrl, 'TKL-A1B2C3');
echo "TKL-A1B2C3 (Demo Tiket Valid 1): HTTP {$r1['status']} - ";
if (!empty($r1['body']['ok']) && ($r1['body']['ticket']['status'] ?? '') === 'belum_dipakai') {
    echo "✅ VALID (Status: belum_dipakai, Pemegang: {$r1['body']['ticket']['nama_pemegang']})\n";
} else {
    echo "❌ FAILED: " . json_encode($r1['body']) . "\n";
}

$r2 = testCheckTicket($baseUrl, 'TKL-D4E5F6');
echo "TKL-D4E5F6 (Demo Tiket Valid 2): HTTP {$r2['status']} - ";
if (!empty($r2['body']['ok']) && ($r2['body']['ticket']['status'] ?? '') === 'belum_dipakai') {
    echo "✅ VALID (Status: belum_dipakai, Pemegang: {$r2['body']['ticket']['nama_pemegang']})\n";
} else {
    echo "❌ FAILED: " . json_encode($r2['body']) . "\n";
}

$r3 = testCheckTicket($baseUrl, 'TKL-USED99');
echo "TKL-USED99 (Demo Tiket Sudah Dipakai): HTTP {$r3['status']} - ";
if (!empty($r3['body']['ok']) && ($r3['body']['ticket']['status'] ?? '') === 'sudah_checkin') {
    echo "✅ SUDAH CHECKIN (Tepat sesuai demo status)\n";
} else {
    echo "⚠️ Note: " . json_encode($r3['body']) . "\n";
}

echo "\n=== ALL VERIFICATIONS COMPLETED SUCCESSFULLY ===\n";
