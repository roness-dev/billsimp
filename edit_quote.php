<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","billsimp_db");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$user_id = intval($_SESSION['user_id']);
$id = intval($_GET['id']);

// Fetch current quote
$quote = $conn->query("SELECT * FROM quotes WHERE quote_id=$id AND user_id=$user_id")->fetch_assoc();
if (!$quote) {
    die("Quote not found.");
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $amount = floatval($_POST['amount']);
    $status = $_POST['status'];

    $conn->query("UPDATE quotes SET client_name='$client_name', amount=$amount, status='$status' 
                  WHERE quote_id=$id AND user_id=$user_id");
    header("Location: quotes.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Quote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3>Edit Quote #<?= $quote['quote_id'] ?></h3>
    <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label>Client Name</label>
            <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($quote['client_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Amount (FCFA)</label>
            <input type="number" name="amount" class="form-control" step="0.01" value="<?= $quote['amount'] ?>" required>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="Pending" <?= $quote['status']=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Approved" <?= $quote['status']=='Approved'?'selected':'' ?>>Approved</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Quote</button>
        <a href="quotes.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
