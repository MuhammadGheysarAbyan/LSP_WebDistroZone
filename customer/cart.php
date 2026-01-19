<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    // Update Quantity
    if (isset($_POST['action']) && $_POST['action'] == 'update_qty') {
        $cart_id = clean_input($_POST['cart_id']);
        $qty = (int)clean_input($_POST['qty']);
        
        if ($qty > 0) {
            $stmt = $conn->prepare("UPDATE cart SET qty = :qty WHERE id = :id AND customer_id = :uid");
            $stmt->execute([':qty' => $qty, ':id' => $cart_id, ':uid' => $_SESSION['user_id']]);
            
            // Get updated item price
            $stmt2 = $conn->prepare("SELECT v.harga FROM cart c JOIN kaos_varian v ON c.kaos_id = v.id WHERE c.id = :id");
            $stmt2->execute([':id' => $cart_id]);
            $item = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            // Get new totals
            $stmt3 = $conn->prepare("SELECT SUM(v.harga * c.qty) as subtotal, SUM(c.qty) as total_items 
                                     FROM cart c JOIN kaos_varian v ON c.kaos_id = v.id 
                                     WHERE c.customer_id = :uid");
            $stmt3->execute([':uid' => $_SESSION['user_id']]);
            $totals = $stmt3->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'item_total' => $item['harga'] * $qty,
                'subtotal' => $totals['subtotal'],
                'total_items' => $totals['total_items']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Qty harus lebih dari 0']);
        }
        exit;
    }
    
    // Change Variant (Size/Color)
    if (isset($_POST['action']) && $_POST['action'] == 'change_variant') {
        $cart_id = clean_input($_POST['cart_id']);
        $new_variant_id = (int)clean_input($_POST['variant_id']);
        
        // Get new variant info
        $stmt = $conn->prepare("SELECT * FROM kaos_varian WHERE id = :id");
        $stmt->execute([':id' => $new_variant_id]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($variant && $variant['stok'] > 0) {
            // Update cart with new variant
            $stmt2 = $conn->prepare("UPDATE cart SET kaos_id = :vid WHERE id = :cid AND customer_id = :uid");
            $stmt2->execute([':vid' => $new_variant_id, ':cid' => $cart_id, ':uid' => $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'variant' => $variant,
                'message' => 'Varian berhasil diubah'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Varian tidak tersedia']);
        }
        exit;
    }
}

// Handle Remove Item
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $cart_id = clean_input($_GET['id']);
    $query = "DELETE FROM cart WHERE id = :id AND customer_id = :uid";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $cart_id, ':uid' => $_SESSION['user_id']]);
    header("Location: cart.php");
    exit();
}

