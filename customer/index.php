<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch 6 featured products with starting price
$query = "SELECT k.*, kat.nama_kategori, MIN(v.harga) as starting_price
          FROM kaos_master k 
          LEFT JOIN kategori kat ON k.kategori_id = kat.id 
          LEFT JOIN kaos_varian v ON k.id = v.kaos_master_id
          GROUP BY k.id
          ORDER BY k.created_at DESC 
          LIMIT 6";
$stmt = $conn->query($query);
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare variants data for JS
$variants_data = [];
foreach ($featured_products as $p) {
    $q = "SELECT id, warna, warna_hex, size, harga, foto_varian as foto, stok FROM kaos_varian WHERE kaos_master_id = :master_id";
    $st = $conn->prepare($q);
    $st->execute(['master_id' => $p['id']]);
    $variants_data[$p['id']] = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DistroZone - Style Your Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }
        
        :root {
            --primary: #10B981; /* Emerald 500 */
            --secondary: #0F766E; /* Teal 700 */
            --accent: #34D399; /* Emerald 400 */
            --dark: #1F2937;
            --light: #F9FAFB;
            --glass: rgba(255, 255, 255, 0.95);
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            background-color: #ECFDF5;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Navbar */
        .navbar {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            padding: 16px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border: 1px solid rgba(255,255,255,0.5);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            top: 0;
            width: 100%;
            max-width: 100%;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.95);
            padding: 16px 40px;
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
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: var(--primary);
            transition: width 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a:hover::after {
            width: 100%;
        }
        
        .nav-icons {
            display: flex;
            gap: 15px;
        }
        
        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s;
            background: rgba(255,255,255,0.5);
        }
        
        .btn-icon:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            padding: 120px 24px 60px;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -2px;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 18px;
            color: #64748B;
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 500px;
        }

        .hero-btns {
            display: flex;
            gap: 16px;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--dark);
            border: 2px solid #E5E7EB;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .hero-visual {
            position: relative;
        }

        .hero-card {
            background: white;
            padding: 20px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(255,255,255,0.5);
            position: relative;
            z-index: 2;
            transform: rotate(-3deg);
            transition: transform 0.3s;
            animation: float 6s ease-in-out infinite;
        }

        .hero-card:hover {
            transform: rotate(0) scale(1.02);
        }

        .hero-card img {
            width: 100%;
            border-radius: 16px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-20px) rotate(-3deg); }
        }

        .floating-badge {
            position: absolute;
            background: white;
            padding: 15px 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 3;
            animation: float-badge 5s ease-in-out infinite reverse;
        }

        .fb-1 { top: 40px; right: -20px; }
        .fb-2 { bottom: 40px; left: -40px; }

        @keyframes float-badge {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(15px); }
        }

        .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Products Section */
        .section {
            padding: 100px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .section-subtitle {
            font-size: 16px;
            color: #64748B;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Categories Section */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            height: 300px;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .category-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .category-card:hover .category-img {
            transform: scale(1.1);
        }
        
        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 30px 20px;
            color: white;
        }
        
        /* About Section */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            background: white;
            border-radius: 30px;
            padding: 60px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        }
        
        .about-text h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .about-text p {
            color: #64748B;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        /* Product Grid */
        .grid-products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #E5E7EB;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.1);
            border-color: var(--primary);
        }

        .product-img {
            height: 280px;
            background: #F3F4F6;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-img div {
            font-size: 64px;
            color: #CBD5E1;
        }

        .product-overlay {
            position: absolute;
            bottom: -60px;
            left: 0;
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            transition: bottom 0.3s;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(5px);
        }

        .product-card:hover .product-overlay {
            bottom: 0;
        }

        .product-content {
            padding: 24px;
        }

        .product-category {
            font-size: 12px;
            text-transform: uppercase;
            color: #6B7280;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--dark);
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Footer */
        footer {
            background: white;
            padding: 80px 24px 24px;
            margin-top: 100px;
            border-top: 1px solid #E5E7EB;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-brand h3 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .footer-brand p {
            color: #6B7280;
            line-height: 1.6;
        }

        .footer-links h4 {
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            text-decoration: none;
            color: #6B7280;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .copyright {
            text-align: center;
            color: #9CA3AF;
            font-size: 14px;
            padding-top: 24px;
            border-top: 1px solid #F3F4F6;
        }

        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding-top: 100px;
            }

            .hero-btns {
                justify-content: center;
            }
            
            .about-content {
                grid-template-columns: 1fr;
                padding: 30px;
            }
            
            .navbar {
                width: 100%;
                top: 0;
                border-radius: 0;
            }
            
            .nav-links {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* SweetAlert on top of modal */
        .swal-on-top {
            z-index: 3000 !important;
        }
        
        /* Quick View Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            width: 100%;
            max-width: 900px;
            border-radius: 30px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            animation: modalScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalScale {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: var(--primary);
            color: white;
            transform: rotate(90deg);
        }

        .modal-img-container {
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .modal-img-container img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 20px;
            object-fit: contain;
            transition: all 0.5s ease;
        }

        .modal-info {
            padding: 40px;
            display: flex;
            flex-direction: column;
        }

        .modal-merek {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .modal-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .modal-price {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
        }

        .modal-desc {
            color: #64748B;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 15px;
        }

        .variant-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: block;
        }

        .color-variants {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
            position: relative;
        }

        .color-option.active {
            border-color: var(--primary);
            transform: scale(1.1);
        }

        .color-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .color-option:hover .color-tooltip {
            opacity: 1;
            visibility: visible;
            bottom: 120%;
        }

        .size-variants {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            max-width: 300px; /* Force wrap around 5 items */
        }

        .size-option {
            padding: 8px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .size-option:hover, .size-option.active {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }

        .modal-actions {
            display: flex;
            gap: 16px;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .modal-content {
                grid-template-columns: 1fr;
                max-height: 90vh;
                overflow-y: auto;
            }
            .modal-info {
                padding: 30px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-layer-group"></i>
            DistroZone
        </a>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#featured">Produk</a>
            <a href="#categories">Kategori</a>
            <a href="#about">Tentang Kami</a>
        </div>
        <div class="nav-icons">
            <a href="shop.php" class="btn-icon">
                <i class="fas fa-search"></i>
            </a>
            <a href="cart.php" class="btn-icon" style="position: relative;">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-count" style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: 700;">
                    <?php 
                        $cc = 0;
                        if(isset($_SESSION['user_id'])) {
                            $stc = $conn->prepare("SELECT SUM(qty) as total FROM cart WHERE customer_id = :uid");
                            $stc->execute(['uid' => $_SESSION['user_id']]);
                            $res = $stc->fetch(PDO::FETCH_ASSOC);
                            $cc = $res['total'] ?? 0;
                        }
                        echo $cc;
                    ?>
                </span>
            </a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="orders.php" class="btn-icon" title="Pesanan Saya">
                    <i class="fas fa-box"></i>
                </a>
                <a href="settings.php" class="btn-icon" title="Pengaturan">
                    <i class="fas fa-cog"></i>
                </a>
                <a href="../auth/logout.php" class="btn-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn-icon" title="Login">
                    <i class="fas fa-user"></i>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-text">
            <span class="hero-badge">New Collection 2026</span>
            <h1 class="hero-title">Style Your <br><span>True Identity</span></h1>
            <p class="hero-desc">
                Temukan koleksi fashion distro terbaik dengan desain eksklusif dan bahan premium. Tampil beda dan percaya diri setiap hari.
            </p>
            <div class="hero-btns">
                <a href="#featured" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
                <a href="shop.php" class="btn btn-outline">
                    Explore All
                </a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="floating-badge fb-1">
                <div class="icon-box">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div style="font-weight: 700;">4.9/5</div>
                    <div style="font-size: 12px; color: #6B7280;">Happy Customers</div>
                </div>
            </div>
            
            <div class="floating-badge fb-2">
                <div class="icon-box" style="background: rgba(52, 211, 153, 0.2); color: var(--secondary);">
                    <i class="fas fa-fire"></i>
                </div>
                <div>
                    <div style="font-weight: 700;">Trending</div>
                    <div style="font-size: 12px; color: #6B7280;">Best Seller Items</div>
                </div>
            </div>

            <div class="hero-card">
                <!-- Product Image from database -->
                <?php if (!empty($featured_products) && !empty($featured_products[0]['foto_utama'])): ?>
                <img src="../<?php echo htmlspecialchars($featured_products[0]['foto_utama']); ?>" alt="Featured Product" style="width: 100%; height: 400px; object-fit: cover; border-radius: 16px;">
                <?php else: ?>
                <img src="../assets/img/distrozonelogo.png" alt="DistroZone" style="width: 100%; height: 400px; object-fit: contain; border-radius: 16px; background: #F3F4F6; padding: 40px;">
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section">
        <div class="grid-products" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); text-align: center;">
            <div style="padding: 20px;">
                <div class="icon-box" style="width: 60px; height: 60px; margin: 0 auto 20px; font-size: 24px;">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3 style="margin-bottom: 8px;">Fast Shipping</h3>
                <p style="color: #6B7280; font-size: 14px;">Pengiriman cepat ke seluruh Indonesia</p>
            </div>
            <div style="padding: 20px;">
                <div class="icon-box" style="width: 60px; height: 60px; margin: 0 auto 20px; font-size: 24px; background: rgba(52, 211, 153, 0.2); color: var(--secondary);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 style="margin-bottom: 8px;">Secure Payment</h3>
                <p style="color: #6B7280; font-size: 14px;">Transaksi aman dan terpercaya</p>
            </div>
            <div style="padding: 20px;">
                <div class="icon-box" style="width: 60px; height: 60px; margin: 0 auto 20px; font-size: 24px; background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class="fas fa-undo"></i>
                </div>
                <h3 style="margin-bottom: 8px;">Easy Return</h3>
                <p style="color: #6B7280; font-size: 14px;">Garansi pengembalian barang</p>
            </div>
            <div style="padding: 20px;">
                <div class="icon-box" style="width: 60px; height: 60px; margin: 0 auto 20px; font-size: 24px; background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 style="margin-bottom: 8px;">24/7 Support</h3>
                <p style="color: #6B7280; font-size: 14px;">Siap membantu Anda kapan saja</p>
            </div>
        </div>
    </section>

    <!-- Popular Products (Moved Up) -->
    <section class="section" id="featured">
        <div class="section-header">
            <h2 class="section-title">Pilihan Favorit</h2>
            <p class="section-subtitle">Produk-produk best seller yang paling diminati oleh pelanggan setia kami</p>
        </div>

        <div class="grid-products">
            <?php foreach ($featured_products as $product): ?>
            <div class="product-card">
                <div class="product-img">
                    <?php if ($product['foto_utama']): ?>
                        <img src="../<?php echo htmlspecialchars($product['foto_utama']); ?>" alt="<?php echo htmlspecialchars($product['nama_kaos']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php elseif (!empty($variants_data[$product['id']][0]['foto'])): ?>
                        <img src="../<?php echo htmlspecialchars($variants_data[$product['id']][0]['foto']); ?>" alt="<?php echo htmlspecialchars($product['nama_kaos']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-tshirt"></i>
                    <?php endif; ?>
                    <div class="product-overlay">
                        <button class="btn btn-primary" style="padding: 10px 20px; width: 100%; justify-content: center; cursor: pointer; border: none;" 
                                onclick='openQuickView(<?php echo json_encode($product); ?>)'>
                            Lihat Detail
                        </button>
                    </div>
                </div>
                <div class="product-content">
                    <div class="product-category"><?php echo htmlspecialchars($product['nama_kategori'] ?? 'Uncategorized'); ?></div>
                    <div class="product-title"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                    <div class="product-price">
                        <?php if ($product['starting_price']): ?>
                            Mulai <?php echo format_rupiah($product['starting_price']); ?>
                        <?php else: ?>
                            Coming Soon
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <a href="shop.php" class="btn btn-outline" style="padding: 12px 40px;">Lihat Semua Produk</a>
        </div>
    </section>
    
    <!-- Categories Section (Moved Down) -->
    <section class="section" id="categories">
        <div class="section-header">
            <h2 class="section-title">Kategori Populer</h2>
            <p class="section-subtitle">Jelajahi berbagai koleksi terbaik kami berdasarkan kategori favorit Anda</p>
        </div>
        
        <div class="categories-grid">
            <a href="shop.php?category=t-shirt" class="category-card">
                <div style="width:100%; height:100%; background:#d1fae5; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-tshirt" style="font-size:64px; color:#10B981; opacity:0.5;"></i>
                </div>
                <div class="category-overlay">
                    <h3>T-Shirt</h3>
                    <p>Koleksi kaos santai</p>
                </div>
            </a>
            <a href="shop.php?category=jaket" class="category-card">
                <div style="width:100%; height:100%; background:#ccfbf1; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-layer-group" style="font-size:64px; color:#0F766E; opacity:0.5;"></i>
                </div>
                <div class="category-overlay">
                    <h3>Jaket</h3>
                    <p>Outerwear premium</p>
                </div>
            </a>
            <a href="shop.php?category=kemeja" class="category-card">
                <div style="width:100%; height:100%; background:#ecfccb; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-user-tie" style="font-size:64px; color:#84cc16; opacity:0.5;"></i>
                </div>
                <div class="category-overlay">
                    <h3>Kemeja</h3>
                    <p>Tampil formal & casual</p>
                </div>
            </a>
            <a href="shop.php?category=aksesoris" class="category-card">
                 <div style="width:100%; height:100%; background:#f3f4f6; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-hat-cowboy" style="font-size:64px; color:#6b7280; opacity:0.5;"></i>
                </div>
                <div class="category-overlay">
                    <h3>Aksesoris</h3>
                    <p>Pelengkap gayamu</p>
                </div>
            </a>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="about-content">
            <div class="about-text">
                <h3>Tentang DistroZone</h3>
                <p>
                    DistroZone hadir sebagai destinasi fashion terdepan bagi mereka yang ingin mengekspresikan jati diri melalui gaya. Kami percaya bahwa setiap orang memiliki cerita unik, dan fashion adalah salah satu cara terbaik untuk menceritakannya.
                </p>
                <p>
                    Sejak 2024, kami berkomitmen untuk menghadirkan produk berkualitas tinggi dengan desain yang selalu up-to-date, menggunakan bahan ramah lingkungan dan proses produksi yang berkelanjutan.
                </p>
                <div style="margin-top: 30px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--primary); font-size: 20px;"></i>
                        <span>100% Produk Original</span>
                    </div>
                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--primary); font-size: 20px;"></i>
                        <span>Bahan Premium Cotton</span>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <i class="fas fa-check-circle" style="color: var(--primary); font-size: 20px;"></i>
                        <span>Desain Eksklusif Terbatas</span>
                    </div>
                </div>
            </div>
            <div class="about-image" style="height: 400px; border-radius: 20px; overflow: hidden;">
                <img src="../assets/img/team.jpg" alt="DistroZone Team" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <h3>DistroZone</h3>
                <p>DistroZone adalah platform e-commerce fashion yang menyediakan berbagai produk distro berkualitas tinggi dengan gaya modern dan urban.</p>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <a href="#" class="icon-box" style="width: 36px; height: 36px; font-size: 16px; background: #F3F4F6; color: var(--dark); text-decoration: none;"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="icon-box" style="width: 36px; height: 36px; font-size: 16px; background: #F3F4F6; color: var(--dark); text-decoration: none;"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="icon-box" style="width: 36px; height: 36px; font-size: 16px; background: #F3F4F6; color: var(--dark); text-decoration: none;"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Belanja</h4>
                <ul>
                    <li><a href="shop.php">Produk Terbaru</a></li>
                    <li><a href="#featured">Paling Laris</a></li>
                    <li><a href="shop.php?category=t-shirt">Kaos Distro</a></li>
                    <li><a href="shop.php?category=jaket">Jaket & Hoodie</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#">Metode Pembayaran</a></li>
                    <li><a href="#">Info Pengiriman</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; 2026 DistroZone. All rights reserved.
        </div>
    </footer>

    <script>
        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <div class="modal-close" onclick="closeQuickView()">
                <i class="fas fa-times"></i>
            </div>
            <div class="modal-img-container">
                <img id="modalImg" src="" alt="Product Image">
            </div>
            <div class="modal-info">
                <div id="modalMerek" class="modal-merek">MEREK</div>
                <h2 id="modalTitle" class="modal-title">Product Name</h2>
                <div id="modalPrice" class="modal-price">Rp 0</div>
                <div id="modalStock" class="modal-stock" style="margin-bottom: 20px; font-size: 14px; color: #64748B;">
                    <i class="fas fa-boxes"></i> Stok: <span id="stockValue">0</span>
                </div>
                <p id="modalDesc" class="modal-desc">Product description goes here.</p>
                
                <div class="variant-selection">
                    <span class="variant-label">Warna</span>
                    <div id="colorContainer" class="color-variants">
                        <!-- Colors will be added here -->
                    </div>

                    <span class="variant-label">Ukuran</span>
                    <div id="sizeContainer" class="size-variants">
                        <!-- Sizes will be added here -->
                    </div>
                </div>

                <div class="modal-actions" style="display: flex; gap: 12px;">
                    <button class="btn btn-primary" style="flex: 1; border: none; cursor: pointer;" onclick="addToCartFromModal()">
                        <i class="fas fa-shopping-cart"></i>  Tambahkan Keranjang
                    </button>
                    <button class="btn btn-primary" style="flex: 1; border: none; cursor: pointer;" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> Beli Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allVariants = <?php echo json_encode($variants_data); ?>;
        let currentProduct = null;
        let selectedVariant = null;

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        function openQuickView(product) {
            currentProduct = product;
            const modal = document.getElementById('quickViewModal');
            const variants = allVariants[product.id];
            
            document.getElementById('modalTitle').innerText = product.nama_kaos;
            document.getElementById('modalMerek').innerText = product.merek || 'DistroZone';
            document.getElementById('modalDesc').innerText = product.deskripsi || 'Kaos premium dengan desain eksklusif, nyaman digunakan untuk aktivitas sehari-hari.';
            
            // Render color options
            const colorContainer = document.getElementById('colorContainer');
            colorContainer.innerHTML = '';
            
            // Get unique colors with their hex codes
            const uniqueColors = [];
            const colorMap = new Map();
            variants.forEach(v => {
                const cName = v.warna;
                const cHex = v.warna_hex || '#CBD5E1';
                if (!colorMap.has(cName)) {
                    colorMap.set(cName, cHex);
                    uniqueColors.push({ name: cName, hex: cHex });
                }
            });
            
            uniqueColors.forEach((colorObj, index) => {
                const opt = document.createElement('div');
                opt.className = 'color-option' + (index === 0 ? ' active' : '');
                opt.style.backgroundColor = colorObj.hex;
                opt.style.border = '2px solid rgba(0,0,0,0.1)';
                opt.onclick = () => selectColor(colorObj.name, product.id, opt);
                
                const tooltip = document.createElement('span');
                tooltip.className = 'color-tooltip';
                tooltip.innerText = colorObj.name;
                opt.appendChild(tooltip);
                
                colorContainer.appendChild(opt);
            });
            
            // Initial selection (first color)
            if (uniqueColors.length > 0) {
                selectColor(uniqueColors[0].name, product.id, colorContainer.firstChild);
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }



        function selectColor(color, masterId, element) {
            // Update UI
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            const variants = allVariants[masterId];
            const colorVariants = variants.filter(v => v.warna === color);
            
            // Render sizes for this color
            const sizeContainer = document.getElementById('sizeContainer');
            sizeContainer.innerHTML = '';
            
            colorVariants.forEach((v, index) => {
                const opt = document.createElement('div');
                opt.className = 'size-option' + (index === 0 ? ' active' : '');
                opt.innerText = v.size;
                opt.onclick = () => selectSize(v, opt);
                sizeContainer.appendChild(opt);
            });
            
            // Initial size selection
            selectSize(colorVariants[0], sizeContainer.firstChild);
        }

        function selectSize(variant, element) {
            document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            selectedVariant = variant;
            
            // Update Modal Image and Price
            const img = document.getElementById('modalImg');
            img.style.opacity = '0';
            setTimeout(() => {
                // Priority: 1. Variant Photo, 2. Master Photo, 3. Placeholder
                let targetSrc = 'https://via.placeholder.com/400x400?text=No+Image';
                if (variant.foto) {
                    targetSrc = '../' + variant.foto;
                } else if (currentProduct && currentProduct.foto_utama) {
                    targetSrc = '../' + currentProduct.foto_utama;
                }
                
                img.src = targetSrc;
                img.style.opacity = '1';
            }, 300);
            
            document.getElementById('modalPrice').innerText = formatRupiah(variant.harga);
            document.getElementById('stockValue').innerText = variant.stok;
        }

        async function addToCartFromModal() {
            if (!selectedVariant) return;
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    title: 'Belum Login',
                    text: 'Silakan login terlebih dahulu untuk berbelanja.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'Login Sekarang',
                    cancelButtonText: 'Nanti',
                    backdrop: true,
                    customClass: {
                        container: 'swal-on-top'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../auth/login.php';
                    }
                });
                return;
            <?php endif; ?>

            try {
                const formData = new FormData();
                formData.append('kaos_id', selectedVariant.id);
                formData.append('qty', 1);

                const response = await fetch('../api/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Produk ditambahkan ke keranjang',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Update cart count
                    document.querySelectorAll('.cart-count').forEach(el => {
                        el.innerText = data.cart_count;
                    });
                    
                    closeQuickView();
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: data.message,
                        icon: 'error'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            }
        }

        async function buyNow() {
            if (!selectedVariant) return;
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    title: 'Belum Login',
                    text: 'Silakan login terlebih dahulu untuk berbelanja.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'Login Sekarang',
                    cancelButtonText: 'Nanti'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../auth/login.php';
                    }
                });
                return;
            <?php endif; ?>

            try {
                const formData = new FormData();
                formData.append('kaos_id', selectedVariant.id);
                formData.append('qty', 1);

                const response = await fetch('../api/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    // Redirect langsung ke checkout
                    window.location.href = 'checkout.php';
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: data.message,
                        icon: 'error'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            }
        }

        function closeQuickView() {
            document.getElementById('quickViewModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('quickViewModal');
            if (event.target == modal) {
                closeQuickView();
            }
        }
    </script>

<?php include '../includes/chat_widget.php'; ?>
</body>
</html>