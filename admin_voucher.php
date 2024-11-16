<?php
include_once "db_connection.php";

session_start();
$conn = new mysqli('localhost', 'root', '', 'student_rewards'); 

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST requests to create a voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['voucher_code']) && isset($_POST['duration']) && isset($_POST['duration_unit'])) {
        $voucher_code = $_POST['voucher_code'];
        $duration = $_POST['duration'];
        $duration_unit = $_POST['duration_unit'];

        // Insert voucher into the database
        $insert_sql = "INSERT INTO vouchers (voucher_code, duration, duration_unit) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sis", $voucher_code, $duration, $duration_unit);
        if ($stmt->execute()) {
            echo "Voucher inserted successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Missing required fields!";
    }
}


// Query to fetch existing vouchers
$vouchers_query = "SELECT voucher_code, time_created, duration, duration_unit FROM vouchers";
$vouchers_result = $conn->query($vouchers_query);

if (!$vouchers_result) {
    die("Error fetching vouchers: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Voucher Management</title>
    <link rel="stylesheet" href="adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modal styles */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            position: relative;
        }

        .close-modal {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .close-modal:hover {
            color: black;
        }
        .success-message {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #4CAF50;
  color: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  text-align: center;
  width: 80%;
  max-width: 400px;
}

.success-message p {
  margin: 0;
  font-size: 1.2em;
}

.success-message.fade-out {
  animation: fadeOut 2s forwards;
}

@keyframes fadeOut {
  from {
      opacity: 1;
  }
  to {
      opacity: 0;
      visibility: hidden;
  }
}
    </style>
</head>
<body>
    
<div class="header">
    <h1>Admin Panel</h1>
</div>
<div class="sidebar">
    <h2>Manage Students</h2>
    <ul>
        <li><a href="admin.php"><i class="fas fa-users"></i> Manage Students</a></li>
        <li><a href="admindash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="manage_posts.php"><i class="fas fa-edit"></i> Manage Posts</a></li>
        <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        <li><a href="admin_voucher.php"><i class="fas fa-file-alt"></i> Voucher</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
    </ul>
</div>

<div class="main-content">
    <h2>Create a New Voucher</h2>
    <form action="admin_voucher.php" method="POST">
        <label for="voucher_code">Voucher Code:</label>
        <input type="text" id="voucher_code" name="voucher_code" required>
        
        <label for="duration">Duration:</label>
        <input type="number" id="duration" name="duration" min="1" required>
        
        <label for="duration_unit">Unit:</label>
        <select id="duration_unit" name="duration_unit">
            <option value="minutes">Minutes</option>
            <option value="hours">Hours</option>
            <option value="days">Days</option>
            <option value="weeks">Weeks</option>
            <option value="months">Months</option>
        </select>
        
        <button type="submit" name="create_voucher">Create Voucher</button>
    </form>
    <?php if (isset($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>

    <h2>Existing Vouchers</h2>
    <table>
        <tr>
            <th>Voucher Code</th>
            <th>Time Created</th>
            <th>Duration</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $vouchers_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['voucher_code']); ?></td>
                <td><?php echo htmlspecialchars($row['time_created']); ?></td>
                <td><?php echo htmlspecialchars($row['duration'] . ' ' . $row['duration_unit']); ?></td>
                <td>
                    <button class="send-button" data-voucher-code="<?php echo htmlspecialchars($row['voucher_code']); ?>">
                        Send
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
    <div class="success-message">
        <p>Email sent successfully!</p>
    </div>
<?php endif; ?>

    <!-- Modal for Sending Voucher -->
    <div id="sendVoucherModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Send Voucher via Email</h2>
            <form id="sendVoucherForm" action="sendVoucher.php" method="POST">
                <input type="hidden" name="voucher_code" id="voucherCode">
                <label for="recipient_email">Recipient Email:</label>
                <input type="email" name="recipient_email" id="recipientEmail" required>
                <button type="submit">Send Voucher</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(function() {
                successMessage.classList.add('fade-out');
            }, 2000); // Message will fade out after 2 seconds
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        const sendButtons = document.querySelectorAll('.send-button');
        const modal = document.getElementById('sendVoucherModal');
        const closeModal = document.querySelector('.close-modal');
        const voucherCodeInput = document.getElementById('voucherCode');

        // Show modal and set voucher code when "Send" button is clicked
        sendButtons.forEach(button => {
            button.addEventListener('click', function() {
                const voucherCode = this.getAttribute('data-voucher-code');
                voucherCodeInput.value = voucherCode;  // Set the voucher code in the hidden input
                modal.style.display = 'block';  // Show the modal
            });
        });

        // Close modal when close button is clicked
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>
