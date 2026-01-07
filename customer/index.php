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
        }
        
        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
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
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .hero-image {
            position: relative;
        }
        
        .hero-image img {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            animation: float-image 6s ease-in-out infinite;
        }
        
        @keyframes float-image {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        /* Features Section */
        .features {
            padding: 100px 24px;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 42px;
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            text-align: center;
            padding: 40px 24px;
            border-radius: 20px;
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #1E293B;
        }
        
        .feature-card p {
            color: #64748B;
            line-height: 1.6;
        }
        
        /* Products Preview */
        .products-preview {
            padding: 100px 24px;
            background: #F8FAFC;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 32px;
            margin-top: 40px;
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .product-info {
            padding: 24px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 16px;
        }
        
        .btn-add-cart {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
            color: white;
        }
        
        .cta h2 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 24px;
        }
        
        .cta p {
            font-size: 20px;
            margin-bottom: 40px;
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
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .footer-section p,
        .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
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
        }
        
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
            }
            
            .hero-text h1 {
                font-size: 36px;
            }
            
            .nav-links {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">DistroZone</div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#products">Produk</a>
                <a href="#about">Tentang</a>
                <a href="#contact">Kontak</a>
                <a href="shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Belanja Sekarang
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
                        Mulai Belanja
                    </a>
                    <a href="#products" class="btn btn-primary">
                        Lihat Koleksi
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
                    <p>Kaos distro original dengan bahan premium dan jahitan rapi</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Harga Terjangkau</h3>
                    <p>Dapatkan harga terbaik dengan berbagai promo menarik</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Pengiriman Cepat</h3>
                    <p>Pengiriman ke seluruh Pulau Jawa dengan ongkir terjangkau</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Products Preview -->
    <section class="products-preview" id="products">
        <div class="container">
            <div class="section-title">
                <h2>Produk Terbaru</h2>
                <p>Koleksi terbaru kami yang wajib kamu miliki</p>
            </div>
            
            <div class="products-grid">
                <!-- Sample products -->
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Classic Distro Tee</div>
                        <div class="product-price">Rp 85.000</div>
                        <button class="btn-add-cart">
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Premium Cotton Tee</div>
                        <div class="product-price">Rp 120.000</div>
                        <button class="btn-add-cart">
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Graphic Print Tee</div>
                        <div class="product-price">Rp 95.000</div>
                        <button class="btn-add-cart">
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <div class="product-card">
                    <div class="product-image"></div>
                    <div class="product-info">
                        <div class="product-name">Vintage Style Tee</div>
                        <div class="product-price">Rp 110.000</div>
                        <button class="btn-add-cart">
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 48px;">
                <a href="shop.php" class="btn btn-primary" style="font-size: 18px; padding: 16px 32px;">
                    Lihat Semua Produk
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Siap Upgrade Style Kamu?</h2>
            <p>Dapatkan koleksi kaos distro terbaru dengan promo spesial hari ini!</p>
            <a href="shop.php" class="btn btn-white" style="font-size: 18px; padding: 16px 32px;">
                <i class="fas fa-shopping-bag"></i>
                Belanja Sekarang
            </a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>DistroZone</h3>
                <p>Jln. Raya Pegangsaan Timur No.29H<br>Kelapa Gading, Jakarta</p>
                <p><i class="fas fa-phone"></i> +62 812-3456-7890</p>
                <p><i class="fas fa-envelope"></i> info@distrozone.com</p>
            </div>
            
            <div class="footer-section">
                <h3>Jam Operasional</h3>
                <p><strong>Offline Store:</strong><br>Selasa - Minggu: 10.00 - 20.00<br>Senin: Libur</p>
                <p><strong>Online Store:</strong><br>Setiap hari: 10.00 - 17.00</p>
            </div>
            
            <div class="footer-section">
                <h3>Informasi</h3>
                <a href="#">Tentang Kami</a>
                <a href="#">Cara Pemesanan</a>
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Syarat & Ketentuan</a>
            </div>
            
            <div class="footer-section">
                <h3>Ikuti Kami</h3>
                <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="#"><i class="fab fa-facebook"></i> Facebook</a>
                <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                <a href="#"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 DistroZone. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>