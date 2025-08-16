<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id    = $_SESSION['user_id'];
    $client     = trim($_POST["client"]);
    $amount     = floatval($_POST["amount"]);
    $details    = trim($_POST["details"]);

    $stmt = $conn->prepare("INSERT INTO quotations (user_id, client_name, amount, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $client, $amount, $details);
    $stmt->execute();
    $stmt->close();

    $success = "Quotation created successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Quotation - BillSimp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h4>Create New Quotation</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Client Name</label>
                            <input type="text" name="client" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Details</label>
                            <textarea name="details" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Save Quotation</button>
                    </form>
                </div>
                <div class="card-footer text-end">
                    <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

