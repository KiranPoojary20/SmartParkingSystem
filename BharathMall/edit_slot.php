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

// Fetch Floors
try {
    $floorsStmt = $pdo->query("SELECT DISTINCT floor_name FROM floors ORDER BY floor_name");
    $floors = $floorsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Floors Fetch Error: " . $e->getMessage());
    $floors = [];
}

// Fetch Slot Details
$slotDetails = null;
$slotId = isset($_GET['slot_id']) ? sanitizeInput($_GET['slot_id']) : null;

if ($slotId) {
    try {
        $slotStmt = $pdo->prepare("
            SELECT s.slot_id, f.floor_name, s.slot_number, s.gps_location, s.slot_price, s.status
            FROM slots s 
            JOIN floors f ON s.floor_id = f.floor_id 
            WHERE s.slot_id = ?
        ");
        $slotStmt->execute([$slotId]);
        $slotDetails = $slotStmt->fetch(PDO::FETCH_ASSOC);

        if (!$slotDetails) {
            die("Slot not found.");
        }
    } catch (PDOException $e) {
        error_log("Slot Fetch Error: " . $e->getMessage());
        die("Error fetching slot details.");
    }
}

// Update Slot Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_slot') {
    $floor_name = sanitizeInput($_POST['floor']);
    $slot_number = sanitizeInput($_POST['slot_number']);
    $gps_location = sanitizeInput($_POST['gps_location'] ?? '');
    $slot_price = sanitizeInput($_POST['slot_price']);
    $status = sanitizeInput($_POST['status']);
    $slot_id = sanitizeInput($_POST['slot_id']);

    try {
        // Get floor_id based on floor name
        $floorStmt = $pdo->prepare("SELECT floor_id FROM floors WHERE floor_name = ?");
        $floorStmt->execute([$floor_name]);
        $floor = $floorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$floor) {
            echo json_encode(['status' => 'error', 'message' => 'Floor not found']);
            exit;
        }

        // Update slot
        $updateStmt = $pdo->prepare("
            UPDATE slots 
            SET floor_id = ?, 
                slot_number = ?, 
                gps_location = ?, 
                slot_price = ?, 
                status = ?
            WHERE slot_id = ?
        ");
        $updateStmt->execute([
            $floor['floor_id'], 
            $slot_number, 
            $gps_location, 
            $slot_price, 
            $status,
            $slot_id
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Slot updated successfully']);
        exit;
    } catch (PDOException $e) {
        error_log("Slot Update Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error updating slot: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parking Slot - Smart Parking System</title>
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

        .edit-slot-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            padding: 30px;
            max-width: 1040px;
            margin: 120px auto;
        }

        .edit-slot-container h1 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--primary-color);
    font-size: 32px; /* Increased font size */
}

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-family: Calibri, sans-serif;
            font-size: 20px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Calibri, sans-serif;
            font-size: 18px;
        }

        .edit-slot-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 15px 30px; /* Increased padding */
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-family: Calibri, sans-serif;
    font-size: 20px; /* Increased font size */
    margin-top: 20px; /* Added margin top */
    width: 200px; /* Set specific width */
    display: block; /* Make it block level */
    margin-left: auto; /* Center the button */
    margin-right: auto;
}

        .edit-slot-btn:hover {
            background-color: #2980b9;
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
            <div class="edit-slot-container">
                <?php if ($slotDetails): ?>
                <h1 style="text-align: center; margin-bottom: 20px; color: var(--primary-color);">Edit Parking Slot</h1>
                
                <form id="editSlotForm">
                    <input type="hidden" id="slotId" name="slot_id" value="<?php echo htmlspecialchars($slotDetails['slot_id']); ?>">
                    
                    <div class="form-group">
                        <label for="floor">Select Floor</label>
                        <select id="floor" name="floor" required>
                            <?php 
                            foreach ($floors as $floor) {
                                $selected = ($floor === $slotDetails['floor_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($floor) . "' $selected>" . htmlspecialchars($floor) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="slotNumber">Slot Number</label>
                        <input type="text" id="slotNumber" name="slot_number" 
                               value="<?php echo htmlspecialchars($slotDetails['slot_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gpsLocation">GPS Location URL</label>
                        <input type="url" id="gpsLocation" name="gps_location" 
                               value="<?php echo htmlspecialchars($slotDetails['gps_location'] ?? ''); ?>" 
                               placeholder="Enter GPS Location URL">
                    </div>
                    
                    <div class="form-group">
                        <label for="slotPrice">Slot Price</label>
                        <input type="number" id="slotPrice" name="slot_price" 
                               value="<?php echo htmlspecialchars($slotDetails['slot_price']); ?>" 
                               step="0.01" placeholder="Enter Slot Price" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Slot Status</label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo ($slotDetails['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="booked" <?php echo ($slotDetails['status'] === 'booked') ? 'selected' : ''; ?>>Booked</option>
                            <option value="parked" <?php echo ($slotDetails['status'] === 'parked') ? 'selected' : ''; ?>>Parked</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="edit-slot-btn">Edit Slot</button>
                </form>
                <?php else: ?>
                    <p style="text-align: center; color: red;">No slot selected for editing.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editSlotForm = document.getElementById('editSlotForm');

            if (editSlotForm) {
                editSlotForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update_slot');

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            window.location.href = 'add_slot.php';
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the slot');
                    });
                });
            }
        });
    </script>
</body>
</html>