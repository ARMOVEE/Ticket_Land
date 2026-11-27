<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/verif_part2_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function httpGet(string $url, string $cookieFile): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function httpPostJson(string $url, array $data, string $csrf, string $cookieFile): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-CSRF-Token: ' . $csrf,
    ]);
    $payload = array_merge($data, ['csrf_token' => $csrf]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true), 'raw' => $body];
}

echo "=== VERIFIKASI PERBAIKAN LANJUTAN (BAGIAN 2) ===\n\n";

// Langkah 1: Server & Reload Awal
$home = httpGet($baseUrl . '/', $cookieFile);
if ($home['code'] !== 200 || strlen($home['body']) < 1000) {
    echo "FAIL Langkah 1: Server tidak merespons HTTP 200\n";
    exit(1);
}
preg_match('/"csrfToken":"([^"]+)"/', $home['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';
echo "PASS Langkah 1: Server berjalan di {$baseUrl} (HTTP 200), halaman awal dimuat ulang dengan bersih.\n";

// Langkah 2: Batas Maksimal Tiket 4 (UI JS + Server Validation)
$jsCode = (string) file_get_contents(dirname(__DIR__) . '/public/assets/js/landing.js');
$hasMax4Summary = str_contains($jsCode, 'Math.min(4, Number(selectedTc.stok_sisa))');
$hasMax4Allowed = str_contains($jsCode, 'Math.min(4, currentQty + stok)');
$hasMax4SetQty  = str_contains($jsCode, 'if (n > 4)');

// Coba pesan 5 tiket ke server -> harus ditolak (HTTP 422)
$resStart5 = httpPostJson($baseUrl . '/api/checkout/start', [
    'ticket_category_id' => 6, // GIIAS 2027 - Tiket Masuk Reguler
    'jumlah'             => 5,
], $csrf, $cookieFile);

// Coba pesan 4 tiket ke server -> harus diterima (HTTP 200)
$resStart4 = httpPostJson($baseUrl . '/api/checkout/start', [
    'ticket_category_id' => 6,
    'jumlah'             => 4,
], $csrf, $cookieFile);
$checkoutToken = $resStart4['body']['checkout_token'] ?? '';

// Coba update-qty ke 5 -> harus ditolak (HTTP 422)
$resUpdate5 = httpPostJson($baseUrl . '/api/checkout/update-qty', [
    'ticket_category_id' => 6,
    'checkout_token'     => $checkoutToken,
    'jumlah'             => 5,
], $csrf, $cookieFile);

// Reset kembali ke 1 untuk tes berikutnya
$resUpdate1 = httpPostJson($baseUrl . '/api/checkout/update-qty', [
    'ticket_category_id' => 6,
    'checkout_token'     => $checkoutToken,
    'jumlah'             => 1,
], $csrf, $cookieFile);

if ($hasMax4Summary && $hasMax4Allowed && $hasMax4SetQty
    && $resStart5['code'] === 422 && ($resStart5['body']['ok'] ?? true) === false
    && $resStart4['code'] === 200 && ($resStart4['body']['ok'] ?? false) === true
    && $resUpdate5['code'] === 422 && ($resUpdate5['body']['ok'] ?? true) === false
) {
    echo "PASS Langkah 2: Stepper mentok di 4; request 5 tiket ke /api/checkout/start & /api/checkout/update-qty ditolak (HTTP 422: \"{$resStart5['body']['message']}\").\n";
} else {
    echo "FAIL Langkah 2: Validasi batas 4 tiket gagal.\n";
    exit(1);
}

// Langkah 3: Urutan Kartu Rincian Pesanan di #orderPage
$html = $home['body'];
$orderPagePos = strpos($html, 'id="orderPage"');
$payPagePos   = strpos($html, 'id="payPage"');
$orderHtml    = substr($html, $orderPagePos, $payPagePos - $orderPagePos);

$posQtyRow    = strpos($orderHtml, 'class="qty-row"');
$posRItem     = strpos($orderHtml, 'class="r-item"');
$posSubtotal  = strpos($orderHtml, 'id="rincianSubtotal"');
$posTotal     = strpos($orderHtml, 'id="rincianTotal"');
$posActions   = strpos($orderHtml, 'class="r-actions"');

if ($posQtyRow !== false && $posRItem !== false && $posSubtotal !== false && $posTotal !== false && $posActions !== false
    && $posQtyRow < $posRItem && $posRItem < $posSubtotal && $posSubtotal < $posTotal && $posTotal < $posActions
) {
    echo "PASS Langkah 3: Urutan kartu Rincian Pesanan terverifikasi: (1) Stepper Jumlah Tiket -> (2) Baris Tiket -> (3) Subtotal -> (4) Total Bayar -> (5) Prev + Lanjutkan.\n";
} else {
    echo "FAIL Langkah 3: Urutan elemen di Rincian Pesanan tidak sesuai.\n";
    exit(1);
}

// Langkah 4: Form Pemegang Tiket Otomatis & Penghapusan Tombol "+ Tambah"
$hasBtnTambahHtml = str_contains($html, 'id="btnTambahTiket"') || str_contains($html, 'id="tambahTiketWrap"');
$hasQty1ZeroForms = str_contains($jsCode, 'if (targetQty <= 1)') && str_contains($jsCode, "attendeesContainer.innerHTML = '';");
$hasQty2ToNForms  = str_contains($jsCode, 'for (var i = existingCount; i < targetQty; i++)');
$hasDraftPreserve = str_contains($jsCode, 'saveAttendeeDraftFromDom') && str_contains($jsCode, 'attendeeDrafts[index]');
$hasSyncSwitch    = str_contains($jsCode, 'id="syncSwitch"') && str_contains($jsCode, 'applySyncToFirstAttendee');

if (!$hasBtnTambahHtml && $hasQty1ZeroForms && $hasQty2ToNForms && $hasDraftPreserve && $hasSyncSwitch) {
    echo "PASS Langkah 4: Tombol '+ Tambah' telah dihapus; qty=1 memiliki 0 form pemegang tiket; qty=2..4 memunculkan 2..4 form otomatis dengan penyimpanan draft & fitur 'Samakan dengan detail pemesan'.\n";
} else {
    echo "FAIL Langkah 4: Logika form pemegang tiket otomatis belum sesuai.\n";
    exit(1);
}

// Langkah 5: Form 1 (Data Pemesan) Urutan Field, KTP Teks Tetap, & Handler Jenis Kelamin
$posBuyerName   = strpos($orderHtml, 'id="buyerName"');
$posBuyerIdType = strpos($orderHtml, 'id="buyerIdType"');
$posBuyerIdNum  = strpos($orderHtml, 'id="buyerIdNum"');
$posBuyerEmail  = strpos($orderHtml, 'id="buyerEmail"');
$posBuyerWa     = strpos($orderHtml, 'id="buyerWa"');
$posBuyerGender = strpos($orderHtml, 'id="buyerGenderRow"');

$isStaticKtp = (bool) preg_match('/<input[^>]+id="buyerIdType"[^>]+value="KTP"[^>]+readonly/i', $orderHtml);
$hasGenderClickHandler = str_contains($jsCode, "setGenderRowValue(buyerGenderRow, val)");

if ($posBuyerName < $posBuyerIdType && $posBuyerIdType < $posBuyerIdNum && $posBuyerIdNum < $posBuyerEmail
    && $posBuyerEmail < $posBuyerWa && $posBuyerWa < $posBuyerGender
    && $isStaticKtp && $hasGenderClickHandler
) {
    echo "PASS Langkah 5: Urutan Form 1: (1) Nama Lengkap -> (2) Tipe Identitas (teks tetap 'KTP' readonly, bukan dropdown) -> (3) NIK -> (4) Email -> (5) No. WA -> (6) Jenis Kelamin (paling bawah) + handler klik Laki-laki/Perempuan aktif.\n";
} else {
    echo "FAIL Langkah 5: Urutan Form 1 atau Tipe Identitas KTP belum sesuai.\n";
    exit(1);
}

// Langkah 6: Validasi Input (Telepon huruf, Telepon 8 digit, NIK 15 digit, Email tanpa @gmail.com)
$baseBuyer = [
    'nama'            => 'Budi Santoso',
    'tipe_identitas'  => 'KTP',
    'nomor_identitas' => '3171011201990001',
    'email'           => 'budi@gmail.com',
    'jenis_kelamin'   => 'Laki-laki',
    'wa'              => '+628123456789',
];

// 6a. Telepon huruf
$badWaLetters = array_merge($baseBuyer, ['wa' => '+628123abcde']);
$rWaLetters = httpPostJson($baseUrl . '/api/checkout/pay', [
    'ticket_category_id' => 6,
    'jumlah'             => 1,
    'metode'             => 'QRIS',
    'checkout_token'     => $checkoutToken,
    'buyer'              => $badWaLetters,
    'attendees'          => [$badWaLetters],
], $csrf, $cookieFile);

// 6b. Telepon 8 digit
$badWa8 = array_merge($baseBuyer, ['wa' => '+6281234567']);
$rWa8 = httpPostJson($baseUrl . '/api/checkout/pay', [
    'ticket_category_id' => 6,
    'jumlah'             => 1,
    'metode'             => 'QRIS',
    'checkout_token'     => $checkoutToken,
    'buyer'              => $badWa8,
    'attendees'          => [$badWa8],
], $csrf, $cookieFile);

// 6c. NIK 15 digit
$badNik15 = array_merge($baseBuyer, ['nomor_identitas' => '317101120199000']);
$rNik15 = httpPostJson($baseUrl . '/api/checkout/pay', [
    'ticket_category_id' => 6,
    'jumlah'             => 1,
    'metode'             => 'QRIS',
    'checkout_token'     => $checkoutToken,
    'buyer'              => $badNik15,
    'attendees'          => [$badNik15],
], $csrf, $cookieFile);

// 6d. Email tanpa @gmail.com
$badEmail = array_merge($baseBuyer, ['email' => 'budi@yahoo.com']);
$rEmail = httpPostJson($baseUrl . '/api/checkout/pay', [
    'ticket_category_id' => 6,
    'jumlah'             => 1,
    'metode'             => 'QRIS',
    'checkout_token'     => $checkoutToken,
    'buyer'              => $badEmail,
    'attendees'          => [$badEmail],
], $csrf, $cookieFile);

// Release hold after test
httpPostJson($baseUrl . '/api/checkout/reset', [], $csrf, $cookieFile);

if ($rWaLetters['code'] === 422 && $rWa8['code'] === 422 && $rNik15['code'] === 422 && $rEmail['code'] === 422) {
    echo "PASS Langkah 6: Validasi menolak telepon huruf (422), telepon 8 digit (422), NIK 15 digit (422), dan email non-@gmail.com (422).\n";
} else {
    echo "FAIL Langkah 6: Salah satu validasi tidak menolak input tidak valid.\n";
    exit(1);
}

// Langkah 7: Keterangan Metode Pembayaran
$payHtml = substr($html, $payPagePos);
$expectedDescs = [
    'Scan QR pakai e-wallet / m-banking apa pun',
    'Bayar langsung dari saldo OVO',
    'Bayar langsung dari saldo DANA',
    'Bayar langsung dari saldo LinkAja!',
    'Bayar langsung dari saldo ShopeePay',
    'Transfer ke rekening BCA, cek otomatis',
    'Transfer ke rekening BNI, cek otomatis',
    'Transfer ke rekening BRI, cek otomatis',
    'Transfer ke rekening Mandiri, cek otomatis',
];
$allDescsPresent = true;
foreach ($expectedDescs as $d) {
    if (!str_contains($payHtml, $d)) {
        $allDescsPresent = false;
        echo "Missing payment desc: {$d}\n";
    }
}
if ($allDescsPresent) {
    echo "PASS Langkah 7: Semua metode pembayaran (E-Wallet/QRIS & Transfer Bank) menampilkan keterangan 1 baris di bawah namanya.\n";
} else {
    echo "FAIL Langkah 7: Ada metode pembayaran yang belum memiliki keterangan.\n";
    exit(1);
}

// Langkah 8: Halaman /staff (Label Tombol, Hapus Contoh QR Demo, Cek TKL-A1B2C3 VALID)
httpPostJson($baseUrl . '/api/customer/login', [
    'email'    => 'staff@ticketland.id',
    'password' => 'ticket1234',
], $csrf, $cookieFile);

$staffPage = httpGet($baseUrl . '/staff', $cookieFile);
$staffHtml = $staffPage['body'];

$hasScanQrBtn       = (bool) preg_match('/id="btnKamera"[^>]*>\s*<i[^>]*><\/i>\s*Scan QR\s*<\/button>/i', $staffHtml);
$hasGunakanKamera   = (bool) preg_match('/id="btnFotoQr"[^>]*>\s*<i[^>]*><\/i>\s*Gunakan Kamera\s*<\/button>/i', $staffHtml);
$hasRemovedDemoQr   = !str_contains($staffHtml, 'Contoh QR Tiket Demo') && !str_contains($staffHtml, 'id="qrDemo1"');

preg_match('/csrfToken:\s*"([^"]+)"/', $staffHtml, $mStaffCsrf);
$staffCsrf = $mStaffCsrf[1] ?? $csrf;

$checkTicket = httpPostJson($baseUrl . '/api/staff/check-ticket', [
    'kode_tiket' => 'TKL-A1B2C3',
], $staffCsrf, $cookieFile);

if ($hasScanQrBtn && $hasGunakanKamera && $hasRemovedDemoQr
    && $checkTicket['code'] === 200 && ($checkTicket['body']['status'] ?? '') === 'VALID'
) {
    echo "PASS Langkah 8: Halaman /staff menampilkan 2 tombol ('Scan QR' & 'Gunakan Kamera'), bagian 'Contoh QR Tiket Demo' sudah dihapus, dan pemeriksaan manual TKL-A1B2C3 tetap VALID (Pemegang: {$checkTicket['body']['data']['nama']}).\n";
} else {
    echo "FAIL Langkah 8: Verifikasi halaman /staff gagal.\n";
    var_dump($hasScanQrBtn, $hasGunakanKamera, $hasRemovedDemoQr, $checkTicket);
    exit(1);
}

echo "\n=== SELURUH 8 LANGKAH PENGUJIAN BERHASIL (100% PASS) ===\n";
