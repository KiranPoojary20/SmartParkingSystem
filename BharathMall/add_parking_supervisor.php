<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mall";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate supervisor ID
        $supervisorId = 'SUP' . date('YmdHis'); // Example format: SUP20241223123456
        
        // Sanitize and validate input
        $supervisorName = $conn->real_escape_string($_POST['supervisorName']);
        $phoneNo = $conn->real_escape_string($_POST['phoneNo']);
        $address = $conn->real_escape_string($_POST['address']);
        $username = $conn->real_escape_string($_POST['username']);
        
        // Hash the password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $shiftFrom = $_POST['shiftFrom'];
        $shiftTo = $_POST['shiftTo'];
        $floor = $conn->real_escape_string($_POST['floor']);
        
        // Get floor_id
        $floorQuery = "SELECT floor_id FROM floors WHERE floor_name = '$floor'";
        $floorResult = $conn->query($floorQuery);
        
        if ($floorResult->num_rows > 0) {
            $floorRow = $floorResult->fetch_assoc();
            $floorId = $floorRow['floor_id'];
            
            // Handle file upload
            $aadharImagePath = '';
            if (isset($_FILES['aadharCard']) && $_FILES['aadharCard']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/aadhar_cards/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = pathinfo($_FILES['aadharCard']['name'], PATHINFO_EXTENSION);
                $filename = $supervisorId . '_aadhar.' . $fileExtension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['aadharCard']['tmp_name'], $uploadPath)) {
                    $aadharImagePath = $uploadPath;
                } else {
                    throw new Exception("Failed to upload file");
                }
            }

            // Insert query
            $sql = "INSERT INTO parking_supervisors 
                    (supervisor_id, supervisor_name, phone_number, address, username, password, shift_from, shift_to, floor_id, aadhaar_card) 
                    VALUES 
                    ('$supervisorId', '$supervisorName', '$phoneNo', '$address', '$username', '$hashedPassword', '$shiftFrom', '$shiftTo', $floorId, '$aadharImagePath')";

            if ($conn->query($sql) === TRUE) {
                echo json_encode(['status' => 'success', 'message' => 'Supervisor added successfully']);
            } else {
                throw new Exception($conn->error);
            }
        } else {
            throw new Exception("Invalid floor selected");
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch floors for the dropdown
$floors = $conn->query("SELECT floor_name FROM floors");
$floorOptions = "";
if ($floors->num_rows > 0) {
    while ($row = $floors->fetch_assoc()) {
        $floorOptions .= "<option value=\"" . htmlspecialchars($row['floor_name']) . "\">" . htmlspecialchars($row['floor_name']) . "</option>";
    }
}

// Fetch parking supervisors for display
$supervisors = $conn->query("SELECT ps.*, f.floor_name 
                             FROM parking_supervisors ps 
                             JOIN floors f ON ps.floor_id = f.floor_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking System - Add Parking Supervisor</title>
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

        .main-content {
            margin-left: 260px;
            padding: 20px;
            flex-grow: 1;
            min-height: calc(100vh - 70px);
            box-sizing: border-box;
            margin-top: 60px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 700;
            font-family: Calibri, sans-serif;
            font-size: 20px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Calibri, sans-serif;
            font-size: 18px;
            font-weight: 500;
        }

        .supervisor-table {
            width: 100%;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            margin-top: 20px;
            overflow-x: auto;
        }

        .supervisor-table table {
            width: 100%;
            border-collapse: collapse;
            font-family: Calibri, sans-serif;
        }

        .supervisor-table th,
        .supervisor-table td {
            border: 1px solid #ddd;
            padding: 14px;
            text-align: left;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .supervisor-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .dashboard-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .supervisor-table-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .add-supervisor-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 16px 32px; /* Increased padding */
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-family: Calibri, sans-serif;
    font-size: 22px; /* Increased size */
    font-weight: 800;
    width: 26%; /* Increased width */
    display: block;
    margin: 30px auto; /* Center the button with auto margins */
}

.add-supervisor-btn:hover {
    background-color: #2980b9;
}

        .file-upload-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-upload-container input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-btn {
            border: 1px solid #ddd;
            display: inline-block;
            padding: 10px 16px;
            cursor: pointer;
            background-color: #f8f9fa;
            font-size: 20px;
        }

        .file-upload-name {
            margin-left: 10px;
            color: #6c757d;
        }

        .details-row {
            background-color: #f8f9fa;
        }

        .supervisor-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px;
        }

        .supervisor-details div {
            flex: 1 1 200px;
            margin-bottom: 5px;
        }

        .view-more-btn {
            background-color: #2980b9;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .view-more-btn:hover {
            background-color: #3498db;
        }

        .supervisor-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
        }

        .details-toggle-btn {
            background-color: white;
            color: var(--primary-color);
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
        }

        .supervisor-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px;
        }

        .additional-details {
            display: none;
        }

        .additional-details.show {
            display: table-cell;
        }

        .toggle-details-btn {
            background-color: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }

        .toggle-details-btn:hover {
            text-decoration: underline;
        }

        /* New styles for form card */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
        }

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

        <main class="main-content">
            <!-- Form Card -->
            <div class="card-container">
                <div class="form-card">
                <h2 style="text-align: center; margin-bottom: 20px; color: var(--primary-color); font-size:50px;">Add</h2>
                    <form id="addSupervisorForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="supervisorName">Supervisor Name</label>
                            <input type="text" id="supervisorName" name="supervisorName" required placeholder="Enter Supervisor Name">
                        </div>
                        <div class="form-group">
                            <label for="phoneNo">Phone Number</label>
                            <input type="tel" id="phoneNo" name="phoneNo" required placeholder="Enter Phone Number" pattern="[0-9]{10}">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required placeholder="Enter Address">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required placeholder="Enter Username" autocomplete="new-username">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required placeholder="Enter Password" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="shiftFrom">Shift From</label>
                            <input type="time" id="shiftFrom" name="shiftFrom" required>
                        </div>
                        <div class="form-group">
                            <label for="shiftTo">Shift To</label>
                            <input type="time" id="shiftTo" name="shiftTo" required>
                        </div>
                        <div class="form-group">
                            <label for="floor">Select Floor</label>
                            <select id="floor" name="floor" required>
                                <option value="">Select Floor</option>
                                <?php echo $floorOptions; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="aadharCard">Upload Aadhar Card</label>
                            <div class="file-upload-container">
                                <input type="file" id="aadharCard" name="aadharCard" accept="image/*" required>
                                <button type="button" class="file-upload-btn">Choose File</button>
                                <span class="file-upload-name">No file chosen</span>
                            </div>
                        </div>
                        <button type="submit" class="add-supervisor-btn">Add Supervisor</button>
                    </form>
                </div>
            </div>

            <!-- Table Card -->
            <div class="dashboard-card">
                <div class="supervisor-table" id="supervisorsContainer">
                    <div class="supervisor-table-header">
                        <h3>Parking Supervisors</h3>
                        <button id="toggleDetailsBtn" class="toggle-details-btn">More...</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Supervisor ID</th>
                                <th>Name</th>
                                <th>Phone No</th>
                                <th>Address</th>
                                <th class="additional-details">Username</th>
                                <th class="additional-details">Shift From</th>
                                <th class="additional-details">Shift To</th>
                                <th class="additional-details">Floor</th>
                                <th class="additional-details">Aadhar Card</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($supervisors->num_rows > 0) {
                                $index = 1;
                                while ($row = $supervisors->fetch_assoc()) {
                                    $aadharLink = $row['aadhaar_card'] ? 
                                        "<a href='" . htmlspecialchars($row['aadhaar_card']) . "' target='_blank'>View</a>" : 
                                        "No Aadhar Card";
                            ?>
                            <tr>
                                <td><?php echo $index; ?></td>
                                <td><?php echo htmlspecialchars($row['supervisor_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td class="additional-details"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td class="additional-details"><?php echo htmlspecialchars($row['shift_from']); ?></td>
                                <td class="additional-details"><?php echo htmlspecialchars($row['shift_to']); ?></td>
                                <td class="additional-details"><?php echo htmlspecialchars($row['floor_name']); ?></td>
                                <td class="additional-details"><?php echo $aadharLink; ?></td>
                            </tr>
                            <?php 
                                    $index++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleDetailsBtn = document.getElementById('toggleDetailsBtn');
            const additionalDetailsCells = document.querySelectorAll('.additional-details');
            let areDetailsVisible = false;

            toggleDetailsBtn.addEventListener('click', function() {
                areDetailsVisible = !areDetailsVisible;
                
                additionalDetailsCells.forEach(cell => {
                    cell.classList.toggle('show', areDetailsVisible);
                });

                toggleDetailsBtn.textContent = areDetailsVisible ? 'Less...' : 'More...';
            });

            // File upload name display
            document.querySelector('.file-upload-container input[type="file"]').addEventListener('change', function() {
                const fileName = this.value.split('\\').pop();
                const fileUploadName = document.querySelector('.file-upload-name');
                fileUploadName.textContent = fileName ? fileName : 'No file chosen';
            });

            // File upload button click handler
            document.querySelector('.file-upload-btn').addEventListener('click', function() {
                document.getElementById('aadharCard').click();
            });

            // Form submission handler
            document.getElementById('addSupervisorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate phone number
                const phoneNo = document.getElementById('phoneNo');
                const phoneRegex = /^[0-9]{10}$/;
                if (!phoneRegex.test(phoneNo.value)) {
                    alert('Please enter a valid 10-digit phone number');
                    return;
                }

                // Validate password
                const password = document.getElementById('password');
                if (password.value.length < 8) {
                    alert('Password must be at least 8 characters long');
                    return;
                }

                // Validate shift times
                const shiftFrom = document.getElementById('shiftFrom');
                const shiftTo = document.getElementById('shiftTo');
                if (shiftFrom.value >= shiftTo.value) {
                    alert('Shift end time must be later than shift start time');
                    return;
                }

                // Create FormData object
                const formData = new FormData(this);

                // Send AJAX request
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the supervisor');
                });
            });
        });
    </script>
</body>
</html>