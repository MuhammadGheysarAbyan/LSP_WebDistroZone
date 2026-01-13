<?php
session_start();
require_once '../config/database.php';

// Helper functions
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Inisialisasi database
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch(Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Get filters dengan sanitasi
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$size = isset($_GET['size']) ? clean_input($_GET['size']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'terbaru';

// Query untuk produk
$query = "SELECT k.*, kat.nama_kategori 
          FROM KAOS k 
          LEFT JOIN KATEGORI kat ON k.kategori_id = kat.id 
          WHERE k.stok > 0";

$params = [];

if (!empty($search)) {
    $query .= " AND (k.nama_kaos LIKE :search OR k.merek LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($kategori > 0) {
    $query .= " AND k.kategori_id = :kategori";
    $params[':kategori'] = $kategori;
}

if (!empty($size)) {
    $query .= " AND k.size = :size";
    $params[':size'] = $size;
}

// Sorting
switch($sort) {
    case 'termurah':
        $query .= " ORDER BY k.harga ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY k.harga DESC";
        break;
    case 'nama':
        $query .= " ORDER BY k.nama_kaos ASC";
        break;
    default:
        $query .= " ORDER BY k.created_at DESC";
}

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
}

// Get categories
try {
    $stmt_kat = $conn->query("SELECT * FROM KATEGORI ORDER BY nama_kategori");
    $categories = $stmt_kat->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_cart = $conn->prepare("SELECT SUM(qty) as total FROM CART WHERE customer_id = :customer_id");
        $stmt_cart->execute([':customer_id' => $_SESSION['user_id']]);
        $cart_result = $stmt_cart->fetch(PDO::FETCH_ASSOC);
        $cart_count = $cart_result['total'] ?? 0;
    } catch(PDOException $e) {
        // Error silent
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - DistroZone</title>
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
        
        /* Navbar - TANPA MENU HAMBURGER */
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-content {
            max-width: 1400px;
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
            text-decoration: none;
            display: inline-block;
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
            transition: color 0.3s;
            padding: 8px 0;
            font-size: 15px;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .nav-links a.active {
            color: #667eea;
            font-weight: 600;
        }
        
        .cart-icon {
            position: relative;
            text-decoration: none;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #EF4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
            min-height: calc(100vh - 80px);
        }
        
        .shop-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
        }
        
        /* Sidebar Filters */
        .filters-sidebar {
            background: white;
            border-radius: 16px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 88px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            margin-bottom: 28px;
        }
        
        .filter-title {
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 16px;
            font-size: 15px;
        }
        
        .filter-option {
            padding: 10px 0;
            cursor: pointer;
            color: #64748B;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .filter-option:hover {
            color: #667eea;
        }
        
        .filter-option.active {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Products Area */
        .shop-header {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .results-count {
            color: #64748B;
            font-size: 15px;
        }
        
        .sort-select {
            padding: 10px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            color: #334155;
        }
        
        .search-bar {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 15px;
            margin-bottom: 20px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%2364748B" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>') no-repeat 16px center;
            transition: border-color 0.3s;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 280px;
            background-size: cover;
            background-position: center;
            background-color: #f1f5f9;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-category {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .product-meta {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 12px;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }
        
        .product-price {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
        }
        
        .btn-add-cart {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-add-cart:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
            background: #CBD5E1;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #CBD5E1;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin-bottom: 12px;
            color: #1E293B;
        }
        
        .empty-state p {
            color: #64748B;
            margin-bottom: 24px;
        }
        
        .stok-info {
            font-size: 13px;
            color: #64748B;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .stok-info .tersedia {
            color: #10B981;
            font-weight: 600;
        }
        
        .stok-info .habis {
            color: #EF4444;
            font-weight: 600;
        }
        
        .size-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #E2E8F0;
            color: #64748B;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 6px 12px;
            background: #F1F5F9;
            border: none;
            border-radius: 8px;
            color: #64748B;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .filter-btn:hover {
            background: #E2E8F0;
            text-decoration: none;
        }
        
        .filter-btn.active {
            background: #667eea;
            color: white;
        }
        
        /* Responsive - TANPA MENU HAMBURGER */
        @media (max-width: 1024px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: relative;
                top: 0;
            }
            
            .nav-links {
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
            
            .navbar-content {
                padding: 0 16px;
                flex-direction: column;
                gap: 16px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 16px;
            }
            
            .container {
                padding: 24px 16px;
            }
            
            .shop-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .sort-select {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-footer {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .btn-add-cart {
                width: 100%;
                justify-content: center;
            }
            
            .nav-links {
                font-size: 14px;
                gap: 12px;
            }
            
            .nav-links a {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar - TANPA MENU HAMBURGER -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">DistroZone</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="shop.php" class="active">Shop</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="orders.php">Pesanan</a>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php">Login</a>
                    <a href="../auth/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <div class="shop-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <div class="filter-section">
                    <h3 class="filter-title">Kategori</h3>
                    <a href="shop.php" class="filter-option <?php echo $kategori == 0 ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Semua Kategori
                    </a>
                    <?php foreach($categories as $kat): ?>
                        <a href="?kategori=<?php echo $kat['id']; ?><?php echo !empty($size) ? '&size='.$size : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $kategori == $kat['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Size</h3>
                    <div class="filter-buttons">
                        <a href="shop.php<?php echo $kategori > 0 ? '?kategori='.$kategori : ''; ?><?php echo !empty($search) ? (($kategori > 0 ? '&' : '?').'search='.urlencode($search)) : ''; ?>" 
                           class="filter-btn <?php echo empty($size) ? 'active' : ''; ?>">
                            Semua
                        </a>
                        <?php 
                        $sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
                        foreach($sizes as $s): ?>
                            <a href="?size=<?php echo $s; ?><?php echo $kategori > 0 ? '&kategori='.$kategori : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                               class="filter-btn <?php echo $size == $s ? 'active' : ''; ?>">
                                <?php echo $s; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Reset Filter</h3>
                    <a href="shop.php" class="btn-add-cart" style="width: 100%; justify-content: center; padding: 12px; text-decoration: none;">
                        <i class="fas fa-redo"></i> Reset Semua Filter
                    </a>
                </div>
            </aside>
            
            <!-- Products Area -->
            <div class="products-area">
                <div class="shop-header">
                    <div class="results-count">
                        <?php echo count($products); ?> produk ditemukan
                    </div>
                    <form method="GET" style="display: flex; gap: 12px; align-items: center;">
                        <?php if($kategori > 0): ?>
                            <input type="hidden" name="kategori" value="<?php echo $kategori; ?>">
                        <?php endif; ?>
                        <?php if(!empty($size)): ?>
                            <input type="hidden" name="size" value="<?php echo $size; ?>">
                        <?php endif; ?>
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                            <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                            <option value="nama" <?php echo $sort == 'nama' ? 'selected' : ''; ?>>Nama A-Z</option>
                        </select>
                    </form>
                </div>
                
                <form method="GET" id="searchForm">
                    <?php if($kategori > 0): ?>
                        <input type="hidden" name="kategori" value="<?php echo $kategori; ?>">
                    <?php endif; ?>
                    <?php if(!empty($size)): ?>
                        <input type="hidden" name="size" value="<?php echo $size; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="search-bar" 
                           placeholder="Cari produk berdasarkan nama atau merek..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="display: none;"></button>
                </form>
                
                <?php if(empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Tidak ada produk yang sesuai dengan kriteria pencarian Anda</p>
                        <a href="shop.php" style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 16px;">
                            <i class="fas fa-redo"></i> Tampilkan Semua Produk
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach($products as $product): 
                            $stok_class = $product['stok'] > 0 ? 'tersedia' : 'habis';
                            $stok_text = $product['stok'] > 0 ? $product['stok'].' pcs tersedia' : 'Stok habis';
                        ?>
                        <div class="product-card">
                            <div class="product-image" style="background-image: url('../assets/uploads/products/<?php echo !empty($product['foto']) ? htmlspecialchars($product['foto']) : 'default.jpg'; ?>');"></div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($product['nama_kategori']); ?></div>
                                <div class="product-name"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                                <div class="product-meta">
                                    <div style="margin-bottom: 8px;">
                                        <span style="color: #667eea; font-weight: 500;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['merek']); ?>
                                        </span>
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <span style="color: #64748B;">
                                            <i class="fas fa-palette"></i> <?php echo htmlspecialchars($product['warna']); ?>
                                        </span>
                                        <span class="size-badge">
                                            <i class="fas fa-ruler"></i> <?php echo htmlspecialchars($product['size']); ?>
                                        </span>
                                    </div>
                                    <div class="stok-info">
                                        <i class="fas fa-box"></i>
                                        <span class="<?php echo $stok_class; ?>"><?php echo $stok_text; ?></span>
                                    </div>
                                </div>
                                <div class="product-footer">
                                    <div class="product-price"><?php echo format_rupiah($product['harga']); ?></div>
                                    <button class="btn-add-cart" 
                                            onclick="addToCart(<?php echo $product['id']; ?>, this)"
                                            <?php echo $product['stok'] <= 0 ? 'disabled' : ''; ?>
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['nama_kaos']); ?>">
                                        <i class="fas fa-cart-plus"></i> 
                                        <?php echo $product['stok'] > 0 ? 'Tambah' : 'Habis'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Add to Cart Function
        async function addToCart(productId, button) {
            <?php if(!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu untuk menambahkan ke keranjang!');
                window.location.href = '../auth/login.php';
                return;
            <?php endif; ?>
            
            const productName = button.getAttribute('data-product-name');
            
            // Disable button and show loading
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('kaos_id', productId);
                formData.append('qty', 1);
                
                const response = await fetch('../api/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if(data.success) {
                    // Update cart count in navbar
                    updateCartCount();
                    
                    // Show success message
                    showNotification(`${productName} berhasil ditambahkan ke keranjang!`);
                    
                    // Update button state
                    button.innerHTML = '<i class="fas fa-check"></i> Ditambahkan';
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 2000);
                    
                } else {
                    alert(data.message);
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang');
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        }
        
        // Update cart count in navbar
        function updateCartCount() {
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                const currentCount = parseInt(cartBadge.textContent) || 0;
                cartBadge.textContent = currentCount + 1;
            } else {
                // Create badge if doesn't exist
                const cartIcon = document.querySelector('.cart-icon');
                if (cartIcon) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = '1';
                    cartIcon.appendChild(badge);
                }
            }
        }
        
        // Show notification
        function showNotification(message) {
            // Remove existing notification
            const existingNotification = document.querySelector('.custom-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'custom-notification';
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #10B981;
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 400px;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Add animation styles for notification
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Search with debounce
        let searchTimeout;
        const searchInput = document.querySelector('.search-bar');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('searchForm').submit();
                }, 500);
            });
        }
        
        // Auto focus search input
        document.addEventListener('DOMContentLoaded', function() {
            if (searchInput && searchInput.value === '') {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>