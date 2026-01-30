<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

// Handle POST actions
if (isset($_POST['action'])) {
    $transaksi_id = $_POST['transaksi_id'];
    if ($_POST['action'] == 'complete_order') {
        $customer_id = $_SESSION['user_id'];
        
        $chk_sql = "SELECT id FROM transaksi WHERE id = :id AND customer_id = :cid AND (status = 'sent' OR status = 'verified')";
        $chk_stmt = $conn->prepare($chk_sql);
        $chk_stmt->execute(['id' => $transaksi_id, 'cid' => $customer_id]);
        
        if ($chk_stmt->rowCount() > 0) {
            $sql = "UPDATE transaksi SET status = 'completed' WHERE id = :id";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute(['id' => $transaksi_id])) {
                header("Location: orders.php?success=Pesanan telah diterima! Terima kasih.");
                exit;
            } else {
                header("Location: orders.php?error=Gagal mengupdate pesanan");
                exit;
            }
        }
    } else if ($_POST['action'] == 'cancel_order') {
        $customer_id = $_SESSION['user_id'];
    
    // Verify order belongs to customer and is pending
    $chk_sql = "SELECT id FROM transaksi WHERE id = :id AND customer_id = :cid AND status = 'pending'";
    $chk_stmt = $conn->prepare($chk_sql);
    $chk_stmt->execute(['id' => $transaksi_id, 'cid' => $customer_id]);
    
    if ($chk_stmt->rowCount() > 0) {
        $conn->beginTransaction();
        try {
            // Update transaction status
            $sql = "UPDATE transaksi SET status = 'cancelled', 
                    cancelled_by = 'customer', 
                    cancel_reason = 'Dibatalkan oleh pelanggan' 
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
            header("Location: orders.php?success=Order berhasil dibatalkan dan stok dikembalikan");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: orders.php?error=Gagal membatalkan order");
            exit;
        }
    }
    }
}

