<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Get categories for dropdown
$query = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt = $conn->query($query);
$kategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$search = $_GET['search'] ?? '';

// Helper for contrast color
function getContrastColor($hex) {
    if (empty($hex)) return 'black';
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) != 6) return 'black';
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'black' : 'white';
}

// Helper to generate consistent kode_varian (KV-001-01 format)
function generateKodeVarian($conn, $master_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM kaos_varian WHERE kaos_master_id = :mid");
    $stmt->execute(['mid' => $master_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] + 1;
    return 'KV-' . str_pad($master_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->beginTransaction();
    try {
        if ($action === 'add') {
            $master_data = [
                'nama_kaos' => $_POST['nama_kaos'],
                'merek' => $_POST['merek'],
                'kategori_id' => $_POST['kategori_id'],
                'type_kaos' => $_POST['type_kaos'],
                'deskripsi' => $_POST['deskripsi']
            ];
            
            if (isset($_FILES['foto_utama']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
                $upload_res = upload_file($_FILES['foto_utama'], '../assets/uploads/products/');
                if ($upload_res['success']) {
                    $master_data['foto_utama'] = 'assets/uploads/products/' . $upload_res['filename'];
                }
            }

            // Temukan ID yang kosong (gap filling)
            $stmt_ids = $conn->query("SELECT id FROM kaos_master ORDER BY id ASC");
            $ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
            $new_id = 1;
            foreach ($ids as $existing_id) {
                if ($existing_id == $new_id) {
                    $new_id++;
                } else {
                    break;
                }
            }
            $master_data['id'] = $new_id;

            $cols = implode(', ', array_keys($master_data));
            $vals = ':' . implode(', :', array_keys($master_data));
            $stmt = $conn->prepare("INSERT INTO kaos_master ($cols) VALUES ($vals)");
            $stmt->execute($master_data);
            $master_id = $new_id;

            if (isset($_POST['warna']) && is_array($_POST['warna'])) {
                foreach ($_POST['warna'] as $i => $warna) {
                    $posted_sizes = $_POST['sizes'][$i] ?? [];
                    
                    // Handle Photo for this color group
                    $photo_path = null;
                    if (isset($_FILES['foto_varian']['name'][$i]) && $_FILES['foto_varian']['error'][$i] === UPLOAD_ERR_OK) {
                        $v_file = [
                            'name' => $_FILES['foto_varian']['name'][$i],
                            'type' => $_FILES['foto_varian']['type'][$i],
                            'tmp_name' => $_FILES['foto_varian']['tmp_name'][$i],
                            'error' => $_FILES['foto_varian']['error'][$i],
                            'size' => $_FILES['foto_varian']['size'][$i]
                        ];
                        $v_upload = upload_file($v_file, '../assets/uploads/products/');
                        if ($v_upload['success']) {
                            $photo_path = 'assets/uploads/products/' . $v_upload['filename'];
                        }
                    }

                    foreach ($posted_sizes as $size) {
                        $v_data = [
                            'kaos_master_id' => $master_id,
                            'kode_varian' => generateKodeVarian($conn, $master_id),
                            'warna' => $warna,
                            'warna_hex' => $_POST['warna_hex'][$i] ?? '#000000',
                            'size' => $size,
                            'harga' => $_POST['harga'][$i] ?? 0,
                            'harga_pokok' => $_POST['harga_pokok'][$i] ?? 0,
                            'stok' => $_POST['stok'][$i] ?? 0
                        ];
                        if ($photo_path) $v_data['foto_varian'] = $photo_path;

                        $v_cols = implode(', ', array_keys($v_data));
                        $v_vals = ':' . implode(', :', array_keys($v_data));
                        $stmt_v = $conn->prepare("INSERT INTO kaos_varian ($v_cols) VALUES ($v_vals)");
                        $stmt_v->execute($v_data);
                    }
                }
            }
            $conn->commit();
            header('Location: kaos.php?success=Produk berhasil ditambahkan');
            exit;
        } elseif ($action === 'edit' && $id) {
            $master_data = [
                'nama_kaos' => $_POST['nama_kaos'],
                'merek' => $_POST['merek'],
                'kategori_id' => $_POST['kategori_id'],
                'type_kaos' => $_POST['type_kaos'],
                'deskripsi' => $_POST['deskripsi']
            ];
            
            if (isset($_FILES['foto_utama']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
                $upload_res = upload_file($_FILES['foto_utama'], '../assets/uploads/products/');
                if ($upload_res['success']) {
                    $master_data['foto_utama'] = 'assets/uploads/products/' . $upload_res['filename'];
                }
            }

            $set = [];
            foreach ($master_data as $key => $val) $set[] = "$key = :$key";
            $stmt = $conn->prepare("UPDATE kaos_master SET " . implode(', ', $set) . " WHERE id = :id");
            $master_data['id'] = $id;
            $stmt->execute($master_data);

            if (isset($_POST['warna']) && is_array($_POST['warna'])) {
                // Keep track of which variants we should keep
                $processed_variant_ids = [];

                foreach ($_POST['warna'] as $i => $warna) {
                    $posted_sizes = $_POST['sizes'][$i] ?? [];
                    
                    // Handle Photo for this color group
                    $photo_path = null;
                    if (isset($_FILES['foto_varian']['name'][$i]) && $_FILES['foto_varian']['error'][$i] === UPLOAD_ERR_OK) {
                        $v_file = [
                            'name' => $_FILES['foto_varian']['name'][$i],
                            'type' => $_FILES['foto_varian']['type'][$i],
                            'tmp_name' => $_FILES['foto_varian']['tmp_name'][$i],
                            'error' => $_FILES['foto_varian']['error'][$i],
                            'size' => $_FILES['foto_varian']['size'][$i]
                        ];
                        $v_upload = upload_file($v_file, '../assets/uploads/products/');
                        if ($v_upload['success']) {
                            $photo_path = 'assets/uploads/products/' . $v_upload['filename'];
                        }
                    }

                    foreach ($posted_sizes as $size) {
                        // Check if this variant already exists
                        $check_stmt = $conn->prepare("SELECT id FROM kaos_varian WHERE kaos_master_id = :mid AND warna = :warna AND size = :size");
                        $check_stmt->execute(['mid' => $id, 'warna' => $warna, 'size' => $size]);
                        $existing_id = $check_stmt->fetchColumn();

                        if ($existing_id) {
                            $v_data = [
                                'harga' => $_POST['harga'][$i] ?? 0,
                                'harga_pokok' => $_POST['harga_pokok'][$i] ?? 0,
                                'stok' => $_POST['stok'][$i] ?? 0,
                                'warna_hex' => $_POST['warna_hex'][$i] ?? '#000000',
                                'id' => $existing_id
                            ];
                            $set_q = "harga = :harga, harga_pokok = :harga_pokok, stok = :stok, warna_hex = :warna_hex";
                            if ($photo_path) {
                                $v_data['foto_varian'] = $photo_path;
                                $set_q .= ", foto_varian = :foto_varian";
                            }
                            $upd_stmt = $conn->prepare("UPDATE kaos_varian SET $set_q WHERE id = :id");
                            $upd_stmt->execute($v_data);
                            $processed_variant_ids[] = $existing_id;
                        } else {
                            $v_data = [
                                'kaos_master_id' => $id,
                                'kode_varian' => generateKodeVarian($conn, $id),
                                'warna' => $warna,
                                'warna_hex' => $_POST['warna_hex'][$i] ?? '#000000',
                                'size' => $size,
                                'harga' => $_POST['harga'][$i] ?? 0,
                                'harga_pokok' => $_POST['harga_pokok'][$i] ?? 0,
                                'stok' => $_POST['stok'][$i] ?? 0
                            ];
                            if ($photo_path) $v_data['foto_varian'] = $photo_path;

                            $v_cols = implode(', ', array_keys($v_data));
                            $v_vals = ':' . implode(', :', array_keys($v_data));
                            $stmt_v = $conn->prepare("INSERT INTO kaos_varian ($v_cols) VALUES ($v_vals)");
                            $stmt_v->execute($v_data);
                            $processed_variant_ids[] = $conn->lastInsertId();
                        }
                    }
                }

                // Delete variants that were removed
                if (!empty($processed_variant_ids)) {
                    $del_stmt = $conn->prepare("DELETE FROM kaos_varian WHERE kaos_master_id = :mid AND id NOT IN (" . implode(',', $processed_variant_ids) . ")");
                    $del_stmt->execute(['mid' => $id]);
                } else {
                    $conn->prepare("DELETE FROM kaos_varian WHERE kaos_master_id = :mid")->execute(['mid' => $id]);
                }
            }
            $conn->commit();
            header('Location: kaos.php?success=Produk berhasil diperbarui');
            exit;
        }
        elseif ($action === 'delete' && $id) {
            // Check if any variant is in transactions
            $stmt = $conn->prepare("SELECT COUNT(*) FROM detail_transaksi WHERE kaos_id IN (SELECT id FROM kaos_varian WHERE kaos_master_id = :id)");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Tidak bisa menghapus produk yang sudah memiliki riwayat transaksi");
            }

            $conn->exec("DELETE FROM kaos_varian WHERE kaos_master_id = $id");
            $conn->exec("DELETE FROM kaos_master WHERE id = $id");
            $conn->commit();
            header('Location: kaos.php?success=Produk berhasil dihapus');
            exit;
        }
    } catch (Exception $e) {
        $conn->rollBack();
        header('Location: kaos.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Get kaos data with search
$query = "SELECT k.*, kat.nama_kategori, 
          (SELECT COUNT(*) FROM kaos_varian WHERE kaos_master_id = k.id) as variant_count,
          (SELECT SUM(stok) FROM kaos_varian WHERE kaos_master_id = k.id) as total_stok
          FROM kaos_master k 
          LEFT JOIN kategori kat ON k.kategori_id = kat.id 
          WHERE 1=1";
          
if ($search) {
    $query .= " AND (k.nama_kaos LIKE :search OR k.merek LIKE :search)";
}

$query .= " ORDER BY k.created_at DESC";
$stmt = $conn->prepare($query);

if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$kaos_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get kaos detail for edit
$kaos_detail = null;
$variants_detail = [];
$grouped_variants = [];
if ($id && $action === 'edit') {
    $query = "SELECT * FROM kaos_master WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    $kaos_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get variants
    $query = "SELECT * FROM kaos_varian WHERE kaos_master_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    $variants_detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by color and photo
    foreach ($variants_detail as $v) {
        $key = $v['warna'] . '_' . ($v['foto_varian'] ?? '');
        if (!isset($grouped_variants[$key])) {
            $grouped_variants[$key] = [
                'warna' => $v['warna'],
                'warna_hex' => $v['warna_hex'] ?? '#000000',
                'harga' => $v['harga'],
                'harga_pokok' => $v['harga_pokok'],
                'stok' => $v['stok'],
                'foto_varian' => $v['foto_varian'],
                'sizes' => []
            ];
        }
        $grouped_variants[$key]['sizes'][] = $v['size'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kaos - DistroZone</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
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
        
        .content-card h3 {
            margin-bottom: 24px;
            color: var(--text-dark);
            font-size: 20px;
        }
        
        /* Search and Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            background: white;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
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
        
        .btn-secondary {
            background: white;
            color: var(--text-light);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .btn-secondary:hover {
            background: var(--bg-color);
            color: var(--primary);
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
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
        
        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }
        
        .badge-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .badge-danger {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .badge-info {
            background: #DBEAFE;
            color: #3B82F6;
        }
        
        /* Product Image */
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: var(--text-dark);
            font-size: 20px;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 24px;
            border-top: 1px solid rgba(16, 185, 129, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Form Styles */
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
        
        /* File Upload */
        .file-upload {
            border: 2px dashed rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(16, 185, 129, 0.05);
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.1);
        }
        
        .file-upload input {
            display: none;
        }
        
        .file-preview {
            margin-top: 16px;
        }
        
        .file-preview img {
            max-width: 200px;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.1);
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
        
        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon.edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }
        
        .btn-icon.edit:hover {
            background: rgba(59, 130, 246, 0.2);
        }
        
        .btn-icon.delete {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        
        .btn-icon.delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .btn-icon.view {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        
        .btn-icon.view:hover {
            background: rgba(16, 185, 129, 0.2);
        }
        
        /* Stock Status */
        .stock-status {
            font-weight: 600;
        }
        
        .stock-low {
            color: #DC2626;
        }
        
        .stock-medium {
            color: #D97706;
        }
        
        .stock-high {
            color: #059669;
        }
        /* Variant Table in Modal */
        .variant-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #F8FAFC;
            border-radius: 12px;
            overflow: hidden;
        }

        .variant-table th {
            background: #F1F5F9;
            text-align: left;
            padding: 12px;
            font-size: 13px;
            color: #64748B;
        }

        .variant-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #E2E8F0;
        }

        .variant-table .form-control {
            padding: 8px;
            font-size: 13px;
        }

        .btn-add-variant {
            background: #F1F5F9;
            color: var(--primary);
            border: 2px dashed var(--primary);
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-add-variant:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .modal-content {
            max-width: 1420px;
            width: 95%;
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
                    <a href="kaos.php" class="nav-link active">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
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
                    <a href="settings.php" class="nav-link">
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
                <h2>Kelola Kaos</h2>
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
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari kaos..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i>
                    Tambah Kaos
                </button>
            </div>
            
            <!-- Kaos Table -->
            <div class="content-card">
                <h3>Daftar Kaos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Info Produk</th>
                            <th>Kategori</th>
                            <th>Varian Warna</th>
                            <th>Total Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kaos_list)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    <i class="fas fa-tshirt" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                                    Belum ada data kaos
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kaos_list as $k): 
                                $stockClass = $k['total_stok'] < 10 ? 'stock-low' : ($k['total_stok'] < 50 ? 'stock-medium' : 'stock-high');
                                
                                // Get colors for this master
                                $c_stmt = $conn->prepare("SELECT DISTINCT warna, warna_hex FROM kaos_varian WHERE kaos_master_id = :id");
                                $c_stmt->execute(['id' => $k['id']]);
                                $colors = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <td>
                                    <?php if ($k['foto_utama']): ?>
                                        <img src="../<?php echo htmlspecialchars($k['foto_utama']); ?>" 
                                             alt="<?php echo htmlspecialchars($k['nama_kaos']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-tshirt" style="color: var(--primary);"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($k['nama_kaos']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-light);">
                                        <?php echo htmlspecialchars($k['merek']); ?> • <?php echo htmlspecialchars($k['type_kaos'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($k['nama_kategori'] ?? '-'); ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php foreach($colors as $c_info): 
                                            $c_name = $c_info['warna'];
                                            $c_hex = $c_info['warna_hex'] ?? '#000000';
                                            $c_text = getContrastColor($c_hex);
                                        ?>
                                            <span style="font-size: 11px; background: <?php echo htmlspecialchars($c_hex); ?>; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.2); color: <?php echo $c_text; ?>; margin-right: 4px; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($c_name); ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <span style="font-size: 11px; color: var(--primary); font-weight: 600;">(<?php echo $k['variant_count']; ?> SKU)</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-status <?php echo $stockClass; ?>">
                                        <?php echo $k['total_stok'] ?? 0; ?> pcs
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon edit" onclick="editKaos(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon delete" onclick="deleteKaos(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="kaosModal" class="modal <?php echo ($action === 'add' || $action === 'edit') ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' ? 'Edit Kaos' : 'Tambah Kaos Baru'; ?></h3>
                <button class="btn-icon" onclick="closeModal()" style="background: none; color: var(--text-light);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="kaos.php?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 32px;">
                        <!-- Master Information -->
                        <div>
                            <h4 style="margin-bottom: 16px; color: var(--primary);"><i class="fas fa-info-circle"></i> Informasi Utama</h4>
                            <div class="form-group">
                                <label for="nama_kaos">Nama Kaos *</label>
                                <input type="text" id="nama_kaos" name="nama_kaos" class="form-control" 
                                       value="<?php echo htmlspecialchars($kaos_detail['nama_kaos'] ?? ''); ?>" required>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label for="merek">Merek *</label>
                                    <input type="text" id="merek" name="merek" class="form-control" 
                                           value="<?php echo htmlspecialchars($kaos_detail['merek'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="kategori_id">Kategori *</label>
                                    <select id="kategori_id" name="kategori_id" class="form-control" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategories as $kat): ?>
                                            <option value="<?php echo $kat['id']; ?>" 
                                                <?php echo (isset($kaos_detail['kategori_id']) && $kaos_detail['kategori_id'] == $kat['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="type_kaos">Tipe Kaos</label>
                                <select id="type_kaos" name="type_kaos" class="form-control">
                                    <option value="Lengan Pendek" <?php echo (isset($kaos_detail['type_kaos']) && $kaos_detail['type_kaos'] == 'Lengan Pendek') ? 'selected' : ''; ?>>Lengan Pendek</option>
                                    <option value="Lengan Panjang" <?php echo (isset($kaos_detail['type_kaos']) && $kaos_detail['type_kaos'] == 'Lengan Panjang') ? 'selected' : ''; ?>>Lengan Panjang</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="deskripsi">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"><?php echo htmlspecialchars($kaos_detail['deskripsi'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="foto_utama">Foto Utama</label>
                                <input type="file" id="foto_utama" name="foto_utama" class="form-control">
                                <?php if (isset($kaos_detail['foto_utama'])): ?>
                                    <div style="margin-top: 8px; font-size: 12px; color: var(--text-light);">
                                        File saat ini: <?php echo basename($kaos_detail['foto_utama']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Variant Management -->
                        <div>
                            <h4 style="margin-bottom: 16px; color: var(--primary);"><i class="fas fa-layer-group"></i> Varian Produk (Warna & Ukuran)</h4>
                            <div style="max-height: 600px; overflow-y: auto;">
                                <table class="variant-table">
                                    <thead>
                                        <tr>
                                            <th>Warna</th>
                                            <th>Ukuran (Checklist)</th>
                                            <th>Harga Pokok</th>
                                            <th>Harga Jual</th>
                                            <th>Stok</th>
                                            <th>Foto</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantContainer">
                                        <?php 
                                        $available_sizes = ['XS','S','M','L','XL','2XL','3XL','4XL','5XL'];
                                        if (empty($grouped_variants)): ?>
                                            <!-- Default empty row if adding new -->
                                            <tr class="variant-row" data-idx="0">
                                                <td>
                                                    <div style="display: flex; gap: 8px; align-items: center;">
                                                        <input type="color" name="warna_hex[0]" class="form-control" style="width: 40px; height: 38px; padding: 2px; flex-shrink: 0; border-radius: 8px;" value="#000000">
                                                        <input type="text" name="warna[0]" class="form-control" placeholder="Nama Warna" style="width: 180px;" required>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="display: flex; flex-wrap: wrap; gap: 8px; max-width: 250px;">
                                                        <?php foreach($available_sizes as $sz): ?>
                                                            <label style="font-size: 12px; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                                                <input type="checkbox" name="sizes[0][]" value="<?php echo $sz; ?>" <?php echo $sz == 'L' ? 'checked' : ''; ?>> <?php echo $sz; ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" name="harga_pokok[0]" class="form-control" placeholder="Harga Pokok" style="width: 120px;" value="0">
                                                </td>
                                                <td>
                                                    <input type="number" name="harga[0]" class="form-control" placeholder="Harga Jual" style="font-weight: 700; font-size: 16px; width: 130px; color: var(--primary);">
                                                </td>
                                                <td><input type="number" name="stok[0]" class="form-control" value="0" style="width: 70px;"></td>
                                                <td><input type="file" name="foto_varian[0]" class="form-control" style="width: 150px;"></td>
                                                <td><button type="button" class="btn-icon delete" onclick="removeVariantRow(this)"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $idx = 0; foreach ($grouped_variants as $gv): ?>
                                                <tr class="variant-row" data-idx="<?php echo $idx; ?>">
                                                    <td>
                                                    <div style="display: flex; gap: 8px; align-items: center;">
                                                        <input type="color" name="warna_hex[<?php echo $idx; ?>]" class="form-control" style="width: 40px; height: 38px; padding: 2px; flex-shrink: 0; border-radius: 8px;" value="<?php echo htmlspecialchars($gv['warna_hex'] ?? '#000000'); ?>">
                                                        <input type="text" name="warna[<?php echo $idx; ?>]" class="form-control" placeholder="Warna" value="<?php echo htmlspecialchars($gv['warna']); ?>" style="width: 180px;" required>
                                                    </div>
                                                </td>
                                                    <td>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; max-width: 250px;">
                                                            <?php foreach($available_sizes as $sz): ?>
                                                                <label style="font-size: 12px; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                                                    <input type="checkbox" name="sizes[<?php echo $idx; ?>][]" value="<?php echo $sz; ?>" 
                                                                           <?php echo in_array($sz, $gv['sizes']) ? 'checked' : ''; ?>> <?php echo $sz; ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                    <td><input type="number" name="harga_pokok[<?php echo $idx; ?>]" class="form-control" placeholder="Harga Pokok" value="<?php echo round($gv['harga_pokok']); ?>" style="width: 120px;"></td>
                                                    <td><input type="number" name="harga[<?php echo $idx; ?>]" class="form-control" placeholder="Harga Jual" value="<?php echo round($gv['harga']); ?>" style="font-weight: 700; font-size: 16px; width: 130px; color: var(--primary);"></td>
                                                    <td><input type="number" name="stok[<?php echo $idx; ?>]" class="form-control" value="<?php echo $gv['stok']; ?>" style="width: 70px;"></td>
                                                    <td>
                                                        <input type="file" name="foto_varian[<?php echo $idx; ?>]" class="form-control" style="width: 150px;">
                                                        <?php if ($gv['foto_varian']): ?>
                                                            <div style="font-size: 10px; color: var(--primary);">Ada foto</div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <td><button type="button" class="btn-icon delete" onclick="removeVariantRow(this)"><i class="fas fa-times"></i></button></td>
                                                </tr>
                                            <?php $idx++; endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <button type="button" class="btn-add-variant" onclick="addVariantRow()">
                                    <i class="fas fa-plus"></i> Tambah Varian Baru
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Produk' : 'Simpan Produk'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const search = this.value;
                window.location.href = `kaos.php?search=${encodeURIComponent(search)}`;
            }
        });
        
        // Modal functions
        let variantCounter = <?php echo isset($grouped_variants) ? count($grouped_variants) : 1; ?>;
        const sizesArr = <?php echo json_encode($available_sizes); ?>;

        function addVariantRow() {
            const container = document.getElementById('variantContainer');
            const idx = variantCounter++;
            const row = document.createElement('tr');
            row.className = 'variant-row';
            row.setAttribute('data-idx', idx);
            
            let sizeHtml = '<div style="display: flex; flex-wrap: wrap; gap: 8px; max-width: 250px;">';
            sizesArr.forEach(sz => {
                sizeHtml += `
                    <label style="font-size: 12px; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                        <input type="checkbox" name="sizes[${idx}][]" value="${sz}" ${sz === 'L' ? 'checked' : ''}> ${sz}
                    </label>
                `;
            });
            sizeHtml += '</div>';

            row.innerHTML = `
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" name="warna_hex[${idx}]" class="form-control" style="width: 40px; height: 38px; padding: 2px; flex-shrink: 0; border-radius: 8px;" value="#000000">
                        <input type="text" name="warna[${idx}]" class="form-control" placeholder="Nama Warna" style="width: 180px;" required>
                    </div>
                </td>
                <td>${sizeHtml}</td>
                <td>
                    <input type="number" name="harga_pokok[${idx}]" class="form-control" placeholder="Harga Pokok" style="width: 120px;" value="0">
                </td>
                <td>
                    <input type="number" name="harga[${idx}]" class="form-control" placeholder="Harga Jual" style="font-weight: 700; font-size: 16px; width: 130px; color: var(--primary);">
                </td>
                <td><input type="number" name="stok[${idx}]" class="form-control" value="0" style="width: 70px;"></td>
                <td><input type="file" name="foto_varian[${idx}]" class="form-control" style="width: 150px;"></td>
                <td><button type="button" class="btn-icon delete" onclick="removeVariantRow(this)"><i class="fas fa-times"></i></button></td>
            `;
            container.appendChild(row);
        }

        function removeVariantRow(btn) {
            const row = btn.closest('tr');
            const container = document.getElementById('variantContainer');
            if (container.querySelectorAll('.variant-row').length > 1) {
                row.remove();
            } else {
                alert('Minimal harus ada satu varian produk.');
            }
        }

        function openModal(type) {
            if (type === 'add') {
                window.location.href = 'kaos.php?action=add';
            }
        }

        function closeModal() {
            window.location.href = 'kaos.php';
        }

        function editKaos(id) {
            window.location.href = 'kaos.php?action=edit&id=' + id;
        }

        function deleteKaos(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini? Semua varian produk ini juga akan terhapus.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'kaos.php?action=delete&id=' + id;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = 'kaos.php?search=' + encodeURIComponent(this.value);
            }
        });
        
        // Image preview for foto_utama
        document.getElementById('foto_utama').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    let img = document.getElementById('imagePreview');
                    if (!img) { // Create if not exists (e.g., for add mode)
                        img = document.createElement('img');
                        img.id = 'imagePreview';
                        img.style.maxWidth = '100px';
                        img.style.maxHeight = '100px';
                        img.style.marginTop = '10px';
                        img.style.borderRadius = '8px';
                        document.getElementById('foto_utama').parentNode.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>