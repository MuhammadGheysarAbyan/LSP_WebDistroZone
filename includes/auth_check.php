<?php
// includes/auth_check.php

function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        header("Location: ../auth/login.php");
        exit();
    }
    
    return true;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function check_admin() {
    check_auth('admin');
}

function check_kasir() {
    check_auth('kasir');
}

function check_customer() {
    check_auth('customer');
}

// Check store hours
function is_store_open($type = 'offline') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT isi_setting FROM settings WHERE nama_setting = :setting";
    $stmt = $conn->prepare($query);
    
    if ($type == 'offline') {
        $stmt->bindValue(':setting', 'jam_operasional_offline');
    } else {
        $stmt->bindValue(':setting', 'jam_operasional_online');
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $hours = json_decode($result['isi_setting'], true);
        $current_day = date('N'); // 1 = Monday, 7 = Sunday
        $current_time = date('H:i');
        
        // Offline: Closed on Monday (1)
        if ($type == 'offline' && $current_day == 1) {
            return false;
        }
        
        $open_time = $hours['open'] ?? '10:00';
        $close_time = $hours['close'] ?? ($type == 'offline' ? '20:00' : '17:00');
        
        return ($current_time >= $open_time && $current_time <= $close_time);
    }
    
    return false;
}
?>