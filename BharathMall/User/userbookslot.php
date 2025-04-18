<?php
    // Session management file
require_once 'session_management.php';



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

// Handle booking details submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store booking details in session to pass to payment page
    $_SESSION['booking_details'] = [
        'user_id' => $_SESSION['user_id'],
        'floor_id' => $_POST['floor_id'],
        'slot_id' => $_POST['slot_id'],
        'vehicle_number' => $_POST['vehicle_number'],
        'booking_date' => $_POST['booking_date'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time'],
        'total_price' => $_POST['total_price']
    ];

    // Redirect to payment page
    header("Location: userpayments.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking - Book Slot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;  /* Dark blue-gray color */
            --secondary-color: #34495E;  /* Slightly lighter shade */
            --background-color: #f4f7f6;
            --text-color: #333;
            --booked-color: #FFC107;
            --parked-color: #DC3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    
        h1 {
    display: block;
    font-size: 1.5em;
    margin-block-start: 0.67em;
    margin-block-end: 0.67em;
    margin-inline-start: 0px;
    margin-inline-end: 0px;
    font-weight: bold;
    unicode-bidi: isolate;
    color:#3498DB;
}



html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        } */

        /* .parking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        } */
        .parking-container {
    max-width: 100%;
    height: 100vh;
    margin: 0;
    /* padding: 20px; */
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

        .header {
            text-align: center;
            
            background:black;
        }
/* 
        .floor-selection {
            margin-bottom: 20px;

        } */
         .floor-selection {
    flex-grow: 1;
    overflow: auto;
    
    
}

        /* .floor-dropdown {
            
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            
            
        }  */

        #floor-select {
            width: 300px;
            max-width: 90%;
            margin: 0 auto;
            display: block;
            text-align: center;
            text-align-last: center;
            padding:10px;
        }

        .floor-dropdown {
            width: 300px;
            max-width: 90%;
            margin: 0 auto;
            text-align: center;
            text-align-last: center;
        }

        

        .parking-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            padding:10px;
        }

        .slot-card {
            
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            height: 60px;
        }

        .slot-card.available {
            background-color: var(--secondary-color);
            color: white;
        }

        .slot-card.booked {
            background-color: var(--booked-color);
            color: black;
            cursor: not-allowed;
        }

        .slot-card.parked {
            background-color: var(--parked-color);
            color: white;
            cursor: not-allowed;
        }

        .slot-card.selected {
            border: 3px solid var(--primary-color);
            transform: scale(1.05);
        }

        .booking-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .booking-modal-content {
            background-color: white;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            padding: 20px;
        }

        .booking-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-color);
        }

        .form-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        



        /* Responsive Design */
        @media (max-width: 768px) {
            .parking-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .booking-modal-content {
                width: 95%;
                max-width: 95%;
            }
        }

        @media (max-width: 480px) {
            .parking-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
    <div class="parking-container">

    <header class="top-header">
            <div class="logo">Smart Parking Bookings</div>
        </header>
        <?php include 'sidebar.html'; ?>
        <div class="floor-selection">
            <select id="floor-select" class="floor-dropdown">
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
            

            <div id="parking-slots-container" class="parking-grid">
                <p style="color: #6c757d;">Select a floor to view available slots</p>
            </div>
        </div>

        <!-- Booking Modal -->
        <div id="booking-modal" class="booking-modal">
            <div class="booking-modal-content">
                <span id="close-modal" class="booking-modal-close">&times;</span>
                <form id="parking-booking-form" method="POST">
                    <h2>Booking Details</h2>
                    
                    <input type="hidden" id="floor-id" name="floor_id">
                    <input type="hidden" id="slot-id" name="slot_id">

                    <div>
                        <label>Selected Floor</label>
                        <input type="text" id="selected-floor" class="form-input" readonly>
                    </div>

                    <div>
                        <label>Selected Slot</label>
                        <input type="text" id="selected-slot" class="form-input" readonly>
                    </div>

                    <div>
                        <label>Vehicle Number</label>
                        <input type="text" id="vehicle-number" name="vehicle_number" 
                               class="form-input" placeholder="Enter vehicle number" required>
                    </div>

                    <div>
                        <label>Booking Date</label>
                        <input type="date" id="booking-date" name="booking_date" 
                               class="form-input" required>
                    </div>

                    <div>
                        <label>Start Time</label>
                        <input type="time" id="start-time" name="start_time" 
                               class="form-input" required>
                    </div>

                    <div>
                        <label>End Time</label>
                        <input type="time" id="end-time" name="end_time" 
                               class="form-input" required>
                    </div>

                    <div>
                        <h3>Price Details</h3>
                        <p>Rate: ₹<span id="slot-rate">0.00</span> per minute</p>
                        <p>Total Price: ₹<span id="calculated-price">0.00</span></p>
                    </div>

                    <input type="hidden" id="total-price" name="total_price">

                    <button type="submit" id="proceed-btn" class="btn-primary" disabled>
                        Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var bookingModal = document.getElementById('booking-modal');
        var closeModalBtn = document.getElementById('close-modal');

        // Set min date to today
        var today = new Date().toISOString().split('T')[0];
        document.getElementById('booking-date').setAttribute('min', today);

        // Fetch slots when floor is selected
        document.getElementById('floor-select').addEventListener('change', function() {
            var floorId = this.value;
            if (floorId) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?action=fetch_slots&floor_id=' + floorId, true);
                xhr.responseType = 'json';
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var slots = xhr.response;
                        var slotsContainer = document.getElementById('parking-slots-container');
                        slotsContainer.innerHTML = '';

                        // Get floor name for display
                        var floorSelect = document.getElementById('floor-select');
                        var floorName = floorSelect.options[floorSelect.selectedIndex].text;
                        document.getElementById('selected-floor').value = floorName;
                        document.getElementById('floor-id').value = floorId;

                        if (slots.length === 0) {
                            slotsContainer.innerHTML = '<p style="color: #6c757d;">No slots available on this floor</p>';
                            return;
                        }

                        slots.forEach(function(slot) {
                            // Create slot card for ALL slots
                            var slotCard = document.createElement('div');
                            slotCard.className = 'slot-card ' + slot.current_status;
                            slotCard.setAttribute('data-slot-id', slot.slot_id);
                            slotCard.setAttribute('data-slot-number', slot.slot_number);
                            slotCard.setAttribute('data-slot-price', slot.slot_price);
                            
                            slotCard.innerHTML = `
                                <div class="slot-number">Slot ${slot.slot_number}</div>
                                <div class="slot-status">${slot.current_status.toUpperCase()}</div>
                            `;

                            if (slot.current_status === 'available') {
                                slotCard.addEventListener('click', function() {
                                    // Remove selected from all slots
                                    document.querySelectorAll('.slot-card').forEach(function(card) {
                                        card.classList.remove('selected');
                                    });
                                    
                                    this.classList.add('selected');
                                    
                                    var slotId = this.getAttribute('data-slot-id');
                                    var slotNumber = this.getAttribute('data-slot-number');
                                    var slotPrice = this.getAttribute('data-slot-price');
                                    
                                    document.getElementById('selected-slot').value = slotNumber;
                                    document.getElementById('slot-id').value = slotId;
                                    document.getElementById('slot-rate').textContent = slotPrice;
                                    
                                    // Reset form validation for price calculation
                                    document.getElementById('proceed-btn').disabled = true;
                                    
                                    // Show booking modal
                                    bookingModal.style.display = 'flex';
                                });
                            }

                            slotsContainer.appendChild(slotCard);
                        });
                    }
                };

                xhr.send();
            } else {
                document.getElementById('parking-slots-container').innerHTML = '';
                document.getElementById('proceed-btn').disabled = true;
            }
        });

        // Close modal when close button is clicked
        closeModalBtn.addEventListener('click', function() {
            bookingModal.style.display = 'none';
            // Deselect slots
            document.querySelectorAll('.slot-card').forEach(function(card) {
                card.classList.remove('selected');
            });
        });

        // Close modal when clicking outside the modal content
        bookingModal.addEventListener('click', function(e) {
            if (e.target === bookingModal) {
                bookingModal.style.display = 'none';
                // Deselect slots
                document.querySelectorAll('.slot-card').forEach(function(card) {
                    card.classList.remove('selected');
                });
            }
        });

        // Price calculation and validation
        ['start-time', 'end-time', 'booking-date'].forEach(function(elementId) {
            document.getElementById(elementId).addEventListener('change', calculatePrice);
        });

        function calculatePrice() {
            var startTimeInput = document.getElementById('start-time');
            var endTimeInput = document.getElementById('end-time');
            var bookingDateInput = document.getElementById('booking-date');
            var selectedSlotInput = document.getElementById('slot-id');
            var proceedBtn = document.getElementById('proceed-btn');

            var startTime = new Date('1970-01-01T' + startTimeInput.value);
            var endTime = new Date('1970-01-01T' + endTimeInput.value);
            var bookingDate = bookingDateInput.value;
            var selectedSlot = selectedSlotInput.value;
            
            // Calculate time difference in minutes
            var timeDiff = (endTime - startTime) / (1000 * 60);
            
            if (timeDiff > 0 && bookingDate && selectedSlot) {
                var slotRate = parseFloat(document.getElementById('slot-rate').textContent);
                var totalPrice = (timeDiff * slotRate).toFixed(2);
                
                document.getElementById('calculated-price').textContent = totalPrice;
                document.getElementById('total-price').value = totalPrice;
                
                proceedBtn.disabled = false;
            } else {
                document.getElementById('calculated-price').textContent = '0.00';
                document.getElementById('total-price').value = '0';
                proceedBtn.disabled = true;
            }
        }

        // Form submission handler
        document.getElementById('parking-booking-form').addEventListener('submit', function(e) {
            // Validate vehicle number
            var vehicleNumber = document.getElementById('vehicle-number').value.trim();
            if (!vehicleNumber) {
                e.preventDefault();
                alert('Please enter a valid vehicle number');
                return;
            }

            // Validate date and time
            var bookingDate = document.getElementById('booking-date').value;
            var startTime = document.getElementById('start-time').value;
            var endTime = document.getElementById('end-time').value;

            if (!bookingDate || !startTime || !endTime) {
                e.preventDefault();
                alert('Please fill in all date and time fields');
                return;
            }

            // Additional time validation
            var startDateTime = new Date('1970-01-01T' + startTime);
            var endDateTime = new Date('1970-01-01T' + endTime);

            if (endDateTime <= startDateTime) {
                e.preventDefault();
                alert('End time must be later than start time');
                return;
            }

            // Validate slot selection
            var selectedSlot = document.getElementById('slot-id').value;
            if (!selectedSlot) {
                e.preventDefault();
                alert('Please select a parking slot');
                return;
            }
        });

        // Prevent selecting past dates
        document.getElementById('booking-date').addEventListener('change', function() {
            var selectedDate = new Date(this.value);
            var today = new Date();
            
            // Reset time if date is in the past
            if (selectedDate < today) {
                this.value = today.toISOString().split('T')[0];
                alert('You cannot select a past date');
            }
        });

        // Time restriction to prevent booking in the past
        document.getElementById('start-time').addEventListener('change', function() {
            var selectedDate = document.getElementById('booking-date').value;
            var today = new Date();
            var selectedDateTime = new Date(selectedDate);

            // If selected date is today, restrict start time to future times
            if (selectedDateTime.toDateString() === today.toDateString()) {
                var currentTime = today.getHours() + ':' + 
                    (today.getMinutes() < 10 ? '0' : '') + today.getMinutes();
                
                if (this.value <= currentTime) {
                    this.value = '';
                    alert('You can only book slots for future times today');
                }
            }
        });
    });
    </script>
</body>
</html>