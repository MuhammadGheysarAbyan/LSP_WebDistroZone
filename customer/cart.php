<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle Remove Item
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $cart_id = clean_input($_GET['id']);
    $query = "DELETE FROM cart WHERE id = :id AND customer_id = :uid";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $cart_id, ':uid' => $_SESSION['user_id']]);
    header("Location: cart.php");
    exit();
}

// Handle Update Quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $cart_id = clean_input($_POST['cart_id']);
    $qty = clean_input($_POST['qty']);
    if ($qty > 0) {
        $query = "UPDATE cart SET qty = :qty WHERE id = :id AND customer_id = :uid";
        $stmt = $conn->prepare($query);
        $stmt->execute([':qty' => $qty, ':id' => $cart_id, ':uid' => $_SESSION['user_id']]);
    }
    header("Location: cart.php");
    exit();
}

// Get Cart Items
$query = "SELECT c.id as cart_id, c.qty, v.id as kaos_id, m.nama_kaos, v.harga, v.foto_varian as foto, v.stok, v.warna, v.size 
          FROM cart c 
          INNER JOIN kaos_varian v ON c.kaos_id = v.id 
          INNER JOIN kaos_master m ON v.kaos_master_id = m.id
          WHERE c.customer_id = :uid 
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['harga'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
        
        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255,255,255,0.3);
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
        
        .btn-back {
            color: #64748B;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .btn-back:hover {
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 30px;
            color: var(--dark);
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        .cart-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255,255,255,0.6);
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 24px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .cart-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 16px;
            background: #F1F5F9;
        }

        .item-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .item-meta {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
        }

        .item-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-family: inherit;
        }

        .btn-update {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-remove {
            color: #EF4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s;
        }
        
        .btn-remove:hover {
            color: #DC2626;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 15px;
            color: #475569;
        }

        .summary-total {
            border-top: 2px dashed #E2E8F0;
            margin-top: 20px;
            padding-top: 20px;
            font-size: 20px;
            font-weight: 800;
            justify-content: space-between;
            display: flex;
            color: var(--dark);
        }

        .btn-checkout {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 24px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
            gap: 8px;
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-cart i {
            font-size: 72px;
            background: linear-gradient(135deg, #CBD5E1 0%, #94A3B8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .cart-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">DistroZone</a>
            <a href="shop.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Lanjut Belanja
            </a>
        </div>
    </nav>
    
    <div class="container">
        <h1 class="page-title">Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="cart-card empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <h2>Keranjang Belanja Kosong</h2>
                <p style="color: #64748B; margin: 12px 0 32px;">Belum ada item yang ditambahkan. Yuk mulai belanja!</p>
                <a href="shop.php" class="btn-checkout" style="max-width: 250px; margin: 0 auto;">
                    Shop Now
                </a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <!-- Items List -->
                <div class="cart-card">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../assets/uploads/products/<?php echo $item['foto'] ? $item['foto'] : 'default.jpg'; ?>" 
                                 class="item-image" alt="<?php echo $item['nama_kaos']; ?>">
                            
                            <div class="item-details" style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div class="item-name"><?php echo $item['nama_kaos']; ?></div>
                                        <div class="item-meta">
                                            <span><i class="fas fa-palette"></i> <?php echo $item['warna']; ?></span>
                                            <span><i class="fas fa-ruler"></i> <?php echo $item['size']; ?></span>
                                        </div>
                                    </div>
                                    <div class="item-price"><?php echo format_rupiah($item['harga']); ?></div>
                                </div>
                                
                                <div class="item-actions">
                                    <form method="POST" class="qty-controls">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" name="update_cart" class="btn-update" title="Update Qty"><i class="fas fa-sync-alt"></i></button>
                                        <input type="number" name="qty" value="<?php echo $item['qty']; ?>" 
                                               min="1" max="<?php echo $item['stok']; ?>" class="qty-input">
                                    </form>
                                    
                                    <a href="?remove=1&id=<?php echo $item['cart_id']; ?>" class="btn-remove" 
                                       onclick="return confirm('Hapus item ini?')">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div style="position: sticky; top: 120px; height: fit-content;">
                    <div class="cart-card">
                        <h3 style="margin-bottom: 24px; font-size: 20px; color: var(--dark);">Order Summary</h3>
                        
                        <div class="summary-row">
                            <span>Total Items</span>
                            <span style="font-weight: 600;"><?php echo array_sum(array_column($cart_items, 'qty')); ?> items</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total Payment</span>
                            <span style="color: var(--primary);"><?php echo format_rupiah($subtotal); ?></span>
                        </div>
                        
                        <a href="checkout.php" class="btn-checkout">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </a>
                        
                        <div style="margin-top: 20px; text-align: center; color: #64748B; font-size: 13px;">
                            <i class="fas fa-lock"></i> Secure Checkout
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
