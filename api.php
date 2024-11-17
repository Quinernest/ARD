<?php
header('Content-Type: application/json');
include_once 'db_connection.php';

$conn = new mysqli('localhost', 'root', '', 'student_rewards');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch vouchers from the main database
$vouchers_query = "SELECT voucher_code, time_created, duration, duration_unit FROM vouchers";
$result = $conn->query($vouchers_query);

if ($result->num_rows > 0) {
    $vouchers = [];
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }

    // Send data to the Orange Pi
    $url = 'http://192.168.2.138/receive_vouchers.php'; // Replace with Orange Pi's IP
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($vouchers),
        ],
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        die("Error syncing data to Orange Pi");
    }

    echo $response; // Display response from the Orange Pi
} else {
    echo json_encode(["status" => "error", "message" => "No vouchers to sync"]);
}


$conn->close();
?>



