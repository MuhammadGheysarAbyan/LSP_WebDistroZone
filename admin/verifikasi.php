<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = $_POST['payment_id'] ?? '';
    
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
        $sql = "UPDATE payment_proof SET status = 'rejected', 
                verified_by = :verified_by, verified_at = NOW() 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'verified_by' => $_SESSION['user_id'],
            'id' => $payment_id
        ]);
        
        header('Location: verifikasi.php?success=Pembayaran berhasil ditolak');
        exit;
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same base styles as previous files */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #334155;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1E293B;
            color: white;
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin: 4px 12px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px;
        }
        
        .top-bar {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .top-bar h2 {
            font-size: 24px;
            color: #1E293B;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3B82F6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .content-card h3 {
            margin-bottom: 20px;
            color: #1E293B;
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
            background: #D1FAE5;
            color: #059669;
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #E2E8F0;
            transition: all 0.3s;
        }
        
        .payment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
        }
        
        .customer-email {
            font-size: 12px;
            color: #64748B;
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
            color: #64748B;
        }
        
        .payment-detail .value {
            font-weight: 500;
        }
        
        .payment-amount {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
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
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
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
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #E2E8F0;
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
            border-radius: 10px;
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
            color: #94A3B8;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1>DistroZone</h1>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="karyawan.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Kelola Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kaos.php" class="nav-link">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="verifikasi.php" class="nav-link active">
                        <i class="fas fa-check-circle"></i>
                        Verifikasi Pembayaran
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
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
                        <div style="font-size: 12px; color: #64748B;">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
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
                                    <div style="font-weight: 600; color: #1E293B;"><?php echo htmlspecialchars($payment['kode_transaksi']); ?></div>
                                    <div style="font-size: 12px; color: #64748B;">
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
                                <div style="font-size: 12px; color: #64748B; margin-bottom: 8px;">Bukti Pembayaran:</div>
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
                                        <div style="font-size: 12px; color: #94A3B8;"><?php echo htmlspecialchars($payment['customer_email']); ?></div>
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
                                            <div style="font-size: 12px; color: #94A3B8;">
                                                <?php echo date('d/m/Y H:i', strtotime($payment['verified_at'])); ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['file_bukti']): ?>
                                            <button class="btn-icon" onclick="viewImage('../<?php echo htmlspecialchars($payment['file_bukti']); ?>')" 
                                                    style="background: #DBEAFE; color: #3B82F6; border: none; width: 36px; height: 36px; border-radius: 8px; cursor: pointer;">
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
        <div class="modal-content" style="max-width: 400px; background: white; border-radius: 16px; padding: 24px;">
            <h3 style="margin-bottom: 16px;">Tolak Pembayaran</h3>
            <p style="margin-bottom: 20px; color: #64748B;">Apakah Anda yakin ingin menolak pembayaran ini?</p>
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
        
        // Close modals on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageView();
                closeRejectModal();
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageView();
            }
        });
        
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>