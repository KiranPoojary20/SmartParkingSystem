<?php
session_start();

// Database Configuration
$host = 'localhost';
$dbname = 'mall';
$username = 'root';
$password = '';

// Enhanced Database Connection with Error Handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact the system administrator.");
}

// Input Sanitization Function
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Add Floor Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_floor') {
    $floor_name = sanitizeInput($_POST['floor_name']);

    try {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM floors WHERE floor_name = ?");
        $checkStmt->execute([$floor_name]);

        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt = $pdo->prepare("INSERT INTO floors (floor_name) VALUES (?)");
            $insertStmt->execute([$floor_name]);
            echo json_encode(['status' => 'success', 'message' => 'Floor added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Floor already exists']);
        }
        exit;
    } catch (PDOException $e) {
        error_log(message: "Floor Addition Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error adding floor']);
        exit;
    }
}

// Add Slot Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_slot') {
    $floor_name = sanitizeInput($_POST['floor']);
    $slot_number = sanitizeInput($_POST['slot_number']);
    $gps_location = sanitizeInput($_POST['gps_location']);
    $slot_price = sanitizeInput($_POST['slot_price'] ?? 30.0);

    try {
        $floorStmt = $pdo->prepare("SELECT floor_id FROM floors WHERE floor_name = ?");
        $floorStmt->execute([$floor_name]);
        $floor = $floorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$floor) {
            echo json_encode(['status' => 'error', 'message' => 'Floor not found']);
            exit;
        }

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE floor_id = ? AND slot_number = ?");
        $checkStmt->execute([$floor['floor_id'], $slot_number]);

        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt = $pdo->prepare("INSERT INTO slots (floor_id, slot_number, status, gps_location, slot_price) VALUES (?, ?, 'available', ?, ?)");
            $insertStmt->execute([$floor['floor_id'], $slot_number, $gps_location, $slot_price]);
            echo json_encode(['status' => 'success', 'message' => 'Slot added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Slot already exists on this floor']);
        }
        exit;
    } catch (PDOException $e) {
        error_log("Slot Addition Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error adding slot: ' . $e->getMessage()]);
        exit;
    }
}

// Delete Slot Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_slot') {
    $slot_id = sanitizeInput($_POST['slot_id']);

    try {
        $deleteStmt = $pdo->prepare("DELETE FROM slots WHERE slot_id = ?");
        $deleteStmt->execute([$slot_id]);

        if ($deleteStmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Slot deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Slot not found']);
        }
        exit;
    } catch (PDOException $e) {
        error_log("Slot Deletion Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error deleting slot']);
        exit;
    }
}

// Fetch Floors
try {
    $floorsStmt = $pdo->query("SELECT DISTINCT floor_name FROM floors ORDER BY floor_name");
    $floors = $floorsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Floors Fetch Error: " . $e->getMessage());
    $floors = [];
}

// Fetch Slots with Details (updated to include more information)
try {
    $slotsStmt = $pdo->query("
        SELECT s.slot_id, f.floor_name, s.slot_number, s.status, s.gps_location, s.slot_price
        FROM slots s 
        JOIN floors f ON s.floor_id = f.floor_id 
        ORDER BY f.floor_name, s.slot_number
    ");
    $slots = $slotsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize slots by floor
    $floorSlots = [];
    foreach ($slots as $slot) {
        $floorSlots[$slot['floor_name']][] = $slot;
    }
} catch (PDOException $e) {
    error_log("Slots Fetch Error: " . $e->getMessage());
    $floorSlots = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parking Slot - Smart Parking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;
            --secondary-color: #f0f3f7;
            --text-color: #0c1b2e;
            --sidebar-bg: #d6dbdf;
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
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    letter-spacing: 1px;
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

.sidebar a.active {
    background-color: #04AA6D;
    color: white;
}

.sidebar a:hover:not(.active) {
    background-color: #1a5276;
    color: white;
}

/* Responsive styles for sidebar */
@media screen and (max-width: 700px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .sidebar a {
        float: left;
    }
    .content {
        margin-left: 0;
    }
}

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .add-slot-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    padding: 15px;
    max-width: 1200px; /* Increased max-width from 1000px to 1200px */
    margin: 50px auto;
}

.add-slot-container h1 {
    text-align: center;
    margin-bottom: 25px;
    color: var(--primary-color);
    font-size: 2.3rem;
    font-weight: 700;
    letter-spacing: 1px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 12px;
    font-weight: 800; /* Made font weight bolder */
    font-family: Calibri, sans-serif;
    font-size: 20px; /* Slightly increased font size */
    /* color: #1a5276; */
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-family: Calibri, sans-serif;
    font-size: 18px;
    font-weight: 600; /* Added bold to input text */
    
}

.add-slot-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-family: Calibri, sans-serif;
    font-size: 1.3rem;
    font-weight: 700; /* Made button text bolder */
    width: 250px; /* Increased button width */
    display: block;
    margin: 30px auto 0;
}

