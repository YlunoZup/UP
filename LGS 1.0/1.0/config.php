<?php
// config.php
$host = "localhost";
$user = "root"; // change if needed
$pass = ""; // change if needed
$dbname = "lead_system";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to MySQL!";
?>
