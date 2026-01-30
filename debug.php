<?php
/**
 * DEBUG - Cek status database dan proses verifikasi
 * http://localhost/distrozoneweb/debug.php
 */
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
$conn = $db->getConnection();

echo "<html><head><style>
body{font-family:Arial;padding:20px;background:#f5f5f5;}
.box{background:white;padding:20px;margin:15px 0;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#10B981;}
h2{color:#0F766E;border-bottom:2px solid #10B981;padding-bottom:10px;}
table{width:100%;border-collapse:collapse;}
th{background:#10B981;color:white;padding:10px;text-align:left;}
td{padding:8px 10px;border-bottom:1px solid #eee;}
.verified{color:#10B981;font-weight:bold;}
.cancelled{color:#EF4444;font-weight:bold;}
.pending{color:#F59E0B;font-weight:bold;}
.btn{padding:12px 24px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;margin:5px;}
.btn-fix{background:#10B981;color:white;}
.btn-fix:hover{background:#059669;}
</style></head><body>";

echo "<h1>🔍 DEBUG: Status Transaksi</h1>";

// 1. CEK SEMUA TRANSAKSI HARI INI
echo "<div class='box'>";
echo "<h2>1. Semua Transaksi Hari Ini</h2>";
$stmt = $conn->query("SELECT id, kode_transaksi, status, kasir_id, cancelled_by, cancel_reason, tanggal, grand_total FROM transaksi WHERE DATE(tanggal) = CURDATE() ORDER BY id DESC");
$trans = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>ID</th><th>Kode</th><th>Status</th><th>Kasir ID</th><th>Cancelled By</th><th>Cancel Reason</th><th>Total</th></tr>";
foreach ($trans as $t) {
    $class = $t['status'] === 'verified' ? 'verified' : ($t['status'] === 'cancelled' ? 'cancelled' : 'pending');
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['kode_transaksi']}</td>";
    echo "<td class='{$class}'>{$t['status']}</td>";
    echo "<td>{$t['kasir_id']}</td>";
    echo "<td>{$t['cancelled_by']}</td>";
    echo "<td>{$t['cancel_reason']}</td>";
    echo "<td>Rp " . number_format($t['grand_total'],0,',','.') . "</td>";
    echo "</tr>";
}
echo "</table></div>";

// 2. CEK PAYMENT PROOF
echo "<div class='box'>";
echo "<h2>2. Payment Proof Status</h2>";
$stmt = $conn->query("SELECT p.id, p.transaksi_id, p.status as proof_status, p.verified_by, p.verified_at, t.status as trx_status, t.kode_transaksi 
                      FROM payment_proof p 
                      LEFT JOIN transaksi t ON p.transaksi_id = t.id 
                      ORDER BY p.id DESC LIMIT 20");
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Proof ID</th><th>Trx ID</th><th>Kode Trx</th><th>Proof Status</th><th>Trx Status</th><th>Verified By</th><th>Verified At</th></tr>";
foreach ($proofs as $p) {
    $mismatch = ($p['proof_status'] === 'verified' && $p['trx_status'] !== 'verified');
    $style = $mismatch ? "style='background:#FEF3C7;'" : "";
    echo "<tr {$style}>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['transaksi_id']}</td>";
    echo "<td>{$p['kode_transaksi']}</td>";
    echo "<td class='" . ($p['proof_status'] === 'verified' ? 'verified' : 'pending') . "'>{$p['proof_status']}</td>";
    echo "<td class='" . ($p['trx_status'] === 'verified' ? 'verified' : 'cancelled') . "'>{$p['trx_status']}</td>";
    echo "<td>{$p['verified_by']}</td>";
    echo "<td>{$p['verified_at']}</td>";
    echo "</tr>";
}
echo "</table></div>";

// 3. IMMEDIATE FIX
if (isset($_POST['fix_now'])) {
    echo "<div class='box' style='background:#D1FAE5;border-left:4px solid #10B981;'>";
    echo "<h2>⚡ Menjalankan Perbaikan...</h2>";
    
    // Fix transaksi berdasarkan payment_proof yang sudah verified
    $sql = "UPDATE transaksi t
            INNER JOIN payment_proof p ON t.id = p.transaksi_id
            SET t.status = 'verified', t.kasir_id = IFNULL(p.verified_by, 2), 
                t.cancelled_by = NULL, t.cancel_reason = NULL
            WHERE p.status = 'verified'";
    $stmt = $conn->exec($sql);
    echo "<p>✅ Transaksi sinkronisasi dengan payment proof: <strong>{$stmt}</strong> rows affected</p>";
    
    // Fix semua cancelled hari ini
    $sql = "UPDATE transaksi SET status = 'verified', kasir_id = 2, 
            cancelled_by = NULL, cancel_reason = NULL 
            WHERE status = 'cancelled' AND DATE(tanggal) = CURDATE()";
    $stmt = $conn->exec($sql);
    echo "<p>✅ Transaksi cancelled hari ini diperbaiki: <strong>{$stmt}</strong> rows affected</p>";
    
    echo "<p><strong>✅ SELESAI! Refresh halaman kasir/index.php sekarang!</strong></p>";
    echo "</div>";
}

echo "<div class='box'>";
echo "<h2>⚡ Perbaikan Langsung</h2>";
echo "<form method='POST'>";
echo "<button type='submit' name='fix_now' class='btn btn-fix'>🔧 PERBAIKI SEMUA SEKARANG</button>";
echo "</form>";
echo "<p style='margin-top:10px;color:#666;'>Klik tombol di atas untuk langsung memperbaiki semua transaksi cancelled menjadi verified.</p>";
echo "</div>";

// 4. CEK SESSION
echo "<div class='box'>";
echo "<h2>3. Session Info</h2>";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Name: " . ($_SESSION['nama'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "</pre></div>";

echo "<p><a href='kasir/index.php' style='color:#10B981;font-weight:bold;'>← Kembali ke Dashboard Kasir</a></p>";
echo "</body></html>";
?>
