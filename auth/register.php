<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $no_telp = clean_input($_POST['no_telp']);
    $alamat = clean_input($_POST['alamat']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($nama) || empty($username) || empty($email) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->execute([':username' => $username]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Check if email exists
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $conn->prepare($query);
            $stmt->execute([':email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email sudah terdaftar!";
            } else {
                // Register user
                $user_code = generate_code('USR');
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (user_code, username, nama, email, no_telp, alamat, 
                          password, role, status, created_at, updated_at) 
                          VALUES (:code, :username, :nama, :email, :telp, :alamat, :password, 
                          'customer', 'active', NOW(), NOW())";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':code' => $user_code,
                    ':username' => $username,
                    ':nama' => $nama,
                    ':email' => $email,
                    ':telp' => $no_telp,
                    ':alamat' => $alamat,
                    ':password' => $hashed_password
                ]);
                
                $success = "Registrasi berhasil! Silakan login.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .register-header {
            background: #1E293B;
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .register-header p {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #64748B;
            font-size: 14px;
        }
        
        .login-link a {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .required {
            color: #EF4444;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Buat Akun Baru</h1>
            <p>Daftar untuk mulai berbelanja</p>
        </div>
        
        <div class="register-body">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="login.php" style="display: block; margin-top: 8px; color: #065F46;">
                        <strong>Klik di sini untuk login</strong>
                    </a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" required placeholder="Username">
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="Email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" placeholder="Masukkan alamat lengkap untuk pengiriman"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" required placeholder="Min. 6 karakter">
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" required placeholder="Ulangi password">
                    </div>
                </div>
                
                <button type="submit" class="btn-register">Daftar Sekarang</button>
            </form>
            
            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>
</body>
</html>