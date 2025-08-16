<?php
$host = "localhost";
$user = "root"; // your DB user
$pass = "";     // your DB password
$db   = "billsimp_db"; // your DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>











