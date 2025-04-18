<?php
// Database connection parameters
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "mall";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$username = "";
$password = "";
$error_message = "";
$login_status = "false"; // Added to pass login status to JavaScript

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture submitted username and password
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
        $login_status = "false";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if login is successful
        if ($result->num_rows == 1) {
            // Start session
            session_start();
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            $login_status = "true";
            
            // Redirect to index.html
            header("Location: index.php");
            exit();
        } else {
            // Login failed
            $error_message = "Invalid username or password. Please try again.";
            $login_status = "false";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Smart Parking System - Login</title>
    <style>
 @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    width: 400px;
    padding: 40px;
    position: relative;
    overflow: hidden;
    transform: scale(0.9);
    opacity: 0;
    animation: fadeIn 0.6s forwards ease-out;
}

@keyframes fadeIn {
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.login-container h1 {
    color: #333;
    font-size: 24px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 30px;
}

.form-group {
    position: relative;
    margin-bottom: 20px;
}

.form-group input {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-group i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    transition: color 0.3s ease;
}

.form-group input:focus + i {
    color: #667eea;
}

.login-button {
    width: 100%;
    padding: 15px;
    background: linear-gradient(to right, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.login-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.footer-links {
    text-align: center;
    margin-top: 20px;
}

.footer-links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.error-message {
    color: #ff6b6b;
    text-align: center;
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.error-message.show {
    opacity: 1;
    transform: translateY(0);
}

.password-toggle {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
    z-index: 10;
}

    </style>
</head>
<body>
    <div class="login-container">

        <h1>Admin Login</h1>
        
        <?php
        // Display error message if login fails
        if (!empty($error_message)) {
            echo "<div class='error-message'>" . htmlspecialchars($error_message) . "</div>";
        }
        ?>

<form action="" method="post">
    <div class="form-group">
        <input type="text" id="username" name="username" placeholder="Username" autocomplete="new-username" value="<?php echo htmlspecialchars($username); ?>" required>
        <i class="fas fa-user"></i>
    </div>
    <div class="form-group">
    <input type="password" id="password" name="password" placeholder="Password" autocomplete="new-password" required>
    <i class="fas fa-lock"></i>
    <span class="password-toggle" id="togglePassword">
        <i class="fas fa-eye"></i>
    </span>
</div>
    <button type="submit" class="login-button">LOGIN</button>
</form>

        <!-- <div class="footer-links">
            <a href="#">Forgot your password?</a>
        </div> -->
    </div>

    <script>
        // Request notification permission
        document.addEventListener('DOMContentLoaded', function() {
            if (!("Notification" in window)) {
                alert("This browser does not support desktop notification");
            } else if (Notification.permission !== "granted") {
                Notification.requestPermission();
            }
        });

        // Get login status from PHP
        var loginStatus = "<?php echo $login_status; ?>";
        var errorMessage = "<?php echo addslashes($error_message); ?>";

        // Show browser notification
        function showNotification(message, type) {
            // Check if browser supports notifications
            if (!("Notification" in window)) {
                alert(message);
                return;
            }

            // Check whether notification permissions have been granted
            if (Notification.permission === "granted") {
                // Create the notification
                var notification = new Notification("Smart Parking System", {
                    body: message,
                    icon: type === 'success' ? 'path/to/success-icon.png' : 'path/to/error-icon.png'
                });

                // Close the notification after 3 seconds
                notification.onclick = function() {
                    notification.close();
                };
                setTimeout(() => notification.close(), 3000);
            } 
            // If permission is not granted, request permission
            else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(function (permission) {
                    if (permission === "granted") {
                        showNotification(message, type);
                    }
                });
            }
        }

        // Check login status and show appropriate notification
        document.addEventListener('DOMContentLoaded', function() {
            if (loginStatus === "false" && errorMessage) {
                // Show error notification
                showNotification(errorMessage, 'error');
            }
        });

        // Clear error message when user starts typing
        document.getElementById('username').addEventListener('input', function() {
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
    </script>
</body>
</html>