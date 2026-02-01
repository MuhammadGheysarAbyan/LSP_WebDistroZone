<?php
/**
 * Test Estimasi-Based Auto Complete
 * 1. Find one completed transaction
 * 2. Revert to 'verified' with old date (> estimasi)
 * 3. Run auto_complete
 * 4. Check if status changed back to 'completed'
 */

$baseDir = 'c:/xampp/htdocs/distrozoneweb/';
require_once $baseDir . 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Testing Estimasi-Based Auto Complete ===\n\n";

// Step 1: Find a completed transaction with shipping_city
$sql = "SELECT t.id, t.kode_transaksi, t.shipping_city, sr.estimasi
        FROM transaksi t
        LEFT JOIN shipping_rates sr ON t.shipping_city = sr.wilayah
        WHERE t.status = 'completed' 
        LIMIT 1";
$stmt = $conn->query($sql);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "No completed transactions found.\n";
    exit;
}

echo "Found: {$row['kode_transaksi']}\n";
echo "Shipping City: {$row['shipping_city']}\n";
echo "Estimasi: {$row['estimasi']}\n";

// Parse max days
$max_days = 2;
if (!empty($row['estimasi']) && preg_match_all('/\d+/', $row['estimasi'], $matches)) {
    $max_days = (int) max($matches[0]);
}
echo "Parsed Max Days: $max_days\n\n";

// Step 2: Revert to 'verified' with date > max_days ago
$old_date = date('Y-m-d H:i:s', strtotime("-" . ($max_days + 2) . " days"));
echo "Setting verified_at to: $old_date (> max_days threshold)\n";

// Update transaksi
$conn->prepare("UPDATE transaksi SET status = 'verified' WHERE id = ?")->execute([$row['id']]);

// Update or insert payment_proof
$chk = $conn->prepare("SELECT id FROM payment_proof WHERE transaksi_id = ?");
$chk->execute([$row['id']]);
if ($chk->fetch()) {
    $conn->prepare("UPDATE payment_proof SET verified_at = ?, status = 'verified' WHERE transaksi_id = ?")
         ->execute([$old_date, $row['id']]);
} else {
    $conn->prepare("INSERT INTO payment_proof (transaksi_id, image, verified_at, verified_by) VALUES (?, 'test.jpg', ?, 1)")
         ->execute([$row['id'], $old_date]);
}

echo "Status reverted to 'verified'.\n\n";

// Step 3: Run auto_complete
echo "Running auto_complete_orders.php...\n";
include $baseDir . 'auto_complete_orders.php';
echo "\n";

// Step 4: Check new status
$chk2 = $conn->prepare("SELECT status FROM transaksi WHERE id = ?");
$chk2->execute([$row['id']]);
$new_status = $chk2->fetchColumn();

echo "=== RESULT ===\n";
echo "New Status: $new_status\n";

if ($new_status === 'completed') {
    echo "SUCCESS! Auto-complete based on estimasi is working correctly.\n";
} else {
    echo "FAILED. Status should be 'completed' but is '$new_status'.\n";
}
?>
