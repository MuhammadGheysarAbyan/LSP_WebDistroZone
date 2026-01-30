<?php
/**
 * DEEP DIAGNOSTIC TOOL
 * http://localhost/distrozoneweb/cek_detail.php
 */
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
$conn = $db->getConnection();

echo "<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ccc;padding:8px;} th{background:#eee;} .red{color:red;font-weight:bold;} .green{color:green;font-weight:bold;}</style>";

echo "<h1>🔍 DIAGNOSIS DATA TRANSAKSI TERAKHIR</h1>";

// 1. Ambil 5 Transaksi Terakhir (Raw)
echo "<h2>1. Tabel TRANSAKSI (5 Terakhir)</h2>";
$stmt = $conn->query("SELECT * FROM transaksi ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Kode</th><th>Status</th><th>Kasir ID</th><th>Cancelled By</th><th>Cancel Reason</th><th>Tanggal</th></tr>";
foreach ($rows as $r) {
    $status_style = $r['status'] == 'verified' ? 'green' : ($r['status'] == 'cancelled' ? 'red' : '');
    echo "<tr>";
    echo "<td>{$r['id']}</td>";
    echo "<td>{$r['kode_transaksi']}</td>";
    echo "<td class='$status_style'>{$r['status']}</td>";
    echo "<td>{$r['kasir_id']}</td>";
    echo "<td>{$r['cancelled_by']}</td>";
    echo "<td>{$r['cancel_reason']}</td>";
    echo "<td>{$r['tanggal']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Ambil 5 Payment Proof Terakhir (Raw)
echo "<h2>2. Tabel PAYMENT_PROOF (5 Terakhir)</h2>";
$stmt = $conn->query("SELECT * FROM payment_proof ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Trx ID</th><th>File Bukti</th><th>Status</th><th>Verified By</th><th>Verified At</th></tr>";
foreach ($rows as $r) {
    echo "<tr>";
    echo "<td>{$r['id']}</td>";
    echo "<td>{$r['transaksi_id']}</td>";
    echo "<td>{$r['file_bukti']}</td>";
    echo "<td>{$r['status']}</td>";
    echo "<td>{$r['verified_by']}</td>";
    echo "<td>{$r['verified_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Cek Relasi Langsung
echo "<h2>3. Cek KONSISTENSI (Join)</h2>";
$stmt = $conn->query("SELECT t.id, t.kode_transaksi, t.status as trx_status, p.status as proof_status 
                      FROM transaksi t 
                      LEFT JOIN payment_proof p ON t.id = p.transaksi_id 
                      WHERE p.id IS NOT NULL 
                      ORDER BY t.id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Trx ID</th><th>Kode</th><th>Trx Status</th><th>Proof Status</th><th>Analisa</th></tr>";
foreach ($rows as $r) {
    $analysis = "OK";
    $style = "green";
    
    if ($r['proof_status'] == 'verified' && $r['trx_status'] == 'cancelled') {
        $analysis = "CRITICAL MISMATCH! Proof verified tapi Trx cancelled.";
        $style = "red";
    } elseif ($r['proof_status'] == 'verified' && $r['trx_status'] != 'verified' && $r['trx_status'] != 'completed') {
        $analysis = "MISMATCH! Proof verified tapi Trx {$r['trx_status']}";
        $style = "red";
    }
    
    echo "<tr>";
    echo "<td>{$r['id']}</td>";
    echo "<td>{$r['kode_transaksi']}</td>";
    echo "<td>{$r['trx_status']}</td>";
    echo "<td>{$r['proof_status']}</td>";
    echo "<td class='$style'>$analysis</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Force Fix Button
echo "<h2>4. AKSI DARURAT</h2>";
echo "<p>Jika tabel no 3 ada yang MERAH, klik tombol ini:</p>";
echo "<form method='POST'>
        <button type='submit' name='super_fix' style='background:red;color:white;padding:15px;font-size:16px;border:none;cursor:pointer;'>
        🚑 PAKSA PERBAIKI SEMUA DATA
        </button>
      </form>";

if (isset($_POST['super_fix'])) {
    // Super aggressive fix
    $conn->exec("UPDATE transaksi t 
                 JOIN payment_proof p ON t.id = p.transaksi_id 
                 SET t.status = 'verified', 
                     t.kasir_id = IFNULL(p.verified_by, 2),
                     t.cancelled_by = NULL,
                     t.cancel_reason = NULL
                 WHERE p.status = 'verified'");
                 
    echo "<h3 style='color:green'>SUKSES! Data dipaksa update. Silakan refresh halaman ini.</h3>";
}
?>
