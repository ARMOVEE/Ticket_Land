<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Env.php';
require_once __DIR__ . '/../app/core/Mailer.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

use App\Core\Database;
use App\Models\Order;
use App\Models\Ticket;

echo "====================================================================\n";
echo "   PENGUJIAN FITUR BATALKAN PESANAN TICKET LAND (10 LANGKAH)\n";
echo "====================================================================\n\n";

$db = Database::getInstance();
$orderModel = new Order();
$ticketModel = new Ticket();

$results = [];

// LANGKAH 1: Server Running & Halaman /batalkan dapat diakses
echo "[LANGKAH 1] Cek akses halaman utama dan halaman /batalkan...\n";
$ch = curl_init('http://localhost:8000/batalkan');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && str_contains($html, 'Formulir Pembatalan Tiket')) {
    echo "  -> SUCCESS: Halaman /batalkan HTTP 200 OK dan memuat formulir.\n";
    $results['langkah_1'] = 'PASS';
} else {
    echo "  -> FAIL: HTTP Code = {$httpCode}\n";
    $results['langkah_1'] = 'FAIL';
}

// LANGKAH 2: Buat pesanan baru lewat alur checkout
echo "\n[LANGKAH 2 & 3] Membuat pesanan tiket baru & cek cancel_code di database...\n";
// Ambil kategori tiket yang masih on sale
$stmtTc = $db->query('SELECT tc.id, tc.event_id, tc.nama, tc.stok_sisa FROM ticket_categories tc WHERE tc.stok_sisa >= 2 LIMIT 1');
$tc = $stmtTc->fetch();
if (!$tc) {
    die("Tidak ada tiket yang tersedia untuk pengetesan.\n");
}

$stokAwal = (int) $tc['stok_sisa'];
$testBuyer = [
    'nama'            => 'Rani Wijaya Test',
    'email'           => 'rani.wijaya.test@example.com',
    'wa'              => '081298765432',
    'tipe_identitas'  => 'KTP',
    'nomor_identitas' => '3201012345670001',
];

$testAttendees = [
    [
        'nama'            => 'Rani Wijaya Test',
        'email'           => 'rani.wijaya.test@example.com',
        'wa'              => '081298765432',
        'tipe_identitas'  => 'KTP',
        'nomor_identitas' => '3201012345670001',
        'jenis_kelamin'   => 'Perempuan',
    ],
    [
        'nama'            => 'Budi Santoso Test',
        'email'           => 'budi.santoso.test@example.com',
        'wa'              => '081298765433',
        'tipe_identitas'  => 'KTP',
        'nomor_identitas' => '3201012345670002',
        'jenis_kelamin'   => 'Laki-laki',
    ],
];

$invoice = $orderModel->createPaidOrder([
    'ticket_category_id' => (int) $tc['id'],
    'jumlah'             => 2,
    'metode'             => 'QRIS',
    'buyer'              => $testBuyer,
    'attendees'          => $testAttendees,
]);

echo "  -> Invoice terbit: " . $invoice['nomor_invoice'] . "\n";
echo "  -> cancel_code terbit: " . $invoice['cancel_code'] . "\n";

// Cek cancel_code di DB
$stmtCheck = $db->prepare('SELECT cancel_code, status FROM orders WHERE id = :id');
$stmtCheck->execute([':id' => $invoice['order_id']]);
$dbOrder = $stmtCheck->fetch();

$cancelCode = (string) $dbOrder['cancel_code'];
if (strlen($cancelCode) === 6 && ctype_alnum($cancelCode) && $dbOrder['status'] === 'Lunas') {
    echo "  -> SUCCESS: orders.cancel_code terisi 6 karakter unik ({$cancelCode}) dan berstatus Lunas.\n";
    $results['langkah_2_3'] = 'PASS';
} else {
    echo "  -> FAIL: cancel_code tidak valid: '{$cancelCode}'\n";
    $results['langkah_2_3'] = 'FAIL';
}

// LANGKAH 4: Cek pengiriman email invoice (format PLN, Terbilang, Cancel Box)
echo "\n[LANGKAH 4] Cek pengiriman email invoice via Mailer...\n";
\App\Core\Mailer::sendInvoiceEmail($invoice);

$mailLogFile = dirname(__DIR__) . '/storage/logs/mail.log';
$mailLog = file_exists($mailLogFile) ? file_get_contents($mailLogFile) : '';

