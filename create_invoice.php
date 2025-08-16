<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "includes/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'User';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name']);
    $amount = floatval($_POST['amount']);
    $status = $_POST['status'] === 'Paid' ? 'Paid' : 'Pending';

    if ($client_name === '' || $amount <= 0) {
        $error = "Please enter valid client name and amount.";
    } else {
        $stmt = $conn->prepare("INSERT INTO invoices (user_id, client_name, amount, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isds", $user_id, $client_name, $amount, $status);
        if ($stmt->execute()) {
            $message = "Invoice created successfully!";
        } else {
            $error = "Error creating invoice: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch recent invoices for display
$recentInvoices = $conn->query("SELECT invoice_id, client_name, status, amount, created_at FROM invoices WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Invoice - BillSimp</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.sidebar { background: linear-gradient(180deg, #2563eb, #1d4ed8); height: 100vh; padding-top: 30px; color: white; position: fixed; width: 220px; }
.sidebar h4 { font-weight: 700; text-align: center; margin-bottom: 2rem; }
.sidebar a { display: block; padding: 14px 20px; color: #cbd5e1; text-decoration: none; font-weight: 500; border-radius: 8px; margin: 4px 12px; transition: 0.3s; }
.sidebar a i { margin-right: 10px; }
.sidebar a.active, .sidebar a:hover { background-color: #1e40af; color: white; }
.main-content { margin-left: 220px; padding: 20px 30px; }
.card { border-radius: 12px; border: none; box-shadow: 0 6px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
.status-paid { background-color: #bbf7d0; color: #065f46; padding: 3px 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; }
.status-pending { background-color: #fde68a; color: #92400e; padding: 3px 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; }
.table-responsive { border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.table th { background-color: #2563eb; color: white; }
.table tbody tr:hover { background-color: #f1f5f9; }
.alert { font-size: 0.95rem; }
</style>
</head>
<body>

<div class="sidebar">
    <h4>💲 BillSimp</h4>
    <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="create_invoice.php" class="active"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
    <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
    <a href="quotes.php"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
    <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
    <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
    <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
    <div class="navbar d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> Create Invoice</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($user_name); ?> 👋</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <div class="card p-4 mb-4">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Client Name</label>
                <input type="text" name="client_name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount (FCFA)</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Pending">Pending</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Create Invoice</button>
                <a href="invoices.php" class="btn btn-secondary"><i class="fa fa-list"></i> All Invoices</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="section-title"><i class="fa-solid fa-clock me-2 text-primary"></i> Recent Invoices</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($recentInvoices->num_rows > 0) {
                        while ($row = $recentInvoices->fetch_assoc()) {
                            $statusClass = strtolower($row['status'])==='paid'?'status-paid':'status-pending';
                            echo "<tr>
                                <td>".htmlspecialchars($row['invoice_id'])."</td>
                                <td>".htmlspecialchars($row['client_name'])."</td>
                                <td><span class='{$statusClass}'>".ucfirst(htmlspecialchars($row['status']))."</span></td>
                                <td>FCFA ".number_format($row['amount'])."</td>
                                <td>".htmlspecialchars(date('d-m-Y', strtotime($row['created_at'])))."</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted'>No invoices yet</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


