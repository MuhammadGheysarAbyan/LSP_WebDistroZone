<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

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
                // $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Removed hash
                
                try {
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
                        ':password' => $password
                    ]);
                    
                    $success = "Registrasi berhasil! Silakan login.";
                } catch (PDOException $e) {
                    $error = "Terjadi kesalahan sistem: " . $e->getMessage();
                }
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ECFDF5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.1) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            padding: 20px;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .main-container {
            width: 1100px;
            max-width: 95%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
            overflow: hidden;
            display: flex;
            position: relative;
            min-height: 700px;
        }
        
        /* Left Side - Visual */
        .visual-side {
            width: 40%;
            background: linear-gradient(135deg, #10B981 0%, #0F766E 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
            overflow: hidden;
        }
        
        .visual-circle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .c1 { width: 300px; height: 300px; top: -50px; left: -50px; }
        .c2 { width: 200px; height: 200px; bottom: -20px; right: -20px; }
        
        .brand-logo {
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Right Side - Form */
        .form-side {
            width: 60%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            overflow-y: auto;
        }
        
        .form-header {
            margin-bottom: 32px;
        }
        
        .form-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .form-header p {
            color: #6B7280;
            font-size: 15px;
        }
        
        .input-group {
            margin-bottom: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            background: #F9FAFB;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 14px;
            color: #1F2937;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .input-field:focus {
            outline: none;
            background: white;
            border-color: #10B981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }
        
        textarea.input-field {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        .login-link {
            text-align: center;
            margin-top: 24px;
            color: #6B7280;
            font-size: 14px;
        }
        
        .login-link a {
            color: #10B981;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 900px) {
            .main-container {
                flex-direction: column;
                height: auto;
            }
            .visual-side {
                width: 100%;
                padding: 30px;
            }
            .form-side {
                width: 100%;
                padding: 30px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="main-container">
        <!-- Visual Side -->
        <div class="visual-side">
            <div class="visual-circle c1"></div>
            <div class="visual-circle c2"></div>
            
            <div class="brand-content">
                <div class="brand-logo">DistroZone</div>
                <p>
                    Bergabung bersama kami dan temukan style terbaikmu.
                </p>
            </div>
        </div>
        
        <!-- Form Side -->
        <div class="form-side">
            <div class="form-header">
                <h2>Buat Akun</h2>
                <p>Isi data diri Anda dengan lengkap</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="login.php" style="font-weight: 700; display: block; margin-top: 5px; color: #047857;">Login Sekarang</a>
                </div>
            <?php else: ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" name="nama" class="input-field" placeholder="Nama Lengkap" required>
                </div>
                
                <div class="form-row">
                    <div class="input-group">
                        <input type="text" name="username" class="input-field" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" class="input-field" placeholder="Email Address" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <input type="text" name="no_telp" class="input-field" placeholder="No. Telepon">
                </div>
                
                <div class="input-group">
                    <textarea name="alamat" class="input-field" placeholder="Alamat Lengkap"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="input-group" style="position: relative;">
                        <input type="password" name="password" id="password" class="input-field" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)" style="position: absolute; right: 14px; top: 14px; cursor: pointer; color: #9CA3AF;"></i>
                    </div>
                    <div class="input-group" style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" class="input-field" placeholder="Konfirmasi Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 14px; top: 14px; cursor: pointer; color: #9CA3AF;"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    Daftar Sekarang
                </button>
                
                <div class="login-link">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>