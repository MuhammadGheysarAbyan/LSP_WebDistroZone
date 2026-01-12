<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get filters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$size = isset($_GET['size']) ? $_GET['size'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Build query
$query = "SELECT k.*, kat.nama_kategori 
          FROM kaos k 
          LEFT JOIN kategori kat ON k.kategori_id = kat.id 
          WHERE k.stok > 0";

$params = [];

if ($search) {
    $query .= " AND (k.nama_kaos LIKE :search OR k.merek LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($kategori) {
    $query .= " AND k.kategori_id = :kategori";
    $params[':kategori'] = $kategori;
}

if ($size) {
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

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$query_kat = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt_kat = $conn->query($query_kat);
$categories = $stmt_kat->fetchAll(PDO::FETCH_ASSOC);

// Get cart count if logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $query_cart = "SELECT SUM(qty) as total FROM cart WHERE customer_id = :customer_id";
    $stmt_cart = $conn->prepare($query_cart);
    $stmt_cart->execute([':customer_id' => $_SESSION['user_id']]);
    $cart_result = $stmt_cart->fetch(PDO::FETCH_ASSOC);
    $cart_count = $cart_result['total'] ?? 0;
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
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .cart-icon {
            position: relative;
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
        }
        
        .filter-option:hover {
            color: #667eea;
        }
        
        .filter-option.active {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Products Area */
        .products-area {
            
        }
        
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
        }
        
        .sort-select {
            padding: 10px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
        }
        
        .search-bar {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 15px;
            margin-bottom: 20px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%2364748B" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>') no-repeat 16px center;
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
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
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
        
        @media (max-width: 1024px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: relative;
                top: 0;
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
                    <a href="shop.php" class="filter-option <?php echo empty($kategori) ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Semua Kategori
                    </a>
                    <?php foreach($categories as $kat): ?>
                        <a href="?kategori=<?php echo $kat['id']; ?>" 
                           class="filter-option <?php echo $kategori == $kat['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-tag"></i> <?php echo $kat['nama_kategori']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Size</h3>
                    <?php 
                    $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                    foreach($sizes as $s): ?>
                        <a href="?size=<?php echo $s; ?><?php echo $kategori ? '&kategori='.$kategori : ''; ?>" 
                           class="filter-option <?php echo $size == $s ? 'active' : ''; ?>">
                            <i class="fas fa-ruler"></i> Size <?php echo $s; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
            
            <!-- Products Area -->
            <div class="products-area">
                <div class="shop-header">
                    <div class="results-count">
                        Menampilkan <?php echo count($products); ?> produk
                    </div>
                    <form method="GET" style="display: flex; gap: 12px;">
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                            <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                            <option value="nama" <?php echo $sort == 'nama' ? 'selected' : ''; ?>>Nama A-Z</option>
                        </select>
                    </form>
                </div>
                
                <form method="GET">
                    <input type="text" name="search" class="search-bar" 
                           placeholder="Cari produk..." 
                           value="<?php echo $search; ?>">
                </form>
                
                <?php if(empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Coba kata kunci atau filter lain</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach($products as $product): ?>
                        <div class="product-card" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                            <div class="product-image" style="background: url('../assets/uploads/products/<?php echo $product['foto'] ?: 'default.jpg'; ?>') center/cover;"></div>
                            <div class="product-info">
                                <div class="product-category"><?php echo $product['nama_kategori']; ?></div>
                                <div class="product-name"><?php echo $product['nama_kaos']; ?></div>
                                <div class="product-meta">
                                    <?php echo $product['merek']; ?> | <?php echo $product['warna']; ?> | Size <?php echo $product['size']; ?>
                                </div>
                                <div class="product-footer">
                                    <div class="product-price"><?php echo format_rupiah($product['harga']); ?></div>
                                    <button class="btn-add-cart" onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add
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
        function addToCart(productId) {
            <?php if(!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu!');
                window.location.href = '../auth/login.php';
                return;
            <?php endif; ?>
            
            fetch('../api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    qty: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Produk ditambahkan ke keranjang!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>