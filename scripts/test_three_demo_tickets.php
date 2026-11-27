<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/staff_cookie_test.txt';

// Staff CSRF from scan.php
$ch = curl_init($baseUrl . '/staff');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$staffHtml = curl_exec($ch);
curl_close($ch);

preg_match('/csrfToken:\s*"([^"]+)"/', (string)$staffHtml, $mStaff);
$csrfStaff = $mStaff[1] ?? '';
echo "Extracted CSRF: " . substr($csrfStaff, 0, 10) . "...\n";

foreach (['TKL-A1B2C3', 'TKL-D4E5F6', 'TKL-USED99'] as $code) {
    $ch = curl_init($baseUrl . '/api/staff/check-ticket');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-CSRF-Token: ' . $csrfStaff
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'kode_tiket' => $code,
        'csrf_token' => $csrfStaff
    ]));
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Ticket {$code}: HTTP {$http} -> {$res}\n";
}
