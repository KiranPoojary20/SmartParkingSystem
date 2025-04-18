<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = ""; // Empty string for default XAMPP setup
$dbname = "mall";

// Create connection with error handling
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection with more detailed error reporting
if ($conn->connect_errno) {
    die("Connection failed: " . $conn->connect_error . 
        "\nError Number: " . $conn->connect_errno);
}


// Handle Floor Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_floor'])) {
    $floor_name = trim($_POST['new_floor_name']);
    
    // Validate floor name
    if (!empty($floor_name)) {
        // Check if floor already exists
        $check_floor = $conn->prepare("SELECT * FROM floors WHERE floor_name = ?");
        $check_floor->bind_param("s", $floor_name);
        $check_floor->execute();
        $result = $check_floor->get_result();
        
        if ($result->num_rows > 0) {
            $floor_error = "Floor already exists!";
        } else {
            // Insert new floor
            $insert_floor = $conn->prepare("INSERT INTO floors (floor_name) VALUES (?)");
            $insert_floor->bind_param("s", $floor_name);
            
            if ($insert_floor->execute()) {
                $floor_success = "Floor added successfully!";
            } else {
                $floor_error = "Error adding floor: " . $conn->error;
            }
            $insert_floor->close();
        }
        $check_floor->close();
    } else {
        $floor_error = "Floor name cannot be empty!";
    }
}

// Handle Slot Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slot'])) {
    $floor_id = $_POST['floor'];
    $slot_number = trim($_POST['slot_no']);
    
    // Validate input
    if (!empty($floor_id) && !empty($slot_number)) {
        // Check if slot already exists on this floor
        $check_slot = $conn->prepare("SELECT * FROM parking_slots WHERE floor_id = ? AND slot_number = ?");
        $check_slot->bind_param("is", $floor_id, $slot_number);
        $check_slot->execute();
        $result = $check_slot->get_result();
        
        if ($result->num_rows > 0) {
            $slot_error = "Slot already exists on this floor!";
        } else {
            // Insert new slot
            $insert_slot = $conn->prepare("INSERT INTO parking_slots (floor_id, slot_number) VALUES (?, ?)");
            $insert_slot->bind_param("is", $floor_id, $slot_number);
            
            if ($insert_slot->execute()) {
                $slot_success = "Slot added successfully!";
            } else {
                $slot_error = "Error adding slot: " . $conn->error;
            }
            $insert_slot->close();
        }
        $check_slot->close();
    } else {
        $slot_error = "Please select floor and enter slot number!";
    }
}

