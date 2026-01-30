<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Debug Data Transaksi:\n";
echo str_pad("ID", 5) . str_pad("Kode", 15) . str_pad("Kasir ID", 10) . str_pad("Status", 12) . str_pad("Platform", 10) . str_pad("Payment", 15) . "\n";
echo str_repeat("-", 70) . "\n";

$sql = "SELECT id, kode_transaksi, kasir_id, status, platform, payment_method FROM transaksi ORDER BY id DESC LIMIT 10";
$stmt = $conn->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo str_pad($row['id'], 5) . 
         str_pad($row['kode_transaksi'], 15) . 
         str_pad($row['kasir_id'] ?? 'NULL', 10) . 
         str_pad($row['status'], 12) . 
         str_pad($row['platform'] ?? 'NULL', 10) .
         str_pad($row['payment_method'] ?? 'NULL', 15) . "\n";
}

echo "\nDebug Data Payment Proof:\n";
$sql = "SELECT id, transaksi_id, status, verified_by FROM payment_proof ORDER BY id DESC LIMIT 5";
$stmt = $conn->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
