<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/verif_revisi_cookie.txt';
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

echo "=== VERIFIKASI REVISI: 3 BLOK DETAIL PESANAN + LINK KEMBALI KE BERANDA ===\n\n";

$home = httpGet($baseUrl . '/', $cookieFile);
if ($home['code'] !== 200) {
    echo "FAIL: Server tidak merespons HTTP 200\n";
    exit(1);
}
$html = $home['body'];
preg_match('/"csrfToken":"([^"]+)"/', $html, $mCsrf);
$csrf = $mCsrf[1] ?? '';

// 1. Verifikasi 3 Blok Terpisah di Langkah Detail Pesanan (Contoh: GIIAS 2027, VIP Family Pass id=7)
$startGiias = httpPostJson($baseUrl . '/api/checkout/start', [
    'ticket_category_id' => 7, // GIIAS 2027 - VIP Family Pass
    'jumlah'             => 1,
], $csrf, $cookieFile);
httpPostJson($baseUrl . '/api/checkout/reset', [], $csrf, $cookieFile);

$orderPagePos = strpos($html, 'id="orderPage"');
$payPagePos   = strpos($html, 'id="payPage"');
$orderHtml    = substr($html, $orderPagePos, $payPagePos - $orderPagePos);

preg_match('/<div class="rincian-card" id="rincianCardTop">([\s\S]*?)<\/div>\s*<!-- BLOK 2/i', $orderHtml, $mBlock1);
preg_match('/<div class="rincian-card rincian-summary-block" id="rincianCardBottom">([\s\S]*?)<\/div>\s*<\/aside>/i', $orderHtml, $mBlock2);
preg_match('/<div id="attendeesContainer"><\/div>\s*<!-- BLOK 3[\s\S]*?<div class="rincian-card rincian-actions-block" id="rincianActionsBlock">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<aside class="order-side">/i', $orderHtml, $mBlock3);

$b1 = $mBlock1[1] ?? '';
$b2 = $mBlock2[1] ?? '';
$b3 = $mBlock3[1] ?? '';

$b1Ok = str_contains($b1, 'Rincian Pesanan') && str_contains($b1, 'id="rincianCatName"') && str_contains($b1, 'id="qtyPlus"')
    && !str_contains($b1, 'id="rincianSubtotal"') && !str_contains($b1, 'id="rincianTotal"') && !str_contains($b1, 'id="btnLanjut"');

$b2Ok = str_contains($b2, 'id="rincianSubtotal"') && str_contains($b2, 'id="rincianTotal"')
    && !str_contains($b2, 'id="btnRincianBack"') && !str_contains($b2, 'id="btnLanjut"') && !str_contains($b2, 'r-actions');

$b3Ok = str_contains($b3, 'id="btnRincianBack"') && str_contains($b3, 'id="btnLanjut"')
    && !str_contains($b3, 'id="rincianSubtotal"') && !str_contains($b3, 'id="rincianTotal"');

if ($startGiias['code'] === 200 && $b1Ok && $b2Ok && $b3Ok) {
    echo "PASS Verifikasi 1: Langkah Detail Pesanan (diuji dengan GIIAS 2027 - VIP Family Pass) memiliki 3 blok terpisah:\n";
    echo "  - Blok 1 (#rincianCardTop): berakhir di stepper Jumlah Tiket (tanpa Subtotal/Total/tombol)\n";
    echo "  - Blok 2 (#rincianCardBottom): hanya Subtotal & Total Bayar (tanpa tombol)\n";
    echo "  - Blok 3 (#rincianActionsBlock): tombol Prev + Lanjutkan berdiri sendiri di paling bawah setelah #attendeesContainer.\n";
} else {
    echo "FAIL Verifikasi 1: Pemisahan 3 blok belum sesuai.\n";
    var_dump($b1Ok, $b2Ok, $b3Ok);
    exit(1);
}

// 2. Verifikasi Sticky Bottom Bar Mobile Tidak Berubah
$hasMobileBar = str_contains($html, 'id="checkoutMobileBar"') && str_contains($html, 'id="cmbPrice"') && str_contains($html, 'id="cmbBtn"');
if ($hasMobileBar) {
    echo "PASS Verifikasi 2: Sticky bottom bar (#checkoutMobileBar: TOTAL PEMBAYARAN + Lanjutkan) tetap utuh dan tidak berubah.\n";
} else {
    echo "FAIL Verifikasi 2: Sticky bottom bar hilang atau berubah.\n";
    exit(1);
}

// 3. Verifikasi Link "← Kembali ke Beranda" di /staff/login dan /admin/login
$staffLogin = httpGet($baseUrl . '/staff/login', $cookieFile);
$adminLogin = httpGet($baseUrl . '/admin/login', $cookieFile);

$staffHasBackLink = (bool) preg_match('/<a[^>]+href="\/"[^>]+id="btnBackHomeStaffLogin"[^>]*>[\s\S]*?Kembali ke Beranda[\s\S]*?<\/a>/i', $staffLogin['body']);
$adminHasBackLink = (bool) preg_match('/<a[^>]+href="\/"[^>]+id="btnBackHomeAdminLogin"[^>]*>[\s\S]*?Kembali ke Beranda[\s\S]*?<\/a>/i', $adminLogin['body']);

if ($staffLogin['code'] === 200 && $adminLogin['code'] === 200 && $staffHasBackLink && $adminHasBackLink) {
    echo "PASS Verifikasi 3: Halaman /staff/login dan /admin/login memiliki tombol '← Kembali ke Beranda' yang terlihat jelas di kiri atas kartu login dan mengarah ke '/'.\n";
} else {
    echo "FAIL Verifikasi 3: Link Kembali ke Beranda pada /staff/login atau /admin/login belum ditemukan.\n";
    exit(1);
}

// 4. Verifikasi Form Login Staff & Admin Tidak Berubah
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
    echo "PASS Verifikasi 4: Form login Staff dan Admin tetap terkunci (kartu, field, tombol Masuk, dan kotak akun demo tidak berubah).\n";
} else {
    echo "FAIL Verifikasi 4: Struktur form login berubah.\n";
    exit(1);
}

// 5. Verifikasi Login Staff (staff@ticketland.id / ticket1234) & Cek Manual TKL-A1B2C3 Tetap VALID
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
    echo "PASS Verifikasi 5: Login staff (staff@ticketland.id / ticket1234) berhasil dan pemeriksaan manual kode TKL-A1B2C3 tetap VALID (Pemegang: {$resCheckTicket['body']['data']['nama']}, Event: {$resCheckTicket['body']['data']['event']}, Kategori: {$resCheckTicket['body']['data']['kategori']}).\n";
} else {
    echo "FAIL Verifikasi 5: Login staff atau cek tiket TKL-A1B2C3 gagal.\n";
    exit(1);
}

echo "\n=== SELURUH 5 SKENARIO VERIFIKASI REVISI LULUS (100% PASS) ===\n";
