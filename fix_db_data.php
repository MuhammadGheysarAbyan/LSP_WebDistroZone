<?php
$baseDir = __DIR__ . '/';
$baseDir = 'c:/xampp/htdocs/distrozoneweb/';

require_once $baseDir . 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "Checking for recent active transactions...\n";

// Find a candidate
$sql = "SELECT t.id, t.kode_transaksi, t.status, pp.verified_at 
        FROM transaksi t 
        LEFT JOIN payment_proof pp ON t.id = pp.transaksi_id 
        WHERE t.status IN ('verified', 'sent') 
        LIMIT 1";
$stmt = $conn->query($sql);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "Found Candidate: " . $row['kode_transaksi'] . " (Status: " . $row['status'] . ")\n";
    
    // Update it to be 2 days old
    $oldDate = date('Y-m-d H:i:s', strtotime('-2 days'));
    echo "Updating verified_at to: $oldDate\n";
    
    $upd = "UPDATE payment_proof SET verified_at = :date WHERE transaksi_id = :id";
    $ustmt = $conn->prepare($upd);
    $ustmt->execute(['date' => $oldDate, 'id' => $row['id']]);
    
    // Also update t.waktu in case fallback is used
    $upd2 = "UPDATE transaksi SET waktu = :date WHERE id = :id";
    $ustmt2 = $conn->prepare($upd2);
    $ustmt2->execute(['date' => $oldDate, 'id' => $row['id']]);
    
    echo "Update executed.\n";
    
    // Run auto-complete logic now
    echo "Running auto-complete logic...\n";
    
    if(file_exists($baseDir . 'auto_complete_orders.php')){
         include $baseDir . 'auto_complete_orders.php';
    } else {
        echo "Could not find auto_complete_orders.php\n";
    }
   
    echo "\nAuto-complete script ran. Check status.\n";
    
    // Check Status again
    $chk = $conn->prepare("SELECT status FROM transaksi WHERE id = ?");
    $chk->execute([$row['id']]);
    $newStatus = $chk->fetchColumn();
    echo "New Status: $newStatus\n";
    
} else {
    echo "No 'verified' or 'sent' transactions found. Checking 'pending'...\n";
    
    $sql2 = "SELECT id, kode_transaksi FROM transaksi WHERE status = 'pending' LIMIT 1";
    $stmt2 = $conn->query($sql2);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if($row2) {
         echo "Found Pending candidate: " . $row2['kode_transaksi'] . ". Forcing to VERIFIED and OLD date to test logic.\n";
         
         // Update to verified and old date
         $oldDate = date('Y-m-d H:i:s', strtotime('-2 days'));
         // Update Transaksi
         $conn->prepare("UPDATE transaksi SET status = 'verified', waktu = ? WHERE id = ?")->execute([$oldDate, $row2['id']]);
         
         // Add/Update payment proof
         // Check if proof exists
         $chkP = $conn->prepare("SELECT id FROM payment_proof WHERE transaksi_id = ?");
         $chkP->execute([$row2['id']]);
         if($chkP->fetch()) {
             $conn->prepare("UPDATE payment_proof SET verified_at = ? WHERE transaksi_id = ?")->execute([$oldDate, $row2['id']]);
         } else {
             $conn->prepare("INSERT INTO payment_proof (transaksi_id, image, verified_at, verified_by) VALUES (?, 'dummy_test.jpg', ?, 1)")
                  ->execute([$row2['id'], $oldDate]);
         }
         
         echo "Transaction forced to VERIFIED + OLD DATE. Running auto-complete script...\n";
         
         if(file_exists($baseDir . 'auto_complete_orders.php')){
             include $baseDir . 'auto_complete_orders.php';
         }
         
         echo "\nAuto-complete script ran. Check status.\n";
         
         // Check Status again
         $chk = $conn->prepare("SELECT status FROM transaksi WHERE id = ?");
         $chk->execute([$row2['id']]);
         $newStatus = $chk->fetchColumn();
         echo "New Status: $newStatus\n";
         
    } else {
        echo "No 'pending' transactions found. Checking 'completed' to revert one for testing...\n";
        
        $sql3 = "SELECT id, kode_transaksi FROM transaksi WHERE status = 'completed' LIMIT 1";
        $stmt3 = $conn->query($sql3);
        $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        if($row3) {
             echo "Found Completed candidate: " . $row3['kode_transaksi'] . ". Reverting to VERIFIED and OLD DATE (-5 days) to test logic.\n";
             
             $oldDate = date('Y-m-d H:i:s', strtotime('-5 days'));
             
             // Update Transaksi
             $conn->prepare("UPDATE transaksi SET status = 'verified', waktu = ? WHERE id = ?")->execute([$oldDate, $row3['id']]);
             
             // Add/Update payment proof
             $chkP = $conn->prepare("SELECT id FROM payment_proof WHERE transaksi_id = ?");
             $chkP->execute([$row3['id']]);
             if($chkP->fetch()) {
                 $conn->prepare("UPDATE payment_proof SET verified_at = ? WHERE transaksi_id = ?")->execute([$oldDate, $row3['id']]);
             } else {
                 $conn->prepare("INSERT INTO payment_proof (transaksi_id, image, verified_at, verified_by) VALUES (?, 'dummy_test.jpg', ?, 1)")
                      ->execute([$row3['id'], $oldDate]);
             }
             
             echo "Transaction reverted to VERIFIED. Running auto-complete script...\n";
             
             if(file_exists($baseDir . 'auto_complete_orders.php')){
                 include $baseDir . 'auto_complete_orders.php';
             }
             
             echo "\nAuto-complete script ran. Check status.\n";
             
             // Check Status again
             $chk = $conn->prepare("SELECT status FROM transaksi WHERE id = ?");
             $chk->execute([$row3['id']]);
             $newStatus = $chk->fetchColumn();
             echo "New Status: $newStatus\n";
             
        } else {
            echo "Database is empty? No transactions found at all.\n";
        }
    }
}
?>
