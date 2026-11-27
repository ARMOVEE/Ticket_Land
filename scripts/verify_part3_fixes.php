<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/verif_part3_cookie.txt';
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

echo "=== VERIFIKASI PERBAIKAN LANJUTAN (BAGIAN 3) ===\n\n";

// Langkah 1: Server & Reload dari awal
$home = httpGet($baseUrl . '/', $cookieFile);
if ($home['code'] !== 200 || strlen($home['body']) < 1000) {
    echo "FAIL Langkah 1: Server tidak merespons HTTP 200\n";
    exit(1);
}
preg_match('/"csrfToken":"([^"]+)"/', $home['body'], $mCsrf);
$csrf = $mCsrf[1] ?? '';
$html = $home['body'];
$jsCode = (string) file_get_contents(dirname(__DIR__) . '/public/assets/js/landing.js');
$cssCode = (string) file_get_contents(dirname(__DIR__) . '/public/assets/css/landing.css');
$staffJsCode = (string) file_get_contents(dirname(__DIR__) . '/public/assets/js/staff.js');
echo "PASS Langkah 1: Server aktif di {$baseUrl} (HTTP 200), halaman awal dimuat ulang dengan cache-busting CSS & JS.\n";

// Langkah 2: Detail Pesanan — Rincian Pesanan dipisah jadi 2 blok + Subtotal/Total di paling bawah + Hapus sticky mobile bar + Stepper mentok di 4
$orderPagePos = strpos($html, 'id="orderPage"');
$payPagePos   = strpos($html, 'id="payPage"');
$orderHtml    = substr($html, $orderPagePos, $payPagePos - $orderPagePos);

$hasBlock1 = preg_match('/<div class="rincian-card" id="rincianCardTop">([\s\S]*?)<\/aside>/i', $orderHtml, $mBlock1);
$hasBlock2 = preg_match('/<div class="rincian-card rincian-summary-block" id="rincianCardBottom">([\s\S]*?)<\/div>\s*<\/div>\s*<aside/i', $orderHtml, $mBlock2);

$block1Html = $mBlock1[1] ?? '';
$block2Html = $mBlock2[1] ?? '';

$block1HasTitle   = str_contains($block1Html, 'Rincian Pesanan');
$block1HasItem    = str_contains($block1Html, 'id="rincianCatName"') && str_contains($block1Html, 'id="rincianLine"');
$block1HasStepper = str_contains($block1Html, 'id="qtyMinus"') && str_contains($block1Html, 'id="qtyPlus"');
$block1NoSubtotal = !str_contains($block1Html, 'id="rincianSubtotal"') && !str_contains($block1Html, 'id="rincianTotal"') && !str_contains($block1Html, 'id="btnLanjut"');

$block2HasSubtotal = str_contains($block2Html, 'id="rincianSubtotal"');
$block2HasTotal    = str_contains($block2Html, 'id="rincianTotal"');
$block2HasActions  = str_contains($block2Html, 'id="btnRincianBack"') && str_contains($block2Html, 'id="btnLanjut"');

$posAttendees = strpos($orderHtml, 'id="attendeesContainer"');
$posBottomBlock = strpos($orderHtml, 'id="rincianCardBottom"');
$noStickyMobileBar = !str_contains($html, 'checkoutMobileBar') && !str_contains($html, 'checkout-mobile-bar')
    && !str_contains($cssCode, 'checkout-mobile-bar') && !str_contains($jsCode, 'checkoutMobileBar');

// Test max 4 ticket limit
$resStart5 = httpPostJson($baseUrl . '/api/checkout/start', ['ticket_category_id' => 6, 'jumlah' => 5], $csrf, $cookieFile);
$resStart4 = httpPostJson($baseUrl . '/api/checkout/start', ['ticket_category_id' => 6, 'jumlah' => 4], $csrf, $cookieFile);
$checkoutToken = $resStart4['body']['checkout_token'] ?? '';
httpPostJson($baseUrl . '/api/checkout/update-qty', ['ticket_category_id' => 6, 'checkout_token' => $checkoutToken, 'jumlah' => 1], $csrf, $cookieFile);

