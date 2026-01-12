<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

// Get customer orders
$query = "SELECT t.*, 
          CASE 
            WHEN t.status = 'pending' THEN 'Menunggu Pembayaran'
            WHEN t.status = 'verified' THEN 'Sedang Diproses'
            WHEN t.status = 'completed' THEN 'Selesai'
            WHEN t.status = 'cancelled' THEN 'Dibatalkan'
          END as status_text,
          COUNT(dt.id) as total_items
          FROM transaksi t
          LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
          WHERE t.customer_id = :customer_id
          GROUP BY t.id
          ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([':customer_id' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #1E293B;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #64748B;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: white;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748B;
        }
        
        .tab-btn.active {
            background: #3B82F6;
            color: white;
            border-color: #3B82F6;
        }
        
        /* Order Cards */
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #F1F5F9;
            margin-bottom: 16px;
        }
        
        .order-info h3 {
            font-size: 18px;
            color: #1E293B;
            margin-bottom: 4px;
        }
        
        .order-date {
            font-size: 13px;
            color: #64748B;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .status-verified {
            background: #DBEAFE;
            color: #1E40AF;
        }
        
        .status-completed {
            background: #D1FAE5;
            color: #059669;
        }
        
        .status-cancelled {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .order-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .order-detail {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        
        .detail-label {
            color: #64748B;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1E293B;
        }
        
        .order-total {
            font-size: 20px;
            font-weight: 700;
            color: #3B82F6;
        }
        
        .order-actions {
            display: flex;
            gap: 12px;
            padding-top: 16px;
            border-top: 2px solid #F1F5F9;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #F1F5F9;
            color: #334155;
        }
        
        .btn-secondary:hover {
            background: #E2E8F0;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #CBD5E1;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #1E293B;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #64748B;
            margin-bottom: 24px;
        }
        
        /* Payment Upload Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1E293B;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #E2E8F0;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .bank-info {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .bank-info h4 {
            margin-bottom: 12px;
            color: #1E293B;
        }
        
        .bank-info p {
            margin-bottom: 8px;
            color: #334155;
        }
        
        @media (max-width: 768px) {
            .order-body {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">DistroZone</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="orders.php" style="color: #667eea;">Pesanan</a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1>Pesanan Saya</h1>
            <p>Kelola dan lacak status pesanan Anda</p>
        </div>
        
        <!-- Tabs Filter -->
        <div class="tabs">
            <button class="tab-btn active" onclick="filterOrders('all')">
                Semua Pesanan
            </button>
            <button class="tab-btn" onclick="filterOrders('pending')">
                Menunggu Pembayaran
            </button>
            <button class="tab-btn" onclick="filterOrders('verified')">
                Sedang Diproses
            </button>
            <button class="tab-btn" onclick="filterOrders('completed')">
                Selesai
            </button>
        </div>
        
        <!-- Orders List -->
        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Yuk mulai berbelanja di DistroZone!</p>
                <a href="shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <div id="ordersList">
                <?php foreach($orders as $order): ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h3><?php echo $order['kode_transaksi']; ?></h3>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo format_datetime($order['created_at']); ?>
                            </div>
                        </div>
                        <div class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo $order['status_text']; ?>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-detail">
                            <div class="detail-row">
                                <span class="detail-label">Total Item:</span>
                                <span class="detail-value"><?php echo $order['total_items']; ?> item</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Metode Pembayaran:</span>
                                <span class="detail-value"><?php echo strtoupper($order['payment_method']); ?></span>
                            </div>
                            <?php if($order['shipping_city']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Pengiriman:</span>
                                <span class="detail-value"><?php echo $order['shipping_city']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-detail">
                            <div class="detail-row">
                                <span class="detail-label">Subtotal:</span>
                                <span class="detail-value"><?php echo format_rupiah($order['total']); ?></span>
                            </div>
                            <?php if($order['shipping_cost'] > 0): ?>
                            <div class="detail-row">
                                <span class="detail-label">Ongkir:</span>
                                <span class="detail-value"><?php echo format_rupiah($order['shipping_cost']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Total:</span>
                                <span class="order-total"><?php echo format_rupiah($order['grand_total']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="../includes/print_invoice.php?trx=<?php echo $order['kode_transaksi']; ?>" 
                           target="_blank" 
                           class="btn btn-secondary">
                            <i class="fas fa-file-invoice"></i> Lihat Invoice
                        </a>
                        
                        <?php if($order['status'] == 'pending'): ?>
                            <button class="btn btn-success" onclick="openUploadModal('<?php echo $order['id']; ?>', '<?php echo $order['kode_transaksi']; ?>')">
                                <i class="fas fa-upload"></i> Upload Bukti Transfer
                            </button>
                        <?php endif; ?>
                        
                        <?php if($order['status'] == 'completed'): ?>
                            <a href="shop.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Pesan Lagi
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Upload Payment Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Upload Bukti Transfer</div>
            
            <div class="bank-info">
                <h4>Informasi Transfer:</h4>
                <p><strong>Bank:</strong> BCA</p>
                <p><strong>No. Rekening:</strong> 1234567890</p>
                <p><strong>Atas Nama:</strong> DistroZone</p>
            </div>
            
            <form method="POST" action="upload_payment.php" enctype="multipart/form-data">
                <input type="hidden" name="transaksi_id" id="transaksi_id">
                
                <div class="form-group">
                    <label>Upload Bukti Transfer (JPG/PNG)</label>
                    <input type="file" name="bukti_transfer" accept="image/*" required>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeUploadModal()" style="flex: 1;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function filterOrders(status) {
            const cards = document.querySelectorAll('.order-card');
            const buttons = document.querySelectorAll('.tab-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function openUploadModal(transaksiId, kodeTransaksi) {
            document.getElementById('transaksi_id').value = transaksiId;
            document.getElementById('uploadModal').classList.add('active');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }
    </script>
</body>
</html>