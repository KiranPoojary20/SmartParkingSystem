<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

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
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

try {
    $conn = getDBConnection();
    
    // Add this to check if connection is successful
    if (!$conn) {
        throw new Exception("Connection is null");
    }

    $query = "
        SELECT 
            v_no,
            DATE_FORMAT(date, '%Y-%m-%d') as entry_date,
            DATE_FORMAT(e_time, '%H:%i:%s') as entry_time
        FROM live 
        WHERE ex_time IS NULL 
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    
    // Add this to debug the query
    error_log("Executing query: " . $query);
    
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add this to debug the result
    error_log("Query result: " . print_r($result, true));

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'detected' => true,
            'data' => [
                'vehicle_number' => $result['v_no'],
                'entry_time' => $result['entry_time'],
                'entry_date' => $result['entry_date']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'detected' => false,
            'data' => null
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>