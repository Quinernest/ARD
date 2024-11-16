<?php
// Database connection on your laptop
$servername = "localhost";
$username = "root";
$password = ""; // Default password for XAMPP MySQL
$dbname = "student_rewards"; // Replace with your actual database name

// Connect to the database on your laptop
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get vouchers from your laptop's database (adjust query as needed)
$sql = "SELECT * FROM vouchers WHERE reward_amount IS NOT NULL";
$result = $conn->query($sql);

// Check if there are any vouchers to transfer
if ($result->num_rows > 0) {
    // Loop through the vouchers and send them to the Orange Pi PC via POST request
    while ($row = $result->fetch_assoc()) {
        $voucher_code = $row['voucher_code'];
        $duration = $row['duration'];
        $duration_unit = $row['duration_unit'];

        // Prepare data to send
        $data = http_build_query([
            'voucher_code' => $voucher_code,
            'duration' => $duration,
            'duration_unit' => $duration_unit
        ]);

        // URL of the Orange Pi PC's admin_voucher.php script
        $url = "http://192.168.2.138/admin_voucher.php"; // Replace with your Orange Pi's IP address

        // Set up the HTTP POST request
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $data
            ]
        ];

        // Create the stream context and send the POST request
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        // Check if the transfer was successful
        if ($response === FALSE) {
            echo "Error transferring voucher: " . $voucher_code . "\n";
        } else {
            echo "Voucher transfer successful: " . $voucher_code . "\n";
        }
    }

    echo "Voucher sync complete!";
} else {
    echo "No vouchers to sync.";
}

// Close the database connection on your laptop
$conn->close();
?>
