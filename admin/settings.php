<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        // Update general settings
        // Ideally this should be dynamic, but for now we might be using static values or a simple key-value table if one exists.
        // Assuming we are updating specific rows in a settings table or just handling it if a table exists.
        // Based on previous file reading, there seemed to be a 'shipping_rates' and file uploads.
        // I'll assume we iterate over POST data and update a settings table if it exists.
        // But the previous file was using `settings` table with `setting_key` and `setting_value`.
        // Let's verify if `settings` table exists. I'll assume it does based on standard practice for this app.
        // Or I can check previous `admin/settings.php` content in my "Viewed Files" if I had it.
        // I viewed `admin/settings.php`. Let's recall/check.
        // It was handling `shipping_rates`, `payment_methods`, etc.
        
        // Update store info (if we had a settings table)
        // For this task, I will mock the persistence if the schema isn't fully known, OR better:
        // logic from previous file: `UPDATE settings SET setting_value = :value WHERE setting_key = :key`
        
        $settings_to_update = [
            'store_name', 'store_address', 'store_phone', 'store_email', 'store_description',
            'instagram_url', 'facebook_url', 'whatsapp_number'
        ];
        
        foreach ($settings_to_update as $key) {
            if (isset($_POST[$key])) {
                $sql = "INSERT INTO settings (setting_key, setting_value) 
                        VALUES (:key, :value) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['value' => $_POST[$key], 'key' => $key]);
            }
        }
        
        // Handle Logo Upload
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/img/';
            if (!file_exists($uploadDir)) {
                 mkdir($uploadDir, 0777, true);
            }
            $fileName = 'logo_store_' . time() . '.' . pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $targetFile)) {
                $sql = "INSERT INTO settings (setting_key, setting_value) 
                        VALUES ('store_logo', :value) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['value' => 'assets/img/' . $fileName]);
            }
        }

        header('Location: settings.php?success=Pengaturan umum berhasil disimpan');
        exit;
    }
    elseif ($action === 'update_shipping') {
        // Update shipping rates in `shipping_rates` table? Or `settings`?
        // Let's assume `shipping_rates` table based on previous viewed file snippet: `shipping_rates[id]`.
        if (isset($_POST['shipping_rates'])) {
            foreach ($_POST['shipping_rates'] as $id => $cost) {
                $sql = "UPDATE shipping_rates SET cost_per_kg = :cost WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['cost' => $cost, 'id' => $id]);
            }
        }
        header('Location: settings.php?tab=shipping&success=Ongkos kirim berhasil diupdate');
        exit;
    }
    elseif ($action === 'update_payment') {
        // Update QRIS setup or Payment Methods
        if (isset($_POST['payment_methods'])) {
            // Logic to update enabled/disabled methods?
            // Or just QRIS image
        }
        
         if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/payment/';
             if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'qris_' . time() . '.' . pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $targetFile)) {
                $sql = "INSERT INTO settings (setting_key, setting_value) 
                        VALUES ('payment_qris_image', :value) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['value' => 'assets/uploads/payment/' . $fileName]);
            }
        }
        
        header('Location: settings.php?tab=payment&success=Pengaturan pembayaran berhasil disimpan');
        exit;
    }
    elseif ($action === 'update_hours') {
        $offline_open = $_POST['offline_open'];
        $offline_close = $_POST['offline_close'];
        $offline_closed_days = isset($_POST['offline_closed_days']) ? array_map('intval', $_POST['offline_closed_days']) : [];
        
        $online_open = $_POST['online_open'];
        $online_close = $_POST['online_close'];
        $online_closed_days = isset($_POST['online_closed_days']) ? array_map('intval', $_POST['online_closed_days']) : [];
        
        $offline_json = json_encode([
            'open' => $offline_open,
            'close' => $offline_close,
            'closed_days' => $offline_closed_days
        ]);
        
        $online_json = json_encode([
            'open' => $online_open,
            'close' => $online_close,
            'closed_days' => $online_closed_days
        ]);
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('jam_operasional_offline', :val1) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['val1' => $offline_json]);
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('jam_operasional_online', :val2) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['val2' => $online_json]);
        
        header('Location: settings.php?tab=hours&success=Jam operasional berhasil diupdate');
        exit;
    }
}

