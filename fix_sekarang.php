<?php
/**
 * Script untuk memperbaiki transaksi yang dibatalkan padahal seharusnya verified
 * Akses: http://localhost/distrozoneweb/fix_sekarang.php
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
$conn = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Transaksi Sekarang</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
h1 { color: #10B981; margin-bottom: 10px; }
h2 { color: #0F766E; margin-top: 30px; border-bottom: 2px solid #10B981; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th { background: #10B981; color: white; padding: 12px; text-align: left; }
td { padding: 10px 12px; border-bottom: 1px solid #eee; }
tr:hover { background: #f9f9f9; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin: 5px; display: inline-block; text-decoration: none; }
.btn-success { background: #10B981; color: white; }
.btn-danger { background: #EF4444; color: white; }
.btn-primary { background: #3B82F6; color: white; }
.btn:hover { opacity: 0.9; transform: translateY(-2px); }
.alert { padding: 15px 20px; border-radius: 8px; margin: 15px 0; }
.alert-success { background: #D1FAE5; border-left: 4px solid #10B981; color: #065F46; }
.alert-danger { background: #FEE2E2; border-left: 4px solid #EF4444; color: #991B1B; }
.alert-warning { background: #FEF3C7; border-left: 4px solid #F59E0B; color: #92400E; }
.status-cancelled { color: #EF4444; font-weight: bold; }
.status-verified { color: #10B981; font-weight: bold; }
.status-pending { color: #F59E0B; font-weight: bold; }
.box { background: #f0fdf4; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #86efac; }
</style></head><body><div class='container'>";

echo "<h1>🚨 Perbaikan Darurat Transaksi</h1>";
echo "<p>Script ini akan memperbaiki semua transaksi yang seharusnya verified tapi masih cancelled.</p>";

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['fix_all'])) {
        $kasir_id = $_POST['kasir_id'] ?? 2; // Default kasir web
        
        // Fix ALL cancelled transactions to verified
        $sql = "UPDATE transaksi SET status = 'verified', kasir_id = :kasir_id 
                WHERE status = 'cancelled' AND DATE(tanggal) = CURDATE()";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['kasir_id' => $kasir_id]);
        $fixed = $stmt->rowCount();
        
        echo "<div class='alert alert-success'>✅ <strong>Berhasil!</strong> {$fixed} transaksi hari ini diperbaiki menjadi 'verified'!</div>";
    }
    
    if (isset($_POST['fix_single'])) {
        $trx_id = $_POST['trx_id'];
        $kasir_id = $_POST['kasir_id'] ?? 2;
        
        $sql = "UPDATE transaksi SET status = 'verified', kasir_id = :kasir_id WHERE id = :trx_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['kasir_id' => $kasir_id, 'trx_id' => $trx_id]);
        
        echo "<div class='alert alert-success'>✅ Transaksi ID {$trx_id} berhasil diubah ke 'verified'!</div>";
    }
    
    if (isset($_POST['verify_with_proof'])) {
        // Fix transaksi berdasarkan payment proof yang sudah verified
        $sql = "UPDATE transaksi t
                INNER JOIN payment_proof p ON t.id = p.transaksi_id
                SET t.status = 'verified', t.kasir_id = IFNULL(p.verified_by, :kasir_id)
                WHERE p.status = 'verified' AND t.status != 'verified'";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['kasir_id' => $_POST['kasir_id'] ?? 2]);
        $fixed = $stmt->rowCount();
        
        echo "<div class='alert alert-success'>✅ {$fixed} transaksi diperbaiki berdasarkan bukti pembayaran yang sudah verified!</div>";
    }
}

// Get kasir list
$kasirs = $conn->query("SELECT id, nama, platform FROM users WHERE role = 'kasir'")->fetchAll(PDO::FETCH_ASSOC);

// ============== SHOW CURRENT STATUS ==============
echo "<h2>📊 Status Transaksi Hari Ini</h2>";

$today_stats = [];
$stmt = $conn->query("SELECT status, COUNT(*) as cnt FROM transaksi WHERE DATE(tanggal) = CURDATE() GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $today_stats[$row['status']] = $row['cnt'];
}

echo "<div class='box'>";
echo "<strong>Total transaksi hari ini:</strong><br><br>";
foreach ($today_stats as $status => $count) {
    $class = $status === 'cancelled' ? 'status-cancelled' : ($status === 'verified' ? 'status-verified' : 'status-pending');
    echo "<span class='{$class}'>{$status}: {$count}</span> &nbsp; | &nbsp; ";
}
echo "</div>";

// ============== TRANSAKSI CANCELLED HARI INI ==============
echo "<h2>❌ Transaksi Cancelled Hari Ini (Perlu Diperbaiki)</h2>";

$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t 
          LEFT JOIN users u ON t.customer_id = u.id 
          WHERE t.status = 'cancelled' AND DATE(t.tanggal) = CURDATE() 
          ORDER BY t.id DESC";
$stmt = $conn->query($query);
$cancelled = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cancelled)) {
    echo "<div class='alert alert-success'>✅ Tidak ada transaksi cancelled hari ini!</div>";
} else {
    echo "<div class='alert alert-warning'>⚠️ Ada " . count($cancelled) . " transaksi cancelled yang perlu diperbaiki!</div>";
    
    echo "<table><tr><th>ID</th><th>Kode</th><th>Customer</th><th>Total</th><th>Cancelled By</th><th>Aksi</th></tr>";
    foreach ($cancelled as $t) {
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>{$t['kode_transaksi']}</td>";
        echo "<td>{$t['customer_name']}</td>";
        echo "<td>Rp " . number_format($t['grand_total'], 0, ',', '.') . "</td>";
        echo "<td>{$t['cancelled_by']}</td>";
        echo "<td>
            <form method='POST' style='display:inline;'>
                <input type='hidden' name='trx_id' value='{$t['id']}'>
                <input type='hidden' name='kasir_id' value='2'>
                <button class='btn btn-success' name='fix_single'>✓ Perbaiki ke Verified</button>
            </form>
        </td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Quick fix all button
    echo "<div class='box'>";
    echo "<h3>⚡ Perbaikan Cepat - Ubah Semua ke Verified</h3>";
    echo "<form method='POST'>";
    echo "<p>Pilih kasir yang akan ditetapkan:</p>";
    echo "<select name='kasir_id' style='padding: 10px; margin-right: 10px; min-width: 200px;'>";
    foreach ($kasirs as $k) {
        $selected = $k['platform'] === 'web' ? 'selected' : '';
        echo "<option value='{$k['id']}' {$selected}>{$k['nama']} ({$k['platform']})</option>";
    }
    echo "</select>";
    echo "<button class='btn btn-danger' name='fix_all' onclick=\"return confirm('Yakin ubah SEMUA transaksi cancelled hari ini ke verified?')\">🔧 Perbaiki SEMUA Transaksi Hari Ini</button>";
    echo "</form></div>";
}

// ============== CEK PAYMENT PROOF ==============
echo "<h2>📄 Status Payment Proof</h2>";

$query = "SELECT p.id, p.status as proof_status, p.verified_by, p.verified_at,
                 t.id as trx_id, t.kode_transaksi, t.status as trx_status, t.grand_total
          FROM payment_proof p
          LEFT JOIN transaksi t ON p.transaksi_id = t.id
          WHERE DATE(p.tanggal_upload) >= CURDATE() - INTERVAL 7 DAY
          ORDER BY p.id DESC";
$stmt = $conn->query($query);
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($proofs)) {
    echo "<div class='alert alert-warning'>⚠️ Tidak ada bukti pembayaran dalam 7 hari terakhir.</div>";
} else {
    echo "<table><tr><th>Proof ID</th><th>Kode Transaksi</th><th>Total</th><th>Proof Status</th><th>Trx Status</th><th>Verified At</th></tr>";
    foreach ($proofs as $p) {
        $mismatch = ($p['proof_status'] === 'verified' && $p['trx_status'] !== 'verified');
        $bg = $mismatch ? "style='background: #FEF3C7;'" : "";
        echo "<tr {$bg}>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['kode_transaksi']}</td>";
        echo "<td>Rp " . number_format($p['grand_total'], 0, ',', '.') . "</td>";
        echo "<td class='" . ($p['proof_status'] === 'verified' ? 'status-verified' : 'status-pending') . "'>{$p['proof_status']}</td>";
        echo "<td class='" . ($p['trx_status'] === 'verified' ? 'status-verified' : 'status-cancelled') . "'>{$p['trx_status']}</td>";
        echo "<td>{$p['verified_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for mismatches
    $mismatch_count = 0;
    foreach ($proofs as $p) {
        if ($p['proof_status'] === 'verified' && $p['trx_status'] !== 'verified') {
            $mismatch_count++;
        }
    }
    
    if ($mismatch_count > 0) {
        echo "<div class='alert alert-warning'>⚠️ Ada {$mismatch_count} payment proof yang sudah verified tapi transaksi belum!</div>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='kasir_id' value='2'>";
        echo "<button class='btn btn-primary' name='verify_with_proof'>🔄 Sinkronkan Transaksi dengan Payment Proof</button>";
        echo "</form>";
    }
}

echo "<hr>";
echo "<p><a href='kasir/index.php' class='btn btn-primary'>← Kembali ke Dashboard Kasir</a></p>";
echo "<p><a href='kasir/laporan.php' class='btn btn-success'>📊 Lihat Laporan</a></p>";
echo "</div></body></html>";
?>
