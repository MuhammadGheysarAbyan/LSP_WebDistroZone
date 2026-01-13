<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DistroZone - Kaos Distro Berkualitas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: #334155;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        
        /* Enhanced smooth scroll */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }
        
        /* Navbar - FIXED: NO HAMBURGER MENU */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar.scrolled {
            padding: 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        /* Nav Links - SELALU TAMPIL, NO HAMBURGER */
        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            padding: 8px 0;
            font-size: 15px;
            white-space: nowrap;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .nav-links a.active {
            color: #667eea;
        }
        
        .nav-links a.active::after {
            width: 100%;
        }
        
        /* Shopping Button in Navbar */
        .nav-shopping-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .nav-shopping-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            gap: 10px;
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            from { transform: translateY(0px); }
            to { transform: translateY(-100px); }
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-text h1 {
            font-size: 56px;
            font-weight: 800;
            color: white;
            margin-bottom: 24px;
            line-height: 1.2;
        }
        
        .hero-text p {
            font-size: 20px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .hero-image {
            position: relative;
        }
        
        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: float-image 6s ease-in-out infinite;
        }
        
        @keyframes float-image {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        /* Features Section */
        .features {
            padding: 80px 24px;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 36px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 16px;
        }
        
        .section-title p {
            font-size: 18px;
            color: #64748B;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 16px;
            transition: all 0.3s;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #1E293B;
        }
        
        .feature-card p {
            color: #64748B;
            line-height: 1.6;
        }
        
        /* About Section */
        .about {
            padding: 80px 24px;
            background: #f8fafc;
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        
        .about-image img {
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .about-text h3 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1E293B;
        }
        
        .about-text p {
            margin-bottom: 20px;
            color: #64748B;
            line-height: 1.7;
        }
        
        .about-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }
        
        .about-feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .about-feature i {
            color: #667eea;
            font-size: 18px;
        }
        
        /* Products Preview */
        .products-preview {
            padding: 80px 24px;
            background: white;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .btn-add-cart {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
            color: white;
        }
        
        .cta h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #1E293B;
            color: white;
            padding: 60px 24px 24px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .footer-section p,
        .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        /* Responsive - NAVBAR TETAP TAMPIL, HANYA WRAP */
        @media (max-width: 1024px) {
            .hero-content,
            .about-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .hero-text h1 {
                font-size: 42px;
            }
            
            .about-image {
                order: -1;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
            }
            
            .nav-links {
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 32px;
            }
            
            /* NAVBAR RESPONSIVE - NO HAMBURGER, JUST WRAP */
            .navbar-content {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 12px;
            }
            
            .nav-links a {
                font-size: 14px;
                padding: 6px 0;
            }
            
            .nav-shopping-btn {
                padding: 8px 16px;
                font-size: 13px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .hero-buttons .btn {
                width: 100%;
                justify-content: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
            
            .cta h2 {
                font-size: 28px;
            }
            
            .section-title h2 {
                font-size: 28px;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .about-features {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                gap: 8px;
            }
            
            .nav-links a {
                font-size: 13px;
            }
            
            .nav-shopping-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
        
        /* Back to Top Button */
        #scrollTopBtn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            z-index: 99;
            transition: all 0.3s;
        }
        
        #scrollTopBtn.visible {
            display: flex;
        }
        
        #scrollTopBtn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navbar - NO HAMBURGER MENU -->
    <nav class="navbar" id="navbar">
        <div class="navbar-content">
            <a href="#home" class="logo">DistroZone</a>
            <div class="nav-links">
                <a href="#home" class="nav-link active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="#products" class="nav-link">
                    <i class="fas fa-tshirt"></i> Produk
                </a>
                <a href="#about" class="nav-link">
                    <i class="fas fa-info-circle"></i> Tentang
                </a>
                <a href="#footer" class="nav-link">
                    <i class="fas fa-phone"></i> Kontak
                </a>
                <a href="shop.php" class="nav-shopping-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Belanja</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Koleksi Kaos Distro Terbaik</h1>
                <p>Temukan berbagai model kaos distro berkualitas dengan desain unik dan harga terjangkau. Express yourself dengan style!</p>
                <div class="hero-buttons">
                    <a href="shop.php" class="btn btn-white">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Mulai Belanja</span>
                    </a>
                    <a href="#products" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        <span>Lihat Koleksi</span>
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <img src="data:image/svg+xml,%3Csvg width='500' height='500' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='500' height='500' fill='%23667eea'/%3E%3Ctext x='50%25' y='50%25' font-size='24' fill='white' text-anchor='middle' dominant-baseline='middle'%3EDistro Collection%3C/text%3E%3C/svg%3E" alt="Hero Image">
            </div>
        </div>
    </section>
    
    <!-- Features -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Kenapa Pilih DistroZone?</h2>
                <p>Belanja mudah, aman, dan terpercaya</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3>Produk Berkualitas</h3>
                    <p>Kaos distro original dengan bahan premium katun combed 30s dan jahitan rapi yang tahan lama</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Harga Terjangkau</h3>
                    <p>Dapatkan harga terbaik mulai dari Rp 85.000 dengan berbagai promo menarik setiap minggu</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Pengiriman Cepat</h3>
                    <p>Pengiriman ke seluruh Pulau Jawa dengan ongkir terjangkau, estimasi 1-3 hari sampai</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Garansi Kepuasan</h3>
                    <p>Garansi 100% uang kembali jika barang cacat atau tidak sesuai dengan pesanan</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Customer Service</h3>
                    <p>Tim customer service siap membantu Anda 24 jam melalui WhatsApp dan live chat</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Desain Eksklusif</h3>
                    <p>Koleksi desain eksklusif dari berbagai brand ternama dengan edisi terbatas</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Products Preview -->
    <section class="products-preview" id="products">
        <div class="container">
            <div class="section-title">
                <h2>Produk Terlaris</h2>
                <p>Koleksi terfavorit yang paling banyak diminati</p>
            </div>
            
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Classic Distro Tee - Black</div>
                        <div class="product-price">Rp 85.000</div>
                        <button class="btn-add-cart" onclick="addToCart('Classic Distro Tee')">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Premium Cotton Tee - White</div>
                        <div class="product-price">Rp 120.000</div>
                        <button class="btn-add-cart" onclick="addToCart('Premium Cotton Tee')">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Graphic Print Tee - Blue</div>
                        <div class="product-price">Rp 95.000</div>
                        <button class="btn-add-cart" onclick="addToCart('Graphic Print Tee')">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Vintage Style Tee - Gray</div>
                        <div class="product-price">Rp 110.000</div>
                        <button class="btn-add-cart" onclick="addToCart('Vintage Style Tee')">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="shop.php" class="btn btn-primary" style="padding: 14px 28px;">
                    <span>Lihat Semua Produk (100+)</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="section-title">
                <h2>Tentang DistroZone</h2>
                <p>Lebih dari sekadar toko kaos, kami adalah komunitas</p>
            </div>
            
            <div class="about-content">
                <div class="about-text">
                    <h3>Menyediakan Kaos Distro Terbaik Sejak 2018</h3>
                    <p>DistroZone didirikan dengan misi untuk memberikan produk kaos distro berkualitas tinggi dengan harga yang terjangkau. Kami percaya bahwa fashion adalah bentuk ekspresi diri, dan setiap orang berhak tampil stylish tanpa harus menguras dompet.</p>
                    <p>Dengan pengalaman lebih dari 5 tahun di industri distro, kami telah melayani ribuan pelanggan dari seluruh Indonesia. Setiap produk yang kami jual melalui proses seleksi ketat untuk memastikan kualitas terbaik.</p>
                    
                    <div class="about-features">
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>100% Original Product</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Free Shipping Over Rp 500k</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Secure Payment</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>30 Days Return Policy</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <a href="#footer" class="btn btn-primary">
                            <i class="fas fa-envelope"></i>
                            <span>Hubungi Kami</span>
                        </a>
                    </div>
                </div>
                <div class="about-image">
                    <img src="data:image/svg+xml,%3Csvg width='500' height='400' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='500' height='400' fill='%23f1f5f9'/%3E%3Ctext x='50%25' y='50%25' font-size='24' fill='%23667eea' text-anchor='middle' dominant-baseline='middle'%3EDistroZone Store%3C/text%3E%3C/svg%3E" alt="DistroZone Store">
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Sudah Siap Upgrade Style Kamu?</h2>
            <p>Dapatkan koleksi kaos distro terbaru dengan promo spesial 15% untuk pembelian pertama!</p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 30px;">
                <a href="shop.php" class="btn btn-white" style="padding: 14px 28px;">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Belanja Sekarang</span>
                </a>
                <a href="#footer" class="btn btn-primary" style="padding: 14px 28px; background: rgba(255,255,255,0.2); border: 2px solid white;">
                    <i class="fas fa-headset"></i>
                    <span>Konsultasi Gratis</span>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer" id="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>DistroZone</h3>
                    <p>Toko kaos distro premium dengan koleksi terlengkap di Jakarta. Kami berkomitmen memberikan produk berkualitas dengan pelayanan terbaik.</p>
                    
                    <div style="margin-top: 20px;">
                        <p><i class="fas fa-map-marker-alt"></i> Jln. Raya Pegangsaan Timur No.29H, Kelapa Gading, Jakarta</p>
                        <p><i class="fas fa-phone"></i> +62 812-3456-7890</p>
                        <p><i class="fas fa-envelope"></i> info@distrozone.com</p>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <a href="#" style="color: white; font-size: 18px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; font-size: 18px;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; font-size: 18px;"><i class="fab fa-tiktok"></i></a>
                        <a href="#" style="color: white; font-size: 18px;"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Tautan Cepat</h3>
                    <a href="#home">Beranda</a>
                    <a href="#products">Produk</a>
                    <a href="#about">Tentang Kami</a>
                    <a href="#footer">Kontak</a>
                    <a href="shop.php">Belanja Online</a>
                </div>
                
                <div class="footer-section">
                    <h3>Layanan</h3>
                    <a href="#">FAQ</a>
                    <a href="#">Pengiriman</a>
                    <a href="#">Pengembalian</a>
                    <a href="#">Garansi</a>
                    <a href="#">Kebijakan Privasi</a>
                </div>
                
                <div class="footer-section">
                    <h3>Jam Operasional</h3>
                    <p><strong>Toko Offline:</strong><br>Selasa - Minggu: 10.00 - 20.00<br>Senin: Libur</p>
                    <p><strong>Online Support:</strong><br>Setiap hari: 10.00 - 17.00</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 DistroZone. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple scroll handling
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            const currentScroll = window.pageYOffset;
            
            // Navbar shadow on scroll
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Back to top button
            if (scrollTopBtn) {
                if (currentScroll > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            }
            
            // Update active nav link
            updateActiveNavLink();
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const offset = targetId === '#footer' ? 0 : 80;
                    const targetPosition = targetElement.offsetTop - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Update active nav link
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Update active nav link
        function updateActiveNavLink() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            const scrollPosition = window.scrollY + 100;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            const footer = document.getElementById('footer');
            if (footer && scrollPosition >= footer.offsetTop - 100) {
                current = 'footer';
            }

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        }

        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector('a[href="#home"]').classList.add('active');
        }

        // Add to cart function
        function addToCart(productName) {
            showNotification(`${productName} berhasil ditambahkan ke keranjang!`);
            
            const button = event?.target;
            if (button) {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Ditambahkan';
                button.disabled = true;
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }, 2000);
            }
        }

        // Notification function
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #10B981;
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 350px;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateActiveNavLink();
            
            // Add back to top button
            const scrollTopBtn = document.createElement('button');
            scrollTopBtn.id = 'scrollTopBtn';
            scrollTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            scrollTopBtn.onclick = scrollToTop;
            document.body.appendChild(scrollTopBtn);
            
            // Smooth page load
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.4s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>