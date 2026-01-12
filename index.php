<?php
/**
 * DistroZone - Main Router
 * Modern POS & E-Commerce System
 * 
 * This file acts as the main entry point and router
 * for the DistroZone application
 */

session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect based on user role
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/index.php");
            exit();
            break;
            
        case 'kasir':
            header("Location: kasir/index.php");
            exit();
            break;
            
        case 'customer':
            header("Location: customer/index.php");
            exit();
            break;
            
        default:
            // Invalid role, logout
            session_destroy();
            header("Location: auth/login.php");
            exit();
    }
} else {
    // Not logged in, show public landing page
    header("Location: customer/index.php");
    exit();
}
?>

<?php
/**
 * ALTERNATIVE: Direct Landing Page
 * Uncomment this section if you want index.php to show the landing page directly
 */

/*
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DistroZone - Kaos Distro Berkualitas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .welcome-container {
            text-align: center;
            padding: 60px 40px;
        }
        
        .logo {
            font-size: 64px;
            font-weight: 800;
            margin-bottom: 24px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        h1 {
            font-size: 42px;
            margin-bottom: 16px;
        }
        
        p {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 48px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 18px 36px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #667eea;
            transform: translateY(-4px);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-top: 80px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .feature h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .feature p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .features {
                grid-template-columns: 1fr;
            }
            
            .logo {
                font-size: 48px;
            }
            
            h1 {
                font-size: 32px;
            }
            
            .buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="logo">DistroZone</div>
        <h1>Selamat Datang!</h1>
        <p>Sistem kasir dan penjualan online modern untuk toko distro Anda</p>
        
        <div class="buttons">
            <a href="customer/shop.php" class="btn btn-white">
                <i class="fas fa-shopping-bag"></i>
                Belanja Sekarang
            </a>
            <a href="auth/login.php" class="btn btn-outline">
                <i class="fas fa-sign-in-alt"></i>
                Login Admin/Kasir
            </a>
        </div>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">🎨</div>
                <h3>Modern Design</h3>
                <p>Interface yang clean dan profesional</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <h3>Fast & Efficient</h3>
                <p>Transaksi cepat dan mudah</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Smart Reports</h3>
                <p>Laporan laba rugi lengkap</p>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
*/
?>

<?php
/**
 * SYSTEM INFORMATION
 * 
 * DistroZone v1.0
 * 
 * Features:
 * - Modern Admin Panel
 * - POS System for Kasir
 * - E-Commerce for Customers
 * - Auto Profit Calculation
 * - Auto Shipping Calculator
 * - Payment Verification
 * - Comprehensive Reports
 * - Professional Invoices
 * 
 * Tech Stack:
 * - PHP 7.4+ (Native/PDO)
 * - MySQL 5.7+
 * - HTML5, CSS3, JavaScript
 * - Font Awesome 6
 * - Google Fonts (Inter)
 * 
 * Created by: Professional Web Developer
 * Date: January 2026
 * License: Proprietary
 */
?>