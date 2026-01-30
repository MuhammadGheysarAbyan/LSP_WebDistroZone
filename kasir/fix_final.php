<?php
require_once '../config/session.php';
require_once '../config/database.php';

check_kasir();
$db = new Database();
$conn = $db->getConnection();

$my_id = $_SESSION['user_id'];
$message = '';

if (isset($_POST['claim_trx'])) {
    $trx_id = $_POST['trx_id'];
    $sql = "UPDATE transaksi SET kasir_id = :my_id, status = 'verified' WHERE id = :trx_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['my_id' => $my_id, 'trx_id' => $trx_id]);
    $message = "Berhasil mengklaim transaksi ID: " . $trx_id;
}

if (isset($_POST['claim_all'])) {
    $sql = "UPDATE transaksi SET kasir_id = :my_id, status = 'verified' 
            WHERE platform = 'web' AND (kasir_id IS NULL OR kasir_id = 0)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['my_id' => $my_id]);
    $count = $stmt->rowCount();
    $message = "Berhasil mengklaim $count transaksi sekaligus!";
}

// Get all web transactions that might be missing
$sql = "SELECT t.*, u.nama as customer_name 
        FROM transaksi t
        LEFT JOIN users u ON t.customer_id = u.id
        WHERE t.platform = 'web' 
        ORDER BY t.id DESC LIMIT 50";
$stmt = $conn->query($sql);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Perbaikan Data Transaksi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #ECFDF5; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #1F2937; margin-top: 0; }
        .alert { background: #D1FAE5; color: #065F46; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; background: #F3F4F6; color: #4B5563; font-size: 14px; }
        td { padding: 12px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
        .btn { border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn-claim { background: #10B981; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #D1FAE5; color: #059669; }
        .badge-warning { background: #FEF3C7; color: #D97706; }
        .badge-danger { background: #FEE2E2; color: #DC2626; }
        .owner-me { color: #059669; font-weight: 600; }
        .owner-null { color: #DC2626; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>🛠️ Perbaikan Data Transaksi</h2>
            <form method="POST" onsubmit="return confirm('Yakin ingin mengklaim SEMUA transaksi web yang kosong?');">
                <button type="submit" name="claim_all" class="btn btn-claim" style="font-size: 14px; padding: 10px 20px;">
                    <i class="fas fa-check-double"></i> KLAIM SEMUA (Force Fix)
                </button>
            </form>
        </div>

        <?php if($message): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <p>Halaman ini menampilkan semua transaksi Web. Jika ada yang berwarna merah (Kasir: KOSONG), klik tombol <strong>Klaim</strong> untuk mengakuinya sebagai transaksi Anda agar muncul di laporan.</p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kode Transaksi</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Kasir Saat Ini</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $trx): ?>
                <tr>
                    <td><?php echo $trx['id']; ?></td>
                    <td><?php echo $trx['kode_transaksi']; ?></td>
                    <td><?php echo $trx['tanggal']; ?></td>
                    <td><?php echo $trx['customer_name'] ?? 'Guest'; ?></td>
                    <td>
                        <span class="badge badge-<?php echo ($trx['status'] == 'verified' || $trx['status'] == 'completed') ? 'success' : 'warning'; ?>">
                            <?php echo $trx['status']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($trx['kasir_id'] == $my_id): ?>
                            <span class="owner-me"><i class="fas fa-check"></i> Saya (<?php echo $trx['kasir_id']; ?>)</span>
                        <?php elseif(empty($trx['kasir_id'])): ?>
                            <span class="owner-null"><i class="fas fa-times"></i> KOSONG</span>
                        <?php else: ?>
                            <span>Kasir Lain (<?php echo $trx['kasir_id']; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($trx['kasir_id'] != $my_id): ?>
                            <form method="POST">
                                <input type="hidden" name="trx_id" value="<?php echo $trx['id']; ?>">
                                <button type="submit" name="claim_trx" class="btn btn-claim">
                                    <i class="fas fa-hand-holding"></i> Klaim
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="laporan.php" style="color: #10B981; text-decoration: none; font-weight: 600;">Kembali ke Laporan</a>
        </div>
    </div>
</body>
</html>
