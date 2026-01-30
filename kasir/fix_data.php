<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Allow any logged in user (kasir/admin) to run this for now, or just open it
$db = new Database();
$conn = $db->getConnection();

echo "<h3>Memperbaiki Data Transaksi...</h3>";

// 1. Update kasir_id from payment_proof for Verified transactions
$sql1 = "UPDATE transaksi t
        JOIN payment_proof p ON t.id = p.transaksi_id
        SET t.kasir_id = p.verified_by
        WHERE t.platform = 'web' 
        AND t.status = 'verified' 
        AND (t.kasir_id IS NULL OR t.kasir_id = 0)
        AND p.verified_by IS NOT NULL";

$stmt1 = $conn->prepare($sql1);
$stmt1->execute();
$count1 = $stmt1->rowCount();

echo "Fix 1 (Verified): Berhasil memperbarui <b>$count1</b> transaksi yang sudah diverifikasi tapi belum ada ID Kasir.<br>";

// 2. Update kasir_id for Completed transactions (if any) that might have been missed
// Assuming completed transactions should also have a kasir_id. 
// If they were verified via the exact same flow, they might be in payment_proof too.
$sql2 = "UPDATE transaksi t
        JOIN payment_proof p ON t.id = p.transaksi_id
        SET t.kasir_id = p.verified_by
        WHERE t.platform = 'web' 
        AND t.status = 'completed'
        AND (t.kasir_id IS NULL OR t.kasir_id = 0)
        AND p.verified_by IS NOT NULL";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute();
$count2 = $stmt2->rowCount();

echo "Fix 2 (Completed): Berhasil memperbarui <b>$count2</b> transaksi completed.<br>";

echo "<hr>";
echo "<h4>Selesai! Silakan kembali ke Dashboard/Laporan dan refresh halaman.</h4>";
?>
