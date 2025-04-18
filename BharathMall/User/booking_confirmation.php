<?php
// Include session management
require_once 'session_management.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch the most recent booking for this user
$user_id = $_SESSION['user_id'];
$booking_query = "SELECT 
                    pb.booking_id, 
                    pb.vehicle_number, 
                    pb.booking_date, 
                    pb.start_time, 
                    pb.end_time, 
                    pb.total_price,
                    f.floor_name,
                    ps.slot_number
                FROM parking_bookings pb
                JOIN slots ps ON pb.slot_id = ps.slot_id
                JOIN floors f ON ps.floor_id = f.floor_id
                WHERE pb.user_id = ?
                ORDER BY pb.booking_id DESC
                LIMIT 1";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="booking_confirmation.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .confirmation-container {
            max-width: 500px;
            margin: 10px auto;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .booking-details {
            text-align: left;
            margin-top: 20px;
        }
        .booking-details p {
            margin: 10px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
        }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div id="booking-receipt" class="confirmation-container">
        <div class="confirmation-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        
        <?php if ($booking): ?>
        <div class="booking-details">
            <h2>Booking Details</h2>
            <p><strong>Booking ID:</strong> <?php echo $booking['booking_id']; ?></p>
            <p><strong>Floor:</strong> <?php echo $booking['floor_name']; ?></p>
            <p><strong>Slot Number:</strong> <?php echo $booking['slot_number']; ?></p>
            <p><strong>Vehicle Number:</strong> <?php echo $booking['vehicle_number']; ?></p>
            <p><strong>Booking Date:</strong> <?php echo $booking['booking_date']; ?></p>
            <p><strong>Time:</strong> <?php echo $booking['start_time'] . ' - ' . $booking['end_time']; ?></p>
            <p><strong>Total Price:</strong> ₹<?php echo number_format($booking['total_price'], 2); ?></p>
        </div>
        <?php else: ?>
        <p>No recent booking found.</p>
        <?php endif; ?>
        
        <div class="button-group">
            <a href="userhome.php" class="btn">Back to Home</a>
            <button onclick="downloadReceipt()" class="btn btn-primary">Download Receipt</button>
        </div>
    </div>

    <script>
    function downloadReceipt() {
        const element = document.getElementById('booking-receipt');
        const opt = {
            margin:       [10, 10, 10, 10],
            filename:     'parking_receipt_<?php echo $booking ? $booking['booking_id'] : 'unknown'; ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save();
    }
    </script>
</body>
</html>