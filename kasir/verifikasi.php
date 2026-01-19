<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

// Auto-cancel orders pending for more than 24 hours without verification
$check_auto_cancel = "SELECT id FROM transaksi 
                      WHERE status = 'pending' 
                      AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$res_auto_cancel = $conn->query($check_auto_cancel);
$orders_to_cancel = $res_auto_cancel->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders_to_cancel as $trx_cancel) {
    $conn->beginTransaction();
    try {
        $tid = $trx_cancel['id'];
        
        // Return stock
        $sql_items = "SELECT kaos_id, qty FROM detail_transaksi WHERE transaksi_id = :id";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->execute(['id' => $tid]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $sql_stock = "UPDATE kaos_varian SET stok = stok + :qty WHERE id = :id";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->execute(['qty' => $item['qty'], 'id' => $item['kaos_id']]);
        }
        
        // Set status to cancelled
        $sql_upd = "UPDATE transaksi SET status = 'cancelled', 
                    cancelled_by = 'system', 
                    cancel_reason = 'Batal otomatis (melebihi 24 jam)' 
                    WHERE id = :id";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->execute(['id' => $tid]);
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
    }
}

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = $_POST['payment_id'] ?? '';
    $transaksi_id = $_POST['transaksi_id'] ?? '';
    
    if ($action === 'verify') {
        $sql = "UPDATE payment_proof SET status = 'verified', 
                verified_by = :verified_by, verified_at = NOW() 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'verified_by' => $_SESSION['user_id'],
            'id' => $payment_id
        ]);
        
        // Also update transaction status
        $sql = "UPDATE transaksi t
                JOIN payment_proof p ON t.id = p.transaksi_id
                SET t.status = 'verified'
                WHERE p.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $payment_id]);
        
        header('Location: verifikasi.php?success=Pembayaran berhasil diverifikasi');
        exit;
    }
    elseif ($action === 'reject') {
        // Delete the rejected payment proof so customer can upload again
        $sql = "DELETE FROM payment_proof WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $payment_id]);
        
        // Transaction stays in 'pending' status so customer can re-upload
        
        header('Location: verifikasi.php?success=Bukti pembayaran ditolak. Customer dapat upload ulang.');
        exit;
    }
    elseif ($action === 'cancel_order') {
        $conn->beginTransaction();
        try {
            // Cancel order
            $sql = "UPDATE transaksi SET status = 'cancelled', 
                    cancelled_by = 'kasir', 
                    cancel_reason = 'Ditolak oleh kasir/admin' 
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id' => $transaksi_id]);
            
            // Return stock
            $sql_items = "SELECT kaos_id, qty FROM detail_transaksi WHERE transaksi_id = :id";
            $stmt_items = $conn->prepare($sql_items);
            $stmt_items->execute(['id' => $transaksi_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                $sql_stock = "UPDATE kaos_varian SET stok = stok + :qty WHERE id = :id";
                $stmt_stock = $conn->prepare($sql_stock);
                $stmt_stock->execute(['qty' => $item['qty'], 'id' => $item['kaos_id']]);
            }
            
            $conn->commit();
            header('Location: verifikasi.php?success=Pesanan berhasil ditolak dan stok dikembalikan');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            header('Location: verifikasi.php?error=Gagal membatalkan pesanan');
            exit;
        }
    }
}

// Get pending payment proofs with transaction details
$query = "SELECT p.*, t.kode_transaksi, t.grand_total, 
                 u.nama as customer_name, u.email as customer_email,
                 admin.nama as verifier_name
          FROM payment_proof p
          LEFT JOIN transaksi t ON p.transaksi_id = t.id
          LEFT JOIN users u ON t.customer_id = u.id
          LEFT JOIN users admin ON p.verified_by = admin.id
          WHERE p.status = 'pending'
          ORDER BY p.tanggal_upload DESC";
$stmt = $conn->query($query);
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending transactions WITHOUT payment proof (waiting for customer to upload)
$query = "SELECT t.*, u.nama as customer_name, u.email as customer_email,
                 TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_pending
          FROM transaksi t
          LEFT JOIN users u ON t.customer_id = u.id
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE t.status = 'pending' AND p.id IS NULL
          ORDER BY t.created_at DESC";
$stmt = $conn->query($query);
$pending_no_proof = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get verified payments for history
$query = "SELECT p.*, t.kode_transaksi, t.grand_total, 
                 u.nama as customer_name, u.email as customer_email,
                 admin.nama as verifier_name
          FROM payment_proof p
          LEFT JOIN transaksi t ON p.transaksi_id = t.id
          LEFT JOIN users u ON t.customer_id = u.id
          LEFT JOIN users admin ON p.verified_by = admin.id
          WHERE p.status != 'pending'
          ORDER BY p.verified_at DESC
          LIMIT 10";
$stmt = $conn->query($query);
$verified_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cancelled orders for history
$query = "SELECT t.*, u.nama as customer_name, u.email as customer_email
          FROM transaksi t
          LEFT JOIN users u ON t.customer_id = u.id
          WHERE t.status = 'cancelled'
          ORDER BY t.created_at DESC
          LIMIT 10";
