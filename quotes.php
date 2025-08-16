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

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $quoteToDelete = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM quotes WHERE quote_id='$quoteToDelete' AND user_id=$user_id");
    header("Location: quotes.php");
    exit();
}

// --- Stats ---
$totalQuotes = $conn->query("SELECT COUNT(*) AS total FROM quotes WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$pendingQuotes = $conn->query("SELECT COUNT(*) AS total FROM quotes WHERE status='Pending' AND user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$sentQuotes = $conn->query("SELECT COUNT(*) AS total FROM quotes WHERE status='Sent' AND user_id = $user_id")->fetch_assoc()['total'] ?? 0;

// --- Search & Filter ---
$whereClauses = ["user_id = $user_id"];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(client_name LIKE '%$search%' OR quote_id LIKE '%$search%')";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "status = '$status'";
}
$whereSQL = implode(" AND ", $whereClauses);

// --- Pagination ---
$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Total for pagination ---
$countResult = $conn->query("SELECT COUNT(*) AS total FROM quotes WHERE $whereSQL");
$totalQuotesCount = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalQuotesCount / $perPage);

// --- Fetch Quotes ---
$quotesResult = $conn->query("
    SELECT quote_id, client_name, amount, status, created_at
    FROM quotes
    WHERE $whereSQL
    ORDER BY created_at DESC
    LIMIT $offset, $perPage
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BillSimp Quotes</title>
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
.status-sent { background-color: #bbf7d0; color: #065f46; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
.status-pending { background-color: #fde68a; color: #92400e; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
.btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
</style>
<script>
function confirmDelete(quoteId) {
    return confirm("Are you sure you want to delete quote " + quoteId + "?");
}
</script>
</head>
<body>
<div class="container-fluid">
  <div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-flex flex-column">
      <h4>💲 BillSimp</h4>
      <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="invoices.php"><i class="fa-solid fa-file-invoice"></i> Invoices</a>
      <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
      <a href="quotes.php" class="active"><i class="fa-solid fa-quote-right"></i> Quotes</a>
      <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-quote-right me-2 text-primary"></i> Quotes</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 p-4">
        <div class="col-md-4"><div class="card bg-gradient-blue p-3 text-center"><div class="card-icon"><i class="fa-solid fa-file-invoice"></i></div><h6>Total Quotes</h6><h4><?= $totalQuotes; ?></h4></div></div>
        <div class="col-md-4"><div class="card bg-gradient-yellow p-3 text-center"><div class="card-icon"><i class="fa-solid fa-clock"></i></div><h6>Pending Quotes</h6><h4><?= $pendingQuotes; ?></h4></div></div>
        <div class="col-md-4"><div class="card bg-gradient-green p-3 text-center"><div class="card-icon"><i class="fa-solid fa-paper-plane"></i></div><h6>Sent Quotes</h6><h4><?= $sentQuotes; ?></h4></div></div>
      </div>

      <!-- Quick Actions -->
      <div class="row px-4 mb-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <h6 class="mb-3">Quick Actions</h6>
            <div class="d-flex flex-wrap gap-3">
              <a href="new_quote.php" class="btn btn-primary quick-action-btn"><i class="fa-solid fa-plus me-2"></i> Create New Quote</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filter -->
      <div class="px-4 mb-3">
        <form method="GET" class="row g-2 align-items-center">
          <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search by client or quote ID" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"></div>
          <div class="col-md-3">
            <select name="status" class="form-select">
              <option value="">All Statuses</option>
              <option value="Pending" <?= isset($_GET['status']) && $_GET['status']==='Pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="Sent" <?= isset($_GET['status']) && $_GET['status']==='Sent' ? 'selected' : ''; ?>>Sent</option>
            </select>
          </div>
          <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button></div>
          <div class="col-md-2"><a href="quotes.php" class="btn btn-secondary w-100"><i class="fa fa-undo"></i> Reset</a></div>
        </form>
      </div>

      <!-- Quotes Table -->
      <div class="px-4 mb-4">
        <div class="table-responsive shadow-sm bg-white rounded p-3">
          <table class="table align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th>Quote ID</th>
                <th>Client</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Date Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($quotesResult->num_rows > 0) {
                  while ($row = $quotesResult->fetch_assoc()) {
                      $statusClass = strtolower($row['status']) === 'sent' ? 'status-sent' : 'status-pending';
                      echo "<tr>
                          <td>".htmlspecialchars($row['quote_id'])."</td>
                          <td>".htmlspecialchars($row['client_name'])."</td>
                          <td><span class='{$statusClass}'>".ucfirst(htmlspecialchars($row['status']))."</span></td>
                          <td>FCFA ".number_format($row['amount'])."</td>
                          <td>".htmlspecialchars(date('d-m-Y', strtotime($row['created_at'])))."</td>
                          <td>
                              <a href='edit_quote.php?id=".urlencode($row['quote_id'])."' class='btn btn-sm btn-info text-white'><i class='fa fa-edit'></i> Edit</a>
                              <a href='quotes.php?delete=".urlencode($row['quote_id'])."' class='btn btn-sm btn-danger' onclick='return confirmDelete(\"".htmlspecialchars($row['quote_id'])."\")'><i class='fa fa-trash'></i> Delete</a>
                          </td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='6' class='text-center text-muted'>No quotes found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Quotes pagination" class="mt-3">
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



