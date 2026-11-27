<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

echo "Checking orders table schema..." . PHP_EOL;

$columns = [];
$stmt = $db->query("SHOW COLUMNS FROM orders");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[$row['Field']] = $row;
}

if (!isset($columns['cancel_code'])) {
    echo "Adding cancel_code column..." . PHP_EOL;
    $db->exec("ALTER TABLE orders ADD COLUMN cancel_code VARCHAR(6) NULL UNIQUE AFTER status");
} else {
    echo "cancel_code already exists." . PHP_EOL;
}

if (!isset($columns['cancelled_at'])) {
    echo "Adding cancelled_at column..." . PHP_EOL;
    $db->exec("ALTER TABLE orders ADD COLUMN cancelled_at DATETIME NULL AFTER cancel_code");
} else {
    echo "cancelled_at already exists." . PHP_EOL;
}

if (!isset($columns['cancellation_ref'])) {
    echo "Adding cancellation_ref column..." . PHP_EOL;
    $db->exec("ALTER TABLE orders ADD COLUMN cancellation_ref VARCHAR(20) NULL AFTER cancelled_at");
} else {
    echo "cancellation_ref already exists." . PHP_EOL;
}

if (!isset($columns['cancellation_reason'])) {
    echo "Adding cancellation_reason column..." . PHP_EOL;
    $db->exec("ALTER TABLE orders ADD COLUMN cancellation_reason TEXT NULL AFTER cancellation_ref");
} else {
    echo "cancellation_reason already exists." . PHP_EOL;
}

// Generate cancel_code for existing orders that have null cancel_code
$stmtEmpty = $db->query("SELECT id, nomor_invoice FROM orders WHERE cancel_code IS NULL OR cancel_code = ''");
$emptyOrders = $stmtEmpty->fetchAll(PDO::FETCH_ASSOC);

if (!empty($emptyOrders)) {
    echo "Generating cancel_code for " . count($emptyOrders) . " existing orders..." . PHP_EOL;
    $updateStmt = $db->prepare("UPDATE orders SET cancel_code = :code WHERE id = :id");
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE cancel_code = :code");
    
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // exclude confusing chars like 0, O, 1, I
    
    foreach ($emptyOrders as $ord) {
        $unique = false;
        $code = '';
        while (!$unique) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $checkStmt->execute([':code' => $code]);
            if ($checkStmt->fetchColumn() == 0) {
                $unique = true;
            }
        }
        $updateStmt->execute([':code' => $code, ':id' => $ord['id']]);
        echo "Order #{$ord['id']} ({$ord['nomor_invoice']}) -> cancel_code: {$code}" . PHP_EOL;
    }
}

echo "Migration finished successfully!" . PHP_EOL;
