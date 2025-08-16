<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "includes/db.php";

$user_id = intval($_SESSION['user_id']);
$error_msg = '';
$success_msg = '';

// --- Handle "Mark as Paid" action ---
if (isset($_GET['mark_paid'])) {
    $invoiceToMark = $conn->real_escape_string($_GET['mark_paid']);
    $update = $conn->query("UPDATE payments SET status='Paid' WHERE invoice_id='$invoiceToMark' AND user_id=$user_id");
    if ($update) {
        header("Location: payments.php"); // refresh page
        exit();
    }
}

// --- Handle Add Payment ---
if (isset($_POST['add_payment'])) {
    $invoice_id = $conn->real_escape_string($_POST['invoice_id'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);

    if ($invoice_id && $amount > 0) {
        $insert = $conn->query("INSERT INTO payments (user_id, invoice_id, amount, status) VALUES ($user_id, '$invoice_id', $amount, 'Pending')");
        if ($insert) {
            $success_msg = "Payment added successfully!";
        } else {
            $error_msg = "Error adding payment: " . $conn->error;
        }
    } else {
        $error_msg = "Please select an invoice and enter a valid amount.";
    }
}

// --- Fetch user invoices for dropdown ---
$invoicesResult = $conn->query("SELECT invoice_id, client_name FROM invoices WHERE user_id=$user_id ORDER BY created_at DESC");

// --- Search & Filter ---
$whereClauses = ["p.user_id = $user_id"];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(i.client_name LIKE '%$search%' OR p.invoice_id LIKE '%$search%')";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "p.status = '$status'";
}
$whereSQL = implode(" AND ", $whereClauses);

// --- Pagination ---
$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Total count for pagination ---
$countResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
    WHERE $whereSQL
");
$totalPaymentsCount = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalPaymentsCount / $perPage);

// --- Fetch Payments with client names ---
$paymentsResult = $conn->query("
    SELECT p.invoice_id, i.client_name, p.amount, p.status
    FROM payments p
    LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
    WHERE $whereSQL
    ORDER BY p.invoice_id DESC
    LIMIT $offset, $perPage
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BillSimp Payments</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.sidebar { background: linear-gradient(180deg, #2563eb, #1d4ed8); height: 100vh; padding-top: 30px; color: white; }
.sidebar h4 { font-weight: 700; text-align: center; margin-bottom: 2rem; }
.sidebar a { display: block; padding: 12px 24px; color: #cbd5e1; text-decoration: none; font-weight: 500; border-radius: 6px; margin: 4px 12px; transition: all 0.25s ease; }
.sidebar a i { margin-right: 10px; }
.sidebar a.active, .sidebar a:hover { background-color: #1e40af; color: white; }
.navbar { background-color: white; padding: 10px 20px; border-bottom: 1px solid #e3e6f0; }
.welcome-text { font-weight: 600; color: #1f2937; }
.card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s ease; }
.card:hover { transform: translateY(-4px); }
.status-paid { background-color: #bbf7d0; color: #065f46; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
.status-pending { background-color: #fde68a; color: #92400e; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-flex flex-column">
      <h4>💲 BillSimp</h4>
      <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="invoices.php"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
      <a href="payments.php" class="active"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
      <a href="quotes.php"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
      <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-money-check-dollar me-2 text-success"></i> Payments</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <!-- Add Payment Form -->
      <div class="px-4 my-3">
        <div class="card p-3 shadow-sm bg-white rounded">
          <h6>Add New Payment</h6>
          <?php if($success_msg) echo "<div class='alert alert-success'>{$success_msg}</div>"; ?>
          <?php if($error_msg) echo "<div class='alert alert-danger'>{$error_msg}</div>"; ?>
          <form method="POST" class="row g-2 align-items-center">
            <div class="col-md-5">
              <select name="invoice_id" class="form-select" required>
                <option value="">Select Invoice</option>
                <?php while($inv = $invoicesResult->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($inv['invoice_id']); ?>">
                    <?= htmlspecialchars($inv['invoice_id'].' - '.$inv['client_name']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-3">
              <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
            </div>
            <div class="col-md-2">
              <button type="submit" name="add_payment" class="btn btn-primary w-100"><i class="fa-solid fa-plus"></i> Add</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Search & Filter -->
      <div class="px-4 my-3">
        <form method="GET" class="row g-2 align-items-center">
          <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search by client or invoice ID" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"></div>
          <div class="col-md-3">
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <option value="Paid" <?= isset($_GET['status']) && $_GET['status']==='Paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="Pending" <?= isset($_GET['status']) && $_GET['status']==='Pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
          </div>
          <div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fa fa-search"></i> Search</button></div>
          <div class="col-md-2"><a href="payments.php" class="btn btn-secondary w-100"><i class="fa fa-undo"></i> Reset</a></div>
        </form>
      </div>

      <!-- Payments Table -->
      <div class="px-4 mb-4">
        <div class="table-responsive shadow-sm bg-white rounded p-3">
          <table class="table align-middle mb-0">
            <thead class="table-success">
              <tr>
                <th>Invoice ID</th>
                <th>Client</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($paymentsResult->num_rows > 0) {
                  while ($row = $paymentsResult->fetch_assoc()) {
                      $statusClass = strtolower($row['status']) === 'paid' ? 'status-paid' : 'status-pending';
                      echo "<tr>
                          <td>".htmlspecialchars($row['invoice_id'])."</td>
                          <td>".htmlspecialchars($row['client_name'])."</td>
                          <td><span class='{$statusClass}'>".ucfirst(htmlspecialchars($row['status']))."</span></td>
                          <td>FCFA ".number_format($row['amount'])."</td>
                          <td>";
                      if (strtolower($row['status']) === 'pending') {
                          echo "<a href='payments.php?mark_paid=".urlencode($row['invoice_id'])."' class='btn btn-sm btn-success'>Mark as Paid</a>";
                      } else {
                          echo "<span class='text-muted'>—</span>";
                      }
                      echo "</td></tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center text-muted'>No payments found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Payment pagination" class="mt-3">
          <ul class="pagination justify-content-center">
            <?php
            $queryString = $_GET;
            for ($i = 1; $i <= $totalPages; $i++) {
                $queryString['page'] = $i;
                $link = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($queryString));
                $active = $i === $page ? 'active' : '';
                echo "<li class='page-item {$active}'><a class='page-link' href='{$link}'>{$i}</a></li>";
            }
            ?>
          </ul>
        </nav>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


















