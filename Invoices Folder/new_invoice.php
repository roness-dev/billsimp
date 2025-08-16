<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Demo
    $_SESSION['user_name'] = 'Demo User';
}

$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_SESSION['user_id']);
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $amount = floatval($_POST['amount']);
    $status = in_array($_POST['status'], ['Paid', 'Pending']) ? $_POST['status'] : 'Pending';
    $invoice_id = "INV" . time(); // Simple unique ID
    $created_at = date("Y-m-d H:i:s");

    $sql = "INSERT INTO invoices (invoice_id, client_name, amount, status, user_id, created_at) 
            VALUES ('$invoice_id', '$client_name', $amount, '$status', $user_id, '$created_at')";
    if ($conn->query($sql)) {
        $success = "Invoice created successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Invoice - BillSimp</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
.sidebar { background: linear-gradient(180deg, #2563eb, #1d4ed8); height: 100vh; padding-top: 30px; color: white; }
.sidebar h4 { font-weight: 700; text-align: center; margin-bottom: 2rem; }
.sidebar a { display: block; padding: 12px 24px; color: #cbd5e1; text-decoration: none; font-weight: 500; border-radius: 6px; margin: 4px 12px; transition: all 0.25s ease; }
.sidebar a.active, .sidebar a:hover { background-color: #1e40af; color: white; }
.navbar { background-color: white; padding: 10px 20px; border-bottom: 1px solid #e3e6f0; }
.welcome-text { font-weight: 600; color: #1f2937; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-flex flex-column">
      <h4>💲 BillSimp</h4>
      <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="invoices.php" class="active"><i class="fa-solid fa-file-invoice"></i> Invoices</a>
      <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> New Invoice</h5>
        <p class="welcome-text m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <div class="p-4">
        <?php if($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Client Name</label>
            <input type="text" name="client_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount (FCFA)</label>
            <input type="number" name="amount" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="Pending">Pending</option>
              <option value="Paid">Paid</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fa fa-check me-1"></i> Create Invoice</button>
          <a href="invoices.php" class="btn btn-secondary"><i class="fa fa-arrow-left me-1"></i> Back</a>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