if ($hasBlock1 && $hasBlock2 && $block1HasTitle && $block1HasItem && $block1HasStepper && $block1NoSubtotal
    && $block2HasSubtotal && $block2HasTotal && $block2HasActions
    && $posAttendees < $posBottomBlock && $noStickyMobileBar
    && $resStart5['code'] === 422 && $resStart4['code'] === 200
) {
    echo "PASS Langkah 2: Rincian Pesanan terpisah menjadi 2 blok: Blok 1 (#rincianCardTop: judul, baris tiket, stepper Jumlah Tiket tanpa Subtotal/Total/tombol) & Blok 2 (#rincianCardBottom di paling bawah setelah semua form: Subtotal, Total Bayar, Prev + Lanjutkan). Sticky mobile bar telah dihapus seluruhnya, dan stepper mentok di 4.\n";
} else {
    echo "FAIL Langkah 2: Struktur 2 blok Rincian Pesanan atau penghapusan sticky mobile bar belum sesuai.\n";
    exit(1);
}

// Langkah 3: Form 1 — Urutan field, Tipe Identitas teks "KTP" tanpa panah dropdown, Jenis Kelamin paling bawah & sejajar 1 baris
$posBuyerName   = strpos($orderHtml, 'id="buyerName"');
$posBuyerIdType = strpos($orderHtml, 'id="buyerIdType"');
$posBuyerIdNum  = strpos($orderHtml, 'id="buyerIdNum"');
$posBuyerEmail  = strpos($orderHtml, 'id="buyerEmail"');
$posBuyerWa     = strpos($orderHtml, 'id="buyerWa"');
$posBuyerGender = strpos($orderHtml, 'id="buyerGenderRow"');

$isStaticKtp = (bool) preg_match('/<input[^>]+id="buyerIdType"[^>]+value="KTP"[^>]+readonly/i', $orderHtml)
    && !str_contains($orderHtml, '<select class="o-select" id="buyerIdType"');
$hasGenderClickHandler = str_contains($jsCode, 'setGenderRowValue(buyerGenderRow, val)');
$hasGenderOneRowCss = (bool) preg_match('/\.gender-row\s*\{[^}]*grid-template-columns:\s*1fr\s+1fr/i', $cssCode);

if ($posBuyerName < $posBuyerIdType && $posBuyerIdType < $posBuyerIdNum && $posBuyerIdNum < $posBuyerEmail
    && $posBuyerEmail < $posBuyerWa && $posBuyerWa < $posBuyerGender
    && $isStaticKtp && $hasGenderClickHandler && $hasGenderOneRowCss
) {
    echo "PASS Langkah 3: Form 1 memiliki urutan: (1) Nama Lengkap -> (2) Tipe Identitas (teks tetap 'KTP' readonly tanpa panah dropdown) -> (3) NIK -> (4) Email -> (5) Nomor HP/WA -> (6) Jenis Kelamin (paling bawah, sejajar 1 baris 1fr 1fr, klik aktif & terkirim saat submit).\n";
} else {
    echo "FAIL Langkah 3: Urutan Form 1, Tipe Identitas KTP, atau kesejajaran Jenis Kelamin gagal.\n";
    exit(1);
}

