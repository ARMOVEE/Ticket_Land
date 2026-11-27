<?php
declare(strict_types=1);

$baseUrl = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/staff_cookie_test.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

// 1. Initial GET /
$ch = curl_init($baseUrl . '/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/"csrfToken":"([^"]+)"/', (string)$html, $m);
$csrf1 = $m[1] ?? '';

// 2. Login as staff
$ch = curl_init($baseUrl . '/api/customer/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-CSRF-Token: ' . $csrf1
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email'      => 'staff@ticketland.id',
    'password'   => 'ticket1234',
    'csrf_token' => $csrf1
]));
$loginRes = curl_exec($ch);
curl_close($ch);
echo "Login: {$loginRes}\n";

// 3. GET /staff to get staff page & staff session CSRF
$ch = curl_init($baseUrl . '/staff');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$staffHtml = curl_exec($ch);
curl_close($ch);

preg_match('/csrfToken\s*=\s*"([^"]+)"/i', (string)$staffHtml, $mStaff);
if (empty($mStaff)) {
    preg_match('/"csrfToken":"([^"]+)"/', (string)$staffHtml, $mStaff);
}
$csrfStaff = $mStaff[1] ?? $csrf1;
echo "Staff CSRF: " . substr($csrfStaff, 0, 10) . "...\n";

// 4. Check Ticket TKL-A1B2C3
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
    'kode_tiket' => 'TKL-A1B2C3',
    'csrf_token' => $csrfStaff
]));
$res = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Check TKL-A1B2C3: HTTP {$http}\n";
echo "Response: {$res}\n";
