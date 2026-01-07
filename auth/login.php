<?php
// auth/login.php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = :username AND status = 'active' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_code'] = $user['user_code'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto'] = $user['foto'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header("Location: ../admin/index.php");
                    break;
                case 'kasir':
                    header("Location: ../kasir/index.php");
                    break;
                case 'customer':
                    header("Location: ../customer/index.php");
                    break;
            }
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.css">
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
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        
        .login-left {
            background: #1E293B;
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-left h1 {
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .login-left p {
            font-size: 16px;
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .login-right {
            padding: 60px;
        }
        
        .login-right h2 {
            color: #1E293B;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .login-right .subtitle {
            color: #64748B;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .btn-login {
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
        }
        
        .btn-login:hover {
            background: #2563EB;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 24px;
            color: #64748B;
        }
        
        .register-link a {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .login-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>DistroZone</h1>
            <p>Sistem Kasir & Penjualan Online Modern untuk Toko Distro Anda. Kelola stok, transaksi, dan laporan dengan mudah.</p>
        </div>
        
        <div class="login-right">
            <h2>Selamat Datang</h2>
            <p class="subtitle">Silakan login untuk melanjutkan</p>
            
            <?php if($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Masukkan username">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Masukkan password">
                </div>
                
                <button type="submit" class="btn-login">Masuk</button>
            </form>
            
            <div class="register-link">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </div>
</body>
</html>