<?php
require_once 'session_management.php';

// Fetch booking history
$query = "
    SELECT 
        pb.booking_id, 
        f.floor_name, 
        ps.slot_number, 
        pb.vehicle_number, 
        pb.booking_date, 
        pb.start_time, 
        pb.end_time, 
        pb.total_price
    FROM 
        parking_bookings pb
    JOIN 
        slots ps ON pb.slot_id = ps.slot_id
    JOIN 
        floors f ON ps.floor_id = f.floor_id
    WHERE 
        pb.user_id = ?
    ORDER BY 
        pb.booking_date DESC, pb.booking_id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Bookings</title>
    <link rel="stylesheet" href="usermybookings.css">
</head>
<body>
    <div class="container">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
            <header class="top-nav">
            <h1 class="booking-header">My Booking History</h1>
            </header>
            <?php include 'sidebar.html'; ?>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Floor</th>
                            <th>Slot Number</th>
                            <th>Vehicle Number</th>
                            <th>Booking Date</th>
                            <th>Duration</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Booking ID"><?php echo htmlspecialchars($row['booking_id']); ?></td>
                                <td data-label="Floor"><?php echo htmlspecialchars($row['floor_name']); ?></td>
                                <td data-label="Slot Number"><?php echo htmlspecialchars($row['slot_number']); ?></td>
                                <td data-label="Vehicle Number"><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                                <td data-label="Booking Date"><?php echo htmlspecialchars($row['booking_date']); ?></td>
                                <td data-label="Duration">
                                    <?php 
                                    $start = new DateTime($row['start_time']);
                                    $end = new DateTime($row['end_time']);
                                    $interval = $start->diff($end);
                                    
                                    $hours = $interval->h;
                                    $minutes = $interval->i;
                                    
                                    echo htmlspecialchars(sprintf("%d hours %d minutes", $hours, $minutes)); 
                                    ?>
                                </td>
                                <td data-label="Total Price">₹<?php echo number_format($row['total_price'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-bookings">
                <p>No booking history found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>