$stmt = $conn->query($query);
$cancelled_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
            --primary: #10B981;
            --primary-dark: #047857;
            --secondary: #0F766E;
            --bg-color: #ECFDF5;
            --text-dark: #1F2937;
            --text-light: #64748B;
            --white: #FFFFFF;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(15, 118, 110, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(15, 118, 110, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
            margin-bottom: 24px;
        }
        
        .content-card h3 {
            margin-bottom: 24px;
            color: var(--text-dark);
            font-size: 20px;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }
        
        .badge-danger {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        /* Payment Cards */
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .payment-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(16, 185, 129, 0.1);
            transition: all 0.3s;
        }
        
        .payment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
        }
        
        .payment-card.pending {
            border-left: 4px solid #F59E0B;
        }
        
        .payment-card.verified {
            border-left: 4px solid #10B981;
        }
        
        .payment-card.rejected {
            border-left: 4px solid #EF4444;
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .payment-customer {
            margin-bottom: 16px;
        }
        
        .customer-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-dark);
        }
        
        .customer-email {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .payment-details {
            margin-bottom: 20px;
        }
        
        .payment-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .payment-detail .label {
            color: var(--text-light);
        }
        
        .payment-detail .value {
            font-weight: 500;
        }
        
        .payment-amount {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .payment-proof {
            margin: 20px 0;
        }
        
        .proof-image {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .proof-image:hover {
            transform: scale(1.02);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
        }
        
        .btn-success {
            background: var(--primary);
            color: white;
        }
        
        .btn-success:hover {
            background: var(--primary-dark);
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        .btn-secondary {
            background: #F1F5F9;
            color: var(--text-light);
        }
        
        .btn-secondary:hover {
            background: #E2E8F0;
        }
        
        .btn-icon {
            background: rgba(59, 130, 246, 0.1); 
            color: #3B82F6; 
            border: none; 
            width: 36px; 
            height: 36px; 
            border-radius: 8px; 
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Modal for image view */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
        }
        
        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        
        .alert-warning {
            background: #FEF3C7;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
            opacity: 0.5;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            border-bottom: 2px solid rgba(16, 185, 129, 0.1);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            vertical-align: middle;
        }
        
        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: rgba(16, 185, 129, 0.05);
        }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-layer-group"></i>
                <h1>DistroZone</h1>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i>Dashboard</a></li>
                <li class="nav-item"><a href="transaksi.php?view=recent" class="nav-link"><i class="fas fa-history"></i>Riwayat Transaksi</a></li>
                <li class="nav-item">
                    <a href="verifikasi.php" class="nav-link active">
                        <i class="fas fa-check-circle"></i>Verifikasi
                        <?php if(count($pending_payments) > 0): ?>
                            <span class="badge badge-danger" style="margin-left: auto; padding: 4px 10px; font-size: 10px;"><?php echo count($pending_payments); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a href="laporan.php" class="nav-link"><i class="fas fa-chart-line"></i>Laporan</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i>Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Verifikasi Pembayaran</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Kasir • <?php echo $_SESSION['shift'] ?? 'On Duty'; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Pending Orders WITHOUT Payment Proof -->
            <div class="content-card" style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="fas fa-clock" style="color: #F59E0B;"></i> Menunggu Upload Bukti Bayar</h3>
                    <span class="badge badge-info"><?php echo count($pending_no_proof); ?> pesanan</span>
                </div>
                
                <?php if (empty($pending_no_proof)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <div>Semua pesanan sudah upload bukti bayar</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Waktu Order</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_no_proof as $trx): ?>
                                <tr>
                                    <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($trx['kode_transaksi']); ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($trx['customer_name']); ?></div>
                                        <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($trx['customer_email']); ?></div>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo format_rupiah($trx['grand_total']); ?></td>
                                    <td>
                                        <div><?php echo date('d M Y, H:i', strtotime($trx['created_at'])); ?></div>
                                        <div style="font-size: 12px; color: <?php echo $trx['hours_pending'] > 20 ? '#EF4444' : '#F59E0B'; ?>;">
                                            <?php echo $trx['hours_pending']; ?> jam yang lalu
                                            <?php if ($trx['hours_pending'] > 20): ?>
                                                <br><small>(Akan otomatis batal)</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-warning">Belum Upload</span></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="cancel_order">
                                            <input type="hidden" name="transaksi_id" value="<?php echo $trx['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan pesanan ini?')">
                                                <i class="fas fa-times"></i> Batalkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pending Payments -->
            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Menunggu Verifikasi</h3>
                    <span class="badge badge-warning"><?php echo count($pending_payments); ?> menunggu</span>
                </div>
                
                <?php if (empty($pending_payments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <div>Tidak ada pembayaran yang menunggu verifikasi</div>
                    </div>
                <?php else: ?>
                    <div class="payment-grid">
                        <?php foreach ($pending_payments as $payment): ?>
                        <div class="payment-card pending">
                            <div class="payment-header">
                                <div>
                                    <div style="font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($payment['kode_transaksi']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-light);">
                                        <?php echo date('d M Y, H:i', strtotime($payment['tanggal_upload'])); ?>
                                    </div>
                                </div>
                                <span class="badge badge-warning">Menunggu</span>
                            </div>
                            
                            <div class="payment-customer">
                                <div class="customer-name"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                <div class="customer-email"><?php echo htmlspecialchars($payment['customer_email']); ?></div>
                            </div>
                            
                            <div class="payment-details">
                                <div class="payment-detail">
                                    <span class="label">Total Transaksi</span>
                                    <span class="value payment-amount"><?php echo format_rupiah($payment['grand_total']); ?></span>
                                </div>
                                <div class="payment-detail">
                                    <span class="label">Tanggal Upload</span>
                                    <span class="value"><?php echo date('d/m/Y H:i', strtotime($payment['tanggal_upload'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($payment['file_bukti']): ?>
                            <div class="payment-proof">
                                <div style="font-size: 12px; color: var(--text-light); margin-bottom: 8px;">Bukti Pembayaran:</div>
                                <img src="../<?php echo htmlspecialchars($payment['file_bukti']); ?>" 
                                     alt="Bukti Pembayaran" 
                                     class="proof-image"
                                     onclick="viewImage('../<?php echo htmlspecialchars($payment['file_bukti']); ?>')">
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="action-buttons">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i>
                                    Terima
                                </button>
                                <button type="button" class="btn btn-danger" onclick="rejectPayment(<?php echo $payment['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                    Tolak
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment History -->
            <div class="content-card">
                <h3>Riwayat Verifikasi</h3>
                
                <?php if (empty($verified_payments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <div>Belum ada riwayat verifikasi</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Customer</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Diverifikasi Oleh</th>
                                    <th>Bukti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verified_payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['kode_transaksi']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                        <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($payment['customer_email']); ?></div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($payment['tanggal_upload'])); ?></td>
                                    <td><?php echo format_rupiah($payment['grand_total']); ?></td>
                                    <td>
                                        <?php if ($payment['status'] === 'verified'): ?>
                                            <span class="badge badge-success">Diterima</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['verifier_name']): ?>
                                            <?php echo htmlspecialchars($payment['verifier_name']); ?>
                                            <div style="font-size: 12px; color: var(--text-light);">
                                                <?php echo date('d/m/Y H:i', strtotime($payment['verified_at'])); ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['file_bukti']): ?>
                                            <button class="btn-icon" onclick="viewImage('../<?php echo htmlspecialchars($payment['file_bukti']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recently Cancelled Orders -->
            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="fas fa-times-circle" style="color: #EF4444;"></i> Riwayat Pembatalan Pesanan</h3>
                    <span class="badge badge-danger"><?php echo count($cancelled_orders); ?> baru</span>
                </div>
                
                <?php if (empty($cancelled_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-double"></i>
                        <div>Tidak ada pesanan yang dibatalkan akhir-akhir ini</div>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode TRX</th>
                                    <th>Customer</th>
                                    <th>Tanggal Order</th>
                                    <th>Total</th>
                                    <th>Dibatalkan Oleh</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancelled_orders as $trx): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trx['kode_transaksi']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($trx['customer_name'] ?? 'Guest'); ?></div>
                                        <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($trx['customer_email'] ?? '-'); ?></div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                                    <td><?php echo format_rupiah($trx['grand_total']); ?></td>
                                    <td>
                                        <?php if ($trx['cancelled_by'] === 'customer'): ?>
                                            <span class="badge badge-info">Pelanggan</span>
                                        <?php elseif ($trx['cancelled_by'] === 'kasir'): ?>
                                            <span class="badge badge-warning">Kasir/Admin</span>
                                        <?php elseif ($trx['cancelled_by'] === 'system'): ?>
                                            <span class="badge badge-secondary">Sistem (Auto)</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 13px; color: #EF4444; font-weight: 500;">
                                        <?php echo htmlspecialchars($trx['cancel_reason'] ?? 'Tidak ada alasan'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Image View Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeImageView()">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" alt="Bukti Pembayaran">
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content" style="max-width: 400px; background: white; border-radius: 20px; padding: 24px;">
            <h3 style="margin-bottom: 16px;">Tolak Pembayaran</h3>
            <p style="margin-bottom: 20px; color: var(--text-light);">Apakah Anda yakin ingin menolak pembayaran ini?</p>
            <form method="POST" id="rejectForm" style="display: flex; gap: 12px;">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="payment_id" id="rejectPaymentId">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()" style="flex: 1;">Batal</button>
                <button type="submit" class="btn btn-danger" style="flex: 1;">Ya, Tolak</button>
            </form>
        </div>
    </div>
    
    <script>
        function viewImage(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
        }
        
        function closeImageView() {
            document.getElementById('imageModal').classList.remove('active');
        }
        
        function rejectPayment(paymentId) {
            document.getElementById('rejectPaymentId').value = paymentId;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('imageModal')) {
                closeImageView();
            }
            if (event.target == document.getElementById('rejectModal')) {
                closeRejectModal();
            }
        }
    </script>
</body>
</html>
