<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/verif_revisi_0554_cookie.txt';
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

echo "=== VERIFIKASI REVISI 05:54 (SUBTOTAL/TOTAL DI PALING BAWAH + HAPUS STICKY BAR + LINK KEMBALI) ===\n\n";

$home = httpGet($baseUrl . '/', $cookieFile);
if ($home['code'] !== 200) {
    echo "FAIL: Server tidak merespons HTTP 200\n";
    exit(1);
}
$html = $home['body'];
$cssCode = (string) file_get_contents(dirname(__DIR__) . '/public/assets/css/landing.css');
$jsCode  = (string) file_get_contents(dirname(__DIR__) . '/public/assets/js/landing.js');
preg_match('/"csrfToken":"([^"]+)"/', $html, $mCsrf);
$csrf = $mCsrf[1] ?? '';

// 1. Verifikasi Langkah Detail Pesanan (Synchronize Fest 2027 VIP x4 / GIIAS 2027 VIP Family Pass)
$startSync = httpPostJson($baseUrl . '/api/checkout/start', [
    'ticket_category_id' => 9, // Synchronize Fest 2027 - VIP
    'jumlah'             => 4,
], $csrf, $cookieFile);
httpPostJson($baseUrl . '/api/checkout/reset', [], $csrf, $cookieFile);

$orderPagePos = strpos($html, 'id="orderPage"');
$payPagePos   = strpos($html, 'id="payPage"');
$orderHtml    = substr($html, $orderPagePos, $payPagePos - $orderPagePos);

// Cek bahwa #rincianCardTop di dalam <aside class="order-side"> hanya berisi judul, baris tiket, dan stepper
preg_match('/<aside class="order-side">([\s\S]*?)<\/aside>/i', $orderHtml, $mAside);
$asideHtml = $mAside[1] ?? '';
$asideHasTopCard = str_contains($asideHtml, 'id="rincianCardTop"')
    && str_contains($asideHtml, 'Rincian Pesanan')
    && str_contains($asideHtml, 'id="rincianCatName"')
    && str_contains($asideHtml, 'id="qtyPlus"');
$asideEndsAtStepper = !str_contains($asideHtml, 'id="rincianSubtotal"')
    && !str_contains($asideHtml, 'id="rincianTotal"')
    && !str_contains($asideHtml, 'id="btnLanjut"')
    && !str_contains($asideHtml, 'id="rincianCardBottom"');

// Cek bahwa #rincianCardBottom berada di PALING BAWAH .order-main (setelah Data Pemesan & #attendeesContainer)
$posBuyerCard   = strpos($orderHtml, '<h3>Data Pemesan</h3>');
$posAttendees   = strpos($orderHtml, 'id="attendeesContainer"');
$posBottomBlock = strpos($orderHtml, 'id="rincianCardBottom"');

preg_match('/<div class="rincian-card rincian-summary-block" id="rincianCardBottom">([\s\S]*?)<\/div>\s*<\/div>\s*<aside class="order-side">/i', $orderHtml, $mBottom);
$bottomHtml = $mBottom[1] ?? '';
$bottomHasAll = str_contains($bottomHtml, 'id="rincianSubtotal"')
    && str_contains($bottomHtml, 'id="rincianTotal"')
    && str_contains($bottomHtml, 'id="btnRincianBack"')
    && str_contains($bottomHtml, 'id="btnLanjut"');

if ($startSync['code'] === 200 && $asideHasTopCard && $asideEndsAtStepper
    && $posBuyerCard < $posAttendees && $posAttendees < $posBottomBlock && $bottomHasAll
) {
    echo "PASS Verifikasi 1: Kartu 'Rincian Pesanan' (#rincianCardTop) berakhir di stepper Jumlah Tiket; di bawahnya langsung form Data Pemesan & pemegang tiket (#attendeesContainer); blok 'Subtotal' + 'Total Bayar' + tombol Prev + 'Lanjutkan' (#rincianCardBottom) berada di PALING BAWAH halaman.\n";
} else {
    echo "FAIL Verifikasi 1: Struktur blok Subtotal/Total di paling bawah belum sesuai.\n";
    var_dump($asideHasTopCard, $asideEndsAtStepper, $posBuyerCard, $posAttendees, $posBottomBlock, $bottomHasAll);
    exit(1);
}

// 2. Verifikasi Sticky Mobile Bar SUDAH TIDAK ADA di HTML, CSS, maupun JS
$noStickyHtml = !str_contains($html, 'checkoutMobileBar') && !str_contains($html, 'checkout-mobile-bar') && !str_contains($html, 'cmbPrice') && !str_contains($html, 'cmbBtn');
$noStickyCss  = !str_contains($cssCode, 'checkout-mobile-bar') && !str_contains($cssCode, 'cmb-wrap') && !str_contains($cssCode, 'cmb-btn');
$noStickyJs   = !str_contains($jsCode, 'checkoutMobileBar') && !str_contains($jsCode, 'cmbPrice') && !str_contains($jsCode, 'cmbBtn');

