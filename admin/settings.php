<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Get current settings
$query = "SELECT * FROM settings";
$stmt = $conn->query($query);
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['nama_setting']] = $setting['isi_setting'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $sql = "UPDATE settings SET isi_setting = :value, updated_at = NOW() 
                WHERE nama_setting = :key";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['value' => $value, 'key' => $key]);
    }
    
    // Handle file upload for logo
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/';
        $fileName = 'logo-' . time() . '.' . pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $targetFile)) {
            $sql = "UPDATE settings SET isi_setting = :value, updated_at = NOW() 
                    WHERE nama_setting = 'store_logo'";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['value' => 'assets/img/' . $fileName]);
        }
    }
    
    header('Location: settings.php?success=Pengaturan berhasil diperbarui');
    exit;
}

// Get shipping rates
$query = "SELECT * FROM shipping_rates ORDER BY wilayah";
$stmt = $conn->query($query);
$shipping_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same base styles as previous files */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #334155;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1E293B;
            color: white;
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin: 4px 12px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px;
        }
        
        .top-bar {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .top-bar h2 {
            font-size: 24px;
            color: #1E293B;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3B82F6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .content-card h3 {
            margin-bottom: 20px;
            color: #1E293B;
            padding-bottom: 16px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            color: #64748B;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #F1F5F9;
            color: #475569;
        }
        
        .tab-btn.active {
            background: #3B82F6;
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* File Upload */
        .file-upload {
            border: 2px dashed #E2E8F0;
            border-radius: 10px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: #3B82F6;
            background: #F8FAFC;
        }
        
        .file-upload input {
            display: none;
        }
        
        .file-preview {
            margin-top: 16px;
            text-align: center;
        }
        
        .file-preview img {
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
        }
        
        /* Operating Hours */
        .hours-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .hour-day {
            background: #F8FAFC;
            border-radius: 10px;
            padding: 16px;
        }
        
        .hour-day.closed {
            background: #FEE2E2;
        }
        
        .day-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1E293B;
        }
        
        .hour-range {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Shipping Rates */
        .shipping-rates {
            width: 100%;
            border-collapse: collapse;
        }
        
        .shipping-rates th,
        .shipping-rates td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #F1F5F9;
        }
        
        .shipping-rates th {
            font-weight: 600;
            color: #64748B;
            background: #F8FAFC;
        }
        
        .shipping-rates td input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
        }
        
        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 10px;
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
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563EB;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #F1F5F9;
            justify-content: flex-end;
        }
        
        /* Help Text */
        .help-text {
            font-size: 12px;
            color: #94A3B8;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
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
                <h2>Pengaturan Sistem</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: #64748B;">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-btn active" onclick="showTab('general')">Umum</button>
                <button class="tab-btn" onclick="showTab('operating')">Jam Operasional</button>
                <button class="tab-btn" onclick="showTab('shipping')">Ongkos Kirim</button>
                <button class="tab-btn" onclick="showTab('payment')">Pembayaran</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- General Settings Tab -->
                <div id="general-tab" class="tab-content active">
                    <div class="content-card">
                        <h3>Pengaturan Toko</h3>
                        
                        <div class="form-group">
                            <label for="store_name">Nama Toko *</label>
                            <input type="text" id="store_name" name="settings[store_name]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['store_name'] ?? 'DistroZone'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="store_address">Alamat Toko *</label>
                            <textarea id="store_address" name="settings[store_address]" class="form-control" required><?php echo htmlspecialchars($settings_array['store_address'] ?? 'Jln. Raya Pegangsaan Timur No.29H Kelapa Gading Jakarta'); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="store_phone">Telepon Toko</label>
                            <input type="text" id="store_phone" name="settings[store_phone]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['store_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="store_email">Email Toko</label>
                            <input type="email" id="store_email" name="settings[store_email]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['store_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="store_description">Deskripsi Toko</label>
                            <textarea id="store_description" name="settings[store_description]" class="form-control"><?php echo htmlspecialchars($settings_array['store_description'] ?? 'Toko ini adalah sebuah toko yang menjual berbagai macam kaos distro dengan berbagai macam model, warna dan ukuran.'); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Logo Toko</label>
                            <div class="file-upload" onclick="document.getElementById('logoInput').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94A3B8; margin-bottom: 12px;"></i>
                                <div style="color: #64748B;">Klik untuk upload logo baru</div>
                                <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">Format: JPG, PNG, SVG. Max: 2MB</div>
                                <input type="file" id="logoInput" name="store_logo" accept="image/*">
                            </div>
                            <div class="file-preview">
                                <?php if (isset($settings_array['store_logo']) && file_exists('../' . $settings_array['store_logo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($settings_array['store_logo']); ?>" 
                                         alt="Logo Toko" style="max-width: 200px;">
                                    <div class="help-text">Logo saat ini</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Operating Hours Tab -->
                <div id="operating-tab" class="tab-content">
                    <div class="content-card">
                        <h3>Jam Operasional</h3>
                        
                        <div class="hours-grid">
                            <?php
                            $days = [
                                'Monday' => 'Senin',
                                'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu',
                                'Thursday' => 'Kamis',
                                'Friday' => 'Jumat',
                                'Saturday' => 'Sabtu',
                                'Sunday' => 'Minggu'
                            ];
                            
                            $operating_hours = json_decode($settings_array['operating_hours'] ?? '[]', true);
                            
                            foreach ($days as $enDay => $idDay):
                                $day_data = $operating_hours[$enDay] ?? [
                                    'open' => ($enDay === 'Monday') ? false : true,
                                    'start' => '10:00',
                                    'end' => '20:00'
                                ];
                            ?>
                            <div class="hour-day <?php echo (!$day_data['open']) ? 'closed' : ''; ?>">
                                <div class="day-name"><?php echo $idDay; ?></div>
                                
                                <div style="margin-bottom: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="settings[operating_hours][<?php echo $enDay; ?>][open]" 
                                               value="1" <?php echo $day_data['open'] ? 'checked' : ''; ?> 
                                               onchange="toggleDayHours(this)">
                                        Buka
                                    </label>
                                </div>
                                
                                <div class="hour-range">
                                    <input type="time" name="settings[operating_hours][<?php echo $enDay; ?>][start]" 
                                           class="form-control" value="<?php echo $day_data['start']; ?>"
                                           <?php echo (!$day_data['open']) ? 'disabled' : ''; ?>>
                                    <span>s.d</span>
                                    <input type="time" name="settings[operating_hours][<?php echo $enDay; ?>][end]" 
                                           class="form-control" value="<?php echo $day_data['end']; ?>"
                                           <?php echo (!$day_data['open']) ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="help-text" style="margin-top: 16px;">
                            <i class="fas fa-info-circle"></i>
                            Untuk transaksi online, jam operasional adalah 10:00 - 17:00 setiap hari
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Rates Tab -->
                <div id="shipping-tab" class="tab-content">
                    <div class="content-card">
                        <h3>Tarif Ongkos Kirim</h3>
                        <div class="help-text" style="margin-bottom: 16px;">
                            <i class="fas fa-info-circle"></i>
                            Tiap 1 kg bisa muat 3 kaos, kaos kurang dari 3 pcs tetap di hitung 1 kg
                        </div>
                        
                        <table class="shipping-rates">
                            <thead>
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Ongkos Kirim per Kg</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipping_rates as $rate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rate['wilayah']); ?></td>
                                    <td>
                                        <input type="number" name="shipping_rates[<?php echo $rate['id']; ?>]" 
                                               value="<?php echo $rate['cost_per_kg']; ?>" 
                                               class="form-control" style="text-align: right;">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Payment Settings Tab -->
                <div id="payment-tab" class="tab-content">
                    <div class="content-card">
                        <h3>Pengaturan Pembayaran</h3>
                        
                        <div class="form-group">
                            <label for="bank_name">Nama Bank</label>
                            <input type="text" id="bank_name" name="settings[bank_name]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['bank_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_number">Nomor Rekening</label>
                            <input type="text" id="account_number" name="settings[account_number]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['account_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_name">Nama Pemilik Rekening</label>
                            <input type="text" id="account_name" name="settings[account_name]" 
                                   class="form-control" value="<?php echo htmlspecialchars($settings_array['account_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Metode Pembayaran yang Tersedia</label>
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="settings[payment_methods][]" value="cash" 
                                           <?php echo (strpos($settings_array['payment_methods'] ?? '', 'cash') !== false) ? 'checked' : ''; ?>>
                                    Tunai / Cash
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="settings[payment_methods][]" value="qris" 
                                           <?php echo (strpos($settings_array['payment_methods'] ?? '', 'qris') !== false) ? 'checked' : ''; ?>>
                                    QRIS
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="settings[payment_methods][]" value="transfer" 
                                           <?php echo (strpos($settings_array['payment_methods'] ?? '', 'transfer') !== false) ? 'checked' : ''; ?>>
                                    Transfer Bank
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="qris_image">QRIS Image</label>
                            <div class="file-upload" onclick="document.getElementById('qrisInput').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94A3B8; margin-bottom: 12px;"></i>
                                <div style="color: #64748B;">Klik untuk upload QRIS baru</div>
                                <input type="file" id="qrisInput" name="qris_image" accept="image/*">
                            </div>
                            <?php if (isset($settings_array['qris_image']) && file_exists('../' . $settings_array['qris_image'])): ?>
                                <div class="file-preview">
                                    <img src="../<?php echo htmlspecialchars($settings_array['qris_image']); ?>" 
                                         alt="QRIS" style="max-width: 200px;">
                                    <div class="help-text">QRIS saat ini</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn btn-danger" onclick="resetSettings()">
                        <i class="fas fa-undo"></i>
                        Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // Tab Navigation
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Toggle day hours
        function toggleDayHours(checkbox) {
            const dayDiv = checkbox.closest('.hour-day');
            const startInput = dayDiv.querySelector('input[type="time"]:first-of-type');
            const endInput = dayDiv.querySelector('input[type="time"]:last-of-type');
            
            if (checkbox.checked) {
                dayDiv.classList.remove('closed');
                startInput.disabled = false;
                endInput.disabled = false;
            } else {
                dayDiv.classList.add('closed');
                startInput.disabled = true;
                endInput.disabled = true;
            }
        }
        
        // Reset settings confirmation
        function resetSettings() {
            if (confirm('Apakah Anda yakin ingin mengembalikan semua pengaturan ke nilai default? Tindakan ini tidak dapat dibatalkan.')) {
                // In real implementation, this would reset to defaults
                alert('Fitur reset pengaturan akan segera hadir!');
            }
        }
        
        // Form submission confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Simpan perubahan pengaturan?')) {
                e.preventDefault();
            }
        });
        
        // Preview image upload
        document.getElementById('logoInput')?.addEventListener('change', function(e) {
            const preview = document.querySelector('.file-preview');
            preview.innerHTML = '';
            
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid #E2E8F0';
                    preview.appendChild(img);
                    
                    const helpText = document.createElement('div');
                    helpText.className = 'help-text';
                    helpText.textContent = 'Preview logo baru';
                    preview.appendChild(helpText);
                }
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>