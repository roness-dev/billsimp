<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $invoice_id = $_POST['invoice_id'];
    $amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];

    if (!empty($invoice_id) && !empty($amount) && !empty($payment_date)) {
        $stmt = $conn->prepare("INSERT INTO payments (invoice_id, payment_amount, payment_date) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $invoice_id, $amount, $payment_date);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Payment recorded successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Please fill in all fields.</div>";
    }
}

// Fetch invoices for dropdown
$invoices = $conn->query("SELECT id, invoice_number FROM invoices ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Payment - BillSimp</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    background-color: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.sidebar {
    background: linear-gradient(180deg, #2563eb, #1d4ed8);
    height: 100vh;
    padding-top: 30px;
    color: white;
}
.sidebar h4 {
    font-weight: 700;
    text-align: center;
    margin-bottom: 2rem;
}
.sidebar a {
    display: block;
    padding: 12px 24px;
    color: #cbd5e1;
    text-decoration: none;
    font-weight: 500;
    border-radius: 6px;
    margin: 4px 12px;
    transition: all 0.25s ease;
}
.sidebar a i {
    margin-right: 10px;
}
.sidebar a.active, .sidebar a:hover {
    background-color: #1e40af;
    color: white;
}
.navbar {
    background-color: white;
    padding: 10px 20px;
    border-bottom: 1px solid #e3e6f0;
}
.welcome-text {
    font-weight: 600;
    color: #1f2937;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-flex flex-column">
            <h4>💲 BillSimp</h4>
            <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="new_invoices.php"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
            <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
            <a href="new_payment.php" class="active"><i class="fa-solid fa-plus"></i> New Payment</a>
            <a href="#"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
            <a href="#"><i class="fa-solid fa-receipt"></i> Statements</a>
            <a href="#"><i class="fa-solid fa-users"></i> Clients</a>
            <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="#"><i class="fa-solid fa-envelope"></i> Send Email</a>
            <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>

        <!-- Main content -->
        <div class="col-md-10">
            <!-- Navbar -->
            <div class="navbar d-flex justify-content-between align-items-center">
                <h5 class="m-0"><i class="fa-solid fa-plus text-success me-2"></i> New Payment</h5>
                <p class="welcome-text m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
            </div>

            <!-- Form -->
            <div class="p-4">
                <?php echo $message; ?>
                <div class="card shadow-sm p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Select Invoice</label>
                            <select name="invoice_id" class="form-select" required>
                                <option value="">-- Select Invoice --</option>
                                <?php while ($row = $invoices->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['invoice_number']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Amount (FCFA)</label>
                            <input type="number" name="payment_amount" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-1"></i> Save Payment</button>
                        <a href="payments.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
