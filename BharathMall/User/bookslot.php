<?php
// Session management file
require_once 'session_management.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch floors for dropdown
$floors_query = "SELECT floor_id, floor_name FROM floors";
$floors_result = $conn->query($floors_query);

// AJAX handler for fetching slots based on selected floor
if (isset($_GET['action']) && $_GET['action'] == 'fetch_slots') {
    $floor_id = $_GET['floor_id'];
    
    $slots_query = "SELECT 
                        ps.slot_id, 
                        ps.slot_number, 
                        ps.slot_price, 
                        ps.status,
                        CASE 
                            WHEN pb.booking_id IS NOT NULL AND pb.booking_date = CURRENT_DATE 
                            AND pb.booking_status IN ('confirmed', 'in_progress') 
                            THEN 'booked' 
                            ELSE ps.status 
                        END AS current_status
                    FROM slots ps
                    LEFT JOIN parking_bookings pb ON ps.slot_id = pb.slot_id 
                    WHERE ps.floor_id = ? 
                    GROUP BY ps.slot_id
                    ORDER BY ps.slot_number";
    
    $stmt = $conn->prepare($slots_query);
    $stmt->bind_param("i", $floor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    
    echo json_encode($slots);
    exit();
}

// Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $floor_id = $_POST['floor_id'];
    $slot_id = $_POST['slot_id'];
    $vehicle_number = $_POST['vehicle_number'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $total_price = $_POST['total_price'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Check slot availability
        $check_slot_query = "SELECT status FROM slots 
                              WHERE slot_id = ? 
                              AND NOT EXISTS (
                                  SELECT 1 FROM parking_bookings 
                                  WHERE slot_id = ? 
                                  AND booking_date = ? 
                                  AND booking_status IN ('confirmed', 'in_progress')
                              ) FOR UPDATE";
        
        $stmt = $conn->prepare($check_slot_query);
        $stmt->bind_param("iis", $slot_id, $slot_id, $booking_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("Slot is not available for the selected date.");
        }

        // Insert booking
        $booking_query = "INSERT INTO parking_bookings (
            user_id, slot_id, vehicle_number, booking_date, 
            start_time, end_time, total_price, booking_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("iissssd", 
            $user_id, $slot_id, $vehicle_number, 
            $booking_date, $start_time, $end_time, $total_price
        );
        $stmt->execute();

        // Update slot status
        $update_slot_query = "UPDATE slots SET status = 'booked' WHERE slot_id = ?";
        $stmt = $conn->prepare($update_slot_query);
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $booking_success = true;
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $booking_error = "Booking failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking - Book Slot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .parking-container {
            display: flex;
            gap: 20px;
        }
        .parking-selection {
            width: 40%;
        }
        .parking-form {
            width: 60%;
        }
        .parking-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .slot-card {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            height: 100px;
        }
        .slot-card.available {
            background-color: #28a745;
            color: white;
        }
        .slot-card.booked {
            background-color: #ffc107;
            color: black;
            cursor: not-allowed;
        }
        .slot-card.parked {
            background-color: #dc3545;
            color: white;
            cursor: not-allowed;
        }
        .slot-card.selected {
            border: 3px solid #007bff;
            box-shadow: 0 0 10px rgba(0,123,255,0.5);
        }
        .slot-card .slot-number {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .slot-card .slot-status {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Book Your Parking Slot</h1>
        
        <?php if (isset($booking_success)): ?>
            <div class="alert alert-success">
                Booking successful! Your slot has been reserved.
            </div>
        <?php endif; ?>
        
        <?php if (isset($booking_error)): ?>
            <div class="alert alert-danger">
                <?php echo $booking_error; ?>
            </div>
        <?php endif; ?>
        
        <div class="parking-container">
            <div class="parking-selection">
                <div class="card">
                    <div class="card-body">
                        <h3>Select Floor</h3>
                        <select id="floor-select" class="form-control mb-3">
                            <option value="">Select Floor</option>
                            <?php 
                            $floors_result->data_seek(0);
                            while($floor = $floors_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $floor['floor_id']; ?>">
                                    <?php echo $floor['floor_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <h3>Parking Slots</h3>
                        <div id="parking-slots-container" class="parking-slots">
                            <p class="text-muted">Select a floor to view available slots</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="parking-form">
                <form id="booking-form" method="POST">
                    <div class="card">
                        <div class="card-body">
                            <input type="hidden" id="floor-id" name="floor_id">
                            <input type="hidden" id="slot-id" name="slot_id">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Selected Floor</label>
                                        <input type="text" id="selected-floor" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Selected Slot</label>
                                        <input type="text" id="selected-slot" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Vehicle Number</label>
                                        <input type="text" id="vehicle-number" name="vehicle_number" 
                                               class="form-control" placeholder="Enter vehicle number" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Booking Date</label>
                                        <input type="date" id="booking-date" name="booking_date" 
                                               class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Start Time</label>
                                        <input type="time" id="start-time" name="start_time" 
                                               class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>End Time</label>
                                        <input type="time" id="end-time" name="end_time" 
                                               class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="total-price" name="total_price">

                            <div class="card mb-3">
                                <div class="card-body">
                                    <h4>Price Details</h4>
                                    <p>Rate: ₹<span id="slot-rate">0.00</span> per minute</p>
                                    <p>Total Price: ₹<span id="calculated-price">0.00</span></p>
                                </div>
                            </div>

                            <button type="submit" id="proceed-btn" class="btn btn-primary w-100" disabled>
                                Book Slot
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Set min date to today
        var today = new Date().toISOString().split('T')[0];
        $('#booking-date').attr('min', today);

        // Fetch slots when floor is selected
        $('#floor-select').change(function() {
            var floorId = $(this).val();
            if (floorId) {
                $.ajax({
                    url: '',
                    method: 'GET',
                    data: {
                        action: 'fetch_slots',
                        floor_id: floorId
                    },
                    dataType: 'json',
                    success: function(slots) {
                        var slotsContainer = $('#parking-slots-container');
                        slotsContainer.empty();

                        // Get floor name for display
                        var floorName = $('#floor-select option:selected').text();
                        $('#selected-floor').val(floorName);
                        $('#floor-id').val(floorId);

                        if (slots.length === 0) {
                            slotsContainer.append('<p class="text-muted">No slots available on this floor</p>');
                            return;
                        }

                        slots.forEach(function(slot) {
                            // Create slot card for ALL slots
                            var slotCard = $(`
                                <div class="slot-card ${slot.current_status}" 
                                     data-slot-id="${slot.slot_id}" 
                                     data-slot-number="${slot.slot_number}" 
                                     data-slot-price="${slot.slot_price}">
                                    <div class="slot-number">Slot ${slot.slot_number}</div>
                                    <div class="slot-status">${slot.current_status.toUpperCase()}</div>
                                </div>
                            `);

                            slotsContainer.append(slotCard);
                        });

                        // Slot selection logic
                        $('.slot-card.available').click(function() {
                            $('.slot-card').removeClass('selected');
                            $(this).addClass('selected');
                            
                            var slotId = $(this).data('slot-id');
                            var slotNumber = $(this).data('slot-number');
                            var slotPrice = $(this).data('slot-price');
                            
                            $('#selected-slot').val(slotNumber);
                            $('#slot-id').val(slotId);
                            $('#slot-rate').text(slotPrice);
                            
                            // Reset form validation for price calculation
                            $('#proceed-btn').prop('disabled', true);
                        });
                    }
                });
            } else {
                $('#parking-slots-container').empty();
                $('#proceed-btn').prop('disabled', true);
            }
        });

        // Price calculation
        $('#start-time, #end-time, #booking-date').change(function() {
            var startTime = new Date('1970-01-01T' + $('#start-time').val());
            var endTime = new Date('1970-01-01T' + $('#end-time').val());
            var bookingDate = $('#booking-date').val();
            var selectedSlot = $('#slot-id').val();
            
            // Calculate time difference in minutes
            var timeDiff = (endTime - startTime) / (1000 * 60);
            
            if (timeDiff > 0 && bookingDate && selectedSlot) {
                var slotRate = parseFloat($('#slot-rate').text());
                var totalPrice = (timeDiff * slotRate).toFixed(2);
                
                $('#calculated-price').text(totalPrice);
                $('#total-price').val(totalPrice);
                
                $('#proceed-btn').prop('disabled', false);
            } else {
                $('#calculated-price').text('0.00');
                $('#total-price').val('0');
                $('#proceed-btn').prop('disabled', true);
            }
        });
    });
    </script>
</body>
</html>