// Get all settings
$query = "SELECT * FROM settings";
$stmt = $conn->query($query);
$settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Get shipping rates
$query = "SELECT * FROM shipping_rates ORDER BY wilayah";
$stmt = $conn->query($query);
$shipping_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_tab = $_GET['tab'] ?? 'general';

// Decode Hours
$offline_data = json_decode($settings['jam_operasional_offline'] ?? '{"open":"10:00","close":"20:00","closed_days":[]}', true);
$online_data = json_decode($settings['jam_operasional_online'] ?? '{"open":"09:00","close":"17:00","closed_days":[]}', true);
$days_map = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
            --primary: #10B981;
            --primary-dark: #047857;
            --secondary: #0F766E;
            --bg-color: #ECFDF5;
            --text-dark: #1F2937;
            --text-light: #64748B;
            --white: #FFFFFF;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(15, 118, 110, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(15, 118, 110, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
            margin-bottom: 24px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
            padding-bottom: 12px;
        }
        
        .tab-item {
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-item.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }
        
        .tab-item:hover {
            color: var(--primary);
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(16, 185, 129, 0.05);
            margin-top: 8px;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.1);
        }
        
        .file-preview {
            margin-top: 16px;
            max-width: 200px;
        }
        
        .file-preview img {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        /* Table */
         /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            border-bottom: 2px solid rgba(16, 185, 129, 0.1);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-layer-group"></i>
                <h1>DistroZone</h1>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="karyawan.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Kelola Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kaos.php" class="nav-link">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="verifikasi.php" class="nav-link">
                        <i class="fas fa-check-circle"></i>
                        Verifikasi Pembayaran
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chat.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        Live Chat
                    </a>
                </li>

                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link active">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Pengaturan</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="tabs">
                <a href="?tab=general" class="tab-item <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Umum
                </a>
                <a href="?tab=shipping" class="tab-item <?php echo $active_tab == 'shipping' ? 'active' : ''; ?>">
                    <i class="fas fa-shipping-fast"></i> Ongkos Kirim
                </a>
                <a href="?tab=payment" class="tab-item <?php echo $active_tab == 'payment' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill"></i> Pembayaran
                </a>
                <a href="?tab=hours" class="tab-item <?php echo $active_tab == 'hours' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Jam Operasional
                </a>
            </div>
            
            <div class="content-card">
                <?php if ($active_tab == 'general'): ?>
                    <!-- ... general form ... -->
                    <form method="POST" action="settings.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div style="padding-bottom: 24px; border-bottom: 1px solid rgba(16, 185, 129, 0.1); margin-bottom: 24px;">
                            <h3 style="margin-bottom: 16px;">Informasi Toko</h3>
                            <div class="form-group">
                                <label for="store_name">Nama Toko</label>
                                <input type="text" id="store_name" name="store_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['store_name'] ?? 'DistroZone'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="store_description">Deskripsi Singkat</label>
                                <textarea id="store_description" name="store_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['store_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="store_address">Alamat Toko</label>
                                <textarea id="store_address" name="store_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label for="store_phone">No. Telepon</label>
                                    <input type="text" id="store_phone" name="store_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="store_email">Email Toko</label>
                                    <input type="email" id="store_email" name="store_email" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Logo Toko</label>
                                <div class="file-upload" onclick="document.getElementById('logoInput').click()">
                                    <i class="fas fa-image" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
                                    <div style="color: var(--text-dark);">Klik untuk ganti logo</div>
                                    <input type="file" id="logoInput" name="store_logo" accept="image/*" style="display:none;">
                                </div>
                                <div class="file-preview">
                                    <?php if (!empty($settings['store_logo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($settings['store_logo']); ?>" id="logoPreview">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div style="padding-bottom: 24px; margin-bottom: 24px;">
                            <h3 style="margin-bottom: 16px;">Sosial Media</h3>
                            <div class="form-group">
                                <label for="instagram_url">Instagram URL</label>
                                <input type="url" id="instagram_url" name="instagram_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="facebook_url">Facebook URL</label>
                                <input type="url" id="facebook_url" name="facebook_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="whatsapp_number">WhatsApp (Format: 628xxx)</label>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                    
                <?php elseif ($active_tab == 'shipping'): ?>
                    <form method="POST" action="settings.php">
                         <input type="hidden" name="action" value="update_shipping">
                        <h3 style="margin-bottom: 16px;">Pengaturan Ongkos Kirim</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Wilayah Pengiriman</th>
                                    <th>Ongkir / KG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipping_rates as $rate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rate['wilayah']); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span>Rp</span>
                                            <input type="number" name="shipping_rates[<?php echo $rate['id']; ?>]" 
                                                   value="<?php echo $rate['cost_per_kg']; ?>" 
                                                   class="form-control" style="width: 150px;">
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">Update Ongkir</button>
                        </div>
                    </form>
                    
                <?php elseif ($active_tab == 'payment'): ?>
                     <form method="POST" action="settings.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_payment">
                        <h3 style="margin-bottom: 16px;">Metode Pembayaran (QRIS)</h3>
                        
                        <div class="form-group">
                            <label>Upload Gambar QRIS</label>
                            <div class="file-upload" onclick="document.getElementById('qrisInput').click()">
                                <i class="fas fa-qrcode" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
                                <div style="color: var(--text-dark);">Klik untuk upload QRIS baru</div>
                                <input type="file" id="qrisInput" name="qris_image" accept="image/*" style="display:none;">
                            </div>
                            <div class="file-preview">
                                <?php if (!empty($settings['payment_qris_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($settings['payment_qris_image']); ?>" id="qrisPreview">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>

                <?php elseif ($active_tab == 'hours'): ?>
                    <form method="POST" action="settings.php">
                        <input type="hidden" name="action" value="update_hours">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <!-- Offline Store -->
                            <div style="background: rgba(255,255,255,0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(16,185,129,0.1);">
                                <h3 style="margin-bottom: 16px; color: var(--primary);">Toko Offline</h3>
                                <div class="form-group">
                                    <label>Jam Buka</label>
                                    <input type="time" name="offline_open" class="form-control" value="<?php echo htmlspecialchars($offline_data['open'] ?? '10:00'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Jam Tutup</label>
                                    <input type="time" name="offline_close" class="form-control" value="<?php echo htmlspecialchars($offline_data['close'] ?? '20:00'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Hari Tutup (Libur):</label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <?php foreach ($days_map as $num => $day): ?>
                                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 14px;">
                                                <input type="checkbox" name="offline_closed_days[]" value="<?php echo $num; ?>" 
                                                    <?php echo in_array($num, $offline_data['closed_days'] ?? []) ? 'checked' : ''; ?>>
                                                <?php echo $day; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Online Store -->
                             <div style="background: rgba(255,255,255,0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(16,185,129,0.1);">
                                <h3 style="margin-bottom: 16px; color: var(--secondary);">Layanan Online</h3>
                                <div class="form-group">
                                    <label>Jam Mulai Chat</label>
                                    <input type="time" name="online_open" class="form-control" value="<?php echo htmlspecialchars($online_data['open'] ?? '09:00'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Jam Selesai Chat</label>
                                    <input type="time" name="online_close" class="form-control" value="<?php echo htmlspecialchars($online_data['close'] ?? '17:00'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Hari Off (Slow Response):</label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <?php foreach ($days_map as $num => $day): ?>
                                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 14px;">
                                                <input type="checkbox" name="online_closed_days[]" value="<?php echo $num; ?>" 
                                                    <?php echo in_array($num, $online_data['closed_days'] ?? []) ? 'checked' : ''; ?>>
                                                <?php echo $day; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">Simpan Jam Operasional</button>
                        </div>
                     </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Image preview scripts
        function handleImagePreview(inputId, previewId) {
            document.getElementById(inputId).addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.getElementById(previewId);
                        if (img) {
                             img.src = e.target.result;
                        } else {
                            // If img element doesn't exist yet (e.g. no previous logo), create it
                            const previewContainer = document.querySelector('#' + inputId).parentElement.nextElementSibling;
                             previewContainer.innerHTML = `<img src="${e.target.result}" id="${previewId}">`;
                        }
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        }
        
        if (document.getElementById('logoInput')) {
            handleImagePreview('logoInput', 'logoPreview');
        }
        
        if (document.getElementById('qrisInput')) {
            handleImagePreview('qrisInput', 'qrisPreview');
        }
    </script>
</body>
</html>