// Fetch floors for dropdown
$floors_query = "SELECT floor_id, floor_name FROM floors ORDER BY floor_name";
$floors_result = $conn->query($floors_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Slot - Smart Parking System</title>
    <link rel="stylesheet" href="addslot.css">
</head>
<body>
    <div class="topbar">
        <div class="topbar-title">Smart Parking System</div>
        <a href="login.html" class="logout-link">Logout</a>
    </div>

    <div class="container">
        <h1 class="page-heading">Add Parking Slot</h1>
        
        <!-- Floor Addition Section -->
        <div class="floor-management-card">
            <form method="POST" action="">
                <div class="floor-input">
                    <input type="text" name="new_floor_name" placeholder="Enter New Floor Name">
                    <input type="submit" name="add_floor" class="add-floor-btn" value="Add Floor">
                </div>
            </form>
            <?php 
            if (isset($floor_success)) {
                echo "<p style='color: green;'>$floor_success</p>";
            }
            if (isset($floor_error)) {
                echo "<p style='color: red;'>$floor_error</p>";
            }
            ?>
        </div>
        
        <!-- Slot Addition Section -->
        <div class="add-slot-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="floor">Select Floor</label>
                    <select name="floor" required>
                        <option value="">Select Floor</option>
                        <?php 
                        // Populate floor dropdown
                        if ($floors_result->num_rows > 0) {
                            while ($floor = $floors_result->fetch_assoc()) {
                                echo "<option value='" . $floor['floor_id'] . "'>" . $floor['floor_name'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="slot_no">Slot Number</label>
                    <input type="text" name="slot_no" required placeholder="Enter Slot Number">
                </div>
                <button type="submit" name="add_slot" class="add-slot-btn">Add Slot</button>
            </form>
            <?php 
            if (isset($slot_success)) {
                echo "<p style='color: green;'>$slot_success</p>";
            }
            if (isset($slot_error)) {
                echo "<p style='color: red;'>$slot_error</p>";
            }
            ?>
        </div>

        <!-- Slots Display Section -->
        <div id="slotsContainer">
            <?php
            // Fetch and display slots by floor
            $slots_query = "
                SELECT f.floor_name, ps.slot_number 
                FROM floors f
                JOIN parking_slots ps ON f.floor_id = ps.floor_id
                ORDER BY f.floor_name, ps.slot_number
            ";
            $slots_result = $conn->query($slots_query);

            if ($slots_result->num_rows > 0) {
                $current_floor = null;
                echo "<div class='floor-card'>";
                while ($slot = $slots_result->fetch_assoc()) {
                    // Start a new floor card if floor changes
                    if ($current_floor !== $slot['floor_name']) {
                        if ($current_floor !== null) {
                            echo "</div><div class='floor-card'>";
                        }
                        echo "<div class='floor-title'>" . htmlspecialchars($slot['floor_name']) . "</div>";
                        echo "<div class='slots-grid'>";
                        $current_floor = $slot['floor_name'];
                    }
                    
                    // Display slot
                    echo "<div class='slot-card slot-available'>" . htmlspecialchars($slot['slot_number']) . "</div>";
                }
                echo "</div></div>"; // Close last grid and floor card
            } else {
                echo "<div class='no-slots'>No slots added yet</div>";
            }

            // Close database connection
            $conn->close();
            ?>
        </div>
    </div>
    <script>
        // Initialize floors in localStorage
        if (!localStorage.getItem('parkingFloors')) {
            localStorage.setItem('parkingFloors', JSON.stringify([]));
        }

        // Initialize slots in localStorage
        if (!localStorage.getItem('parkingSlots')) {
            localStorage.setItem('parkingSlots', JSON.stringify([]));
        }

        // Populate floor dropdown
        function populateFloorDropdown() {
            const floors = JSON.parse(localStorage.getItem('parkingFloors')) || [];
            const floorSelect = document.getElementById('floor');
            
            // Clear existing options except the first one
            while (floorSelect.options.length > 1) {
                floorSelect.remove(1);
            }

            // Add floors to dropdown
            floors.forEach(floor => {
                const option = new Option(floor, floor);
                floorSelect.add(option);
            });
        }

        // Add floor button event listener
        document.getElementById('addFloorBtn').addEventListener('click', function() {
            const newFloorName = document.getElementById('newFloorName').value.trim();
            
            if (!newFloorName) {
                alert('Please enter a floor name');
                return;
            }

            const floors = JSON.parse(localStorage.getItem('parkingFloors')) || [];
            
            // Check if floor already exists
            if (floors.includes(newFloorName)) {
                alert('This floor already exists');
                return;
            }

            // Add new floor
            floors.push(newFloorName);
            localStorage.setItem('parkingFloors', JSON.stringify(floors));

            // Populate dropdown
            populateFloorDropdown();

            // Clear input
            document.getElementById('newFloorName').value = '';
        });

        // Add slot form submission
        document.getElementById('addSlotForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const floor = document.getElementById('floor').value;
            const slotNo = document.getElementById('slotNo').value;

            let slots = JSON.parse(localStorage.getItem('parkingSlots'));

            const isDuplicate = slots.some(slot => 
                slot.floor === floor && slot.slotNo === slotNo
            );

            if (isDuplicate) {
                alert('This slot already exists on the selected floor!');
                return;
            }

            slots.push({ floor, slotNo });
            localStorage.setItem('parkingSlots', JSON.stringify(slots));

            displaySlots();
            this.reset();
        });

        function displaySlots() {
            const slotsContainer = document.getElementById('slotsContainer');
            const slots = JSON.parse(localStorage.getItem('parkingSlots'));

            const slotsByFloor = slots.reduce((acc, slot) => {
                if (!acc[slot.floor]) {
                    acc[slot.floor] = [];
                }
                acc[slot.floor].push(slot);
                return acc;
            }, {});

            slotsContainer.innerHTML = '';

            if (Object.keys(slotsByFloor).length === 0) {
                slotsContainer.innerHTML = '<div class="no-slots">No slots added yet</div>';
                return;
            }

            Object.keys(slotsByFloor).forEach(floor => {
                const floorCard = document.createElement('div');
                floorCard.className = 'floor-card';
                
                const floorTitle = document.createElement('div');
                floorTitle.className = 'floor-title';
                floorTitle.textContent = floor;
                floorCard.appendChild(floorTitle);

                const slotsGrid = document.createElement('div');
                slotsGrid.className = 'slots-grid';

                slotsByFloor[floor].forEach(slot => {
                    const slotCard = document.createElement('div');
                    slotCard.className = 'slot-card slot-available';
                    slotCard.textContent = `${slot.slotNo}`;
                    slotsGrid.appendChild(slotCard);
                });

                floorCard.appendChild(slotsGrid);
                slotsContainer.appendChild(floorCard);
            });
        }

        // Initial population of floor dropdown and slots display
        populateFloorDropdown();
        displaySlots();
    </script>
</body>
</html>