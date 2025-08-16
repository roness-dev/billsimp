<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","billsimp_db");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $amount = floatval($_POST['amount']);
    $status = $_POST['status'];

    $conn->query("INSERT INTO quotes (user_id, client_name, status, amount) 
                  VALUES ($user_id, '$client_name', '$status', $amount)");
    header("Location: quotes.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Quote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3>Add New Quote</h3>
    <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label>Client Name</label>
            <input type="text" name="client_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Amount (FCFA)</label>
            <input type="number" name="amount" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Quote</button>
        <a href="quotes.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
