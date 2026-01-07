<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'new';
$transaksi_id = $_GET['id'] ?? '';

// Handle new transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        // Generate transaction code
        $date = date('Ymd');
        $query = "SELECT COUNT(*) as count FROM transaksi WHERE DATE(tanggal) = CURDATE()";
        $stmt = $conn->query($query);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
        $kode_transaksi = 'TRX-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Get cart items from session
        $cart_items = $_SESSION['cart'] ?? [];
        if (empty($cart_items)) {
            header('Location: transaksi.php?error=Keranjang kosong');
            exit;
        }
        
        // Calculate totals
        $total = 0;
        $total_laba = 0;
        $cart_details = [];
        
        foreach ($cart_items as $item) {
            // Get product details
            $query = "SELECT harga, harga_pokok FROM kaos WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute(['id' => $item['kaos_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $subtotal = $product['harga'] * $item['qty'];
                $laba = ($product['harga'] - $product['harga_pokok']) * $item['qty'];
                $total += $subtotal;
                $total_laba += $laba;
                
                $cart_details[] = [
                    'kaos_id' => $item['kaos_id'],
                    'qty' => $item['qty'],
                    'harga_jual' => $product['harga'],
                    'harga_modal' => $product['harga_pokok'],
                    'subtotal' => $subtotal,
                    'laba' => $laba
                ];
            }
        }
        
        // Add shipping if online transaction
        $shipping_cost = $_POST['shipping_cost'] ?? 0;
        $grand_total = $total + $shipping_cost;
        
        // Insert transaction
        $transaksi_data = [
            'kode_transaksi' => $kode_transaksi,
            'customer_id' => $_POST['customer_id'] ?? null,
            'kasir_id' => $_SESSION['user_id'],
            'total' => $total,
            'shipping_city' => $_POST['shipping_city'] ?? null,
            'shipping_cost' => $shipping_cost,
            'grand_total' => $grand_total,
            'tanggal' => date('Y-m-d'),
            'waktu' => date('H:i:s'),
            'payment_method' => $_POST['payment_method'],
            'status' => ($_POST['payment_method'] === 'cash' || $_POST['payment_method'] === 'qris') ? 'completed' : 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO transaksi (" . implode(', ', array_keys($transaksi_data)) . ") 
                VALUES (:" . implode(', :', array_keys($transaksi_data)) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute($transaksi_data);
        $transaksi_id = $conn->lastInsertId();
        
        // Insert transaction details
        foreach ($cart_details as $detail) {
            $detail['transaksi_id'] = $transaksi_id;
            $sql = "INSERT INTO detail_transaksi (transaksi_id, kaos_id, qty, harga_jual, harga_modal, subtotal, laba) 
                    VALUES (:transaksi_id, :kaos_id, :qty, :harga_jual, :harga_modal, :subtotal, :laba)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($detail);
            
            // Update product stock
            $sql = "UPDATE kaos SET stok = stok - :qty WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['qty' => $detail['qty'], 'id' => $detail['kaos_id']]);
        }
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to receipt
        header('Location: transaksi.php?action=receipt&id=' . $transaksi_id);
        exit;
    }
}

// Handle adding to cart
if (isset($_GET['add_to_cart'])) {
    $kaos_id = $_GET['kaos_id'];
    $size = $_GET['size'];
    $qty = $_GET['qty'] ?? 1;
    
    // Check product availability
    $query = "SELECT * FROM kaos WHERE id = :id AND stok >= :qty";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $kaos_id, 'qty' => $qty]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['kaos_id'] == $kaos_id && $item['size'] == $size) {
                $item['qty'] += $qty;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'kaos_id' => $kaos_id,
                'size' => $size,
                'qty' => $qty
            ];
        }
        
        header('Location: transaksi.php?action=cart');
        exit;
    } else {
        header('Location: transaksi.php?error=Stok tidak mencukupi');
        exit;
    }
}

