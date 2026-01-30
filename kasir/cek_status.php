<?php
require_once '../config/session.php';
require_once '../config/database.php';

check_kasir();
$db = new Database();
$conn = $db->getConnection();

$my_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cek Data Transaksi</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .match { background-color: #dff0d8; }
        .mismatch { background-color: #f2dede; }
    </style>
</head>
<body>
    <h2>Debug Data Transaksi</h2>
    <div style="margin-bottom: 20px; padding: 15px; background: #e8f4fd; border-radius: 8px;">
        <strong>Info Sesi Anda:</strong><br>
        ID Kasir Login: <strong><?php echo $my_id; ?></strong><br>
        Nama: <?php echo $_SESSION['nama']; ?><br>
        Tanggal: <?php echo date('Y-m-d'); ?>
    </div>

    <h3>Daftar 20 Transaksi Terakhir</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Platform</th>
                <th>Kasir ID (DB)</th>
                <th>Payment Method</th>
                <th>Analisa</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM transaksi ORDER BY id DESC LIMIT 20";
            $stmt = $conn->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $kasir_id_db = $row['kasir_id'];
                $is_match = ($kasir_id_db == $my_id);
                $class = $is_match ? 'match' : 'mismatch';
                
                $analisa = [];
                if (!$is_match) $analisa[] = "ID Kasir Beda/Kosong";
                if ($row['status'] != 'verified' && $row['status'] != 'completed') $analisa[] = "Status bukan verified/completed";
                if (date('Y-m-d', strtotime($row['tanggal'])) != date('Y-m-d')) $analisa[] = "Tanggal bukan hari ini";
                
                if (empty($analisa)) $analisa[] = "✅ Seharusnya Masuk Laporan";
                
                echo "<tr class='$class'>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['kode_transaksi']}</td>";
                echo "<td>{$row['tanggal']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['platform']}</td>";
                echo "<td>" . ($kasir_id_db ?? 'NULL') . "</td>";
                echo "<td>{$row['payment_method']}</td>";
                echo "<td>" . implode(", ", $analisa) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <h3>Perbaikan Cepat</h3>
        <p>Jika Anda yakin transaksi di atas adalah milik Anda, klik tombol di bawah untuk <strong>mengklaim</strong> semua transaksi yang berstatus 'verified' tapi ID Kasir-nya kosong.</p>
        <form method="POST">
            <button type="submit" name="fix_all" style="background: #10B981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;">
                KLIK DISINI UNTUK PERBAIKI DATA SAYA
            </button>
        </form>
    </div>

    <?php
    if (isset($_POST['fix_all'])) {
        $sql_fix = "UPDATE transaksi SET kasir_id = :my_id 
                    WHERE status IN ('verified', 'completed') 
                    AND (kasir_id IS NULL OR kasir_id = 0)";
        $stmt_fix = $conn->prepare($sql_fix);
        $stmt_fix->execute(['my_id' => $my_id]);
        $updated = $stmt_fix->rowCount();
        echo "<script>alert('Berhasil mengupdate $updated transaksi ke akun Anda!'); window.location.reload();</script>";
    }
    ?>
</body>
</html>