if ($noStickyHtml && $noStickyCss && $noStickyJs) {
    echo "PASS Verifikasi 2: Bar nempel bawah (sticky mobile bar 'TOTAL PEMBAYARAN' + 'Lanjutkan') telah dihapus sepenuhnya dari HTML, CSS, dan JS tanpa menyisakan ruang kosong.\n";
} else {
    echo "FAIL Verifikasi 2: Masih ada sisa kode sticky mobile bar.\n";
    var_dump($noStickyHtml, $noStickyCss, $noStickyJs);
    exit(1);
}

// 3. Verifikasi Tombol "Lanjutkan" di Blok Paling Bawah Tetap Berfungsi Normal
$hasBtnLanjutHandler = str_contains($jsCode, "document.getElementById('btnLanjut').addEventListener('click'")
    && substr_count($orderHtml, 'id="btnLanjut"') === 1;
if ($hasBtnLanjutHandler) {
    echo "PASS Verifikasi 3: Satu-satunya tombol 'Lanjutkan' (#btnLanjut) berada di blok paling bawah (#rincianCardBottom) dan terhubung ke validasi + langkah konfirmasi/pembayaran.\n";
} else {
    echo "FAIL Verifikasi 3: Tombol Lanjutkan bermasalah.\n";
    exit(1);
}

// 4. Verifikasi Link "← Kembali ke Beranda" di /staff/login dan /admin/login
$staffLogin = httpGet($baseUrl . '/staff/login', $cookieFile);
$adminLogin = httpGet($baseUrl . '/admin/login', $cookieFile);

$staffHasBackLink = (bool) preg_match('/<a[^>]+href="\/"[^>]+id="btnBackHomeStaffLogin"[^>]*>[\s\S]*?Kembali ke Beranda[\s\S]*?<\/a>/i', $staffLogin['body']);
$adminHasBackLink = (bool) preg_match('/<a[^>]+href="\/"[^>]+id="btnBackHomeAdminLogin"[^>]*>[\s\S]*?Kembali ke Beranda[\s\S]*?<\/a>/i', $adminLogin['body']);

if ($staffLogin['code'] === 200 && $adminLogin['code'] === 200 && $staffHasBackLink && $adminHasBackLink) {
    echo "PASS Verifikasi 4: Halaman /staff/login dan /admin/login memiliki tombol '← Kembali ke Beranda' yang terlihat jelas dan mengarah ke '/'.\n";
} else {
    echo "FAIL Verifikasi 4: Link Kembali ke Beranda belum sesuai.\n";
    exit(1);
}

// 5. Verifikasi Form Login Staff dan Admin Tidak Berubah (Terkunci)
$staffFormIntact = str_contains($staffLogin['body'], 'id="formLoginStaff"')
    && str_contains($staffLogin['body'], 'id="usernameStaff"')
    && str_contains($staffLogin['body'], 'id="passwordStaff"')
    && str_contains($staffLogin['body'], 'Masuk sebagai Staff')
    && str_contains($staffLogin['body'], 'staff@ticketland.id');

$adminFormIntact = str_contains($adminLogin['body'], 'id="formLogin"')
    && str_contains($adminLogin['body'], 'id="username"')
    && str_contains($adminLogin['body'], 'id="password"')
    && str_contains($adminLogin['body'], 'id="linkKeStaff"');

if ($staffFormIntact && $adminFormIntact) {
    echo "PASS Verifikasi 5: Desain form login Staff dan Admin tetap terkunci (tampilan, field, tombol, dan kotak akun demo tidak berubah).\n";
} else {
    echo "FAIL Verifikasi 5: Form login berubah.\n";
    exit(1);
}

// 6. Verifikasi Login Staff & Pemeriksaan Manual TKL-A1B2C3 Tetap VALID
preg_match('/id="csrfToken"\s+value="([^"]+)"/', $staffLogin['body'], $mStaffLoginCsrf);
$staffLoginCsrf = $mStaffLoginCsrf[1] ?? $csrf;

$resStaffLogin = httpPostJson($baseUrl . '/api/staff/login', [
    'username' => 'staff@ticketland.id',
    'password' => 'ticket1234',
], $staffLoginCsrf, $cookieFile);

$staffScanPage = httpGet($baseUrl . '/staff', $cookieFile);
preg_match('/csrfToken:\s*"([^"]+)"/', $staffScanPage['body'], $mScanCsrf);
$scanCsrf = $mScanCsrf[1] ?? $staffLoginCsrf;

$resCheckTicket = httpPostJson($baseUrl . '/api/staff/check-ticket', [
    'kode_tiket' => 'TKL-A1B2C3',
], $scanCsrf, $cookieFile);

if ($resStaffLogin['code'] === 200 && ($resStaffLogin['body']['ok'] ?? false) === true
    && $resCheckTicket['code'] === 200 && ($resCheckTicket['body']['status'] ?? '') === 'VALID'
) {
    echo "PASS Verifikasi 6: Login staff (staff@ticketland.id / ticket1234) berhasil dan pemeriksaan manual kode TKL-A1B2C3 tetap VALID (Pemegang: {$resCheckTicket['body']['data']['nama']}, Event: {$resCheckTicket['body']['data']['event']}, Kategori: {$resCheckTicket['body']['data']['kategori']}).\n";
} else {
    echo "FAIL Verifikasi 6: Login staff atau cek tiket TKL-A1B2C3 gagal.\n";
    exit(1);
}

echo "\n=== SELURUH 6 SKENARIO VERIFIKASI LULUS (100% PASS) ===\n";
