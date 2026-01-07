<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

// Get cart items
$query = "SELECT c.*, k.nama_kaos, k.harga, k.foto, k.stok 
          FROM cart c 
          INNER JOIN kaos k ON c.kaos_id = k.id 
          WHERE c.customer_id = :customer_id";
$stmt = $conn->prepare($query);
$stmt->execute([':customer_id' => $_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
$total_weight = 0; // in kg (3 pcs = 1 kg)
foreach ($cart_items as $item) {
    $subtotal += $item['harga'] * $item['qty'];
    $total_weight += ceil($item['qty'] / 3); // 3 kaos = 1 kg
}

// Get shipping rates
$query_shipping = "SELECT * FROM shipping_rates ORDER BY cost_per_kg";
$stmt_shipping = $conn->query($query_shipping);
$shipping_rates = $stmt_shipping->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_city = clean_input($_POST['shipping_city']);
    $payment_method = clean_input($_POST['payment_method']);
    
    // Get shipping cost
    $query_rate = "SELECT cost_per_kg FROM shipping_rates WHERE wilayah = :wilayah";
    $stmt_rate = $conn->prepare($query_rate);
    $stmt_rate->execute([':wilayah' => $shipping_city]);
    $rate = $stmt_rate->fetch(PDO::FETCH_ASSOC);
    
    if (!$rate) {
        $error = "Wilayah pengiriman tidak tersedia!";
    } else {
        $shipping_cost = $rate['cost_per_kg'] * $total_weight;
        $grand_total = $subtotal + $shipping_cost;
        
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Create transaction
            $kode_transaksi = generate_code('TRX');
            $query_trx = "INSERT INTO transaksi (kode_transaksi, customer_id, total, shipping_city, 
                          shipping_cost, grand_total, tanggal, payment_method, status, waktu, created_at) 
                          VALUES (:kode, :customer, :total, :city, :shipping, :grand, CURDATE(), :payment, 'pending', NOW(), NOW())";
            
            $stmt_trx = $conn->prepare($query_trx);
            $stmt_trx->execute([
                ':kode' => $kode_transaksi,
                ':customer' => $_SESSION['user_id'],
                ':total' => $subtotal,
                ':city' => $shipping_city,
                ':shipping' => $shipping_cost,
                ':grand' => $grand_total,
                ':payment' => $payment_method
            ]);
            
            $transaksi_id = $conn->lastInsertId();
            
            // Insert transaction details and calculate profit
            foreach ($cart_items as $item) {
                // Get product cost
                $query_kaos = "SELECT harga_pokok FROM kaos WHERE id = :id";
                $stmt_kaos = $conn->prepare($query_kaos);
                $stmt_kaos->execute([':id' => $item['kaos_id']]);
                $kaos = $stmt_kaos->fetch(PDO::FETCH_ASSOC);
                
                $harga_modal = $kaos['harga_pokok'];
                $subtotal_item = $item['harga'] * $item['qty'];
                $laba = ($item['harga'] - $harga_modal) * $item['qty'];
                
                $query_detail = "INSERT INTO detail_transaksi (transaksi_id, kaos_id, qty, harga_jual, 
                                harga_modal, subtotal, laba, created_at) 
                                VALUES (:trx_id, :kaos_id, :qty, :harga, :modal, :subtotal, :laba, NOW())";
                
                $stmt_detail = $conn->prepare($query_detail);
                $stmt_detail->execute([
                    ':trx_id' => $transaksi_id,
                    ':kaos_id' => $item['kaos_id'],
                    ':qty' => $item['qty'],
                    ':harga' => $item['harga'],
                    ':modal' => $harga_modal,
                    ':subtotal' => $subtotal_item,
                    ':laba' => $laba
                ]);
                
                // Update stock
                $query_update = "UPDATE kaos SET stok = stok - :qty WHERE id = :id";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->execute([':qty' => $item['qty'], ':id' => $item['kaos_id']]);
            }
            
            // Clear cart
            $query_clear = "DELETE FROM cart WHERE customer_id = :customer_id";
            $stmt_clear = $conn->prepare($query_clear);
            $stmt_clear->execute([':customer_id' => $_SESSION['user_id']]);
            
            $conn->commit();
            
            // Redirect to payment page
            header("Location: payment.php?trx=" . $kode_transaksi);
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DistroZone</title>
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
            color: #1E293B;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        
        .checkout-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1E293B;
        }
        
        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 4px;
        }
        
        .item-meta {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 8px;
        }
        
        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: #3B82F6;
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
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 15px;
        }
        
        .summary-row.total {
            border-top: 2px solid #E2E8F0;
            margin-top: 16px;
            padding-top: 16px;
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: #3B82F6;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 24px;
        }
        
        .btn-checkout:hover {
            background: #2563EB;
            transform: translateY(-2px);
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .shipping-info {
            background: #DBEAFE;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            font-size: 14px;
            color: #1E40AF;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">DistroZone</div>
            <div>Checkout</div>
        </div>
    </nav>
    
    <div class="container">
        <?php if(isset($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(empty($cart_items)): ?>
            <div class="checkout-card" style="text-align: center; padding: 60px;">
                <i class="fas fa-shopping-cart" style="font-size: 64px; color: #CBD5E1; margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">Keranjang Kosong</h3>
                <p style="color: #64748B;">Yuk mulai belanja!</p>
                <a href="shop.php" style="display: inline-block; margin-top: 24px; padding: 12px 24px; background: #3B82F6; color: white; text-decoration: none; border-radius: 10px;">Lihat Produk</a>
            </div>
        <?php else: ?>
        
        <form method="POST">
            <div class="checkout-grid">
                <!-- Left: Cart Items & Shipping -->
                <div>
                    <div class="checkout-card">
                        <h2 class="card-title">Pesanan Anda</h2>
                        
                        <?php foreach($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../assets/uploads/products/<?php echo $item['foto'] ?: 'default.jpg'; ?>" 
                                 alt="<?php echo $item['nama_kaos']; ?>" 
                                 class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo $item['nama_kaos']; ?></div>
                                <div class="item-meta">
                                    Size: <?php echo $item['size']; ?> | Qty: <?php echo $item['qty']; ?>
                                </div>
                                <div class="item-price"><?php echo format_rupiah($item['harga'] * $item['qty']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="checkout-card" style="margin-top: 24px;">
                        <h2 class="card-title">Informasi Pengiriman</h2>
                        
                        <div class="form-group">
                            <label>Wilayah Pengiriman</label>
                            <select name="shipping_city" id="shipping_city" required onchange="calculateShipping()">
                                <option value="">Pilih wilayah...</option>
                                <?php foreach($shipping_rates as $rate): ?>
                                    <option value="<?php echo $rate['wilayah']; ?>" data-cost="<?php echo $rate['cost_per_kg']; ?>">
                                        <?php echo $rate['wilayah']; ?> - <?php echo format_rupiah($rate['cost_per_kg']); ?>/kg
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <select name="payment_method" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="cod">Cash on Delivery (COD)</option>
                            </select>
                        </div>
                        
                        <div class="shipping-info">
                            <strong>Info Pengiriman:</strong><br>
                            Berat: <?php echo $total_weight; ?> kg (<?php echo array_sum(array_column($cart_items, 'qty')); ?> pcs kaos)<br>
                            <small>*1 kg = 3 kaos</small>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Order Summary -->
                <div>
                    <div class="checkout-card">
                        <h2 class="card-title">Ringkasan Pesanan</h2>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal"><?php echo format_rupiah($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Ongkir (<?php echo $total_weight; ?> kg)</span>
                            <span id="shipping">Rp 0</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="grandTotal"><?php echo format_rupiah($subtotal); ?></span>
                        </div>
                        
                        <button type="submit" class="btn-checkout">
                            <i class="fas fa-lock"></i> Bayar Sekarang
                        </button>
                        
                        <div style="text-align: center; margin-top: 16px; font-size: 13px; color: #64748B;">
                            <i class="fas fa-shield-alt"></i> Pembayaran aman & terpercaya
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <?php endif; ?>
    </div>
    
    <script>
        const subtotal = <?php echo $subtotal; ?>;
        const totalWeight = <?php echo $total_weight; ?>;
        
        function calculateShipping() {
            const select = document.getElementById('shipping_city');
            const selectedOption = select.options[select.selectedIndex];
            const costPerKg = selectedOption.getAttribute('data-cost');
            
            if (costPerKg) {
                const shippingCost = costPerKg * totalWeight;
                const grandTotal = subtotal + shippingCost;
                
                document.getElementById('shipping').textContent = formatRupiah(shippingCost);
                document.getElementById('grandTotal').textContent = formatRupiah(grandTotal);
            }
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>