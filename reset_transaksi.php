<?php
/**
 * RESET TRANSAKSI SCRIPT
 * DANGER: This will delete ALL transaction history.
 * Products and Users will remain safe.
 */
require_once 'config/database.php';
require_once 'config/session.php';

check_admin(); // Only admin/kasir should access

$db = new Database();
$conn = $db->getConnection();

echo "<h1>⚠️ RESET RIWAYAT TRANSAKSI</h1>";
echo "<p>Script ini akan menghapus SEMUA data di tabel:</p>";
echo "<ul><li>payment_proof</li><li>detail_transaksi</li><li>transaksi</li></ul>";
echo "<p>Data Produk (Kaos) dan User TIDAK akan dihapus.</p>";

if (isset($_POST['confirm_reset'])) {
    try {
        $conn->beginTransaction();
        
        // Disable foreign key checks to allow truncation
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $conn->exec("TRUNCATE TABLE payment_proof");
        $conn->exec("TRUNCATE TABLE detail_transaksi");
        $conn->exec("TRUNCATE TABLE transaksi");
        
        // Re-enable foreign key checks
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $conn->commit();
        
        echo "<h2 style='color:green'>✅ SUKSES! Semua riwayat transaksi telah dihapus.</h2>";
        echo "<p><a href='kasir/index.php'>Kembali ke Dashboard</a></p>";
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<h2 style='color:red'>❌ ERROR: " . $e->getMessage() . "</h2>";
    }
} else {
    echo "<form method='POST'>
            <button type='submit' name='confirm_reset' style='background:red;color:white;padding:20px;font-size:18px;border:none;cursor:pointer;border-radius:10px;'>
                🗑️ SAYA YAKIN - HAPUS SEMUA TRANSAKSI
            </button>
          </form>";
}
?>