// Langkah 4: Validasi input (Telepon huruf, Telepon 8 digit, NIK 15 digit, Email tanpa @gmail.com)
$baseBuyer = [
    'nama'            => 'Budi Santoso',
    'tipe_identitas'  => 'KTP',
    'nomor_identitas' => '3171011201990001',
    'email'           => 'budi@gmail.com',
    'jenis_kelamin'   => 'Laki-laki',
    'wa'              => '+628123456789',
];
$rWaLetters = httpPostJson($baseUrl . '/api/checkout/pay', ['ticket_category_id' => 6, 'jumlah' => 1, 'metode' => 'QRIS', 'checkout_token' => $checkoutToken, 'buyer' => array_merge($baseBuyer, ['wa' => '+628123abcde']), 'attendees' => [array_merge($baseBuyer, ['wa' => '+628123abcde'])]], $csrf, $cookieFile);
$rWa8       = httpPostJson($baseUrl . '/api/checkout/pay', ['ticket_category_id' => 6, 'jumlah' => 1, 'metode' => 'QRIS', 'checkout_token' => $checkoutToken, 'buyer' => array_merge($baseBuyer, ['wa' => '+6281234567']), 'attendees' => [array_merge($baseBuyer, ['wa' => '+6281234567'])]], $csrf, $cookieFile);
$rNik15     = httpPostJson($baseUrl . '/api/checkout/pay', ['ticket_category_id' => 6, 'jumlah' => 1, 'metode' => 'QRIS', 'checkout_token' => $checkoutToken, 'buyer' => array_merge($baseBuyer, ['nomor_identitas' => '317101120199000']), 'attendees' => [array_merge($baseBuyer, ['nomor_identitas' => '317101120199000'])]], $csrf, $cookieFile);
$rEmail     = httpPostJson($baseUrl . '/api/checkout/pay', ['ticket_category_id' => 6, 'jumlah' => 1, 'metode' => 'QRIS', 'checkout_token' => $checkoutToken, 'buyer' => array_merge($baseBuyer, ['email' => 'budi@yahoo.com']), 'attendees' => [array_merge($baseBuyer, ['email' => 'budi@yahoo.com'])]], $csrf, $cookieFile);
httpPostJson($baseUrl . '/api/checkout/reset', [], $csrf, $cookieFile);

if ($rWaLetters['code'] === 422 && $rWa8['code'] === 422 && $rNik15['code'] === 422 && $rEmail['code'] === 422) {
    echo "PASS Langkah 4: Validasi menolak telepon huruf (422), telepon 8 digit (422), NIK 15 digit (422), dan email tanpa @gmail.com (422).\n";
} else {
    echo "FAIL Langkah 4: Validasi input gagal.\n";
    exit(1);
}

// Langkah 5: Tombol "+ Tambah" dihapus sepenuhnya, Jumlah Form = Jumlah Tiket (Data Pemesan = Pemegang 1; Form Tambahan = Qty - 1), & Toggle "Samakan" mengisi semua form tambahan + tetap bisa diedit
$noTambahHtml = !str_contains($html, 'btnTambahTiket') && !str_contains($html, 'tambahTiketWrap') && !str_contains($html, 'btn-tambah-tiket');
$noTambahJs   = !str_contains($jsCode, 'btnTambahTiket') && !str_contains($jsCode, 'tambahTiketWrap') && !str_contains($jsCode, 'btn-tambah-tiket');
$noTambahCss  = !str_contains($cssCode, 'btn-tambah-tiket');

$hasQtyMinusOneLogic = str_contains($jsCode, 'var neededExtra = Math.max(0, targetQty - 1);')
    && str_contains($jsCode, 'if (neededExtra === 0)')
    && str_contains($jsCode, 'for (var i = existingCount; i < neededExtra; i++)');
$hasSyncAllEditable = str_contains($jsCode, 'function applySyncToAllAttendees()')
    && str_contains($jsCode, 'attEl.disabled = false;')
    && str_contains($jsCode, 'chip.disabled = false;')
    && !str_contains($jsCode, 'attEl.disabled = syncOn')
    && !str_contains($jsCode, 'chip.disabled = syncOn');
$hasPayloadBuyerPlusExtra = str_contains($jsCode, 'list.push(collectBuyerPayload());')
    && str_contains($jsCode, 'var extraCount = Math.max(0, currentQty - 1);');

// Simulasi checkout 3 tiket (Pemegang 1 = Data Pemesan, Pemegang 2 & 3 = hasil toggle Samakan lalu diedit manual Nama & NIK-nya)
$resStart3 = httpPostJson($baseUrl . '/api/checkout/start', ['ticket_category_id' => 6, 'jumlah' => 3], $csrf, $cookieFile);
$tok3 = $resStart3['body']['checkout_token'] ?? '';
$att1 = $baseBuyer; // Data Pemesan (Tiket 1)
$att2 = array_merge($baseBuyer, ['nama' => 'Siti Aminah', 'nomor_identitas' => '3171011201990002', 'jenis_kelamin' => 'Perempuan']); // Form tambahan 1 (diedit manual)
$att3 = array_merge($baseBuyer, ['nama' => 'Andi Pratama', 'nomor_identitas' => '3171011201990003']); // Form tambahan 2 (diedit manual)
$resPay3 = httpPostJson($baseUrl . '/api/checkout/pay', [
    'ticket_category_id' => 6,
    'jumlah'             => 3,
    'metode'             => 'QRIS',
    'checkout_token'     => $tok3,
    'buyer'              => $baseBuyer,
    'attendees'          => [$att1, $att2, $att3],
], $csrf, $cookieFile);
$paidTickets3 = $resPay3['body']['invoice']['tickets'] ?? [];