// Handle remove from cart
if (isset($_GET['remove_from_cart'])) {
    $index = $_GET['index'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
    header('Location: transaksi.php?action=cart');
    exit;
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    header('Location: transaksi.php');
    exit;
}

// Get products for sale
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM kaos WHERE stok > 0";
if ($search) {
    $query .= " AND (nama_kaos LIKE :search OR merek LIKE :search OR kode_kaos LIKE :search)";
}
$query .= " ORDER BY nama_kaos";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart items with details
$cart_items = [];
$cart_total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $query = "SELECT k.*, kat.nama_kategori 
                  FROM kaos k 
                  LEFT JOIN kategori kat ON k.kategori_id = kat.id 
                  WHERE k.id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $item['kaos_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['cart_qty'] = $item['qty'];
            $product['cart_size'] = $item['size'];
            $product['subtotal'] = $product['harga'] * $item['qty'];
            $cart_total += $product['subtotal'];
            $cart_items[] = $product;
        }
    }
}

// Get transaction details for receipt
$transaction = null;
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
    $query = "SELECT dt.*, k.nama_kaos, k.merek, k.kode_kaos 
              FROM detail_transaksi dt
              JOIN kaos k ON dt.kaos_id = k.id
              WHERE dt.transaksi_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $transaksi_id]);
    $transaction_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent transactions
