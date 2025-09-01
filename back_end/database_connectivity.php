
<?php
// database_connectivity.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'online-food-order';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Set charset to utf8mb4 for proper encoding
$conn->set_charset("utf8mb4");
?>


