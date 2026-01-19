<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

// Default view is now always history
$view = $_GET['view'] ?? 'recent';
$transaksi_id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';

// Get transaction details for receipt
$transaction = null;
$transaction_items = [];
if ($action === 'receipt' && $transaksi_id) {
    $query = "SELECT t.*, u.nama as customer_name, kasir.nama as kasir_name 
              FROM transaksi t
              LEFT JOIN users u ON t.customer_id = u.id
              LEFT JOIN users kasir ON t.kasir_id = kasir.id
              WHERE t.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $transaksi_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get transaction items
    $query = "SELECT dt.*, m.nama_kaos, m.merek, v.kode_varian as kode_kaos 
              FROM detail_transaksi dt
              JOIN kaos_varian v ON dt.kaos_id = v.id
              JOIN kaos_master m ON v.kaos_master_id = m.id
              WHERE dt.transaksi_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $transaksi_id]);
    $transaction_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get history
$recent_all = [];
$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t
          LEFT JOIN users u ON t.customer_id = u.id
          WHERE t.kasir_id = :kasir_id 
          ORDER BY t.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $_SESSION['user_id']]);
$recent_all = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - DistroZone Kasir</title>
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
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(16, 185, 129, 0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #F1F5F9;
        }
        
        .product-info {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .product-price {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .product-stock {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 12px;
        }
        
        .product-actions {
            margin-top: auto;
            display: flex;
            gap: 8px;
        }
        
        function addToCart(id, size) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'transaksi.php?action=add_to_cart';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'kaos_id';
            idInput.value = id;
            
            const sizeInput = document.createElement('input');
            sizeInput.type = 'hidden';
            sizeInput.name = 'size';
            sizeInput.value = size;
            
            form.appendChild(idInput);
            form.appendChild(sizeInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        /* Search Box */
        .search-box {
            position: relative;
            margin-bottom: 24px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            background: white;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.1);
            padding: 24px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .cart-sidebar.active {
            display: block;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
        }
        
        .cart-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            margin-bottom: 16px;
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-outline {
            background: white;
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--primary);
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
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Receipt */
        .receipt {
            text-align: center;
            padding: 24px;
            background: white;
            border-radius: 20px;
        }
        
        /* Form Inputs */
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            margin-bottom: 16px;
            font-family: 'Outfit', sans-serif;
        }

        /* Floating Cart Button */
        .floating-cart-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
            cursor: pointer;
            z-index: 900;
            transition: all 0.3s;
        }
        
        .floating-cart-btn:hover {
            transform: scale(1.1);
        }
        
        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #EF4444;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
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
                <li class="nav-item"><a href="transaksi.php?view=recent" class="nav-link <?php echo $view === 'recent' ? 'active' : ''; ?>"><i class="fas fa-history"></i>Riwayat Transaksi</a></li>
                <li class="nav-item"><a href="verifikasi.php" class="nav-link"><i class="fas fa-check-circle"></i>Verifikasi</a></li>
                <li class="nav-item"><a href="laporan.php" class="nav-link"><i class="fas fa-chart-line"></i>Laporan</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i>Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2><?php echo $view === 'recent' ? 'Riwayat Transaksi' : 'Transaksi Baru'; ?></h2>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Kasir</div>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(16, 185, 129, 0.05);">
                                <th style="padding: 12px; text-align: left;">Kode</th>
                                <th style="padding: 12px; text-align: left;">Pelanggan</th>
                                <th style="padding: 12px; text-align: left;">Tanggal</th>
                                <th style="padding: 12px; text-align: left;">Total</th>
                                <th style="padding: 12px; text-align: left;">Metode</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_all)): ?>
                                <tr><td colspan="7" style="text-align: center; padding: 20px;">Belum ada riwayat transaksi</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_all as $r): ?>
                                <tr style="border-bottom: 1px solid rgba(16, 185, 129, 0.05);">
                                    <td style="padding: 12px;"><strong><?php echo $r['kode_transaksi']; ?></strong></td>
                                    <td style="padding: 12px;"><?php echo $r['customer_name'] ?? 'Guest'; ?></td>
                                    <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($r['tanggal'])); ?></td>
                                    <td style="padding: 12px;"><?php echo format_rupiah($r['grand_total']); ?></td>
                                    <td style="padding: 12px;"><span style="text-transform: uppercase; font-size: 11px; font-weight: 600;"><?php echo $r['payment_method']; ?></span></td>
                                    <td style="padding: 12px;">
                                        <span class="badge <?php echo ($r['status'] === 'completed' || $r['status'] === 'verified') ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $r['status']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="?action=receipt&id=<?php echo $r['id']; ?>" class="btn btn-outline" style="padding: 4px 8px;">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
        <!-- Receipt Modal (If Action is Receipt) -->
        <?php if ($action === 'receipt' && $transaction): ?>
        <div id="receiptModal" class="modal active">
            <div class="receipt">
                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--primary); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">Detail Transaksi</h3>
                <p style="color: var(--text-light); margin-bottom: 24px;">#<?php echo $transaction['kode_transaksi']; ?></p>
                
                <div style="text-align: left; background: #F8FAFC; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                    <?php foreach ($transaction_items as $item): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                        <span><?php echo $item['nama_kaos']; ?> (x<?php echo $item['qty']; ?>)</span>
                        <span><?php echo format_rupiah($item['subtotal']); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="border-top: 1px dashed #E2E8F0; margin-top: 8px; padding-top: 8px; display: flex; justify-content: space-between; font-weight: 700;">
                        <span>TOTAL</span>
                        <span><?php echo format_rupiah($transaction['grand_total']); ?></span>
                    </div>
                </div>
                
                <button onclick="window.location.href='transaksi.php'" class="btn btn-primary" style="width: 100%;">Tutup</button>
                <button onclick="window.print()" class="btn btn-outline" style="width: 100%; margin-top: 8px;">Cetak Struk</button>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <script>
        // Transaksi module now only handles history and receipts.
    </script>
</body>
</html>