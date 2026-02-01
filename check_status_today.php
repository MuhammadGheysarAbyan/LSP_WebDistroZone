&lt;?php
require_once 'koneksi.php';

echo "&lt;h2&gt;Pengecekan Status Transaksi Hari Ini&lt;/h2&gt;";

// Cek transaksi hari ini
$sql = "SELECT status, COUNT(*) as jumlah, SUM(total) as total_pendapatan  
        FROM TRANSAKSI 
        WHERE DATE(tanggal) = CURDATE() 
        GROUP BY status";
$result = mysqli_query($conn, $sql);

echo "&lt;h3&gt;Transaksi Hari Ini per Status:&lt;/h3&gt;";
echo "&lt;table border='1' cellpadding='5'&gt;";
echo "&lt;tr&gt;&lt;th&gt;Status&lt;/th&gt;&lt;th&gt;Jumlah&lt;/th&gt;&lt;th&gt;Total Pendapatan&lt;/th&gt;&lt;/tr&gt;";

$total_all = 0;
$count_all = 0;

while($row = mysqli_fetch_assoc($result)) {
    $total_all += $row['total_pendapatan'];
    $count_all += $row['jumlah'];
    echo "&lt;tr&gt;";
    echo "&lt;td&gt;" . $row['status'] . "&lt;/td&gt;";
    echo "&lt;td&gt;" . $row['jumlah'] . "&lt;/td&gt;";
    echo "&lt;td&gt;Rp " . number_format($row['total_pendapatan'], 0, ',', '.') . "&lt;/td&gt;";
    echo "&lt;/tr&gt;";
}

echo "&lt;tr style='font-weight:bold'&gt;";
echo "&lt;td&gt;TOTAL&lt;/td&gt;";
echo "&lt;td&gt;" . $count_all . "&lt;/td&gt;";
echo "&lt;td&gt;Rp " . number_format($total_all, 0, ',', '.') . "&lt;/td&gt;";
echo "&lt;/tr&gt;";
echo "&lt;/table&gt;";
?&gt;
