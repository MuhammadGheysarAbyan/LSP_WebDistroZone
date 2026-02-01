<?php
echo "<pre>";
$conn = new mysqli("localhost", "root", "", "distrozone_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

echo "Connected to: " . $conn->host_info . "\n";
echo "Server info: " . $conn->server_info . "\n\n";

// List Tables
echo "Tables in database:\n";
$tables = $conn->query("SHOW TABLES");
if ($tables) {
    while ($row = $tables->fetch_array()) {
        $table_name = $row[0];
        $count_res = $conn->query("SELECT COUNT(*) FROM `$table_name`");
        $count_row = $count_res->fetch_array();
        echo "- $table_name: " . $count_row[0] . " rows\n";
    }
} else {
    echo "Error listing tables: " . $conn->error;
}

echo "\nChecking transactions simply:\n";
$trx = $conn->query("SELECT * FROM transaksi LIMIT 5");
if ($trx) {
    if ($trx->num_rows > 0) {
        while ($row = $trx->fetch_assoc()) {
            echo "ID: " . $row['id'] . " | Status: " . $row['status'] . "\n";
        }
    } else {
        echo "0 transactions in select *.\n";
    }
} else {
    echo "Error selecting transaksi: " . $conn->error;
}
echo "</pre>";
?>
