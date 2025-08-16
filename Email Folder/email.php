<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");

require_once "includes/db.php";

$user_id = intval($_SESSION['user_id']);
$success = $error = '';

// Handle sending email
if (isset($_POST['send_email'])) {
    $client_email = $conn->real_escape_string($_POST['client_email']);
    $subject      = $conn->real_escape_string($_POST['subject']);
    $message      = $conn->real_escape_string($_POST['message']);

    if (empty($client_email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO email_logs (user_id, client_email, subject, message, sent_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $user_id, $client_email, $subject, $message);
        if ($stmt->execute()) $success = "Email sent successfully!";
        else $error = "Error sending email: " . $conn->error;
    }
}

// Fetch recent emails
$recentEmails = $conn->query("SELECT client_email, subject, sent_at FROM email_logs WHERE user_id=$user_id ORDER BY sent_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Send Email - BillSimp</title>
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
.card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s ease; margin-bottom:20px; }
.card:hover { transform: translateY(-3px); }
.btn-primary { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border:none; }
.btn-primary:hover { background: linear-gradient(135deg,#2563eb,#1e40af); }
.form-control, .form-select { border-radius: 8px; }
.alert { border-radius: 8px; }
.section-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; color:#1f2937; }
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
      <a href="statements.php"><i class="fa-solid fa-receipt"></i> Statements</a>
      <a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a>
      <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="#" class="active"><i class="fa-solid fa-envelope"></i> Send Email</a>
      <a href="logout.php" class="mt-auto mb-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-10">
      <div class="navbar d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-envelope me-2 text-primary"></i> Send Email</h5>
        <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
      </div>

      <div class="p-4">
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <div class="card p-4">
          <div class="section-title"><i class="fa fa-paper-plane me-2 text-success"></i> Compose Email</div>
          <form method="POST">
            <div class="mb-3">
              <label>Recipient Email</label>
              <input type="email" name="client_email" class="form-control" placeholder="recipient@example.com" required>
            </div>
            <div class="mb-3">
              <label>Subject</label>
              <input type="text" name="subject" class="form-control" placeholder="Email Subject" required>
            </div>
            <div class="mb-3">
              <label>Message</label>
              <textarea name="message" class="form-control" rows="6" placeholder="Write your message..." required></textarea>
            </div>
            <button type="submit" name="send_email" class="btn btn-primary w-100"><i class="fa fa-paper-plane me-2"></i> Send Email</button>
          </form>
        </div>

        <!-- Recent Emails -->
        <div class="card p-4 mt-4">
          <div class="section-title"><i class="fa fa-envelope-open-text me-2 text-info"></i> Recent Emails Sent</div>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-primary">
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
                            <td>".htmlspecialchars($row['sent_at'])."</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>No emails sent yet.</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
