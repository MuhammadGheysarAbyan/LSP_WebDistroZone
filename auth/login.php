<?php
// auth/login.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php'; // Use centralized functions

$db = new Database();
$conn = $db->getConnection();

$error = "";
$username = "";
$selected_role = "customer"; // default role

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $role = clean_input($_POST['role']);
    
    // Simpan role yang dipilih untuk tampilan kembali
    $selected_role = $role;
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Silakan isi username dan password!";
    } else {
        $query = "SELECT * FROM users WHERE username = :username AND role = :role AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($password === $user['password']) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
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
                    default:
                        $error = "Role tidak valid!";
                        break;
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Akun tidak ditemukan atau role salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DistroZone</title>
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
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .main-container {
            width: 1000px;
            max-width: 95%;
            height: 600px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
            overflow: hidden;
            display: flex;
            position: relative;
        }
        
        /* Left Side - Visual */
        .visual-side {
            flex: 1;
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
        
        .brand-content {
            position: relative;
            z-index: 10;
        }
        
        .brand-logo {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .brand-tagline {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            font-weight: 300;
        }
        
        /* Right Side - Form */
        .form-side {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: white;
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
        
        .role-selector {
            display: flex;
            background: #F3F4F6;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            color: #6B7280;
            transition: all 0.3s ease;
        }
        
        .role-option input {
            display: none;
        }
        
        .role-option.active {
            background: white;
            color: #10B981;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6B7280;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        
        .input-field {
            width: 100%;
            padding: 14px 16px;
            padding-left: 42px;
            background: #F9FAFB;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 15px;
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
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: 38px;
            color: #9CA3AF;
            transition: color 0.3s;
        }
        
        .input-field:focus ~ .input-icon {
            color: #10B981;
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 38px;
            color: #9CA3AF;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #10B981;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }
        
        .register-link {
            text-align: center;
            margin-top: 24px;
            color: #6B7280;
            font-size: 14px;
        }
        
        .register-link a {
            color: #10B981;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                height: auto;
                max-width: 100%;
                border-radius: 0;
            }
            .visual-side {
                padding: 40px 20px;
            }
            .form-side {
                padding: 40px 20px;
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
                <p class="brand-tagline">
                    Sistem Manajemen Distro Modern.<br>
                    Kelola stok, transaksi, dan customer dalam satu platform.
                </p>
            </div>
        </div>
        
        <!-- Form Side -->
        <div class="form-side">
            <div class="form-header">
                <h2>Selamat Datang</h2>
                <p>Silakan masuk ke akun Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Role Selector -->
                <div class="role-selector">
                    <label class="role-option <?php echo ($selected_role == 'customer') ? 'active' : ''; ?>" onclick="selectRole('customer')">
                        Customer
                        <input type="radio" name="role" value="customer" <?php echo ($selected_role == 'customer') ? 'checked' : ''; ?>>
                    </label>
                    <label class="role-option <?php echo ($selected_role == 'kasir') ? 'active' : ''; ?>" onclick="selectRole('kasir')">
                        Kasir
                        <input type="radio" name="role" value="kasir" <?php echo ($selected_role == 'kasir') ? 'checked' : ''; ?>>
                    </label>
                    <label class="role-option <?php echo ($selected_role == 'admin') ? 'active' : ''; ?>" onclick="selectRole('admin')">
                        Admin
                        <input type="radio" name="role" value="admin" <?php echo ($selected_role == 'admin') ? 'checked' : ''; ?>>
                    </label>
                </div>
                
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" class="input-field" placeholder="Masukkan username Anda" value="<?php echo htmlspecialchars($username); ?>" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" class="input-field" placeholder="Masukkan password Anda" required>
                    <i class="fas fa-lock input-icon"></i>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
                
                <button type="submit" class="btn-submit">
                    Masuk Sekarang
                </button>
                
                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar Customer</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectRole(role) {
            // Remove active class from all
            document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
            
            // Add active class to clicked
            const selected = document.querySelector(`input[value="${role}"]`).parentElement;
            selected.classList.add('active');
            
            // Select radio button
            document.querySelector(`input[value="${role}"]`).checked = true;
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Add ripple effect to button
        document.querySelector('.btn-submit').addEventListener('click', function(e) {
            let x = e.clientX - e.target.offsetLeft;
            let y = e.clientY - e.target.offsetTop;
            
            let ripples = document.createElement('span');
            ripples.style.left = x + 'px';
            ripples.style.top = y + 'px';
            this.appendChild(ripples);
            
            setTimeout(() => {
                ripples.remove()
            }, 1000);
        });
    </script>

</body>
</html>