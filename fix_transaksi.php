<?php
/**
 * Script untuk mengecek dan memperbaiki masalah transaksi verified yang tidak masuk laporan
 * Akses: http://localhost/distrozoneweb/fix_transaksi.php
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Transaksi</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; }
h1 { color: #10B981; }
h2 { color: #0F766E; margin-top: 30px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
th { background: #10B981; color: white; padding: 12px; text-align: left; }
td { padding: 10px 12px; border-bottom: 1px solid #eee; }
tr:hover { background: #f9f9f9; }
.alert { padding: 15px; border-radius: 8px; margin: 15px 0; }
.alert-warning { background: #FEF3C7; border-left: 4px solid #F59E0B; color: #92400E; }
.alert-success { background: #D1FAE5; border-left: 4px solid #10B981; color: #065F46; }
.alert-danger { background: #FEE2E2; border-left: 4px solid #EF4444; color: #991B1B; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin: 5px; text-decoration: none; display: inline-block; }
.btn-primary { background: #10B981; color: white; }
.btn-danger { background: #EF4444; color: white; }
.btn:hover { opacity: 0.9; }
.status-verified { color: #10B981; font-weight: bold; }
.status-pending { color: #F59E0B; font-weight: bold; }
.status-cancelled { color: #EF4444; font-weight: bold; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Diagnostik & Perbaikan Transaksi</h1>";
echo "<p>Tanggal: " . date('d M Y H:i:s') . "</p>";

// ============== 1. LIHAT SEMUA TRANSAKSI ==============
echo "<h2>1. Semua Transaksi di Database</h2>";
$query = "SELECT t.id, t.kode_transaksi, t.tanggal, t.status, t.platform, t.kasir_id, 
                 t.grand_total, t.customer_id, u.nama as kasir_nama, c.nama as customer_nama
          FROM transaksi t
          LEFT JOIN users u ON t.kasir_id = u.id
          LEFT JOIN users c ON t.customer_id = c.id
          ORDER BY t.id DESC LIMIT 30";
$stmt = $conn->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>Kode</th><th>Tanggal</th><th>Status</th><th>Platform</th><th>Kasir ID</th><th>Kasir</th><th>Customer</th><th>Total</th></tr>";
$has_verified = false;
foreach ($transactions as $t) {
    $status_class = '';
    if ($t['status'] === 'verified' || $t['status'] === 'completed' || $t['status'] === 'selesai') {
        $status_class = 'status-verified';
        $has_verified = true;
    } elseif ($t['status'] === 'pending') {
        $status_class = 'status-pending';
    } else {
        $status_class = 'status-cancelled';
    }
    
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['kode_transaksi']}</td>";
    echo "<td>{$t['tanggal']}</td>";
    echo "<td class='{$status_class}'>{$t['status']}</td>";
    echo "<td>{$t['platform']}</td>";
    echo "<td>{$t['kasir_id']}</td>";
    echo "<td>{$t['kasir_nama']}</td>";
    echo "<td>{$t['customer_nama']}</td>";
    echo "<td>Rp " . number_format($t['grand_total'], 0, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

if (!$has_verified) {
    echo "<div class='alert alert-danger'>⚠️ <strong>Masalah Ditemukan:</strong> Tidak ada transaksi dengan status 'verified', 'completed', atau 'selesai'. Semua transaksi sudah dibatalkan!</div>";
}

// ============== 2. CEK PAYMENT PROOF ==============
echo "<h2>2. Status Bukti Pembayaran (Payment Proof)</h2>";
$query = "SELECT p.*, t.kode_transaksi, t.status as trx_status, t.kasir_id
          FROM payment_proof p
          LEFT JOIN transaksi t ON p.transaksi_id = t.id
          ORDER BY p.id DESC LIMIT 20";
$stmt = $conn->query($query);
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($proofs)) {
    echo "<div class='alert alert-warning'>⚠️ Tidak ada bukti pembayaran yang diupload. Customer perlu upload bukti bayar terlebih dahulu.</div>";
} else {
    echo "<table><tr><th>Proof ID</th><th>Transaksi ID</th><th>Kode</th><th>Proof Status</th><th>Trx Status</th><th>Kasir ID</th><th>Verified By</th><th>Tanggal Upload</th></tr>";
    foreach ($proofs as $p) {
        $mismatch = ($p['status'] === 'verified' && $p['trx_status'] !== 'verified' && $p['trx_status'] !== 'completed');
        echo "<tr" . ($mismatch ? " style='background: #FEF3C7;'" : "") . ">";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['transaksi_id']}</td>";
        echo "<td>{$p['kode_transaksi']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td class='" . ($p['trx_status'] === 'verified' ? 'status-verified' : 'status-cancelled') . "'>{$p['trx_status']}</td>";
        echo "<td>{$p['kasir_id']}</td>";
        echo "<td>{$p['verified_by']}</td>";
        echo "<td>{$p['tanggal_upload']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ============== 3. CEK USERS/KASIR ==============
echo "<h2>3. Daftar Kasir (untuk referensi)</h2>";
$query = "SELECT id, nama, email, role, platform, shift FROM users WHERE role = 'kasir' ORDER BY id";
$stmt = $conn->query($query);
$kasirs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>Nama</th><th>Email</th><th>Platform</th><th>Shift</th></tr>";
foreach ($kasirs as $k) {
    echo "<tr><td>{$k['id']}</td><td>{$k['nama']}</td><td>{$k['email']}</td><td>{$k['platform']}</td><td>{$k['shift']}</td></tr>";
}
echo "</table>";

// ============== 4. RINGKASAN MASALAH ==============
echo "<h2>4. Ringkasan Masalah</h2>";

// Hitung statistik
$stats = [];

// Transaksi cancelled
$stmt = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE status = 'cancelled'");
$stats['cancelled'] = $stmt->fetch()['cnt'];

// Transaksi pending
$stmt = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE status = 'pending'");
$stats['pending'] = $stmt->fetch()['cnt'];

// Transaksi verified/completed
$stmt = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE status IN ('verified', 'completed', 'selesai')");
$stats['verified'] = $stmt->fetch()['cnt'];

// Payment proof verified tapi transaksi masih pending/cancelled
$stmt = $conn->query("SELECT COUNT(*) as cnt FROM payment_proof p 
                      LEFT JOIN transaksi t ON p.transaksi_id = t.id 
                      WHERE p.status = 'verified' AND t.status NOT IN ('verified', 'completed', 'selesai')");
$stats['proof_mismatch'] = $stmt->fetch()['cnt'];

echo "<div class='alert alert-warning'>";
echo "<ul>";
echo "<li>Transaksi Dibatalkan: <strong>{$stats['cancelled']}</strong></li>";
echo "<li>Transaksi Pending: <strong>{$stats['pending']}</strong></li>";
echo "<li>Transaksi Sukses (verified/completed): <strong>{$stats['verified']}</strong></li>";
echo "<li>Bukti bayar verified tapi transaksi tidak verified: <strong>{$stats['proof_mismatch']}</strong></li>";
echo "</ul>";
echo "</div>";

// ============== 5. OPSI PERBAIKAN ==============
echo "<h2>5. Opsi Perbaikan</h2>";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['fix_proof_mismatch'])) {
        // Fix transaksi yang payment proof-nya sudah verified tapi transaksi belum
        $sql = "UPDATE transaksi t
                INNER JOIN payment_proof p ON t.id = p.transaksi_id
                SET t.status = 'verified', t.kasir_id = IFNULL(p.verified_by, t.kasir_id)
                WHERE p.status = 'verified' AND t.status NOT IN ('verified', 'completed', 'selesai')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $fixed = $stmt->rowCount();
        echo "<div class='alert alert-success'>✅ Berhasil memperbaiki {$fixed} transaksi yang payment proof-nya sudah verified!</div>";
    }
    
    if (isset($_POST['verify_pending'])) {
        // Verify semua transaksi pending untuk kasir tertentu
        $kasir_id = $_POST['kasir_id'] ?? 0;
        if ($kasir_id > 0) {
            $sql = "UPDATE transaksi SET status = 'verified', kasir_id = :kasir_id 
                    WHERE status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['kasir_id' => $kasir_id]);
            $fixed = $stmt->rowCount();
            echo "<div class='alert alert-success'>✅ Berhasil memverifikasi {$fixed} transaksi pending!</div>";
        }
    }
    
    if (isset($_POST['create_test_transaksi'])) {
        // Buat transaksi test langsung dengan status verified
        $kasir_id = $_POST['kasir_id'] ?? 0;
        if ($kasir_id > 0) {
            $kode = 'TEST-' . date('YmdHis');
            $sql = "INSERT INTO transaksi (kode_transaksi, customer_id, kasir_id, tanggal, waktu, total, 
                    diskon, grand_total, payment_method, status, platform, created_at) 
                    VALUES (:kode, NULL, :kasir_id, CURDATE(), CURTIME(), 100000, 0, 100000, 
                    'Transfer BCA', 'verified', 'web', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['kode' => $kode, 'kasir_id' => $kasir_id]);
            $trx_id = $conn->lastInsertId();
            
            // Juga insert detail transaksi (dummy)
            // Ambil kaos varian pertama yang ada
            $kaos = $conn->query("SELECT id, harga_jual, harga_pokok FROM kaos_varian LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($kaos) {
                $laba = $kaos['harga_jual'] - $kaos['harga_pokok'];
                $sql = "INSERT INTO detail_transaksi (transaksi_id, kaos_id, qty, harga_modal, harga_jual, subtotal, laba)
                        VALUES (:trx_id, :kaos_id, 1, :modal, :jual, :jual, :laba)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'trx_id' => $trx_id,
                    'kaos_id' => $kaos['id'],
                    'modal' => $kaos['harga_pokok'],
                    'jual' => $kaos['harga_jual'],
                    'laba' => $laba
                ]);
            }
            
            echo "<div class='alert alert-success'>✅ Berhasil membuat transaksi test dengan kode: {$kode}</div>";
        }
    }
    
    if (isset($_POST['uncancel_transaksi'])) {
        // Kembalikan transaksi cancelled menjadi verified
        $trx_id = $_POST['trx_id'] ?? 0;
        $kasir_id = $_POST['kasir_id'] ?? 0;
        if ($trx_id > 0 && $kasir_id > 0) {
            $sql = "UPDATE transaksi SET status = 'verified', kasir_id = :kasir_id WHERE id = :trx_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['kasir_id' => $kasir_id, 'trx_id' => $trx_id]);
            echo "<div class='alert alert-success'>✅ Transaksi ID {$trx_id} berhasil dipulihkan ke status 'verified'!</div>";
        }
    }
}

// Forms
echo "<div style='background: white; padding: 20px; border-radius: 10px; margin: 15px 0;'>";
echo "<h3>A. Perbaiki Payment Proof yang Sudah Verified</h3>";
echo "<p>Jika bukti pembayaran sudah diverifikasi tapi transaksi belum ter-update:</p>";
echo "<form method='POST'><button class='btn btn-primary' name='fix_proof_mismatch'>🔧 Sinkronkan Status Transaksi</button></form>";
echo "</div>";

echo "<div style='background: white; padding: 20px; border-radius: 10px; margin: 15px 0;'>";
echo "<h3>B. Verifikasi Transaksi Pending</h3>";
echo "<p>Verifikasi semua transaksi pending dan assign ke kasir:</p>";
echo "<form method='POST'>";
echo "<select name='kasir_id' style='padding: 10px; margin-right: 10px;'>";
foreach ($kasirs as $k) {
    echo "<option value='{$k['id']}'>{$k['nama']} ({$k['platform']})</option>";
}
echo "</select>";
echo "<button class='btn btn-primary' name='verify_pending'>✅ Verifikasi Semua Pending</button>";
echo "</form></div>";

echo "<div style='background: white; padding: 20px; border-radius: 10px; margin: 15px 0;'>";
echo "<h3>C. Buat Transaksi Test (untuk testing laporan)</h3>";
echo "<p>Buat transaksi dummy dengan status verified untuk test laporan:</p>";
echo "<form method='POST'>";
echo "<select name='kasir_id' style='padding: 10px; margin-right: 10px;'>";
foreach ($kasirs as $k) {
    echo "<option value='{$k['id']}'>{$k['nama']} ({$k['platform']})</option>";
}
echo "</select>";
echo "<button class='btn btn-primary' name='create_test_transaksi'>➕ Buat Transaksi Test</button>";
echo "</form></div>";

echo "<div style='background: white; padding: 20px; border-radius: 10px; margin: 15px 0;'>";
echo "<h3>D. Pulihkan Transaksi yang Dibatalkan</h3>";
echo "<p>Kembalikan transaksi yang sudah dibatalkan menjadi verified:</p>";
echo "<form method='POST'>";
echo "<input type='number' name='trx_id' placeholder='ID Transaksi' style='padding: 10px; margin-right: 10px; width: 120px;'>";
echo "<select name='kasir_id' style='padding: 10px; margin-right: 10px;'>";
foreach ($kasirs as $k) {
    echo "<option value='{$k['id']}'>{$k['nama']} ({$k['platform']})</option>";
}
echo "</select>";
echo "<button class='btn btn-danger' name='uncancel_transaksi'>♻️ Pulihkan Transaksi</button>";
echo "</form></div>";

echo "<hr><p><a href='kasir/index.php' class='btn btn-primary'>← Kembali ke Dashboard</a></p>";
echo "</div></body></html>";
?>
