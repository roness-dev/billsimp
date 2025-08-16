<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Demo User';
}

$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_SESSION['user_id']);

// --- Search & Filter ---
$whereClauses = ["i.user_id = $user_id"];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(i.client_name LIKE '%$search%' OR i.invoice_id LIKE '%$search%')";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "i.status = '$status'";
}
if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $whereClauses[] = "DATE(i.created_at) = '$date'";
}

$whereSQL = implode(" AND ", $whereClauses);

// --- Pagination ---
$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Count total invoices ---
$countResult = $conn->query("SELECT COUNT(*) AS total FROM invoices i WHERE $whereSQL");
$totalInvoices = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalInvoices / $perPage);

// --- Fetch statements ---
$statementsQuery = "
    SELECT i.invoice_id, i.client_name, i.amount AS invoice_amount, i.status,
           IFNULL(SUM(p.amount),0) AS paid_amount,
           (i.amount - IFNULL(SUM(p.amount),0)) AS balance,
           i.created_at
    FROM invoices i
    LEFT JOIN payments p ON i.invoice_id = p.invoice_id AND p.user_id = $user_id AND p.status='Paid'
    WHERE $whereSQL
    GROUP BY i.invoice_id
    ORDER BY i.created_at DESC
    LIMIT $offset, $perPage
";
$statements = $conn->query($statementsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Statements - BillSimp</title>
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
      <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
      <a href="quotes.php"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
      <a href="statements.php" class="active"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-receipt me-2 text-primary"></i> Statements</h5>
        <p class="welcome-text m-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <!-- Search & Filter -->
      <div class="px-4 py-4">
        <form method="GET" class="row g-2 align-items-center mb-3">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Client or Invoice ID" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <option value="Paid" <?php if(isset($_GET['status']) && $_GET['status']==='Paid') echo 'selected'; ?>>Paid</option>
              <option value="Pending" <?php if(isset($_GET['status']) && $_GET['status']==='Pending') echo 'selected'; ?>>Pending</option>
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i></button>
          </div>
          <div class="col-md-2">
            <a href="statements.php" class="btn btn-secondary w-100"><i class="fa fa-undo"></i> Reset</a>
          </div>
        </form>

        <!-- Statements Table -->
        <div class="table-responsive shadow-sm bg-white rounded p-3">
          <table class="table table-hover align-middle">
            <thead class="table-primary">
              <tr>
                <th>Invoice ID</th>
                <th>Client</th>
                <th>Status</th>
                <th>Invoice Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($statements->num_rows > 0) {
                  while ($row = $statements->fetch_assoc()) {
                      $statusClass = strtolower($row['status']) === 'paid' ? 'status-paid' : 'status-pending';
                      echo "<tr>
                          <td>".htmlspecialchars($row['invoice_id'])."</td>
                          <td>".htmlspecialchars($row['client_name'])."</td>
                          <td><span class='{$statusClass}'>".ucfirst(htmlspecialchars($row['status']))."</span></td>
                          <td>FCFA ".number_format($row['invoice_amount'])."</td>
                          <td>FCFA ".number_format($row['paid_amount'])."</td>
                          <td>FCFA ".number_format($row['balance'])."</td>
                          <td>".date('d-m-Y', strtotime($row['created_at']))."</td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='7' class='text-center text-muted'>No statements found</td></tr>";
              }
              ?>
            </tbody>
          </table>

          <!-- Pagination -->
          <?php if($totalPages > 1): ?>
          <nav aria-label="Statements pagination" class="mt-3">
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
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>






