<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = $conn->real_escape_string($_POST['client_name']);
    $email = $conn->real_escape_string($_POST['client_email']);
    $amount = floatval($_POST['amount']);
    $status = $conn->real_escape_string($_POST['status']);
    $due_date = $conn->real_escape_string($_POST['due_date']);
    $user_id = intval($_SESSION['user_id']);

    $query = "INSERT INTO invoices (client_name, client_email, amount, status, due_date, user_id, created_at) 
              VALUES ('$client', '$email', $amount, '$status', '$due_date', $user_id, NOW())";
    if ($conn->query($query)) {
        $msg = "<div class='alert alert-success shadow-sm'>✅ Invoice created successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'>❌ Error: " . $conn->error . "</div>";
    }
}
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
body { background-color: #f4f6f9; }
.sidebar { background: linear-gradient(180deg, #2563eb, #1d4ed8); height: 100vh; padding-top: 30px; color: white; }
.sidebar h4 { font-weight: 700; text-align: center; margin-bottom: 2rem; }
.sidebar a { display: block; padding: 12px 24px; color: #cbd5e1; text-decoration: none; font-weight: 500; border-radius: 6px; margin: 4px 12px; transition: all 0.25s ease; }
.sidebar a.active, .sidebar a:hover { background-color: #1e40af; color: white; }
.navbar { background-color: white; padding: 10px 20px; border-bottom: 1px solid #e3e6f0; }
.welcome-text { font-weight: 600; color: #1f2937; }
.card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.card-header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; font-weight: bold; }
.form-control, .form-select { border-radius: 8px; padding: 10px; }
.form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.25); }
.btn-primary { border-radius: 8px; padding: 10px 18px; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-flex flex-column">
      <h4>💲 BillSimp</h4>
      <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="invoices.php" class="active"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
      <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
      <a href="create_quote.php"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
      <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="send_email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> Create Invoice</h5>
        <p class="welcome-text m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <div class="p-4">
        <?php echo $msg; ?>
        <div class="card shadow-sm">
          <div class="card-header"><i class="fa-solid fa-plus-circle me-2"></i> New Invoice</div>
          <div class="card-body">
            <form method="POST" class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Client Name</label>
                <input type="text" name="client_name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Client Email</label>
                <input type="email" name="client_email" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Amount (FCFA)</label>
                <input type="number" name="amount" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option value="Pending">Pending</option>
                  <option value="Paid">Paid</option>
                </select>
              </div>
              <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i> Save Invoice</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>



