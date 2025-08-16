<?php
// Start session only if not started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once "includes/db.php";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate fields
    if (empty($username) || empty($email) || empty($password)) {
        die("All fields are required.");
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Email already registered.");
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (user_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['user_id']   = $stmt->insert_id;
        $_SESSION['user_name'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
}
?>
