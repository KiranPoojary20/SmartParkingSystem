<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking System - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a5276;  /* Darker blue */
            --secondary-color: #f0f3f7;  /* Slightly darker background */
            --text-color: #0c1b2e;  /* Even darker text color */
            --sidebar-bg: #d6dbdf;  /* Slightly darker sidebar */
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
            font-size: 17px;  /* Slightly larger base font size */
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
    font-weight: 700;  /* Bolder title */
    letter-spacing: 1px;
}

.topbar-title{ font-family: 'Playfair Display', calibri; font-size: 2.8rem; color: #ffffff; text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5); }    

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
    color: #1c2833;  /* Darker text color for logout button */
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 1.3rem;
}

.logout-btn li a i {
    margin-right: 5px;
}


       

        .stat-card h2 {
            font-size: 2.3rem;  /* Increased from 2rem */
            margin-bottom: 12px;  /* Slightly more margin */
            font-weight: 700;
            font-family: Calibri, sans-serif;
        }

        .stat-card p {
            font-size: 1.2rem;  /* Increased from 1rem */
            font-weight: 600;
            font-family: Calibri, sans-serif;
        }

        /* Rest of the previous CSS remains the same */
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
  margin-right: 10px; /* Space between icon and text */
}

    .sidebar a.active {
      background-color: #04AA6D;
      color: white;
    }

    .sidebar a:hover:not(.active) {
      background-color: #1a5276;
      color: white;
    }
    /* Responsive adjustments */
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

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            padding: 30px;
            max-width: 1200px;
            margin: 50px auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .stat-card {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: white;
            transition: transform 0.3s ease;
            font-family: Calibri, sans-serif;
        }

        .stat-card:hover {
            transform: scale(1.05);
        }

        .bg-blue { background-color: #1a5276; }
        .bg-green { background-color: #1e8449; }
        .bg-yellow { background-color: #b7950b; }
        .bg-red { background-color: #922b21; }

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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .topbar-title {
                font-size: 2rem;
            }
        }
    </style>
    
</head>
<body>
    <?php
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "mall";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to get total slots by counting all slots in the slots table
$total_slots_query = "SELECT COUNT(*) AS total_slots FROM slots";
$total_slots_result = $conn->query($total_slots_query);
$total_slots = 0;
if ($total_slots_result->num_rows > 0) {
    $total_slots_row = $total_slots_result->fetch_assoc();
    $total_slots = $total_slots_row['total_slots'];
}

// Query for available, booked, and occupied slots
$slots_query = "SELECT 
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_slots,
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS booked_slots,
    SUM(CASE WHEN status = 'parked' THEN 1 ELSE 0 END) AS parked_slots
FROM slots";

$slots_result = $conn->query($slots_query);
$slots_stats = $slots_result->fetch_assoc();

$available_slots = $slots_stats['available_slots'] ?? 0;
$booked_slots = $slots_stats['booked_slots'] ?? 0;
$parked_slots = $slots_stats['parked_slots'] ?? 0;

    $slots_result = $conn->query($slots_query);

    // Initialize variables
    $actual_slots_count = $available_slots = $parked_slots = $booked_slots = 0;

    // Fetch slot statistics
    if ($slots_result->num_rows > 0) {
        $row = $slots_result->fetch_assoc();
        $actual_slots_count = $row['actual_slots_count'];
        $available_slots = $row['available_slots'];
        $parked_slots = $row['parked_slots'];
        $booked_slots = $row['booked_slots'];
    }

    // Close connection
    $conn->close();
    ?>

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
            <div class="dashboard-card">
                <div class="stats-grid">
                    <div class="stat-card bg-blue">
                        <h2><?php echo $total_slots; ?></h2>
                        <p>Total Slots</p>
                    </div>
                    <div class="stat-card bg-green">
                        <h2><?php echo $available_slots; ?></h2>
                        <p>Available Slots</p>
                    </div>
                    <div class="stat-card bg-yellow">
                        <h2><?php echo $booked_slots; ?></h2>
                        <p>Booked Slots</p>
                    </div>
                    <div class="stat-card bg-red">
                        <h2><?php echo $parked_slots; ?></h2>
                        <p>Parked Slots</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Optional: If you want to keep the dynamic update functionality
        function updateDashboardStats() {
            document.querySelectorAll('.stat-card h2').forEach((el, index) => {
                const values = [
                    <?php echo $total_slots; ?>,
                    <?php echo $available_slots; ?>,
                    <?php echo $booked_slots; ?>,
                    <?php echo $parked_slots; ?>
                ];
                el.textContent = values[index];
            });
        }
        window.onload = updateDashboardStats;
    </script>
</body>
</html>