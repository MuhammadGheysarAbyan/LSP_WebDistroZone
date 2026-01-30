<?php
/**
 * PERBAIKAN LANGSUNG - Jalankan ini untuk memperbaiki semua transaksi
 * http://localhost/distrozoneweb/perbaiki.php
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1 style='font-family:Arial; color: #10B981;'>🔧 Memperbaiki Transaksi...</h1>";

// 1. Cari semua payment_proof yang sudah verified
$stmt = $conn->query("SELECT p.transaksi_id, p.verified_by, t.kode_transaksi, t.status as trx_status 
                      FROM payment_proof p 
                      LEFT JOIN transaksi t ON p.transaksi_id = t.id 
                      WHERE p.status = 'verified'");
$verified_proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2 style='font-family:Arial;'>Payment Proof yang Sudah Verified:</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; font-family:monospace;'>";
print_r($verified_proofs);
echo "</pre>";

$fixed = 0;

foreach ($verified_proofs as $proof) {
    if ($proof['trx_status'] !== 'verified') {
        // Fix this transaction
        $kasir_id = $proof['verified_by'] ?: 2; // Default kasir ID 2 if not set
        
        $sql = "UPDATE transaksi SET status = 'verified', kasir_id = :kasir_id, 
                cancelled_by = NULL, cancel_reason = NULL 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['kasir_id' => $kasir_id, 'id' => $proof['transaksi_id']]);
        
        echo "<p style='font-family:Arial; color: green;'>✅ Transaksi {$proof['kode_transaksi']} diperbaiki! (was: {$proof['trx_status']} → now: verified)</p>";
        $fixed++;
    }
}

// 2. Juga perbaiki SEMUA transaksi cancelled hari ini
$stmt = $conn->query("SELECT id, kode_transaksi FROM transaksi WHERE status = 'cancelled' AND DATE(tanggal) = CURDATE()");
$cancelled_today = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2 style='font-family:Arial;'>Transaksi Cancelled Hari Ini:</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; font-family:monospace;'>";
print_r($cancelled_today);
echo "</pre>";

foreach ($cancelled_today as $trx) {
    $sql = "UPDATE transaksi SET status = 'verified', kasir_id = 2, 
            cancelled_by = NULL, cancel_reason = NULL 
            WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $trx['id']]);
    
    echo "<p style='font-family:Arial; color: green;'>✅ Transaksi {$trx['kode_transaksi']} diperbaiki ke VERIFIED!</p>";
    $fixed++;
}

echo "<hr>";
echo "<h2 style='font-family:Arial; color: #10B981;'>Total {$fixed} transaksi diperbaiki!</h2>";

// 3. Tampilkan status terbaru
echo "<h2 style='font-family:Arial;'>Status Transaksi Sekarang:</h2>";
$stmt = $conn->query("SELECT id, kode_transaksi, status, kasir_id, tanggal, grand_total FROM transaksi ORDER BY id DESC LIMIT 10");
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table style='font-family:Arial; border-collapse:collapse; width:100%;'>";
echo "<tr style='background:#10B981; color:white;'><th style='padding:10px;'>ID</th><th style='padding:10px;'>Kode</th><th style='padding:10px;'>Status</th><th style='padding:10px;'>Kasir ID</th><th style='padding:10px;'>Tanggal</th><th style='padding:10px;'>Total</th></tr>";
foreach ($transaksi as $t) {
    $color = $t['status'] === 'verified' ? '#10B981' : ($t['status'] === 'cancelled' ? '#EF4444' : '#F59E0B');
    echo "<tr style='border-bottom:1px solid #eee;'>";
    echo "<td style='padding:10px;'>{$t['id']}</td>";
    echo "<td style='padding:10px;'>{$t['kode_transaksi']}</td>";
    echo "<td style='padding:10px; color:{$color}; font-weight:bold;'>{$t['status']}</td>";
    echo "<td style='padding:10px;'>{$t['kasir_id']}</td>";
    echo "<td style='padding:10px;'>{$t['tanggal']}</td>";
    echo "<td style='padding:10px;'>Rp " . number_format($t['grand_total'], 0, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";
echo "<a href='kasir/index.php' style='background:#10B981; color:white; padding:15px 30px; text-decoration:none; border-radius:10px; font-family:Arial; font-weight:bold;'>➡️ Kembali ke Dashboard Kasir</a>";
echo " &nbsp; ";
echo "<a href='kasir/laporan.php' style='background:#3B82F6; color:white; padding:15px 30px; text-decoration:none; border-radius:10px; font-family:Arial; font-weight:bold;'>📊 Lihat Laporan</a>";
?>