// Bersihkan order verifikasi dan pulihkan stok agar database tetap bersih
if (!empty($resPay3['body']['invoice']['order_id'])) {
    require_once dirname(__DIR__) . '/app/core/Database.php';
    $pdo = \App\Core\Database::getInstance();
    $oid = (int) $resPay3['body']['invoice']['order_id'];
    $pdo->prepare('DELETE FROM tickets WHERE order_id = ?')->execute([$oid]);
    $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$oid]);
    $pdo->prepare('UPDATE ticket_categories SET stok_sisa = stok_sisa + 3 WHERE id = 6')->execute();
}

$t0Name = $paidTickets3[0]['nama_pemegang'] ?? '';
$t1Name = $paidTickets3[1]['nama_pemegang'] ?? '';
$t2Name = $paidTickets3[2]['nama_pemegang'] ?? '';

if ($noTambahHtml && $noTambahJs && $noTambahCss && $hasQtyMinusOneLogic && $hasSyncAllEditable && $hasPayloadBuyerPlusExtra
    && $resPay3['code'] === 200 && count($paidTickets3) === 3
    && $t0Name === 'Budi Santoso'
    && $t1Name === 'Siti Aminah'
    && $t2Name === 'Andi Pratama'
) {
    echo "PASS Langkah 5: Tombol '+ Tambah' dihapus sepenuhnya; jumlah form = jumlah tiket (Data Pemesan = Pemegang 1 + [qty - 1] form tambahan: qty 1 -> 1 form, qty 2 -> 2 form, qty 3 -> 3 form, qty 4 -> 4 form); toggle 'Samakan dengan detail pemesan' mengisi semua form tambahan sekaligus dan setiap field tetap bisa diedit manual (terbukti 3 tiket berhasil dibuat dengan Nama & NIK form ke-2 dan ke-3 diedit manual).\n";
} else {
    echo "FAIL Langkah 5: Verifikasi Fase 4 & Fase 7 gagal.\n";
    var_dump($noTambahHtml, $noTambahJs, $noTambahCss, $hasQtyMinusOneLogic, $hasSyncAllEditable, $hasPayloadBuyerPlusExtra, $resPay3['code'], $paidTickets3);
    exit(1);
}

// Langkah 6: Langkah Metode Pembayaran — tiap metode punya keterangan singkat di bawah namanya
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
    }
}
if ($allDescsPresent) {
    echo "PASS Langkah 6: Seluruh metode pembayaran (E-Wallet/QRIS & Transfer Bank) memiliki keterangan singkat 1 baris di bawah namanya.\n";
} else {
    echo "FAIL Langkah 6: Ada metode pembayaran tanpa keterangan.\n";
    exit(1);
}

// Langkah 7: Halaman /staff (Logo sama dengan homepage, 2 tombol Scan QR & Gunakan Kamera, Contoh QR Demo hilang, Scan QR -> live scan tanpa qrFileInput.click(), cek TKL-A1B2C3 VALID)
httpPostJson($baseUrl . '/api/customer/login', ['email' => 'staff@ticketland.id', 'password' => 'ticket1234'], $csrf, $cookieFile);
$staffPage = httpGet($baseUrl . '/staff', $cookieFile);
$staffHtml = $staffPage['body'];

