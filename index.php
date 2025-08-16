<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'User';

// --- Stats ---
$totalInvoices = $conn->query("SELECT COUNT(*) AS total FROM invoices WHERE user_id=$user_id")->fetch_assoc()['total'] ?? 0;
$paymentsReceived = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE status='Paid' AND user_id=$user_id")->fetch_assoc()['total'] ?? 0;
$emailsSent = $conn->query("SELECT COUNT(*) AS total FROM email_logs WHERE user_id=$user_id")->fetch_assoc()['total'] ?? 0;
$outstandingPayments = $conn->query("SELECT SUM(amount) AS total FROM invoices WHERE status='Pending' AND user_id=$user_id")->fetch_assoc()['total'] ?? 0;

// --- Recent Invoices ---
$recentInvoices = $conn->query("SELECT invoice_id, client_name, status, amount, created_at FROM invoices WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");

// --- Recent Emails ---
$recentEmails = $conn->query("SELECT client_email, subject, sent_at FROM email_logs WHERE user_id=$user_id ORDER BY sent_at DESC LIMIT 5");

// --- Chart Data ---
$chartLabels = ['Payments Received', 'Outstanding Payments'];
$chartValues = [(float)$paymentsReceived, (float)$outstandingPayments];

$invoiceTrendData = [];
$invoiceTrendLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $invoiceTrendLabels[] = date('M Y', strtotime("-$i month"));
    $count = $conn->query("SELECT COUNT(*) AS total FROM invoices WHERE user_id=$user_id AND DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['total'] ?? 0;
    $invoiceTrendData[] = (int)$count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BillSimp Dashboard</title>
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
.navbar { background-color: white; padding: 12px 20px; border-bottom: 1px solid #e3e6f0; position: sticky; top: 0; z-index: 100; }
.welcome-text { font-weight: 600; color: #1f2937; }
.card { border-radius: 16px; border: none; box-shadow: 0 6px 15px rgba(0,0,0,0.08); transition: transform 0.2s ease, box-shadow 0.2s ease; margin-bottom: 20px; }
.card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.12); }
.card-icon { font-size: 2.5rem; margin-bottom: 12px; }
.bg-gradient-blue { background: linear-gradient(135deg, #3b82f6, #1e3a8a); color: white; }
.bg-gradient-green { background: linear-gradient(135deg, #22c55e, #15803d); color: white; }
.bg-gradient-yellow { background: linear-gradient(135deg, #facc15, #ca8a04); color: white; }
.bg-gradient-purple { background: linear-gradient(135deg, #a855f7, #6b21a8); color: white; }
.status-paid { background-color: #bbf7d0; color: #065f46; padding: 3px 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; transition: 0.2s; }
.status-pending { background-color: #fde68a; color: #92400e; padding: 3px 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; transition: 0.2s; }
.table-responsive { border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.table th { background-color: #2563eb; color: white; }
.table tbody tr:hover { background-color: #f1f5f9; }
.section-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; color:#1f2937; }
.main-content { margin-left: 220px; padding: 20px 30px; }
.create-btn { margin-bottom: 15px; }
@media(max-width:768px){
    .table-responsive { display:none; }
    .card-swipable { display:block; overflow-x:auto; white-space: nowrap; }
    .card-swipable .card { display:inline-block; min-width:250px; margin-right:10px; vertical-align:top; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h4>💲 BillSimp</h4>
    <a href="#" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="invoices.php"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
    <a href="payments.php"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
    <a href="quotes.php"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
    <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
    <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="email.php"><i class="fa-solid fa-envelope"></i> Send Email</a>
    <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
    <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-house me-2 text-primary"></i> Dashboard</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($user_name); ?> 👋</p>
    </div>

    <div class="row g-4 mt-3">
        <div class="col-md-3">
            <div class="card bg-gradient-blue text-center" data-bs-toggle="tooltip" title="Total invoices created">
                <div class="card-icon"><i class="fa-solid fa-file-invoice"></i></div>
                <h6>Total Invoices</h6>
                <h4><?= $totalInvoices; ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-green text-center" data-bs-toggle="tooltip" title="Payments received">
                <div class="card-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <h6>Payments Received</h6>
                <h4>FCFA <?= number_format($paymentsReceived); ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-purple text-center" data-bs-toggle="tooltip" title="Emails sent to clients">
                <div class="card-icon"><i class="fa-solid fa-envelope"></i></div>
                <h6>Emails Sent</h6>
                <h4><?= $emailsSent; ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-yellow text-center" data-bs-toggle="tooltip" title="Outstanding invoices">
                <div class="card-icon"><i class="fa-solid fa-clock"></i></div>
                <h6>Outstanding Payments</h6>
                <h4>FCFA <?= number_format($outstandingPayments); ?></h4>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card p-3">
                <div class="section-title">Payments vs Outstanding</div>
                <canvas id="paymentsChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <div class="section-title">Invoices Created (Last 6 months)</div>
                <canvas id="invoiceTrendChart"></canvas>
            </div>
        </div>
    </div>

    <a href="create_invoice.php" class="btn btn-primary create-btn mt-4"><i class="fa fa-plus"></i> Create Invoice</a>

    <!-- Recent Invoices -->
    <div class="card p-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="section-title"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> Recent Invoices</div>
            <a href="invoices.php" class="text-decoration-none">View All →</a>
        </div>

        <!-- Table for desktop -->
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
                            <td>".htmlspecialchars(date('d M Y', strtotime($row['created_at'])))."</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center text-muted'>No invoices found</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <!-- Swipeable cards for mobile -->
        <div class="card-swipable mt-2">
            <?php
            if ($recentInvoices->num_rows > 0) {
                $recentInvoices->data_seek(0); // reset pointer
                while ($row = $recentInvoices->fetch_assoc()) {
                    $statusClass = strtolower($row['status'])==='paid'?'status-paid':'status-pending';
                    echo "<div class='card p-3'>
                        <h6>Invoice #".htmlspecialchars($row['invoice_id'])."</h6>
                        <p>Client: ".htmlspecialchars($row['client_name'])."</p>
                        <p>Status: <span class='{$statusClass}'>".ucfirst(htmlspecialchars($row['status']))."</span></p>
                        <p>Amount: FCFA ".number_format($row['amount'])."</p>
                        <p>Date: ".htmlspecialchars(date('d M Y', strtotime($row['created_at'])))."</p>
                    </div>";
                }
            }
            ?>
        </div>
    </div>

    <!-- Recent Emails -->
    <div class="card p-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="section-title"><i class="fa-solid fa-envelope-open-text me-2 text-success"></i> Recent Emails</div>
            <a href="email.php" class="text-decoration-none">View All →</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($recentEmails->num_rows > 0) {
                    while ($row = $recentEmails->fetch_assoc()) {
                        echo "<tr>
                            <td>".htmlspecialchars($row['client_email'])."</td>
                            <td>".htmlspecialchars($row['subject'])."</td>
                            <td>".htmlspecialchars(date('d M Y', strtotime($row['sent_at'])))."</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>No emails sent yet.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <!-- Swipeable cards for mobile -->
        <div class="card-swipable mt-2">
            <?php
            if ($recentEmails->num_rows > 0) {
                $recentEmails->data_seek(0);
                while ($row = $recentEmails->fetch_assoc()) {
                    echo "<div class='card p-3'>
                        <p>To: ".htmlspecialchars($row['client_email'])."</p>
                        <p>Subject: ".htmlspecialchars($row['subject'])."</p>
                        <p>Sent: ".htmlspecialchars(date('d M Y', strtotime($row['sent_at'])))."</p>
                    </div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });

// Payments vs Outstanding Chart
const ctx1 = document.getElementById('paymentsChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels); ?>,
        datasets: [{ label: 'FCFA', data: <?= json_encode($chartValues); ?>, backgroundColor: ['#22c55e','#facc15'] }]
    },
    options: { responsive:true, plugins: { legend: { display:false } } }
});

// Invoice Trend Chart
const ctx2 = document.getElementById('invoiceTrendChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: <?= json_encode($invoiceTrendLabels); ?>,
        datasets: [{ label: 'Invoices', data: <?= json_encode($invoiceTrendData); ?>, backgroundColor:'rgba(59,130,246,0.2)', borderColor:'#3b82f6', tension:0.3, fill:true, pointRadius:5 }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});
</script>
</body>
</html>

