<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = 'c:/xampp/htdocs/distrozoneweb/';
require_once $baseDir . 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== SIMPLE DEBUG ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Count transactions
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM transaksi");
    echo "Total Transaksi: " . $stmt->fetchColumn() . "\n";
} catch(Exception $e) {
    echo "Error counting transaksi: " . $e->getMessage() . "\n";
}

// 2. Check verified/sent status
try {
    $stmt = $conn->query("SELECT id, kode_transaksi, status, shipping_city FROM transaksi WHERE status IN ('verified', 'sent')");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nVerified/Sent Orders: " . count($rows) . "\n";
    foreach($rows as $r) {
        echo "  - {$r['kode_transaksi']} | {$r['status']} | City: {$r['shipping_city']}\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Check payment_proof
try {
    $stmt = $conn->query("SELECT transaksi_id, verified_at FROM payment_proof WHERE verified_at IS NOT NULL LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nPayment Proofs with verified_at: " . count($rows) . "\n";
    foreach($rows as $r) {
        echo "  - TrxID: {$r['transaksi_id']} | VerifiedAt: {$r['verified_at']}\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 4. All transactions
try {
    $stmt = $conn->query("SELECT id, kode_transaksi, status FROM transaksi ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nLast 5 Transactions:\n";
    foreach($rows as $r) {
        echo "  - ID:{$r['id']} | {$r['kode_transaksi']} | {$r['status']}\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== END ===\n";
?>
