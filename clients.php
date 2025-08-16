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

// --- Handle Delete Action ---
if (isset($_GET['delete'])) {
    $clientToDelete = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM clients WHERE client_name='$clientToDelete' AND user_id=$user_id");
    header("Location: clients.php");
    exit();
}

// --- Search ---
$whereClauses = ["user_id = $user_id"];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(client_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
$whereSQL = implode(" AND ", $whereClauses);

// --- Pagination ---
$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Total count ---
$countResult = $conn->query("SELECT COUNT(*) AS total FROM clients WHERE $whereSQL");
$totalClientsCount = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalClientsCount / $perPage);

// --- Fetch Clients ---
$clientsResult = $conn->query("
    SELECT client_name, email, phone, created_at
    FROM clients
    WHERE $whereSQL
    ORDER BY created_at DESC
    LIMIT $offset, $perPage
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BillSimp Clients</title>
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
.btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
</style>
<script>
function confirmDelete(clientName) {
    return confirm("Are you sure you want to delete client: " + clientName + "?");
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
      <a href="quotes.php"><i class="fa-solid fa-quote-right"></i> Quotes</a>
      <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php" class="active"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-users me-2 text-primary"></i> Clients</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <!-- Search -->
      <div class="px-4 my-3">
        <form method="GET" class="row g-2 align-items-center">
          <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"></div>
          <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button></div>
          <div class="col-md-2"><a href="clients.php" class="btn btn-secondary w-100"><i class="fa fa-undo"></i> Reset</a></div>
          <div class="col-md-2"><a href="add_client.php" class="btn btn-success w-100"><i class="fa fa-user-plus"></i> Add Client</a></div>
        </form>
      </div>

      <!-- Clients Table -->
      <div class="px-4 mb-4">
        <div class="table-responsive shadow-sm bg-white rounded p-3">
          <table class="table align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th>Client Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Date Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($clientsResult->num_rows > 0) {
                  while ($row = $clientsResult->fetch_assoc()) {
                      echo "<tr>
                          <td>".htmlspecialchars($row['client_name'])."</td>
                          <td>".htmlspecialchars($row['email'])."</td>
                          <td>".htmlspecialchars($row['phone'])."</td>
                          <td>".htmlspecialchars(date('d-m-Y', strtotime($row['created_at'])))."</td>
                          <td>
                              <a href='edit_client.php?client=".urlencode($row['client_name'])."' class='btn btn-sm btn-info text-white'><i class='fa fa-edit'></i> Edit</a>
                              <a href='clients.php?delete=".urlencode($row['client_name'])."' class='btn btn-sm btn-danger' onclick='return confirmDelete(\"".htmlspecialchars($row['client_name'])."\")'><i class='fa fa-trash'></i> Delete</a>
                          </td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center text-muted'>No clients found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Client pagination" class="mt-3">
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





