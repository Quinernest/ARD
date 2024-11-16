<?php
include_once "db_connection.php";

// Database credentials
$servername = "localhost";
$username = "root";
$password = ""; // Default password for XAMPP MySQL
$dbname = "student_rewards"; // Replace with your actual database name

// Establish connection to the MySQL database
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
        $url = "http://192.168.2.138/admin_voucher.php"; // Replace with your Orange Pi IP address
        $data = http_build_query([
            'voucher_code' => $voucher_code,
            'duration' => $duration,
            'duration_unit' => $duration_unit
        ]);

        // Use cURL for sending the POST request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        // Check for errors in cURL
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch) . "\n";
            error_log("Failed to send voucher: " . $voucher_code . " Error: " . curl_error($ch), 3, "/path/to/log/file.log");
        } else {
            // Check if the response indicates success
            echo "Voucher transfer successful: " . $voucher_code . " Response: " . $response . "\n";
        }
        curl_close($ch);
    }

    echo "Voucher sync complete!";
} else {
    echo "No vouchers to sync.";
}

// Close the database connection
$conn->close();
?>