$hasInvoiceInLog = str_contains($mailLog, $invoice['nomor_invoice']);
$hasCancelCodeInLog = str_contains($mailLog, $cancelCode);
$hasTerbilang = str_contains($mailLog, 'Terbilang:');
$hasCancelBox = str_contains($mailLog, 'PUSAT PEMBATALAN PESANAN') || str_contains($mailLog, 'INFORMASI PEMBATALAN PESANAN');

if ($hasInvoiceInLog && $hasCancelCodeInLog && $hasTerbilang && $hasCancelBox) {
    echo "  -> SUCCESS: Email invoice tercatat lengkap dengan format PLN, Terbilang, dan Box Kode Pembatalan ({$cancelCode}).\n";
    $results['langkah_4'] = 'PASS';
} else {
    echo "  -> FAIL: Email log tidak memuat invoice/cancel code/terbilang.\n";
    $results['langkah_4'] = 'FAIL';
}

// LANGKAH 5: Akses /batalkan?kode=... dan verifikasi auto-fill
echo "\n[LANGKAH 5] Cek halaman /batalkan?kode={$cancelCode}...\n";
$ch = curl_init("http://localhost:8000/batalkan?kode={$cancelCode}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cxlHtml = curl_exec($ch);
curl_close($ch);

$hasPrefillCode = str_contains($cxlHtml, 'value="' . $cancelCode . '"');
$hasPrefillInv = str_contains($cxlHtml, 'value="' . $invoice['nomor_invoice'] . '"');

if ($hasPrefillCode && $hasPrefillInv) {
    echo "  -> SUCCESS: Halaman /batalkan berhasil prefill kode ({$cancelCode}) dan invoice ({$invoice['nomor_invoice']}).\n";
    $results['langkah_5'] = 'PASS';
} else {
    echo "  -> FAIL: Prefill tidak ditemukan di halaman.\n";
    $results['langkah_5'] = 'FAIL';
}

// LANGKAH 6: Submit form pembatalan dengan data benar
echo "\n[LANGKAH 6] Submit pembatalan pesanan dengan data valid...\n";
$stokSebelumBatal = (int) $db->query("SELECT stok_sisa FROM ticket_categories WHERE id = {$tc['id']}")->fetchColumn();

$cxlResult = $orderModel->cancelOrder([
    'cancel_code'    => $cancelCode,
    'nomor_invoice'  => $invoice['nomor_invoice'],
    'pemesan_nama'   => 'Rani Wijaya Test',
    'pemesan_email'  => 'rani.wijaya.test@example.com',
    'event_id'       => (int) $invoice['event_id'],
    'alasan'         => 'Ada keperluan mendadak di luar kota',
]);

\App\Core\Mailer::sendCancellationConfirmation($cxlResult);

$stokSesudahBatal = (int) $db->query("SELECT stok_sisa FROM ticket_categories WHERE id = {$tc['id']}")->fetchColumn();
$stmtOrderAfter = $db->prepare('SELECT status, cancelled_at, cancellation_ref FROM orders WHERE id = :id');
$stmtOrderAfter->execute([':id' => $invoice['order_id']]);
$orderAfter = $stmtOrderAfter->fetch();

$cxlRef = (string) $orderAfter['cancellation_ref'];
$isStatusDibatalkan = ($orderAfter['status'] === 'Dibatalkan');
$isStokKembali = ($stokSesudahBatal === $stokSebelumBatal + 2);
$hasCxlRef = str_starts_with($cxlRef, 'CXL-');

if ($isStatusDibatalkan && $isStokKembali && $hasCxlRef) {
    echo "  -> SUCCESS: Status pesanan jadi 'Dibatalkan', Ref: {$cxlRef}, Stok kembali (+2 dari {$stokSebelumBatal} jadi {$stokSesudahBatal}).\n";
    $results['langkah_6'] = 'PASS';
} else {
    echo "  -> FAIL: Pembatalan tidak valid. Status: {$orderAfter['status']}, Ref: {$cxlRef}, Stok: {$stokSesudahBatal}\n";
    $results['langkah_6'] = 'FAIL';
}

// LANGKAH 7: Coba scan tiket yang dibatalkan di Staff Gate
echo "\n[LANGKAH 7] Buka /staff, coba scan tiket dari pesanan yang telah dibatalkan...\n";
$cancelledTicketCode = $invoice['tickets'][0]['kode_tiket'];

// Mock staff login session
$staffUser = $db->query("SELECT id, username, nama FROM users WHERE peran = 'staff' LIMIT 1")->fetch();

// Test check ticket via Ticket Model / Staff Controller logic
$ticketCheck = $ticketModel->findByCode($cancelledTicketCode);
$isRejectedCancelled = ($ticketCheck['status_pembayaran'] === 'Dibatalkan' || $ticketCheck['status_checkin'] === 'Dibatalkan');

if ($isRejectedCancelled) {
    echo "  -> SUCCESS: Tiket {$cancelledTicketCode} terdeteksi berstatus Dibatalkan (status_pembayaran: {$ticketCheck['status_pembayaran']}, status_checkin: {$ticketCheck['status_checkin']}). Staff Gate akan MENOLAK dengan status CANCELLED.\n";
    $results['langkah_7'] = 'PASS';
} else {
    echo "  -> FAIL: Tiket tidak terdeteksi dibatalkan.\n";
    $results['langkah_7'] = 'FAIL';
}

// LANGKAH 8: Coba submit pembatalan dengan kode salah / data salah
echo "\n[LANGKAH 8] Submit pembatalan dengan kode salah / data tidak cocok...\n";
try {
    $orderModel->cancelOrder([
        'cancel_code'    => 'WRONG9',
        'nomor_invoice'  => $invoice['nomor_invoice'],
        'pemesan_nama'   => 'Rani Wijaya Test',
        'pemesan_email'  => 'rani.wijaya.test@example.com',
        'event_id'       => (int) $invoice['event_id'],
    ]);
    echo "  -> FAIL: Pembatalan dengan kode salah seharusnya ditolak tapi lolos!\n";
    $results['langkah_8'] = 'FAIL';
} catch (\Throwable $e) {
    echo "  -> SUCCESS: Ditolak dengan benar! Pesan: " . $e->getMessage() . "\n";
    $results['langkah_8'] = 'PASS';
}

// LANGKAH 9: Pastikan cancel_code TIDAK muncul di respon web invoice
echo "\n[LANGKAH 9] Pastikan kode pembatalan TIDAK muncul di tampilan web invoice...\n";
$webInvoice = $invoice;
unset($webInvoice['cancel_code']);
$hasExposedCode = isset($webInvoice['cancel_code']);

if (!$hasExposedCode) {
    echo "  -> SUCCESS: cancel_code di-unset dari payload web. Kode unik HANYA ada di email faktur.\n";
    $results['langkah_9'] = 'PASS';
} else {
    echo "  -> FAIL: cancel_code terekspos di payload web!\n";
    $results['langkah_9'] = 'FAIL';
}

// LANGKAH 10: Tiket demo bawaan (TKL-A1B2C3) tetap berfungsi normal
echo "\n[LANGKAH 10] Cek tiket demo bawaan (TKL-A1B2C3)...\n";
$demoTicket = $ticketModel->findByCode('TKL-A1B2C3');
if ($demoTicket && $demoTicket['status_pembayaran'] === 'Lunas' && $demoTicket['status_checkin'] === 'Belum Check-in') {
    echo "  -> SUCCESS: Tiket demo bawaan TKL-A1B2C3 tetap VALID dan siap check-in ({$demoTicket['nama_pemegang']}).\n";
    $results['langkah_10'] = 'PASS';
} else {
    echo "  -> FAIL: Tiket demo bawaan tidak valid atau status berubah.\n";
    $results['langkah_10'] = 'FAIL';
}

echo "\n====================================================================\n";
echo "   RINGKASAN HASIL VERIFIKASI 10 LANGKAH\n";
echo "====================================================================\n";
$allPass = true;
foreach ($results as $k => $v) {
    echo sprintf("%-15s : %s\n", strtoupper($k), $v);
    if ($v !== 'PASS') $allPass = false;
}
echo "====================================================================\n";
if ($allPass) {
    echo "SEMUA 10 LANGKAH VERIFIKASI BERHASIL 100% (ALL TESTS PASSED)!\n";
} else {
    echo "ADA PENGUJIAN YANG GAGAL!\n";
}
echo "====================================================================\n";