// Get Cart Items with master_id for variant lookup
$query = "SELECT c.id as cart_id, c.qty, v.id as kaos_id, v.kaos_master_id, m.nama_kaos, v.harga, v.foto_varian as foto, v.stok, v.warna, v.size 
          FROM cart c 
          INNER JOIN kaos_varian v ON c.kaos_id = v.id 
          INNER JOIN kaos_master m ON v.kaos_master_id = m.id
          WHERE c.customer_id = :uid 
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all variants for each product in cart (for dropdowns)
$variants_by_master = [];
foreach ($cart_items as $item) {
    if (!isset($variants_by_master[$item['kaos_master_id']])) {
        $stmt = $conn->prepare("SELECT id, warna, warna_hex, size, harga, stok, foto_varian FROM kaos_varian WHERE kaos_master_id = :mid AND stok > 0");
        $stmt->execute([':mid' => $item['kaos_master_id']]);
        $variants_by_master[$item['kaos_master_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #10B981;
            --secondary: #0F766E;
            --dark: #1F2937;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ECFDF5;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            color: #334155;
            min-height: 100vh;
        }
        
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
        
        .btn-back:hover { color: var(--primary); }

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

        .cart-item:last-child { border-bottom: none; padding-bottom: 0; }

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
            flex-wrap: wrap;
        }

        .item-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        .variant-select {
            padding: 6px 10px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .variant-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #E5E7EB;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--dark);
            transition: all 0.2s;
        }
        
        .qty-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .qty-input {
            width: 50px;
            padding: 6px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-family: inherit;
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
            background: none;
            border: none;
            cursor: pointer;
        }
        
        .btn-remove:hover { color: #DC2626; }

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
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
            gap: 8px;
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
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
        
        .item-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .cart-grid { grid-template-columns: 1fr; }
            .cart-item { flex-direction: column; }
            .item-image { width: 100%; height: 200px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">
                <i class="fas fa-layer-group"></i>
                DistroZone
            </a>
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
                    <?php foreach ($cart_items as $item): 
                        $variants = $variants_by_master[$item['kaos_master_id']];
                        // Get unique colors and sizes
                        $colors = [];
                        $sizes = [];
                        foreach ($variants as $v) {
                            if (!in_array($v['warna'], array_column($colors, 'warna'))) {
                                $colors[] = ['warna' => $v['warna'], 'warna_hex' => $v['warna_hex']];
                            }
                            if (!in_array($v['size'], $sizes)) {
                                $sizes[] = $v['size'];
                            }
                        }
                    ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" 
                             data-price="<?php echo $item['harga']; ?>"
                             data-master-id="<?php echo $item['kaos_master_id']; ?>">
                            <img src="../<?php echo $item['foto'] ? $item['foto'] : 'assets/img/no-image.jpg'; ?>" 
                                 class="item-image" id="img-<?php echo $item['cart_id']; ?>" 
                                 alt="<?php echo $item['nama_kaos']; ?>">
                            
                            <div class="item-details" style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                                    <div>
                                        <div class="item-name"><?php echo $item['nama_kaos']; ?></div>
                                        <div class="item-meta">
                                            <span>
                                                <i class="fas fa-palette"></i>
                                                <select class="variant-select color-select" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                    <?php foreach ($colors as $c): ?>
                                                        <option value="<?php echo $c['warna']; ?>" 
                                                                <?php echo $c['warna'] == $item['warna'] ? 'selected' : ''; ?>>
                                                            <?php echo $c['warna']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </span>
                                            <span>
                                                <i class="fas fa-ruler"></i>
                                                <select class="variant-select size-select" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                    <?php 
                                                    // Get sizes for current color
                                                    $current_sizes = array_filter($variants, fn($v) => $v['warna'] == $item['warna']);
                                                    foreach ($current_sizes as $v): ?>
                                                        <option value="<?php echo $v['size']; ?>" 
                                                                data-variant-id="<?php echo $v['id']; ?>"
                                                                data-price="<?php echo $v['harga']; ?>"
                                                                data-foto="<?php echo $v['foto_varian']; ?>"
                                                                <?php echo $v['size'] == $item['size'] ? 'selected' : ''; ?>>
                                                            <?php echo $v['size']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="item-price" id="price-<?php echo $item['cart_id']; ?>">
                                        <?php echo format_rupiah($item['harga'] * $item['qty']); ?>
                                    </div>
                                </div>
                                
                                <div class="item-actions">
                                    <div class="qty-controls">
                                        <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['cart_id']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="qty-input" id="qty-<?php echo $item['cart_id']; ?>" 
                                               value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stok']; ?>"
                                               onchange="updateQty(<?php echo $item['cart_id']; ?>)">
                                        <button type="button" class="qty-btn" onclick="changeQty(<?php echo $item['cart_id']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
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
                            <span style="font-weight: 600;" id="total-items"><?php echo array_sum(array_column($cart_items, 'qty')); ?> items</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total Payment</span>
                            <span style="color: var(--primary);" id="subtotal"><?php echo format_rupiah($subtotal); ?></span>
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
    
    <script>
        const allVariants = <?php echo json_encode($variants_by_master); ?>;
        
        function formatRupiah(num) {
            return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        function changeQty(cartId, delta) {
            const input = document.getElementById('qty-' + cartId);
            let newVal = parseInt(input.value) + delta;
            if (newVal < 1) newVal = 1;
            if (newVal > parseInt(input.max)) newVal = parseInt(input.max);
            input.value = newVal;
            updateQty(cartId);
        }
        
        function updateQty(cartId) {
            const qty = document.getElementById('qty-' + cartId).value;
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_qty&cart_id=${cartId}&qty=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('price-' + cartId).textContent = formatRupiah(data.item_total);
                    document.getElementById('subtotal').textContent = formatRupiah(data.subtotal);
                    document.getElementById('total-items').textContent = data.total_items + ' items';
                }
            });
        }
        
        // Handle color change
        document.querySelectorAll('.color-select').forEach(select => {
            select.addEventListener('change', function() {
                const cartId = this.dataset.cartId;
                const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                const masterId = cartItem.dataset.masterId;
                const selectedColor = this.value;
                
                // Update size options for selected color
                const sizeSelect = document.querySelector(`.size-select[data-cart-id="${cartId}"]`);
                const variants = allVariants[masterId] ? allVariants[masterId].filter(v => v.warna === selectedColor) : [];
                
                if (variants.length === 0) {
                    Swal.fire('Info', 'Tidak ada stok untuk warna ini', 'info');
                    return;
                }
                
                sizeSelect.innerHTML = '';
                variants.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.size;
                    opt.textContent = v.size;
                    opt.dataset.variantId = v.id;
                    opt.dataset.price = v.harga;
                    opt.dataset.foto = v.foto_varian || '';
                    sizeSelect.appendChild(opt);
                });
                
                // Trigger size change to update variant
                sizeSelect.dispatchEvent(new Event('change'));
            });
        });
        
        // Handle size change
        document.querySelectorAll('.size-select').forEach(select => {
            select.addEventListener('change', function() {
                const cartId = this.dataset.cartId;
                const selectedOption = this.options[this.selectedIndex];
                
                if (!selectedOption || !selectedOption.dataset.variantId) {
                    console.log('No valid variant selected');
                    return;
                }
                
                const variantId = selectedOption.dataset.variantId;
                const newPrice = selectedOption.dataset.price;
                const newFoto = selectedOption.dataset.foto;
                
                // Update cart with new variant
                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=change_variant&cart_id=${cartId}&variant_id=${variantId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update image
                        const img = document.getElementById('img-' + cartId);
                        if (newFoto) {
                            img.src = '../' + newFoto;
                        }
                        
                        // Update price
                        const qty = document.getElementById('qty-' + cartId).value;
                        document.getElementById('price-' + cartId).textContent = formatRupiah(newPrice * qty);
                        
                        // Recalculate total
                        updateQty(cartId);
                        
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Varian diubah',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Error', 'Terjadi kesalahan', 'error');
                });
            });
        });
    </script>
</body>
</html>