// Get customer orders with payment proof status
$query = "SELECT t.*, 
          CASE 
            WHEN t.status = 'pending' AND (SELECT COUNT(*) FROM payment_proof pp WHERE pp.transaksi_id = t.id) = 0 THEN 'Menunggu Pembayaran'
            WHEN t.status = 'pending' AND (SELECT COUNT(*) FROM payment_proof pp WHERE pp.transaksi_id = t.id) > 0 THEN 'Menunggu Verifikasi'
            WHEN t.status = 'verified' THEN 'Pembayaran Berhasil'
            WHEN t.status = 'sent' THEN 'Dalam Pengiriman - Pesanan Akan Tiba'
            WHEN t.status = 'completed' THEN 'Selesai - Pesanan Telah Tiba di Rumah'
            WHEN t.status = 'cancelled' AND t.cancelled_by = 'kasir' THEN 'Ditolak'
            WHEN t.status = 'cancelled' AND t.cancelled_by = 'customer' THEN 'Dibatalkan'
            WHEN t.status = 'cancelled' THEN 'Dibatalkan'
          END as status_text,
          t.cancelled_by, t.cancel_reason,
          COUNT(dt.id) as total_items,
          (SELECT COUNT(*) FROM payment_proof pp WHERE pp.transaksi_id = t.id) as has_payment_proof,
          sr.estimasi
          FROM transaksi t
          LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
          LEFT JOIN shipping_rates sr ON t.shipping_city = sr.wilayah
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #10B981; /* Emerald 500 */
            --secondary: #0F766E; /* Teal 700 */
            --dark: #1F2937;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ECFDF5;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            color: #334155;
            min-height: 100vh;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
         .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
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
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        .page-header {
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #64748B;
            font-size: 16px;
        }
        
        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 8px;
        }
        
        .tab-btn {
            white-space: nowrap;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748B;
            font-family: inherit;
        }
        
        .tab-btn:hover {
            background: white;
            color: var(--primary);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.6);
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
             box-shadow: 0 15px 25px -3px rgba(0,0,0,0.08);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #E5E7EB;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .order-info h3 {
            font-size: 20px;
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .order-date {
            font-size: 13px;
            color: #64748B;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-verified { background: #DBEAFE; color: #1E40AF; }
        .status-sent { background: #E0E7FF; color: #4338CA; }
        .status-completed { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
        
        .order-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 24px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .detail-label { color: #64748B; }
        .detail-value { font-weight: 600; }
        
        .order-total {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
        }
        
        .order-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
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
            font-family: inherit;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(79, 70, 229, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid #E5E7EB;
        }
        
        .btn-secondary:hover {
            background: #F9FAFB;
            border-color: #D1D5DB;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
             transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(239, 68, 68, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #CBD5E1;
            margin-bottom: 24px;
        }
        
        /* Modal */
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
            backdrop-filter: blur(5px);
        }
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #E5E7EB;
            border-radius: 12px;
            cursor: pointer;
            background: #F9FAFB;
        }
        
        .bank-info {
            background: #F0F9FF;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid #BAE6FD;
            color: #0369A1;
        }
        
        .bank-info h4 { margin-bottom: 8px; font-weight: 700; }
        .bank-info p { margin-bottom: 4px; font-size: 14px; }
        
        @media (max-width: 768px) {
            .navbar-content { flex-direction: column; gap: 16px; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 16px; }
            .order-body { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-layer-group"></i>
                DistroZone
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="orders.php" class="active" title="Pesanan Saya"><i class="fas fa-box"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-bag"></i></a>
                <a href="settings.php" title="Pengaturan"><i class="fas fa-cog"></i></a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="../auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Pesanan Saya</h1>
            <p>Lacak status pesanan dan riwayat belanja Anda di sini.</p>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="filterOrders('all')">Semua</button>
            <button class="tab-btn" onclick="filterOrders('pending')">Menunggu Pembayaran</button>
            <button class="tab-btn" onclick="filterOrders('verified')">Diproses</button>
             <button class="tab-btn" onclick="filterOrders('completed')">Selesai</button>
            <button class="tab-btn" onclick="filterOrders('cancelled')">Dibatalkan</button>
        </div>
        
        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Keranjang pesananmu masih kosong nih.</p>
                <a href="shop.php" class="btn btn-primary">
                    Mulai Belanja Sekarang
                </a>
            </div>
        <?php else: ?>
            <div id="ordersList">
                <?php foreach($orders as $order): ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h3><?php echo htmlspecialchars($order['kode_transaksi']); ?></h3>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d M Y - H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo htmlspecialchars($order['status_text']); ?>
                        </div>
                        <?php if($order['status'] == 'cancelled' && $order['cancel_reason']): ?>
                            <div style="font-size: 11px; color: #EF4444; margin-top: 4px; font-weight: 500;">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($order['cancel_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-body">
                        <div>
                            <div class="detail-row">
                                <span class="detail-label">Total Item</span>
                                <span class="detail-value"><?php echo $order['total_items']; ?> pcs</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Metode Pembayaran</span>
                                <span class="detail-value"><?php echo strtoupper($order['payment_method']); ?></span>
                            </div>
                            <?php if($order['shipping_city']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Tujuan Pengiriman</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['shipping_city']); ?></span>
                            </div>
                            <?php if($order['status'] == 'completed'): ?>
                            <div class="detail-row">
                                <span class="detail-label">Status Pengiriman</span>
                                <span class="detail-value" style="color: var(--primary); font-weight: 700;">
                                    <i class="fas fa-check-circle"></i> Pesanan telah sampai kerumah
                                </span>
                            </div>
                            <?php elseif(isset($order['estimasi']) && $order['status'] != 'cancelled'): ?>
                            <div class="detail-row">
                                <span class="detail-label">Estimasi Tiba</span>
                                <span class="detail-value" style="color: var(--primary);"><i class="fas fa-truck"></i> <?php echo htmlspecialchars($order['estimasi']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                             <div class="detail-row">
                                <span class="detail-label">Subtotal</span>
                                <span class="detail-value"><?php echo format_rupiah($order['total']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ongkos Kirim</span>
                                <span class="detail-value"><?php echo format_rupiah($order['shipping_cost']); ?></span>
                            </div>
                            <div class="detail-row" style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #E5E7EB;">
                                <span class="detail-label" style="font-weight: 600; color: var(--dark);">Total Bayar</span>
                                <span class="order-total"><?php echo format_rupiah($order['grand_total']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="../includes/print_invoice.php?trx=<?php echo $order['kode_transaksi']; ?>" 
                           target="_blank" 
                           class="btn btn-secondary">
                            <i class="fas fa-print"></i> Invoice
                        </a>
                        
                        <?php if($order['status'] == 'pending'): ?>
                            <?php if($order['has_payment_proof'] == 0): ?>
                                <button class="btn btn-success" onclick="openUploadModal('<?php echo $order['id']; ?>')">
                                    <i class="fas fa-upload"></i> Upload Bukti Bayar
                                </button>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="cursor: default;">
                                    <i class="fas fa-clock"></i> Menunggu Verifikasi
                                </span>
                            <?php endif; ?>
                            
                            <button class="btn btn-danger" onclick="cancelOrder('<?php echo $order['id']; ?>')">
                                <i class="fas fa-trash"></i> Batalkan Pesanan
                            </button>
                        <?php endif; ?>
                        
                        <?php if($order['status'] == 'sent' || $order['status'] == 'verified'): ?>
                            <button class="btn btn-primary" onclick="completeOrder('<?php echo $order['id']; ?>')">
                                <i class="fas fa-check"></i> Pesanan Selesai
                            </button>
                        <?php endif; ?>
                        
                        <?php if($order['status'] == 'completed'): ?>
                            <a href="shop.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Pesan Lagi
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Upload Bukti Transfer</div>
            
            <div class="bank-info">
                <h4><i class="fas fa-info-circle"></i> Rekening Tujuan</h4>
                <p><strong>BCA:</strong> 123-456-7890 (DistroZone)</p>
            </div>
            
            <form method="POST" action="upload_payment.php" enctype="multipart/form-data">
                <input type="hidden" name="transaksi_id" id="transaksi_id">
                
                <div class="form-group">
                    <label>Pilih Foto Bukti Transfer</label>
                    <input type="file" name="bukti_transfer" accept="image/jpeg,image/png" required>
                    <small style="color: #64748B; margin-top: 6px; display: block;">Format: JPG, PNG. Max: 2MB</small>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 30px;">
                    <button type="button" class="btn btn-secondary" onclick="closeUploadModal()" style="flex: 1; justify-content: center;">Batal</button>
                    <button type="submit" class="btn btn-success" style="flex: 1; justify-content: center;">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function filterOrders(status) {
            const cards = document.querySelectorAll('.order-card');
            const btns = document.querySelectorAll('.tab-btn');
            
            btns.forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if(status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function openUploadModal(id) {
            document.getElementById('transaksi_id').value = id;
            document.getElementById('uploadModal').classList.add('active');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }
        
        function completeOrder(id) {
            Swal.fire({
                title: 'Pesanan Sudah Diterima?',
                text: "Pastikan barang sudah sampai dengan baik sebelum menyelesaikan pesanan.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Terima Pesanan',
                cancelButtonText: 'Belum'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'orders.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'complete_order';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'transaksi_id';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        function cancelOrder(id) {
            Swal.fire({
                title: 'Batalkan Pesanan?',
                text: "Pesanan yang dibatalkan tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'orders.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'cancel_order';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'transaksi_id';
                    idInput.value = id;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // Close modal on click outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                closeUploadModal();
            }
        }
    </script>

<?php include '../includes/chat_widget.php'; ?>
</body>
</html>