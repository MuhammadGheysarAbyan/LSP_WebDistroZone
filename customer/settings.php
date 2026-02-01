<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $no_telp = clean_input($_POST['no_telp']);
    $alamat = clean_input($_POST['alamat']);
    $desa = clean_input($_POST['desa']);
    $kecamatan = clean_input($_POST['kecamatan']);
    $kabupaten = clean_input($_POST['kabupaten']);
    $kodepos = clean_input($_POST['kodepos']);
    
    try {
        // Check if email already exists for another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check_stmt->execute(['email' => $email, 'id' => $user_id]);
        
        if ($check_stmt->fetch()) {
            $error_msg = "Email sudah digunakan oleh pengguna lain.";
        } else {
            $query = "UPDATE users SET nama = :nama, email = :email, no_telp = :no_telp, alamat = :alamat, desa = :desa, kecamatan = :kecamatan, kabupaten = :kabupaten, kodepos = :kodepos, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'nama' => $nama,
                'email' => $email,
                'no_telp' => $no_telp,
                'alamat' => $alamat,
                'desa' => $desa,
                'kecamatan' => $kecamatan,
                'kabupaten' => $kabupaten,
                'kodepos' => $kodepos,
                'id' => $user_id
            ]);
            
            $_SESSION['nama'] = $nama; // Update session name
            $success_msg = "Profil berhasil diperbarui!";
        }
    } catch (PDOException $e) {
        $error_msg = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error_msg = "Konfirmasi password baru tidak cocok.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_password !== $user['password']) {
            $error_msg = "Password saat ini salah.";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $update_stmt->execute(['password' => $new_password, 'id' => $user_id]);
            $success_msg = "Password berhasil diubah!";
        }
    }
}

// Handle Account Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password_confirm = $_POST['delete_password_confirm'];
    
    // Verify password first
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($password_confirm !== $user['password']) {
        $error_msg = "Password salah. Gagal menghapus akun.";
    } else {
        try {
            $conn->beginTransaction();
            
            // 1. Anonymize transactions (set customer_id to NULL)
            // This keeps the transaction record for financial reports but removes the link to the user
            $stmt_trx = $conn->prepare("UPDATE transaksi SET customer_id = NULL WHERE customer_id = :id");
            $stmt_trx->execute(['id' => $user_id]);
            
            // 2. Clear cart
            $stmt_cart = $conn->prepare("DELETE FROM cart WHERE customer_id = :id");
            $stmt_cart->execute(['id' => $user_id]);
            
            // 3. Close chat conversations
            $stmt_chat = $conn->prepare("UPDATE chat_conversations SET status = 'closed' WHERE customer_id = :id");
            $stmt_chat->execute(['id' => $user_id]);
            
            // 4. Delete user (This will cascade delete chat_messages if configured, otherwise we might need to handle it)
            // Based on constraints: chat_messages triggers CASCADE on user delete?
            // Let's check: chat_messages -> sender_id has ON DELETE CASCADE.
            // chat_conversations -> customer_id has ON DELETE CASCADE.
            // So deleting user should clean up chats.
            
            // However, payment_proof -> customer_id has ON DELETE SET NULL.
            
            $stmt_del = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt_del->execute(['id' => $user_id]);
            
            $conn->commit();
            
            // Logout and redirect
            session_destroy();
            header("Location: index.php?msg=account_deleted");
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error_msg = "Gagal menghapus akun: " . $e->getMessage();
        }
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Profil - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10B981;
            --secondary: #0F766E;
            --dark: #1F2937;
            --bg-color: #ECFDF5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--dark);
            min-height: 100vh;
        }

        .navbar {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 24px;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
        }

        .btn-back {
            text-decoration: none;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">
                <i class="fas fa-layer-group"></i>
                DistroZone
            </a>
            <div style="display: flex; gap: 20px;">
                <a href="index.php" style="text-decoration: none; color: var(--dark); font-weight: 500;">Home</a>
                <a href="orders.php" style="text-decoration: none; color: var(--dark); font-weight: 500;">Pesanan</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Beranda
        </a>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="settings-card">
            <h2 class="card-title">
                <i class="fas fa-user-circle"></i>
                Profil Saya
            </h2>
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="no_telp">No. Telepon</label>
                    <input type="text" id="no_telp" name="no_telp" class="form-control" value="<?php echo htmlspecialchars($user['no_telp']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat (Jalan, No. Rumah, RT/RW)</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="2" placeholder="Contoh: Jl. Merdeka No. 123, RT 01/RW 02"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="desa">Desa/Kelurahan</label>
                        <input type="text" id="desa" name="desa" class="form-control" value="<?php echo htmlspecialchars($user['desa'] ?? ''); ?>" placeholder="Contoh: Sukamaju">
                    </div>
                    <div class="form-group">
                        <label for="kecamatan">Kecamatan</label>
                        <input type="text" id="kecamatan" name="kecamatan" class="form-control" value="<?php echo htmlspecialchars($user['kecamatan'] ?? ''); ?>" placeholder="Contoh: Cilandak">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="kabupaten">Kabupaten/Kota</label>
                        <input type="text" id="kabupaten" name="kabupaten" class="form-control" value="<?php echo htmlspecialchars($user['kabupaten'] ?? ''); ?>" placeholder="Contoh: Jakarta Selatan">
                    </div>
                    <div class="form-group">
                        <label for="kodepos">Kode Pos</label>
                        <input type="text" id="kodepos" name="kodepos" class="form-control" value="<?php echo htmlspecialchars($user['kodepos'] ?? ''); ?>" placeholder="12345" maxlength="10">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="settings-card">
            <h2 class="card-title">
                <i class="fas fa-key"></i>
                Ubah Password
            </h2>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="update_password" class="btn btn-primary">
                    <i class="fas fa-lock"></i> Perbarui Password
                </button>
            </form>
        </div>

        <div class="settings-card" style="border: 1px solid #FEE2E2;">
            <h2 class="card-title" style="color: #991B1B;">
                <i class="fas fa-exclamation-triangle"></i>
                Hapus Akun
            </h2>
            <p style="margin-bottom: 20px; color: #4B5563;">
                Menghapus akun Anda bersifat permanen. Semua data riwayat pesanan yang terkait dengan akun Anda akan di-anonymize, dan Anda tidak akan bisa login kembali.
            </p>
            <button type="button" class="btn" style="background: #EF4444; color: white;" onclick="openDeleteModal()">
                <i class="fas fa-trash-alt"></i> Hapus Akun Saya
            </button>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h3 style="color: #991B1B; margin-bottom: 16px;">Konfirmasi Penghapusan</h3>
                <p style="margin-bottom: 24px;">Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan. Silakan masukkan password Anda untuk konfirmasi.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="delete_password_confirm">Password Anda</label>
                        <input type="password" name="delete_password_confirm" id="delete_password_confirm" class="form-control" required placeholder="Masukkan password...">
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                        <button type="submit" name="delete_account" class="btn" style="background: #EF4444; color: white;">Ya, Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
             /* Modal Styles Reuse or Add if missing in this file scope */
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(5px);
            }
            .modal.active { display: flex; }
            .modal-content {
                background: white;
                border-radius: 20px;
                padding: 32px;
                max-width: 500px;
                width: 90%;
            }
        </style>

        <script>
            function openDeleteModal() {
                document.getElementById('deleteModal').classList.add('active');
            }
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.remove('active');
            }
            window.onclick = function(event) {
                const modal = document.getElementById('deleteModal');
                if (event.target == modal) {
                    closeDeleteModal();
                }
            }
        </script>
    </div>
</body>
</html>
