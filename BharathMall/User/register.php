<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mall";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $vehicle_number = $_POST['vehicle_number'];

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, password, vehicle_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone_number, $password, $vehicle_number);

    if ($stmt->execute()) {
        // Redirect to login.php
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
