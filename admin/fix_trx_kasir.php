<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Memperbaiki data transaksi web...\n";

// Query to update transaction kasir_id from payment_proof verified_by
$sql = "UPDATE transaksi t
        JOIN payment_proof p ON t.id = p.transaksi_id
        SET t.kasir_id = p.verified_by
        WHERE t.platform = 'web' 
        AND t.status = 'verified' 
        AND t.kasir_id IS NULL 
        AND p.verified_by IS NOT NULL";

$stmt = $conn->prepare($sql);
$stmt->execute();
$count = $stmt->rowCount();

echo "Berhasil memperbarui $count transaksi yang kehilangan ID Kasir.\n";
?>