$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t 
          LEFT JOIN users u ON t.customer_id = u.id 
          WHERE t.kasir_id = :kasir_id 
          ORDER BY t.created_at DESC 
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $_SESSION['user_id']]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - DistroZone Kasir</title>
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
        
        /* Tabs */
        .transaksi-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            color: #64748B;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #F1F5F9;
            color: #475569;
        }
        
        .tab-btn.active {
            background: #3B82F6;
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #F1F5F9;
        }
        
        .product-info {
            padding: 16px;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: #1E293B;
        }
        
        .product-meta {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-weight: 700;
            color: #059669;
            margin-bottom: 12px;
        }
        
        .product-stock {
            font-size: 12px;
            color: #DC2626;
            margin-bottom: 12px;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563EB;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: #F59E0B;
            color: white;
        }
        
        .btn-warning:hover {
            background: #D97706;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        /* Search Bar */
        .search-box {
            position: relative;
            margin-bottom: 24px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
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
            border-bottom: 1px solid #E2E8F0;
        }
        
        .cart-items {
            margin-bottom: 24px;
        }
        
        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #F1F5F9;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .cart-item-meta {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 8px;
        }
        
        .cart-item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
            width: 24px;
            height: 24px;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-summary {
            padding: 16px;
            background: #F8FAFC;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 18px;
            border-top: 2px solid #E2E8F0;
            padding-top: 12px;
            margin-top: 12px;
        }
        
        /* Payment Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 24px;
            border-top: 1px solid #E2E8F0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Receipt */
        .receipt {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px dashed #E2E8F0;
            padding-bottom: 16px;
        }
        
        .receipt-items {
            margin-bottom: 24px;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dotted #E2E8F0;
        }
        
        .receipt-total {
            border-top: 2px solid #E2E8F0;
            padding-top: 16px;
            margin-top: 16px;
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
        
        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .alert-warning {
            background: #FEF3C7;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #F8FAFC;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #64748B;
            font-size: 14px;
            border-bottom: 2px solid #E2E8F0;
        }
        
        td {
            padding: 16px 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        tbody tr:hover {
            background: #F8FAFC;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #D1FAE5;
            color: #059669;
        }
        
        .badge-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .badge-danger {
            background: #FEE2E2;
            color: #DC2626;
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
                    <a href="transaksi.php" class="nav-link active">
                        <i class="fas fa-cash-register"></i>
                        Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        Profile
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
                <h2>Transaksi Penjualan</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: #64748B;">Kasir • <?php echo $_SESSION['shift'] ?? 'Shift'; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'receipt' && $transaction): ?>
                <!-- Receipt View -->
                <div class="receipt">
                    <div class="receipt-header">
                        <h2>DistroZone</h2>
                        <p style="color: #64748B; font-size: 14px; margin-top: 4px;">Jln. Raya Pegangsaan Timur No.29H Kelapa Gading Jakarta</p>
                        <p style="color: #64748B; font-size: 12px; margin-top: 8px;">Telp: 021-12345678</p>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 16px;">
                        <div style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($transaction['kode_transaksi']); ?></div>
                        <div style="font-size: 14px; color: #64748B;"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></div>
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Kasir:</span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($transaction['kasir_name']); ?></span>
                        </div>
                        <?php if ($transaction['customer_name']): ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Customer:</span>
                            <span><?php echo htmlspecialchars($transaction['customer_name']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="receipt-items">
                        <?php foreach ($transaction_items as $item): ?>
                        <div class="receipt-item">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($item['nama_kaos']); ?></div>
                                <div style="font-size: 12px; color: #64748B;">
                                    <?php echo $item['qty']; ?> × <?php echo format_rupiah($item['harga_jual']); ?>
                                </div>
                            </div>
                            <div style="font-weight: 600;"><?php echo format_rupiah($item['subtotal']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="receipt-total">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Subtotal:</span>
                            <span><?php echo format_rupiah($transaction['total']); ?></span>
                        </div>
                        <?php if ($transaction['shipping_cost'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Ongkos Kirim:</span>
                            <span><?php echo format_rupiah($transaction['shipping_cost']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 18px;">
                            <span>Total:</span>
                            <span><?php echo format_rupiah($transaction['grand_total']); ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 24px; padding-top: 16px; border-top: 2px dashed #E2E8F0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Metode Bayar:</span>
                            <span style="font-weight: 600;">
                                <?php 
                                    $method = $transaction['payment_method'];
                                    echo $method === 'cash' ? 'Tunai' : ($method === 'qris' ? 'QRIS' : 'Transfer');
                                ?>
                            </span>
                        </div>
                        <div style="text-align: center; margin-top: 16px; font-size: 12px; color: #64748B;">
                            Terima kasih atas pembeliannya!
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 32px;">
                        <button onclick="printReceipt()" class="btn btn-primary" style="margin-right: 12px;">
                            <i class="fas fa-print"></i> Cetak Struk
                        </button>
                        <a href="transaksi.php" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Transaksi Baru
                        </a>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Transaction Interface -->
                <div class="transaksi-tabs">
                    <button class="tab-btn <?php echo $action === 'new' ? 'active' : ''; ?>" onclick="showTab('products')">
                        <i class="fas fa-tshirt"></i> Produk
                    </button>
                    <button class="tab-btn <?php echo $action === 'cart' ? 'active' : ''; ?>" onclick="showTab('cart')" id="cartTabBtn">
                        <i class="fas fa-shopping-cart"></i> Keranjang
                        <?php if (!empty($cart_items)): ?>
                            <span style="background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: 6px;">
                                <?php echo count($cart_items); ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn <?php echo $action === 'recent' ? 'active' : ''; ?>" onclick="showTab('recent')">
                        <i class="fas fa-history"></i> Riwayat
                    </button>
                </div>
                
                <!-- Products Tab -->
                <div id="products-tab" class="tab-content <?php echo $action === 'new' ? 'active' : ''; ?>">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearch" placeholder="Cari kaos..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <?php if (empty($products)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tidak ada produk yang tersedia
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php if ($product['foto']): ?>
                                    <img src="../<?php echo htmlspecialchars($product['foto']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['nama_kaos']); ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image" style="display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tshirt" style="font-size: 48px; color: #94A3B8;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                                    <div class="product-meta">
                                        <?php echo htmlspecialchars($product['merek']); ?> • 
                                        <?php echo htmlspecialchars($product['size']); ?> • 
                                        <?php echo htmlspecialchars($product['type']); ?>
                                    </div>
                                    <div class="product-price"><?php echo format_rupiah($product['harga']); ?></div>
                                    <div class="product-stock">
                                        Stok: <?php echo $product['stok']; ?> pcs
                                    </div>
                                    <div class="product-actions">
                                        <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo $product['size']; ?>')" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-cart-plus"></i> Tambah
                                        </button>
                                        <button onclick="quickView(<?php echo $product['id']; ?>)" 
                                                class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Cart Tab -->
                <div id="cart-tab" class="tab-content <?php echo $action === 'cart' ? 'active' : ''; ?>">
                    <?php if (empty($cart_items)): ?>
                        <div class="alert alert-warning" style="text-align: center; padding: 40px;">
                            <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 16px; display: block; color: #F59E0B;"></i>
                            <div style="font-size: 18px; margin-bottom: 8px;">Keranjang kosong</div>
                            <div style="color: #64748B;">Tambahkan produk untuk memulai transaksi</div>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                            <h3>Keranjang Belanja</h3>
                            <button onclick="clearCart()" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Kosongkan
                            </button>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Size</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $index => $item): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($item['foto']): ?>
                                                <img src="../<?php echo htmlspecialchars($item['foto']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['nama_kaos']); ?>" 
                                                     style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($item['nama_kaos']); ?></div>
                                                <div style="font-size: 12px; color: #94A3B8;"><?php echo htmlspecialchars($item['merek']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['cart_size']); ?></td>
                                    <td><?php echo format_rupiah($item['harga']); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <button onclick="updateQty(<?php echo $index; ?>, -1)" class="qty-btn">-</button>
                                            <span style="min-width: 30px; text-align: center;"><?php echo $item['cart_qty']; ?></span>
                                            <button onclick="updateQty(<?php echo $index; ?>, 1)" class="qty-btn">+</button>
                                        </div>
                                    </td>
                                    <td><?php echo format_rupiah($item['subtotal']); ?></td>
                                    <td>
                                        <button onclick="removeFromCart(<?php echo $index; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="background: white; border-radius: 12px; padding: 24px; margin-top: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 24px; font-weight: 700;"><?php echo format_rupiah($cart_total); ?></div>
                                    <div style="color: #64748B; font-size: 14px;">Total belanja</div>
                                </div>
                                <div>
                                    <button onclick="showPaymentModal()" class="btn btn-success" style="font-size: 16px; padding: 12px 32px;">
                                        <i class="fas fa-credit-card"></i> Lanjut ke Pembayaran
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Transactions Tab -->
                <div id="recent-tab" class="tab-content <?php echo $action === 'recent' ? 'active' : ''; ?>">
                    <h3 style="margin-bottom: 24px;">Riwayat Transaksi</h3>
                    
                    <?php if (empty($recent_transactions)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-history"></i>
                            Belum ada riwayat transaksi
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $trx): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($trx['kode_transaksi']); ?></div>
                                        <div style="font-size: 12px; color: #94A3B8;"><?php echo $trx['waktu']; ?></div>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($trx['tanggal'])); ?></td>
                                    <td><?php echo htmlspecialchars($trx['customer_name'] ?? 'Guest'); ?></td>
                                    <td><?php echo format_rupiah($trx['grand_total']); ?></td>
                                    <td>
                                        <?php 
                                            $method = $trx['payment_method'];
                                            echo $method === 'cash' ? 'Tunai' : ($method === 'qris' ? 'QRIS' : 'Transfer');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($trx['status'] == 'completed' || $trx['status'] == 'verified'): ?>
                                            <span class="badge badge-success">Selesai</span>
                                        <?php elseif($trx['status'] == 'pending'): ?>
                                            <span class="badge badge-warning">Menunggu</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Dibatalkan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="viewReceipt(<?php echo $trx['id']; ?>)" class="btn btn-primary btn-sm">
                                            <i class="fas fa-receipt"></i> Struk
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pembayaran</h3>
                <button class="btn-icon" onclick="closePaymentModal()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="transaksi.php?action=create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Metode Pembayaran *</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; padding: 16px; border: 2px solid #E2E8F0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="cash" checked required>
                                <div>
                                    <div style="font-weight: 600;">Tunai</div>
                                    <div style="font-size: 12px; color: #64748B;">Cash</div>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 16px; border: 2px solid #E2E8F0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="qris" required>
                                <div>
                                    <div style="font-weight: 600;">QRIS</div>
                                    <div style="font-size: 12px; color: #64748B;">Scan QR Code</div>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 16px; border: 2px solid #E2E8F0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="transfer" required>
                                <div>
                                    <div style="font-weight: 600;">Transfer</div>
                                    <div style="font-size: 12px; color: #64748B;">Bank Transfer</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_id">Customer (Opsional)</label>
                        <select id="customer_id" name="customer_id" class="form-control">
                            <option value="">Pilih Customer</option>
                            <?php
                            $query = "SELECT id, nama, email FROM users WHERE role = 'customer' ORDER BY nama";
                            $stmt = $conn->query($query);
                            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['nama']); ?> (<?php echo htmlspecialchars($customer['email']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_city">Kota Pengiriman (Opsional)</label>
                        <select id="shipping_city" name="shipping_city" class="form-control">
                            <option value="">Pilih Kota</option>
                            <option value="Jakarta">Jakarta</option>
                            <option value="Depok">Depok</option>
                            <option value="Bekasi">Bekasi</option>
                            <option value="Tangerang">Tangerang</option>
                            <option value="Bogor">Bogor</option>
                            <option value="Jawa Barat">Seluruh Wilayah Jawa Barat</option>
                            <option value="Jawa Tengah">Seluruh Wilayah Jawa Tengah</option>
                            <option value="Jawa Timur">Seluruh Wilayah Jawa Timur</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="shippingCostContainer" style="display: none;">
                        <label>Ongkos Kirim</label>
                        <input type="number" id="shipping_cost" name="shipping_cost" class="form-control" value="0" readonly>
                        <div class="help-text" style="font-size: 12px; color: #94A3B8; margin-top: 4px;">
                            *Perhitungan: 1 kg = 3 kaos, biaya sesuai wilayah
                        </div>
                    </div>
                    
                    <div class="summary-row" style="background: #F8FAFC; padding: 16px; border-radius: 8px; margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Subtotal:</span>
                            <span id="modalSubtotal"><?php echo format_rupiah($cart_total); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; display: none;" id="shippingRow">
                            <span>Ongkos Kirim:</span>
                            <span id="modalShipping">Rp0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 18px; border-top: 2px solid #E2E8F0; padding-top: 12px;">
                            <span>Total:</span>
                            <span id="modalTotal"><?php echo format_rupiah($cart_total); ?></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Quick View Modal -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Produk</h3>
                <button class="btn-icon" onclick="closeQuickView()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="quickViewContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        // Tab Navigation
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Product Search
        document.getElementById('productSearch')?.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const search = this.value;
                window.location.href = `transaksi.php?search=${encodeURIComponent(search)}`;
            }
        });
        
        // Cart Functions
        function addToCart(kaosId, size) {
            const qty = prompt('Masukkan jumlah:', '1');
            if (qty && !isNaN(qty) && parseInt(qty) > 0) {
                window.location.href = `transaksi.php?add_to_cart=1&kaos_id=${kaosId}&size=${size}&qty=${qty}`;
            }
        }
        
        function updateQty(index, change) {
            window.location.href = `transaksi.php?update_qty=1&index=${index}&change=${change}`;
        }
        
        function removeFromCart(index) {
            if (confirm('Hapus item dari keranjang?')) {
                window.location.href = `transaksi.php?remove_from_cart=1&index=${index}`;
            }
        }
        
        function clearCart() {
            if (confirm('Kosongkan keranjang belanja?')) {
                window.location.href = `transaksi.php?clear_cart=1`;
            }
        }
        
        // Payment Modal
        function showPaymentModal() {
            if (<?php echo count($cart_items); ?> === 0) {
                alert('Keranjang belanja kosong!');
                return;
            }
            document.getElementById('paymentModal').classList.add('active');
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        
        // Shipping calculation
        document.getElementById('shipping_city')?.addEventListener('change', function() {
            const city = this.value;
            const itemCount = <?php echo array_sum(array_column($cart_items, 'cart_qty')); ?>;
            const shippingRow = document.getElementById('shippingRow');
            const shippingCostContainer = document.getElementById('shippingCostContainer');
            const shippingCost = document.getElementById('shipping_cost');
            const modalShipping = document.getElementById('modalShipping');
            const modalTotal = document.getElementById('modalTotal');
            
            let costPerKg = 0;
            
            // Shipping rates
            const rates = {
                'Jakarta': 24000,
                'Depok': 24000,
                'Bekasi': 25000,
                'Tangerang': 25000,
                'Bogor': 27000,
                'Jawa Barat': 31000,
                'Jawa Tengah': 39000,
                'Jawa Timur': 47000
            };
            
            if (city && rates[city]) {
                costPerKg = rates[city];
                const kg = Math.ceil(itemCount / 3);
                const totalShipping = costPerKg * kg;
                
                shippingCost.value = totalShipping;
                modalShipping.textContent = 'Rp' + totalShipping.toLocaleString('id-ID');
                shippingRow.style.display = 'flex';
                shippingCostContainer.style.display = 'block';
                
                const subtotal = <?php echo $cart_total; ?>;
                const total = subtotal + totalShipping;
                modalTotal.textContent = 'Rp' + total.toLocaleString('id-ID');
            } else {
                shippingCost.value = 0;
                modalShipping.textContent = 'Rp0';
                shippingRow.style.display = 'none';
                shippingCostContainer.style.display = 'none';
                
                const subtotal = <?php echo $cart_total; ?>;
                modalTotal.textContent = 'Rp' + subtotal.toLocaleString('id-ID');
            }
        });
        
        // Quick View
        function quickView(productId) {
            fetch(`get_product_detail.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    const content = document.getElementById('quickViewContent');
                    content.innerHTML = `
                        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 24px;">
                            <div>
                                ${data.foto ? 
                                    `<img src="../${data.foto}" style="width: 100%; border-radius: 12px; border: 1px solid #E2E8F0;">` :
                                    `<div style="width: 100%; aspect-ratio: 1; background: #F1F5F9; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tshirt" style="font-size: 64px; color: #94A3B8;"></i>
                                    </div>`
                                }
                            </div>
                            
                            <div>
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Kode Kaos</div>
                                    <div style="font-weight: 600; font-size: 18px;">${data.kode_kaos}</div>
                                </div>
                                
                                <div style="margin-bottom: 16px;">
                                    <div style="font-weight: 600; font-size: 24px;">${data.nama_kaos}</div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Merek</div>
                                        <div>${data.merek}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Kategori</div>
                                        <div>${data.nama_kategori || '-'}</div>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Type</div>
                                        <div>${data.type}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Warna</div>
                                        <div>${data.warna}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Size</div>
                                        <div>${data.size}</div>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Harga</div>
                                        <div style="font-weight: 600; font-size: 20px; color: #059669;">Rp${data.harga.toLocaleString('id-ID')}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Stok</div>
                                        <div style="font-weight: 600; font-size: 20px;">${data.stok} pcs</div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 24px;">
                                    <button onclick="addToCart(${data.id}, '${data.size}')" class="btn btn-success" style="width: 100%;">
                                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('quickViewModal').classList.add('active');
                });
        }
        
        function closeQuickView() {
            document.getElementById('quickViewModal').classList.remove('active');
        }
        
        // Receipt Functions
        function viewReceipt(transaksiId) {
            window.location.href = `transaksi.php?action=receipt&id=${transaksiId}`;
        }
        
        function printReceipt() {
            window.print();
        }
        
        // Close modals on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
                closeQuickView();
            }
        });
        
        // Initialize active tab
        <?php if ($action === 'cart'): ?>
            document.getElementById('cartTabBtn').classList.add('active');
            document.getElementById('cart-tab').classList.add('active');
        <?php elseif ($action === 'recent'): ?>
            document.querySelectorAll('.tab-btn')[2].classList.add('active');
            document.getElementById('recent-tab').classList.add('active');
        <?php endif; ?>
    </script>
</body>
</html>