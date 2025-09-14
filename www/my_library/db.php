<?php
// Database connection (adjust credentials to your DB)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "library_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>