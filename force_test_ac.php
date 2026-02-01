<?php
/**
 * Force setup test data for auto-complete
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = 'c:/xampp/htdocs/distrozoneweb/';
require_once $baseDir . 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== FORCE SETUP TEST DATA ===\n\n";

// 1. Find any transaction
$stmt = $conn->query("SELECT id, kode_transaksi, status, shipping_city FROM transaksi LIMIT 1");
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    echo "ERROR: No transactions found in database.\n";
    exit;
}

echo "Found Transaction: {$trx['kode_transaksi']} (Current Status: {$trx['status']})\n";

// 2. Set shipping_city if NULL (get from shipping_rates)
$city = $trx['shipping_city'];
if (empty($city)) {
    $stmt2 = $conn->query("SELECT wilayah FROM shipping_rates LIMIT 1");
    $city = $stmt2->fetchColumn();
    if ($city) {
        $conn->prepare("UPDATE transaksi SET shipping_city = ? WHERE id = ?")->execute([$city, $trx['id']]);
        echo "Set shipping_city to: $city\n";
    }
}

// 3. Get estimasi for this city
$stmt3 = $conn->prepare("SELECT estimasi FROM shipping_rates WHERE wilayah = ?");
$stmt3->execute([$city]);
$estimasi = $stmt3->fetchColumn();
echo "Estimasi for $city: $estimasi\n";

// Parse max days
$max_days = 2;
if (!empty($estimasi) && preg_match_all('/\d+/', $estimasi, $matches)) {
    $max_days = (int) max($matches[0]);
}
echo "Parsed max_days: $max_days\n";

// 4. Force status to 'verified' and set old date
$old_date = date('Y-m-d H:i:s', strtotime("-" . ($max_days + 1) . " days")); // 1 day past deadline
echo "\nSetting status to 'verified' and verified_at to: $old_date\n";

// Update transaksi
$conn->prepare("UPDATE transaksi SET status = 'verified' WHERE id = ?")->execute([$trx['id']]);

// Check/insert payment_proof
$chk = $conn->prepare("SELECT id FROM payment_proof WHERE transaksi_id = ?");
$chk->execute([$trx['id']]);
$ppRow = $chk->fetch();

if ($ppRow) {
    $conn->prepare("UPDATE payment_proof SET verified_at = ? WHERE transaksi_id = ?")
         ->execute([$old_date, $trx['id']]);
    echo "Updated existing payment_proof\n";
} else {
    $conn->prepare("INSERT INTO payment_proof (transaksi_id, image, verified_at, verified_by) VALUES (?, 'test.jpg', ?, 1)")
         ->execute([$trx['id'], $old_date]);
    echo "Created new payment_proof\n";
}

// 5. Verify setup
echo "\n=== VERIFICATION ===\n";
$verify = $conn->prepare("SELECT t.id, t.kode_transaksi, t.status, t.shipping_city, pp.verified_at, sr.estimasi
                          FROM transaksi t
                          LEFT JOIN payment_proof pp ON t.id = pp.transaksi_id
                          LEFT JOIN shipping_rates sr ON t.shipping_city = sr.wilayah
                          WHERE t.id = ?");
$verify->execute([$trx['id']]);
$result = $verify->fetch(PDO::FETCH_ASSOC);
print_r($result);

echo "\n=== NOW RUN AUTO-COMPLETE ===\n";
include $baseDir . 'auto_complete_orders.php';

// 6. Check final status
echo "\n=== FINAL STATUS ===\n";
$final = $conn->prepare("SELECT status FROM transaksi WHERE id = ?");
$final->execute([$trx['id']]);
$finalStatus = $final->fetchColumn();
echo "Final Status: $finalStatus\n";

if ($finalStatus === 'completed') {
    echo "\n*** SUCCESS! Auto-complete is working! ***\n";
} else {
    echo "\n*** FAILED - Status should be 'completed' ***\n";
}
?>
