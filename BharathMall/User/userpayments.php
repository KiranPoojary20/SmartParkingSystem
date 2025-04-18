<?php
// Include session management
require_once 'session_management.php';

// Ensure booking details exist in session
$booking_details = isset($_SESSION['booking_details']) ? $_SESSION['booking_details'] : null;

// Handle payment and booking confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_completed'])) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check slot availability again before booking
        $check_slot_query = "SELECT status FROM slots 
                              WHERE slot_id = ? 
                              AND NOT EXISTS (
                                  SELECT 1 FROM parking_bookings 
                                  WHERE slot_id = ? 
                                  AND booking_date = ? 
                                  AND booking_status IN ('confirmed', 'in_progress')
                              ) FOR UPDATE";
        
        $stmt = $conn->prepare($check_slot_query);
        $stmt->bind_param("iis", 
            $booking_details['slot_id'], 
            $booking_details['slot_id'], 
            $booking_details['booking_date']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Slot is no longer available.");
        }

        // Insert booking
        $booking_query = "INSERT INTO parking_bookings (
            user_id, slot_id, vehicle_number, booking_date, 
            start_time, end_time, total_price, booking_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("iissssd", 
            $booking_details['user_id'], 
            $booking_details['slot_id'], 
            $booking_details['vehicle_number'], 
            $booking_details['booking_date'], 
            $booking_details['start_time'], 
            $booking_details['end_time'], 
            $booking_details['total_price']
        );
        $stmt->execute();

        // Update slot status
        $update_slot_query = "UPDATE slots SET status = 'booked' WHERE slot_id = ?";
        $stmt = $conn->prepare($update_slot_query);
        $stmt->bind_param("i", $booking_details['slot_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Clear booking details from session
        unset($_SESSION['booking_details']);

        // Redirect to a success page
        header("Location: booking_confirmation.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $booking_error = "Booking failed: " . $e->getMessage();
    }
}

// Fetch floor names 
$floors_query = "SELECT floor_id, floor_name FROM floors";
$floors_result = $conn->query($floors_query);
$floor_map = [];
while($floor = $floors_result->fetch_assoc()) {
    $floor_map[$floor['floor_id']] = $floor['floor_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;  /* Dark blue-gray color */
            --secondary-color: #34495E;  /* Slightly lighter shade */
            --text-color: #333;
            --background-color: #f4f4f4;
            --hover-color: #e6f2ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        

       
        body, html {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
}

.container {
    width: 100%;
    max-width: 100%;
    padding: 0;
    margin: 0;
}

.payment-container {
    width: 100%;
    padding: 0 15px;
}


        

        .payment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .back-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
        }

        .payment-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .payment-methods {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .payment-option i {
            font-size: 30px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .payment-option:hover {
            background-color: var(--hover-color);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: var(--hover-color);
        }

        .order-summary {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .summary-details {
            margin-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-weight: bold;
        }

        #pay-now-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }

        #pay-now-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .payment-container {
                flex-direction: column;
            }

            .payment-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .top-nav {
    height: 64px;
    background-color: rgb(0, 0, 0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    justify-content: center;
}
.booking-header {
    text-align: center;
    
    font-size: 24px;
    color: var(--primary-color);
}
.top-header {
    background-color: var(--primary-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 22px 26px;
}

.logo {
    color: white;
    font-size: 2.8rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-align: center; 
    font-family: 'Playfair Display', calibri;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
}

    </style>
</head>
<body>
    <div class="container">
        
    <header class="top-header">
            <div class="logo">Payments</div>
        </header>
        
        <?php if (isset($booking_error)): ?>
            <div class="alert alert-danger">
                <?php echo $booking_error; ?>
            </div>
        <?php endif; ?>
        <?php include 'sidebar.html'; ?>
        
        <div class="payment-container">
            <div class="payment-methods">
                <h2>Select Payment Method</h2>
                <div class="payment-options">
                    <div class="payment-option" data-method="credit-card">
                        <i class="fas fa-credit-card"></i>
                        <span>Credit Card</span>
                    </div>
                    <div class="payment-option" data-method="upi">
                        <i class="fab fa-google-pay"></i>
                        <span>UPI</span>
                    </div>
                    <div class="payment-option" data-method="net-banking">
                        <i class="fas fa-university"></i>
                        <span>Net Banking</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="order-summary-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Order Summary</h2>
                    <button class="close-modal" onclick="closeOrderSummary()">&times;</button>
                </div>
                <div class="order-summary">
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Floor</span>
                            <span id="floor-name">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Slot</span>
                            <span id="slot-number">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Vehicle Number</span>
                            <span id="vehicle-number">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Booking Date</span>
                            <span id="booking-date">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Booking Time</span>
                            <span id="booking-time">-</span>
                        </div>
                        <div class="summary-total">
                            <span>Total Amount</span>
                            <span id="total-amount">₹0.00</span>
                        </div>
                    </div>
                    <form id="payment-form" method="POST">
                        <input type="hidden" name="payment_completed" value="1">
                        <button type="submit" id="pay-now-btn" disabled>Pay Now</button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const paymentOptions = document.querySelectorAll('.payment-option');
        const payNowBtn = document.getElementById('pay-now-btn');
        const orderSummaryModal = document.getElementById('order-summary-modal');

        // Booking details from PHP session
        const bookingDetails = {
            floor: '<?php echo isset($booking_details['floor_id']) ? $booking_details['floor_id'] : ''; ?>',
            slot: '<?php echo isset($booking_details['slot_id']) ? $booking_details['slot_id'] : ''; ?>',
            vehicleNumber: '<?php echo isset($booking_details['vehicle_number']) ? $booking_details['vehicle_number'] : ''; ?>',
            bookingDate: '<?php echo isset($booking_details['booking_date']) ? $booking_details['booking_date'] : ''; ?>',
            startTime: '<?php echo isset($booking_details['start_time']) ? $booking_details['start_time'] : ''; ?>',
            endTime: '<?php echo isset($booking_details['end_time']) ? $booking_details['end_time'] : ''; ?>',
            totalPrice: '<?php echo isset($booking_details['total_price']) ? $booking_details['total_price'] : '0'; ?>'
        };

        // Floor mapping from PHP
        const floorMap = <?php echo json_encode($floor_map); ?>;

        // Get floor name
        function getFloorName(floorId) {
            return floorMap[floorId] || 'Unknown';
        }

        // Populate order summary
        function populateOrderSummary() {
            document.getElementById('floor-name').textContent = getFloorName(bookingDetails.floor);
            document.getElementById('slot-number').textContent = bookingDetails.slot;
            document.getElementById('vehicle-number').textContent = bookingDetails.vehicleNumber;
            document.getElementById('booking-date').textContent = bookingDetails.bookingDate;
            document.getElementById('booking-time').textContent = `${bookingDetails.startTime} - ${bookingDetails.endTime}`;
            document.getElementById('total-amount').textContent = `₹${bookingDetails.totalPrice}`;
        }

        // Payment method selection
        paymentOptions.forEach(option => {
            option.addEventListener('click', () => {
                // Remove selected from all options
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected to clicked option
                option.classList.add('selected');
                
                // Show order summary modal
                orderSummaryModal.style.display = 'flex';
                populateOrderSummary();
                
                // Enable pay now button
                payNowBtn.disabled = false;
            });
        });
    });

    function closeOrderSummary() {
        document.getElementById('order-summary-modal').style.display = 'none';
    }
    </script>
</body>
</html>