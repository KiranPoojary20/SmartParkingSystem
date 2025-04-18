<?php
// Database connection
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'mall';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Get all floors
function getFloors() {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT DISTINCT f.floor_id, f.floor_name 
        FROM floors f 
        INNER JOIN slots s ON f.floor_id = s.floor_id 
        ORDER BY f.floor_id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get slots by floor
function getSlotsByFloor($floor_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM slots WHERE floor_id = ?");
    $stmt->execute([$floor_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get booking details
function getBookingDetails($slot_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            u.phone_number,
            pb.booking_date,
            pb.start_time,
            pb.vehicle_number
        FROM parking_bookings pb
        JOIN users u ON pb.user_id = u.user_id
        WHERE pb.slot_id = ? 
        AND pb.booking_status = 'confirmed'
        AND pb.actual_exit_time IS NULL
        ORDER BY pb.booking_date DESC, pb.start_time DESC
        LIMIT 1
    ");
    $stmt->execute([$slot_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle AJAX requests for slot details
if (isset($_POST['action']) && $_POST['action'] === 'getSlotDetails') {
    $slot_id = $_POST['slot_id'];
    $details = getBookingDetails($slot_id);
    
    if ($details) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'name' => $details['full_name'],
                'phone' => $details['phone_number'],
                'bookingDate' => $details['booking_date'],
                'bookingTime' => $details['start_time'],
                'vehicleNumber' => $details['vehicle_number']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No booking found']);
    }
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$floors = getFloors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking System - Live Parking</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;
            --secondary-color: #f0f3f7;
            --text-color: #0c1b2e;
            --sidebar-bg: #d6dbdf;
            --road-color: #95a5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Calibri, 'Montserrat', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
            font-weight: 600;
            font-size: 17px;
        }

        .topbar {
            background-color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .topbar-title {
            font-family: 'Playfair Display', calibri;
            font-size: 2.8rem;
            color: #ffffff;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .logout-btn {
            background-color: white;
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: var(--secondary-color);
        }

        .logout-btn li {
            list-style: none;
        }

        .logout-btn li a {
            text-decoration: none;
            color: #1c2833;
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .logout-btn li a i {
            margin-right: 5px;
        }

        .dashboard-container {
            display: flex;
            margin-top: 70px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100%;
            background-color: #C8C8C8;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            font-family: "calibri";
            margin-top: 75px;
        }

        .sidebar a {
            display: block;
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            font-size: 19px;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover:not(.active) {
            background-color: #1a5276;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .main-content h1 {
            text-align: center;
            margin: 30px 0 30px 0;
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .floor-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .floor-heading {
            text-align: center;
            margin-bottom: 20px;
        }

        .parking-section {
            position: relative;
            margin-top: 20px;
            padding-top: 20px;
        }

        .road-vertical {
            width: 50px;
            background-color: var(--road-color);
            position: absolute;
            top: 0;
            bottom: 0;
            z-index: 1;
        }

        .road-vertical-left {
            left: 0;
        }

        .road-vertical-right {
            right: 0;
        }

        .slots-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            position: relative;
            padding: 0 50px;
            margin: 0 50px;
        }

        .road {
            width: 100%;
            height: 50px;
            background-color: var(--road-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-style: italic;
            position: relative;
        }

        .road::before,
        .road::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 50px;
            background-color: var(--road-color);
            top: 0;
        }

        .road::before {
            left: -50px;
        }

        .road::after {
            right: -50px;
        }

        .slots-row {
            display: flex;
            gap: 15px;
            justify-content: center;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .slot-card {
            width: 160px;
            height: 110px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            position: relative;
        }

        .slot-available {
            background-color: #2ecc71;
        }

        .slot-booked {
            background-color: #f39c12;
        }

        .slot-parked {
            background-color: #e74c3c;
        }

        .details-btn {
            position: absolute;
            bottom: 10px;
            background-color: rgba(255,255,255,0.8);
            color: #2c3e50;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .live-preview-container {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .camera-container {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .video-wrapper {
        width: 640px;  /* Fixed width */
        height: 360px; /* Reduced height */
        background: #000;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-feed {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

        @media screen and (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-title">Smart Parking System</div>
        <button class="logout-btn">
            <li><a href="main.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </button>
    </div>

    <div class="dashboard-container">
        <nav class="sidebar">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="add_slot.php"><i class="fas fa-parking"></i> Parking Slot</a>
            <a href="add_parking_supervisor.php"><i class="fas fa-user-tie"></i> Parking Supervisor</a>
            <a href="live_parking.php" class="active"><i class="fas fa-video"></i> Live Parking</a>
            <a href="report.php"><i class="fas fa-chart-line"></i> Reports</a>
        </nav>

        <main class="main-content">
            <h1>Live Parking</h1>
            <div id="parkingContainer">
                <?php foreach ($floors as $floor): ?>
                    <?php 
                    $slots = getSlotsByFloor($floor['floor_id']);
                    $slotsPerRow = 5;
                    ?>
                    <div class="floor-card">
                        <h2 class="floor-heading"><?php echo htmlspecialchars($floor['floor_name']); ?></h2>
                        
                        <!-- Live Preview Section -->
                        <div class="live-preview-container">
    <h3><?php echo htmlspecialchars($floor['floor_name']); ?> - Live Camera Feed</h3>
    <div class="camera-container">
        <div class="video-wrapper">
            <img id="videoFeed-<?php echo $floor['floor_id']; ?>" 
                 class="video-feed"
                 alt="Loading camera feed..." 
                 style="width: 100%; height: 100%; background: #000;">
        </div>
    </div>
</div>

                        <!-- Parking Section -->
                        <div class="parking-section">
                            <div class="road-vertical road-vertical-left"></div>
                            <div class="road-vertical road-vertical-right"></div>
                            
                            <div class="slots-container">
                                <div class="road">Entrance Road</div>
                                <?php 
                             $totalSlots = count($slots);
                                for ($rowStart = 0; $rowStart < $totalSlots; $rowStart += $slotsPerRow * 2):
                                ?>
                                    <!-- First row of slots -->
                                    <div class="slots-row">
                                        <?php for ($i = $rowStart; $i < min($rowStart + $slotsPerRow, $totalSlots); $i++): ?>
                                            <?php $slot = $slots[$i]; ?>
                                            <div class="slot-card slot-<?php echo $slot['status']; ?>" data-slot-id="<?php echo $slot['slot_id']; ?>">
                                                <div>Slot <?php echo htmlspecialchars($slot['slot_number']); ?></div>
                                                <div><?echo ucfirst($slot['status']); ?></div>
                                                <?php if ($slot['status'] !== 'available'): ?>
                                                    <button class="details-btn" onclick="showSlotDetails(<?php echo $slot['slot_id']; ?>)">Details</button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                    <!-- Second row of slots -->
                                    <?php if ($rowStart + $slotsPerRow < $totalSlots): ?>
                                        <div class="slots-row">
                                            <?php for ($i = $rowStart + $slotsPerRow; $i < min($rowStart + $slotsPerRow * 2, $totalSlots); $i++): ?>
                                                <?php $slot = $slots[$i]; ?>
                                                <div class="slot-card slot-<?php echo $slot['status']; ?>" data-slot-id="<?php echo $slot['slot_id']; ?>">
                                                    <div>Slot <?php echo htmlspecialchars($slot['slot_number']); ?></div>
                                                    <div><?php echo ucfirst($slot['status']); ?></div>
                                                    <?php if ($slot['status'] !== 'available'): ?>
                                                        <button class="details-btn" onclick="showSlotDetails(<?php echo $slot['slot_id']; ?>)">Details</button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        
                                        <?php if ($rowStart + $slotsPerRow * 2 < $totalSlots): ?>
                                            <div class="road">Connecting Road</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <div class="road">Exit Road</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="bookedModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('bookedModal')">&times;</span>
            <h2>Booking Details</h2>
            <div id="bookedDetails"></div>
        </div>
    </div>

    <script>
    // Slot details functionality
    function showSlotDetails(slotId) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=getSlotDetails&slot_id=${slotId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const details = data.data;
                const modalContent = `
                    <p><strong>Name:</strong> ${details.name}</p>
                    <p><strong>Phone:</strong> ${details.phone}</p>
                    <p><strong>Booking Date:</strong> ${details.bookingDate}</p>
                    <p><strong>Booking Time:</strong> ${details.bookingTime}</p>
                    <p><strong>Vehicle Number:</strong> ${details.vehicleNumber}</p>
                `;
                document.getElementById('bookedDetails').innerHTML = modalContent;
                document.getElementById('bookedModal').style.display = 'block';
            } else {
                alert('Could not fetch booking details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching the details');
        });
    }

    // Modal close functionality
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Window click event for closing modals
    window.onclick = function(event) {
        const bookedModal = document.getElementById('bookedModal');
        
        if (event.target == bookedModal) {
            bookedModal.style.display = 'none';
        }
    };

    // Setup video feed for each floor
    document.addEventListener('DOMContentLoaded', function() {
        function setupVideoFeed(floorId) {
            const videoFeed = document.getElementById(`videoFeed-${floorId}`);
            if (!videoFeed) return;

            function updateFeed() {
                videoFeed.src = `http://localhost:5000/video_feed?t=${new Date().getTime()}`;
            }

            updateFeed();

            videoFeed.onerror = function() {
                console.log(`Error loading feed for floor ${floorId}`);
                setTimeout(updateFeed, 1000);
            };

            videoFeed.onload = function() {
                console.log(`Feed loaded successfully for floor ${floorId}`);
            };
        }

        // Setup feed for each floor
        <?php foreach ($floors as $floor): ?>
            setupVideoFeed(<?php echo $floor['floor_id']; ?>);
        <?php endforeach; ?>
    });

    // Auto-refresh functionality
    function refreshSlotStatus() {
        location.reload();
    }
    // Refresh every 5 minutes
    setInterval(refreshSlotStatus, 300000);
    </script>
</body>
</html>