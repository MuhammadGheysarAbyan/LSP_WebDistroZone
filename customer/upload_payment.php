<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaksi_id = $_POST['transaksi_id'];
    $customer_id = $_SESSION['user_id'];
    
    // Verify transaction belongs to customer
    $query = "SELECT * FROM transaksi WHERE id = :id AND customer_id = :customer_id AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $transaksi_id, ':customer_id' => $customer_id]);
    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaksi) {
        header("Location: orders.php?error=invalid_transaction");
        exit;
    }
    
    // Handle file upload
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $upload_result = upload_file($_FILES['bukti_transfer'], '../assets/uploads/payments/', ['jpg', 'jpeg', 'png']);
        
        if ($upload_result['success']) {
            $file_path = 'assets/uploads/payments/' . $upload_result['filename'];
            
            // Check if proof already exists
            $query_check = "SELECT id FROM payment_proof WHERE transaksi_id = :transaksi_id";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->execute([':transaksi_id' => $transaksi_id]);
            
            if ($stmt_check->rowCount() > 0) {
                // Update existing proof
                $query_update = "UPDATE payment_proof 
                                SET file_bukti = :file, tanggal_upload = NOW(), status = 'pending' 
                                WHERE transaksi_id = :transaksi_id";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->execute([
                    ':file' => $file_path,
                    ':transaksi_id' => $transaksi_id
                ]);
            } else {
                // Insert new proof
                $query_insert = "INSERT INTO payment_proof (transaksi_id, customer_id, file_bukti, 
                                tanggal_upload, status) 
                                VALUES (:transaksi_id, :customer_id, :file, NOW(), 'pending')";
                $stmt_insert = $conn->prepare($query_insert);
                $stmt_insert->execute([
                    ':transaksi_id' => $transaksi_id,
                    ':customer_id' => $customer_id,
                    ':file' => $file_path
                ]);
            }
            
            header("Location: orders.php?success=payment_uploaded");
            exit;
        } else {
            header("Location: orders.php?error=" . urlencode($upload_result['message']));
            exit;
        }
    } else {
        header("Location: orders.php?error=no_file");
        exit;
    }
} else {
    header("Location: orders.php");
    exit;
}
?>