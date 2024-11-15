<?php
include_once "db_connection.php";

$servername = "localhost";
$username = "root";
$password = ""; // Default password for XAMPP MySQL
$dbname = "student_rewards"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all vouchers from the laptop database
$sql = "SELECT * FROM vouchers WHERE reward_amount IS NOT NULL";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Loop through the vouchers and send them to the Orange Pi PC via REST API
    while ($row = $result->fetch_assoc()) {
        $voucher_code = $row['voucher_code'];
        $duration = $row['duration'];
        $duration_unit = $row['duration_unit'];
      

        // Send the voucher data to the Orange Pi PC's API
        $url = "http://http://192.168.2.138/admin_voucher.php"; // Replace with your Orange Pi IP address
        $data = [
            'voucher_code' => $voucher_code,
            'duration' => $duration,
            'duration_unit' => $duration_unit,
           
        ];

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // Check if the transfer was successful
        if ($result === FALSE) {
            echo "Error transferring voucher: " . $voucher_code . "\n";
        }
    }

    echo "Voucher sync complete!";
} else {
    echo "No vouchers to sync.";
}

$conn->close();
?>

