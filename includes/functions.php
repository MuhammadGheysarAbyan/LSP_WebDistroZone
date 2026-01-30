<?php
/**
 * DistroZone Web Application - Functions
 */

// Clean input function
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role
function check_role($allowed_roles = []) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if (empty($allowed_roles)) {
        return true;
    }
    
    return in_array($_SESSION['role'], $allowed_roles);
}

// Redirect if not logged in
function require_login($redirect_url = '../auth/login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url");
        exit();
    }
}

// Redirect if not authorized
function require_role($allowed_roles, $redirect_url = '../auth/login.php') {
    require_login($redirect_url);
    
    if (!check_role($allowed_roles)) {
        header("Location: $redirect_url");
        exit();
    }
}

// Get kategori name
function get_kategori_name($conn, $kategori_id) {
    if(empty($kategori_id)) return 'Uncategorized';
    
    try {
        $stmt = $conn->prepare("SELECT nama_kategori FROM kategori WHERE id = :id");
        $stmt->execute([':id' => $kategori_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nama_kategori'] : 'Uncategorized';
    } catch(PDOException $e) {
        return 'Uncategorized';
    }
}

// Get user data
function get_user_data($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
    
    return $randomString;
}

// Upload file function
function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5000000) {
    $errors = [];
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "Ukuran file terlalu besar. Maksimal " . ($max_size / 1000000) . "MB";
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "Format file tidak didukung. Gunakan: " . implode(', ', $allowed_types);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    
    // Ensure directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . $filename;
    
    if (empty($errors)) {
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $target_file
            ];
        } else {
            $errors[] = "Gagal mengupload file";
        }
    }
    
    return [
        'success' => false,
        'errors' => $errors
    ];
}

// Calculate shipping cost
function calculate_shipping($city, $quantity) {
    $shipping_rates = [
        'Jakarta' => 24000,
        'Depok' => 24000,
        'Bekasi' => 25000,
        'Tangerang' => 25000,
        'Bogor' => 27000,
        'Seluruh Wilayah Jawa Barat' => 31000,
        'Seluruh Wilayah Jawa Tengah' => 39000,
        'Seluruh Wilayah Jawa Timur' => 47000
    ];
    
    if (!isset($shipping_rates[$city])) {
        return 0;
    }
    
    // 3 kaos per kg
    $kg = ceil($quantity / 3);
    return $shipping_rates[$city] * $kg;
}

// Get cart total
function get_cart_total($user_id) {
    global $conn;
    
    $query = "SELECT SUM(c.qty * k.harga) as total 
              FROM cart c 
              JOIN kaos_varian k ON c.kaos_id = k.id 
              WHERE c.customer_id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'] ?? 0;
}

// Get cart items count
function get_cart_count($conn, $user_id) {
    try {
        $query_cart = "SELECT SUM(qty) as total FROM cart WHERE customer_id = :customer_id";
        $stmt_cart = $conn->prepare($query_cart);
        $stmt_cart->execute([':customer_id' => $user_id]);
        $result = $stmt_cart->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

// Send email notification
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: DistroZone <no-reply@distrozone.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number
function validate_phone($phone) {
    return preg_match('/^[0-9]{10,13}$/', $phone);
}

// Create slug
function create_slug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Get setting value
function get_setting($name) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :name");
    $stmt->execute([':name' => $name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['setting_value'] ?? null;
}

// Log activity
function log_activity($user_id, $action, $details = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
                           VALUES (:user_id, :action, :details, :ip, :agent)");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':action' => $action,
        ':details' => $details,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Get pagination links
function get_pagination_links($current_page, $total_pages, $url, $max_links = 5) {
    $links = [];
    
    $start = max(1, $current_page - floor($max_links / 2));
    $end = min($total_pages, $start + $max_links - 1);
    
    if ($end - $start + 1 < $max_links) {
        $start = max(1, $end - $max_links + 1);
    }
    
    // Previous link
    if ($current_page > 1) {
        $links[] = [
            'page' => $current_page - 1,
            'label' => '&laquo;',
            'active' => false,
            'url' => $url . '?page=' . ($current_page - 1)
        ];
    }
    
    // Numbered links
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'page' => $i,
            'label' => $i,
            'active' => $i == $current_page,
            'url' => $url . '?page=' . $i
        ];
    }
    
    // Next link
    if ($current_page < $total_pages) {
        $links[] = [
            'page' => $current_page + 1,
            'label' => '&raquo;',
            'active' => false,
            'url' => $url . '?page=' . ($current_page + 1)
        ];
    }
    
    return $links;
}
// Generate Code (User Code / Transaction Code)
// Generate Code (User Code / Transaction Code)
function generate_code($prefix) {
    global $conn;

    // Handle Karyawan/Admin codes (Format: KSR0001 / ADM0001) - Match with Desktop App
    if (in_array($prefix, ['KSR', 'ADM'])) {
        // Collect all used numbers
        $stmt_ids = $conn->prepare("SELECT CAST(SUBSTRING(user_code, 4) AS UNSIGNED) as num FROM users WHERE user_code LIKE :prefix ORDER BY num ASC");
        $stmt_ids->execute(['prefix' => $prefix . '%']);
        $existing_nums = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

        $new_num = 1;
        foreach ($existing_nums as $num) {
            if ($num == $new_num) {
                $new_num++;
            } else {
                break; // Gap found
            }
        }
        
        // Ensure max 4 digits (9999), though we iterate.
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }
    
    // Original Logic for TRX and others
    $date = date('Ymd');
    $code_prefix = $prefix . '-' . $date . '-';
    
    // Get the last code for today
    if (in_array($prefix, ['USR', 'CST'])) {
        $query = "SELECT MAX(CAST(SUBSTRING_INDEX(user_code, '-', -1) AS UNSIGNED)) as last_num 
                  FROM users WHERE user_code LIKE :pattern";
    } elseif ($prefix === 'TRX') {
        $query = "SELECT MAX(CAST(SUBSTRING_INDEX(kode_transaksi, '-', -1) AS UNSIGNED)) as last_num 
                  FROM transaksi WHERE kode_transaksi LIKE :pattern";
    } else {
        return $prefix . '-' . time();
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':pattern' => $code_prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $next_num = ($result['last_num'] ?? 0) + 1;
    return $code_prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

// Format DateTime
function format_datetime($datetime) {
    if (empty($datetime)) return '-';
    return date('d M Y H:i', strtotime($datetime));
}
?>