/* Title styling */
.add-slot-container h1 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--primary-color);
    font-size: 2.8rem; /* Increased title size */
    font-weight: 800; /* Made title bolder */
  
    letter-spacing: 1px;
}

/* Floor input section styling */
.floor-management-card {
    margin-bottom: 30px;
}

.floor-input input[type="text"] {
    padding: 15px;
    font-size: 1.2rem;
    font-weight: 600; /* Added bold to floor input */
    border: 2px solid #ddd;
    border-radius: 8px;
}

.add-floor-btn {
    padding: 15px 25px;
    font-size: 1.2rem;
    font-weight: 700; /* Made floor button text bolder */
    border-radius: 8px;
}

/* Placeholder text styling */
.form-group input::placeholder,
.form-group select::placeholder {
    font-weight: 600; /* Made placeholder text bold */
    color: #95a5a6; /* Slightly darker placeholder color */
}

        .floors-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .floor-details-card {
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    padding: 25px;
    width: 100%;
    margin-bottom: 20px;
}

.floor-details-title {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 1.8em;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
}

.slots-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.slot-details-card {
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.slot-available {
    background-color: #2ecc71;
    color: white;
}

/* .slot-occupied {
    background-color: #e74c3c;
    color: white;
} */

.slot-number {
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 10px;
}

.slot-status {
    font-size: 1em;
    text-transform: capitalize;
}

        .floor-input {
            display: flex;
            flex-grow: 1;
            gap: 10px;
            margin-left: 20px;
            margin-right: 20px;
        }

        .floor-input input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Calibri, sans-serif;
        }

        .add-floor-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            white-space: nowrap;
            font-family: Calibri, sans-serif;
        }

        .add-floor-btn:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                overflow-y: visible;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .slots-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .slot-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }
        .slot-details-card {
            position: relative;
            transition: transform 0.3s ease;
        }

        .slot-details-card:hover {
            transform: scale(1.05);
        }

        .slot-hover-actions {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .slot-details-card:hover .slot-hover-actions {
            display: block;
        }

        .slot-hover-actions .edit-btn, 
        .slot-hover-actions .delete-btn {
            display: block;
            width: 120px;
            margin: 10px 0;
            text-align: center;
        }
        .slot-details-card {
            position: relative;
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .slot-action-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            z-index: 20;
        }

        .slot-action-icon:hover {
            background-color: rgba(200, 200, 200, 0.9);
        }

        .slot-action-icon i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .slot-hover-actions {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 30;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: row;
            gap: 10px;
            align-items: center;
        }

        .slot-hover-actions.show {
            transform: translate(-50%, -50%) scale(1);
        }

        .slot-hover-actions .edit-btn, 
        .slot-hover-actions .delete-btn {
            padding: 6px 10px;
            font-size: 12px;
            margin: 0;
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
            <ul class="sidebar-nav">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="add_slot.php"><i class="fas fa-parking"></i> Parking Slot</a></li>
                <li><a href="add_parking_supervisor.php"><i class="fas fa-user-tie"></i> Parking Supervisor</a></li>
                <li><a href="live_parking.php"><i class="fas fa-video"></i> Live Parking</a></li>
                <li><a href="report.php"><i class="fas fa-chart-line"></i> Reports</a></li>
            </ul>
        </nav>

        <div class="main-content">
            <div class="add-slot-container">
            <h1 style="text-align: center; margin-bottom: 20px; color: var(--primary-color); font-size:50px;">Add</h1>
                
                <!-- Floor Addition Section -->
                <div class="floor-management-card">
                    <div class="floor-input">
                        <input type="text" id="newFloorName" placeholder="Enter New Floor Name">
                        <input type="submit" id="addFloorBtn" class="add-floor-btn" value="Add Floor">
                    </div>
                </div>
                
                <!-- Slot Addition Section -->
                <form id="addSlotForm" style="background-color: white; padding: 20px; border-radius: 10px;">
                    <div class="form-group">
                        <label for="floor">Select Floor</label>
                        <select id="floor" required>
                            <option value="">Select Floor</option>
                            <?php 
                            foreach ($floors as $floor) {
                                echo "<option value='" . htmlspecialchars($floor) . "'>" . htmlspecialchars($floor) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="slotNo">Slot Number</label>
                        <input type="text" id="slotNo" required placeholder="Enter Slot Number">
                    </div>
                    <div class="form-group">
                        <label for="gpsLocation">GPS Location URL</label>
                        <input type="url" id="gpsLocation" placeholder="Enter GPS Location URL">
                    </div>
                    <div class="form-group">
                        <label for="slotPrice">Slot Price</label>
                        <input type="number" id="slotPrice" value="30" step="0.01" placeholder="Enter Slot Price">
                    </div>
                    <button type="submit" class="add-slot-btn">Add Slot</button>
                </form>
            </div>

            <!-- Floors Container -->
            <div id="floorsContainer" class="floors-container">
                <?php
                if (!empty($floorSlots)) {
                    foreach ($floorSlots as $floorName => $slots) {
                        echo "<div class='floor-details-card'>";
                        echo "<h2 class='floor-details-title'>" . htmlspecialchars($floorName) . " Details</h2>";
                        echo "<div class='slots-details-grid'>";
                        
                        foreach ($slots as $slot) {
                            echo "<div class='slot-details-card slot-available'>";
                            echo "<div class='slot-action-icon'><i class='fas fa-ellipsis-v'></i></div>";
                            echo "<div class='slot-number'>" . htmlspecialchars($slot['slot_number']) . "</div>";
                            echo "<div class='slot-status'>" . htmlspecialchars(ucfirst($slot['status'])) . "</div>";
                    
                            // Show GPS Location if available
                            if (!empty($slot['gps_location'])) {
                                echo "<div class='slot-gps'><a href='" . htmlspecialchars($slot['gps_location']) . "' target='_blank'>View Location</a></div>";
                            }
                            
                            // Hover Actions
                            echo "<div class='slot-hover-actions'>";
                            echo "<a href='edit_slot.php?slot_id=" . htmlspecialchars($slot['slot_id']) . "' class='edit-btn'>Edit</a>";
                            echo "<button class='delete-btn' data-slot-id='" . htmlspecialchars($slot['slot_id']) . "'>Delete</button>";
                            echo "</div>";
                            
                            echo "</div>";
                        }
                        
                        echo "</div></div>";
                    }
                } else {
                    echo "<div style='text-align: center; color: #7f8c8d; width: 100%; padding: 20px;'>No floors or slots added yet</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Slot Addition Script
            document.getElementById('addSlotForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const floor = document.getElementById('floor').value;
                const slotNo = document.getElementById('slotNo').value;
                const gpsLocation = document.getElementById('gpsLocation').value || '';
                const slotPrice = document.getElementById('slotPrice').value || 30;

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add_slot&floor=' + encodeURIComponent(floor) + 
                          '&slot_number=' + encodeURIComponent(slotNo) + 
                          '&gps_location=' + encodeURIComponent(gpsLocation) +
                          '&slot_price=' + encodeURIComponent(slotPrice)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the slot');
                });
            });

            // Floor Addition Script
            document.getElementById('addFloorBtn').addEventListener('click', function() {
                const newFloorName = document.getElementById('newFloorName').value.trim();
                
                if (!newFloorName) {
                    alert('Please enter a floor name');
                    return;
                }

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add_floor&floor_name=' + encodeURIComponent(newFloorName)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the floor');
                });
            });

            // Slot Action Icon and Hover Actions Handling
    document.querySelectorAll('.slot-details-card').forEach(card => {
        const actionIcon = card.querySelector('.slot-action-icon');
        const hoverActions = card.querySelector('.slot-hover-actions');

        // When action icon is clicked
        actionIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Close any other open action menus
            document.querySelectorAll('.slot-hover-actions').forEach(actions => {
                if (actions !== hoverActions) {
                    actions.classList.remove('show');
                }
            });

            // Toggle current menu
            hoverActions.classList.toggle('show');
        });

        // Hide hover actions when mouse leaves the card
        card.addEventListener('mouseleave', function() {
            hoverActions.classList.remove('show');
        });
    });

    // Close action menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.slot-details-card')) {
            document.querySelectorAll('.slot-hover-actions').forEach(actions => {
                actions.classList.remove('show');
            });
        }
    });

            // Delete Slot Script
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-btn')) {
                    const slotId = e.target.getAttribute('data-slot-id');
                    
                    if (confirm('Are you sure you want to delete this parking slot?')) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete_slot&slot_id=' + encodeURIComponent(slotId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the slot');
                        });
                    }
                }
            });
        });
    </script>
</body>