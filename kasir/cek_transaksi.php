<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Diagnostik Transaksi DistroZone</h2>";
echo "<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #10B981; color: white; } tr:nth-child(even) { background-color: #f2f2f2; } .problem { background-color: #fef3c7 !important; }</style>";

// 1. Show all recent transactions
echo "<h3>1. Semua Transaksi (20 terakhir)</h3>";
$query = "SELECT t.id, t.kode_transaksi, t.tanggal, t.created_at, t.status, t.platform, t.kasir_id, 
                 t.grand_total, u.nama as kasir_nama
          FROM transaksi t
          LEFT JOIN users u ON t.kasir_id = u.id
          ORDER BY t.id DESC LIMIT 20";
$stmt = $conn->query($query);
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>ID</th><th>Kode</th><th>Tanggal</th><th>Created At</th><th>Status</th><th>Platform</th><th>Kasir ID</th><th>Kasir</th><th>Total</th></tr>";
foreach ($all as $t) {
    $problem_class = '';
    if ($t['status'] === 'verified' && empty($t['kasir_id'])) {
        $problem_class = 'problem';
    }
    echo "<tr class='{$problem_class}'>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['kode_transaksi']}</td>";
    echo "<td>{$t['tanggal']}</td>";
    echo "<td>{$t['created_at']}</td>";
    echo "<td>{$t['status']}</td>";
    echo "<td>{$t['platform']}</td>";
    echo "<td>{$t['kasir_id']}</td>";
    echo "<td>{$t['kasir_nama']}</td>";
    echo "<td>" . number_format($t['grand_total']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check verified transactions
echo "<h3>2. Transaksi dengan status 'verified' atau 'completed'</h3>";
$query = "SELECT t.id, t.kode_transaksi, t.status, t.platform, t.kasir_id, t.tanggal, t.created_at
          FROM transaksi t
          WHERE t.status IN ('verified', 'completed')
          ORDER BY t.id DESC";
$stmt = $conn->query($query);
$verified = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Jumlah: <strong>" . count($verified) . "</strong></p>";
echo "<table><tr><th>ID</th><th>Kode</th><th>Status</th><th>Platform</th><th>Kasir ID</th><th>Tanggal</th><th>Created At</th></tr>";
foreach ($verified as $t) {
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['kode_transaksi']}</td>";
    echo "<td>{$t['status']}</td>";
    echo "<td>{$t['platform']}</td>";
    echo "<td>{$t['kasir_id']}</td>";
    echo "<td>{$t['tanggal']}</td>";
    echo "<td>{$t['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check payment_proof table
echo "<h3>3. Payment Proof yang sudah verified</h3>";
$query = "SELECT p.id, p.transaksi_id, p.status, p.verified_by, p.verified_at, t.kode_transaksi, t.status as trx_status
          FROM payment_proof p
          LEFT JOIN transaksi t ON p.transaksi_id = t.id
          WHERE p.status = 'verified'
          ORDER BY p.id DESC LIMIT 20";
$stmt = $conn->query($query);
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>Proof ID</th><th>Transaksi ID</th><th>Kode Transaksi</th><th>Proof Status</th><th>Trx Status</th><th>Verified By</th><th>Verified At</th></tr>";
foreach ($proofs as $p) {
    $class = ($p['trx_status'] !== 'verified' && $p['trx_status'] !== 'completed') ? 'problem' : '';
    echo "<tr class='{$class}'>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['transaksi_id']}</td>";
    echo "<td>{$p['kode_transaksi']}</td>";
    echo "<td>{$p['status']}</td>";
    echo "<td>{$p['trx_status']}</td>";
    echo "<td>{$p['verified_by']}</td>";
    echo "<td>{$p['verified_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Date range check for reports
$start = date('Y-m-01');
$end = date('Y-m-d');
echo "<h3>4. Query Laporan Admin (periode: {$start} s/d {$end})</h3>";
$query = "SELECT COUNT(*) as cnt, SUM(grand_total) as total 
          FROM transaksi t 
          WHERE DATE(t.created_at) BETWEEN :start AND :end 
          AND t.status IN ('verified', 'completed', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute(['start' => $start, 'end' => $end]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Jumlah Transaksi: <strong>{$result['cnt']}</strong>, Total: <strong>Rp " . number_format($result['total']) . "</strong></p>";

echo "<h3>5. Query Laporan Kasir (menggunakan kolom tanggal)</h3>";
$query = "SELECT COUNT(*) as cnt, SUM(grand_total) as total 
          FROM transaksi t 
          WHERE DATE(t.tanggal) BETWEEN :start AND :end 
          AND t.status IN ('verified', 'completed')";
$stmt = $conn->prepare($query);
$stmt->execute(['start' => $start, 'end' => $end]);
$result2 = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Jumlah Transaksi: <strong>{$result2['cnt']}</strong>, Total: <strong>Rp " . number_format($result2['total']) . "</strong></p>";

// 5. Problems summary
echo "<h3>6. Ringkasan Masalah</h3>";
$query = "SELECT COUNT(*) as cnt FROM transaksi WHERE status = 'verified' AND (kasir_id IS NULL OR kasir_id = 0)";
$stmt = $conn->query($query);
$no_kasir = $stmt->fetch()['cnt'];

$query = "SELECT COUNT(*) as cnt FROM payment_proof p 
          LEFT JOIN transaksi t ON p.transaksi_id = t.id 
          WHERE p.status = 'verified' AND t.status NOT IN ('verified', 'completed')";
$stmt = $conn->query($query);
$mismatched = $stmt->fetch()['cnt'];

echo "<ul>";
echo "<li>Transaksi verified tanpa kasir_id: <strong>" . ($no_kasir > 0 ? "⚠️ {$no_kasir}" : "✅ 0") . "</strong></li>";
echo "<li>Payment verified tapi transaksi belum verified: <strong>" . ($mismatched > 0 ? "⚠️ {$mismatched}" : "✅ 0") . "</strong></li>";
echo "</ul>";

if ($no_kasir > 0 || $mismatched > 0) {
    echo "<h3>7. Fix Otomatis</h3>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix' value='1' style='background: #10B981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔧 Perbaiki Semua Masalah</button>";
    echo "</form>";
    
    if (isset($_POST['fix'])) {
        echo "<h4>Hasil Perbaikan:</h4>";
        
        // Fix 1: Update mismatched statuses
        if ($mismatched > 0) {
            $sql = "UPDATE transaksi t
                    INNER JOIN payment_proof p ON t.id = p.transaksi_id
                    SET t.status = 'verified', t.kasir_id = IFNULL(p.verified_by, t.kasir_id)
                    WHERE p.status = 'verified' AND t.status NOT IN ('verified', 'completed')";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            echo "<p>✅ Diperbaiki {$stmt->rowCount()} transaksi yang status tidak sinkron</p>";
        }
        
        // Fix 2: Assign a default kasir for verified transactions without kasir_id
        if ($no_kasir > 0) {
            // Get first web kasir
            $kasir_query = "SELECT id FROM users WHERE role = 'kasir' AND platform = 'web' LIMIT 1";
            $kasir_stmt = $conn->query($kasir_query);
            $default_kasir = $kasir_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($default_kasir) {
                $sql = "UPDATE transaksi SET kasir_id = :kasir_id 
                        WHERE status = 'verified' AND (kasir_id IS NULL OR kasir_id = 0)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['kasir_id' => $default_kasir['id']]);
                echo "<p>✅ Diperbaiki {$stmt->rowCount()} transaksi yang tidak punya kasir_id</p>";
            } else {
                echo "<p>⚠️ Tidak ada kasir web ditemukan untuk assign default</p>";
            }
        }
        
        echo "<p><a href='cek_transaksi.php'>🔄 Refresh halaman ini</a></p>";
    }
}
?>