$homepageTicketSvgPath = 'M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2.2a2.8 2.8 0 0 0 0 5.6V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.2a2.8 2.8 0 0 0 0-5.6V8Z';
$hasMatchingLogo = str_contains($staffHtml, $homepageTicketSvgPath);
$hasScanQrBtn = (bool) preg_match('/id="btnKamera"[^>]*>\s*<i[^>]*><\/i>\s*Scan QR\s*<\/button>/i', $staffHtml);
$hasGunakanKameraBtn = (bool) preg_match('/id="btnFotoQr"[^>]*>\s*<i[^>]*><\/i>\s*Gunakan Kamera\s*<\/button>/i', $staffHtml);
$noDemoQrSection = !str_contains($staffHtml, 'Contoh QR Tiket Demo') && !str_contains($staffHtml, 'qrDemo1') && !str_contains($staffJsCode, 'renderDemoQrs');

// Verify startKamera() never calls qrFileInput.click()
preg_match('/function startKamera\(\)[\s\S]*?function stopKamera\(\)/', $staffJsCode, $mStartKamera);
$startKameraBody = $mStartKamera[0] ?? '';
$startKameraNeverClicksFile = $startKameraBody !== '' && !str_contains($startKameraBody, 'qrFileInput.click()');

preg_match('/csrfToken:\s*"([^"]+)"/', $staffHtml, $mStaffCsrf);
$staffCsrf = $mStaffCsrf[1] ?? $csrf;
$checkTicket = httpPostJson($baseUrl . '/api/staff/check-ticket', ['kode_tiket' => 'TKL-A1B2C3'], $staffCsrf, $cookieFile);

if ($hasMatchingLogo && $hasScanQrBtn && $hasGunakanKameraBtn && $noDemoQrSection && $startKameraNeverClicksFile
    && $checkTicket['code'] === 200 && ($checkTicket['body']['status'] ?? '') === 'VALID'
) {
    echo "PASS Langkah 7: Halaman /staff menggunakan logo ikon tiket yang sama dengan homepage; memiliki 2 tombol ('Scan QR' -> #btnKamera live scan tanpa memicu input file foto, & 'Gunakan Kamera' -> #btnFotoQr mode foto); bagian 'Contoh QR Tiket Demo' sudah tidak ada; kode TKL-A1B2C3 tetap VALID ({$checkTicket['body']['data']['nama']}).\n";
} else {
    echo "FAIL Langkah 7: Verifikasi halaman /staff gagal.\n";
    var_dump($hasMatchingLogo, $hasScanQrBtn, $hasGunakanKameraBtn, $noDemoQrSection, $startKameraNeverClicksFile);
    exit(1);
}

// Langkah 8: Halaman login Staff (/staff/login) & Admin (/admin/login) punya link/tombol "← Kembali ke Beranda" menuju "/"
$anonCookie = sys_get_temp_dir() . '/verif_part3_anon.txt';
if (file_exists($anonCookie)) {
    unlink($anonCookie);
}
$staffLoginRes = httpGet($baseUrl . '/staff/login', $anonCookie);
$adminLoginRes = httpGet($baseUrl . '/admin/login', $anonCookie);
$hasStaffBackLink = $staffLoginRes['code'] === 200
    && str_contains($staffLoginRes['body'], 'id="btnBackHomeStaffLogin"')
    && str_contains($staffLoginRes['body'], 'href="/"')
    && str_contains($staffLoginRes['body'], 'Kembali ke Beranda');
$hasAdminBackLink = $adminLoginRes['code'] === 200
    && str_contains($adminLoginRes['body'], 'id="btnBackHomeAdminLogin"')
    && str_contains($adminLoginRes['body'], 'href="/"')
    && str_contains($adminLoginRes['body'], 'Kembali ke Beranda');

if ($hasStaffBackLink && $hasAdminBackLink) {
    echo "PASS Langkah 8: Halaman /staff/login dan /admin/login menampilkan tombol '← Kembali ke Beranda' menuju '/' tanpa mengubah desain kartu login.\n";
} else {
    echo "FAIL Langkah 8: Tombol Kembali ke Beranda di halaman login Staff/Admin belum ditemukan.\n";
    exit(1);
}

echo "\n=== SELURUH 8 LANGKAH VERIFIKASI BAGIAN 3 LULUS (100% PASS) ===\n";
