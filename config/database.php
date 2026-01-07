<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "distrozone_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

// Helper Functions
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_code($prefix, $length = 8) {
    return $prefix . strtoupper(substr(uniqid(), -$length));
}

function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png']) {
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($imageFileType, $allowed_types)) {
        return ["success" => false, "message" => "Only " . implode(", ", $allowed_types) . " files allowed"];
    }
    
    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File too large (max 5MB)"];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_path = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_path)) {
        return ["success" => true, "filename" => $new_filename];
    }
    
    return ["success" => false, "message" => "Upload failed"];
}

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}
?>