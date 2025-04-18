<?php
// Include session management
require_once 'session_management.php';

// Query the database to get the necessary data
$sql = "SELECT 
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_slots,
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS booked_slots,
    SUM(CASE WHEN status = 'parked' THEN 1 ELSE 0 END) AS parked_slots
FROM slots";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $available_slots = $row['available_slots'];
    $booked_slots = $row['booked_slots'];
    $parked_slots = $row['parked_slots'];
} else {
    // Handle the case where no data is found
    $available_slots = 0;
    $booked_slots = 0;
    $parked_slots = 0;
}

// Fetch user profile information
$user_id = $_SESSION['user_id']; // Assuming you have user_id in session
$user_sql = "SELECT full_name, email, phone_number, vehicle_number FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_profile = $user_result->fetch_assoc();




// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Validate and sanitize inputs
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $vehicle_number = filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }

    if (empty($phone_number)) {
        $errors[] = "Phone number is required";
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            // Prepare update statement
            $update_sql = "UPDATE users SET 
                full_name = ?, 
                email = ?, 
                phone_number = ?, 
                vehicle_number = ? 
                WHERE user_id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssi", $full_name, $email, $phone_number, $vehicle_number, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['full_name'] = $full_name;
                
                // Prepare success response
                $response = [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                // Database error
                $response = [
                    'success' => false,
                    'message' => 'Database error: ' . $stmt->error
                ];
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Catch any unexpected errors
            $response = [
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ];
        }
    } else {
        // Validation errors
        $response = [
            'success' => false,
            'message' => implode(', ', $errors)
        ];
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;  /* Dark blue-gray color */
            --secondary-color: #34495E;  /* Slightly lighter shade */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            font-family: 'Arial', sans-serif;
            height: 100%;
            background-color: #f4f4f4;
            line-height: 1.6;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .top-header {
    background-color: var(--primary-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 25px;
}

.logo {
    color: white;
    font-size: 2.8rem;
    font-weight: 700;
    letter-spacing: 1px;
    font-family: 'Playfair Display', calibri;
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
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Dashboard Content Styles */
        .dashboard-content {
            flex-grow: 1;
            padding: 25px;
            overflow-y: auto;
            background-color: #ECF0F1;
        }

        .welcome-section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: #F1F3F4;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: scale(1.05);
        }

        .stat-title {
            color: #666;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
        }

        .available-slots .stat-value { color: #2ECC71; }
        .booked-slots .stat-value { color: #F1C40F; }
        .parked-slots .stat-value { color: #3498DB; }

        /* Quick Actions Styles */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
      
        .action-card {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .action-card:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .action-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .book-slot .action-icon { background-color: #2ECC71; }
        .view-bookings .action-icon { background-color: #3498DB; }
        .make-payment .action-icon { background-color: #F1C40F; }

        .action-title {
            font-weight: 500;
            color: #333;
        }

        /* User Profile Popup Styles */
        .popup {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .popup-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            position: relative;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
        }

        .button-group button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #edit-btn {
            background-color: #28a745;
            color: white;
        }

        #save-btn {
            background-color: #007bff;
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .quick-stats, .quick-actions {
                grid-template-columns: 1fr;
            }

            .top-header {
                padding: 10px 15px;
            }

            .logo {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Top Header -->
        <header class="top-header">
            <div class="logo">Smart Parking System</div>
            <div class="header-actions">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    Logout
                </button>
                <button class="profile-btn">
                    <i class="fas fa-user"></i>
                </button>
            </div>
        </header>
        <?php include 'sidebar.html'; ?>

        <!-- User Profile Popup -->
        <div id="user-profile-popup" class="popup">
            <div class="popup-content">
                <span class="close-btn">&times;</span>
                <h2>User Profile</h2>
                <form id="user-profile-form">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_profile['full_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_profile['phone_number']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="vehicle_number">Vehicle Number:</label>
                        <input type="text" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($user_profile['vehicle_number']); ?>" readonly>
                    </div>
                    <div class="button-group">
                        <button type="button" id="edit-btn">Edit</button>
                        <button type="button" id="save-btn" style="display:none;">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <section class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
                
                <div class="quick-stats">
                    <div class="stat-card available-slots">
                        <p class="stat-title">Available Slots</p>
                        <p class="stat-value"><?php echo $available_slots; ?></p>
                    </div>
                    <div class="stat-card booked-slots">
                        <p class="stat-title">Booked Slots</p>
                        <p class="stat-value"><?php echo $booked_slots; ?></p>
                    </div>
                    <div class="stat-card parked-slots">
                        <p class="stat-title">Parked Slots</p>
                        <p class="stat-value"><?php echo $parked_slots; ?></p>
                    </div>
                </div>

                <!-- Quick Actions Section -->
                <div class="quick-actions">
                    <div class="action-card book-slot">
                        <div class="action-icon">
                            <i class="fas fa-parking"></i>
                        </div>
                        <p class="action-title">Book a Slot</p>
                    </div>
                    <div class="action-card view-bookings">
                        <div class="action-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <p class="action-title">View Bookings</p>
                    </div>
                    <div class="action-card make-payment">
                        <div class="action-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <p class="action-title">Make Payment</p>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profileBtn = document.querySelector('.profile-btn');
            const popup = document.getElementById('user-profile-popup');
            const closeBtn = document.querySelector('.close-btn');
            const editBtn = document.getElementById('edit-btn');
            const saveBtn = document.getElementById('save-btn');
            const form = document.getElementById('user-profile-form');

            // Input fields
            const fullNameInput = document.getElementById('full_name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone_number');
            const vehicleInput = document.getElementById('vehicle_number');

            // Open popup
            profileBtn.addEventListener('click', () => {
                popup.style.display = 'block';
            });

            // Close popup
            closeBtn.addEventListener('click', () => {
                popup.style.display = 'none';
            });

            // Edit mode
            editBtn.addEventListener('click', () => {
                fullNameInput.readOnly = false;
                emailInput.readOnly = false;
                phoneInput.readOnly = false;
                vehicleInput.readOnly = false;
                
                editBtn.style.display = 'none';
                saveBtn.style.display = 'block';
            });

            // Save changes
            saveBtn.addEventListener('click', () => {
                // Prepare form data
                const formData = new FormData(form);
                formData.append('action', 'update_profile');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Success handling
                        alert(result.message);
                        
                        // Disable edit mode
                        fullNameInput.readOnly = true;
                        emailInput.readOnly = true;
                        phoneInput.readOnly = true;
                        vehicleInput.readOnly = true;
                        
                        // Reset button states
                        editBtn.style.display = 'block';
                        saveBtn.style.display = 'none';
                    } else {
                        // Error handling
                        alert('Update failed: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the profile');
                });
            });

            // Quick Actions Navigation
            const quickActionCards = document.querySelectorAll('.action-card');
            quickActionCards.forEach(card => {
                card.addEventListener('click', () => {
                    const actionTitle = card.querySelector('.action-title').textContent;
                    switch(actionTitle) {
                        case 'Book a Slot':
                            window.location.href = 'userbookslot.php';
                            break;
                        case 'View Bookings':
                            window.location.href = 'usermybookings.php';
                            break;
                        case 'Make Payment':
                            window.location.href = 'userpayments.php';
                            break;
                    }
                });
            });
        });
    </script>

    <!-- Update Profile PHP Script -->
    <script>
        // PHP script for updating profile
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $phone_number = $_POST['phone_number'];
            $vehicle_number = $_POST['vehicle_number'];

            $update_sql = "UPDATE users SET 
                full_name = ?, 
                email = ?, 
                phone_number = ?, 
                vehicle_number = ? 
                WHERE id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssi", $full_name, $email, $phone_number, $vehicle_number, $user_id);
            
            $response = ['success' => false];
            
            if ($stmt->execute()) {
                $response['success'] = true;
                // Update session variables if needed
                $_SESSION['full_name'] = $full_name;
            } else {
                $response['message'] = $stmt->error;
            }
            
            $stmt->close();
            
            // Send JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        ?>
    </script>
</body>
</html>

