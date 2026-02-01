<?php
echo "<pre>";
$conn = new mysqli("localhost", "root", "", "distrozone_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "SELECT * FROM transaksi WHERE id IN (5, 6)";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
    print_r($row);
    
    // Check hidden characters in status
    echo "Status length: " . strlen($row['status']) . "\n";
    echo "Status hex: " . bin2hex($row['status']) . "\n";
    echo "--------------------\n";
}
echo "</pre>";
?>
