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

// Function to get parking reports from live table
function getParkingReports() {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT 
            id as slNo,
            date,
            v_no as vehicleNumber,
            e_time as entryTime,
            ex_time as exitTime
        FROM live
        ORDER BY date DESC, e_time DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request for filtered data
if (isset($_POST['action']) && $_POST['action'] === 'filterReports') {
    $vehicleNumber = $_POST['vehicleNumber'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    
    $conn = getDBConnection();
    $query = "SELECT 
                id as slNo,
                date,
                v_no as vehicleNumber,
                e_time as entryTime,
                ex_time as exitTime
              FROM live
              WHERE 1=1";
    
    $params = [];
    
    if ($vehicleNumber) {
        $query .= " AND v_no LIKE ?";
        $params[] = "%$vehicleNumber%";
    }
    
    if ($startDate) {
        $query .= " AND date >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND date <= ?";
        $params[] = $endDate;
    }
    
    $query .= " ORDER BY date DESC, e_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

$initialReports = getParkingReports();
?>

<!DOCTYPE html>
<html lang="en">
<!-- Keep the existing head section and styles -->
<head>
    <<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Reports - Smart Parking System</title>
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
            font-size: 20px;
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

        .reports-container {
            max-width: 1200px;
            margin: 50px auto;
            
        }

        .page-heading {
            text-align: center; 
            margin-bottom: 20px; 
            color: var(--primary-color);
        }

        .filter-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            
        }

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            align-items: center;
            

        }

        .filter-section select,
        .filter-section input,
        .filter-section button {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Calibri, sans-serif;
            font-size: 20px;
        }

        .filter-section button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-section button:hover {
            background-color: #2980b9;
        }

        .reports-table-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table th, 
        .reports-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-family: Calibri, sans-serif;
        }

        .reports-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .reports-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .reports-table tr:hover {
            background-color: #e6f2ff;
        }

        .download-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-family: Calibri, sans-serif;
        }

        .download-btn:hover {
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
        }
        .vehicle-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .reports-table td {
            vertical-align: middle;
        }
    </style>
</head>
body>
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
        <div class="reports-container">
            <h1 class="page-heading">Parking Reports</h1>

            <div class="filter-card">
                <div class="filter-section">
                    <input type="text" id="vehicleNumberFilter" placeholder="Enter Vehicle Number">
                    <input type="date" id="startDateFilter">
                    <input type="date" id="endDateFilter">
                    <button onclick="filterReports()">Apply Filter</button>
                </div>
            </div>

            <div class="reports-table-card">
                <table class="reports-table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th>Date</th>
                            <th>Vehicle Number</th>
                            <th>Entry Time</th>
                            <th>Exit Time</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <!-- Will be populated dynamically -->
                    </tbody>
                </table>
            </div>

            <a href="#" class="download-btn" onclick="generatePDFReport()">Download Report</a>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.15/jspdf.plugin.autotable.min.js"></script>

    <script>
        // Initialize with PHP data
        const parkingReports = <?php echo json_encode($initialReports); ?>;

        // Function to populate the reports table
        function populateReportsTable(data) {
        const tableBody = document.getElementById('reportsTableBody');
        tableBody.innerHTML = '';

        data.forEach(report => {
            const exitTime = report.exitTime || 'Not Exited';
            const row = `
                <tr>
                    <td>${report.slNo}</td>
                    <td>${report.date}</td>
                    <td>${report.vehicleNumber}</td>
                    <td>${report.entryTime}</td>
                    <td>${exitTime}</td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }

        // Function to filter reports
        function filterReports() {
            const vehicleNumber = document.getElementById('vehicleNumberFilter').value;
            const startDate = document.getElementById('startDateFilter').value;
            const endDate = document.getElementById('endDateFilter').value;

            const formData = new FormData();
            formData.append('action', 'filterReports');
            formData.append('vehicleNumber', vehicleNumber);
            formData.append('startDate', startDate);
            formData.append('endDate', endDate);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                populateReportsTable(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while filtering the reports');
            });
        }

        // Function to generate PDF report
        function generatePDFReport() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.text('Parking System Report', 14, 20);

        // Get visible data from the table
        const tableData = Array.from(document.querySelectorAll('#reportsTableBody tr')).map(row => {
            const cells = Array.from(row.cells);
            return [
                cells[0].textContent,
                cells[1].textContent,
                cells[2].textContent,
                cells[3].textContent,
                cells[4].textContent
            ];
        });

        doc.autoTable({
            startY: 30,
            head: [['SL No', 'Date', 'Vehicle Number', 'Entry Time', 'Exit Time']],
            body: tableData
        });

        doc.save('parking_report.pdf');
    }

    // Initial table population
    populateReportsTable(parkingReports);
    </script>
</body>
</html>