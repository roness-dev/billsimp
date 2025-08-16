<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");

require_once "includes/db.php";

$success = $error = '';

// --- Determine primary key ---
$primaryKey = 'id';
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'id'");
if ($res->num_rows === 0) $primaryKey = 'user_id';

$user_id = intval($_SESSION['user_id']);

// --- Check optional columns ---
$columnsRes = $conn->query("SHOW COLUMNS FROM users");
$columns = [];
while ($row = $columnsRes->fetch_assoc()) $columns[] = $row['Field'];

$hasEmailNotifications = in_array('email_notifications', $columns);
$hasThemeMode = in_array('theme_mode', $columns);

// --- Fetch user info ---
$selectCols = ['user_name', 'email'];
if ($hasEmailNotifications) $selectCols[] = 'email_notifications';
if ($hasThemeMode) $selectCols[] = 'theme_mode';

$userResult = $conn->query("SELECT " . implode(",", $selectCols) . " FROM users WHERE $primaryKey = $user_id");
$user = $userResult->fetch_assoc() ?? ['user_name'=>'', 'email'=>''];

if (!$hasEmailNotifications) $user['email_notifications'] = 1;
if (!$hasThemeMode) $user['theme_mode'] = 'light';

// --- Handle updates ---
if (isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['user_name']);
    $email = $conn->real_escape_string($_POST['email']);
    if ($conn->query("UPDATE users SET user_name='$name', email='$email' WHERE $primaryKey=$user_id")) {
        $success = "Profile updated successfully!";
        $_SESSION['user_name'] = $name;
        $user['user_name'] = $name;
        $user['email'] = $email;
    } else $error = "Error updating profile: " . $conn->error;
}

if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $result = $conn->query("SELECT password FROM users WHERE $primaryKey=$user_id");
    $row = $result->fetch_assoc();
    if (!password_verify($current, $row['password'] ?? '')) $error = "Current password is incorrect.";
    elseif ($new !== $confirm) $error = "New password and confirm password do not match.";
    else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE $primaryKey=$user_id");
        $success = "Password changed successfully!";
    }
}

if (isset($_POST['update_preferences'])) {
    $email_notify = isset($_POST['email_notifications']) ? 1 : 0;
    $theme_mode = $_POST['theme_mode'] === 'dark' ? 'dark' : 'light';
    $updates = [];
    if ($hasEmailNotifications) $updates[] = "email_notifications=$email_notify";
    if ($hasThemeMode) $updates[] = "theme_mode='$theme_mode'";
    if ($updates) {
        $conn->query("UPDATE users SET " . implode(",", $updates) . " WHERE $primaryKey=$user_id");
        $success = "Preferences updated successfully!";
        if ($hasEmailNotifications) $user['email_notifications'] = $email_notify;
        if ($hasThemeMode) $user['theme_mode'] = $theme_mode;
    } else $success = "Preferences saved (optional columns not present).";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings - BillSimp</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; }
.sidebar { background: linear-gradient(180deg, #2563eb, #1d4ed8); height: 100vh; padding-top: 30px; color: white; position: fixed; top:0; left:0; width:220px; transition: all 0.3s ease; overflow-y:auto; }
.sidebar.collapsed { width:70px; }
.sidebar h4 { font-weight:700; text-align:center; margin-bottom:2rem; font-size:1.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sidebar a { display:block; padding:12px 24px; color:#cbd5e1; text-decoration:none; font-weight:500; border-radius:6px; margin:4px 12px; transition: all 0.25s ease; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sidebar a i { margin-right:10px; min-width:20px; text-align:center; }
.sidebar a.active, .sidebar a:hover { background-color:#1e40af; color:white; }
.navbar { background-color:white; padding:10px 20px; border-bottom:1px solid #e3e6f0; margin-left:220px; transition: margin-left 0.3s ease; }
.main-content { margin-left:220px; padding:20px; transition: margin-left 0.3s ease; }
.sidebar.collapsed ~ .main-content, .sidebar.collapsed ~ .main-content .navbar { margin-left:70px; }
.welcome-text { font-weight:600; color:#1f2937; }
.card { border-radius:12px; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s ease; margin-bottom:20px; }
.card:hover { transform: translateY(-4px); }
.btn-primary { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border:none; }
.btn-primary:hover { background: linear-gradient(135deg,#2563eb,#1e40af); }
.form-control, .form-select { border-radius:8px; }
.alert { border-radius:8px; }
.section-title { font-size:1.1rem; font-weight:600; margin-bottom:15px; }
#sidebarToggle { cursor:pointer; margin-bottom:10px; display:inline-block; }
@media (max-width:768px) {
    .sidebar { left:-220px; }
    .sidebar.active { left:0; }
    .main-content { margin-left:0; }
    .navbar { margin-left:0; }
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <h4>💲 BillSimp</h4>
  <a href="index.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
  <a href="invoices.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Create Invoice"><i class="fa-solid fa-file-invoice"></i> Create Invoice</a>
  <a href="payments.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Payments"><i class="fa-solid fa-money-check-dollar"></i> Payments</a>
  <a href="quotes.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Create Quote"><i class="fa-solid fa-quote-right"></i> Create Quote</a>
  <a href="statements.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Statements"><i class="fa-solid fa-receipt"></i> Statements</a>
  <a href="clients.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Clients"><i class="fa-solid fa-users"></i> Clients</a>
  <a href="#" class="active" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings"><i class="fa-solid fa-gear"></i> Settings</a>
  <a href="email.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Send Email"><i class="fa-solid fa-envelope"></i> Send Email</a>
  <a href="logout.php" class="mt-auto mb-3" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main-content">
  <div class="navbar d-flex justify-content-between align-items-center">
    <div>
      <i id="sidebarToggle" class="fa fa-bars me-2 text-primary"></i>
      <span class="fw-bold">Settings</span>
    </div>
    <p class="welcome-text m-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']); ?> 👋</p>
  </div>

  <div class="p-4">
    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="row g-4">
      <!-- Profile -->
      <div class="col-md-6">
        <div class="card p-4">
          <h4 class="mb-3"><i class="fa fa-user me-2 text-primary"></i> Profile</h4>
          <form method="POST">
            <div class="mb-3"><label>Name</label><input type="text" name="user_name" class="form-control" value="<?= htmlspecialchars($user['user_name']); ?>" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required></div>
            <button type="submit" name="update_profile" class="btn btn-primary w-100"><i class="fa fa-save me-2"></i> Update Profile</button>
          </form>
        </div>
      </div>
      <!-- Change Password -->
      <div class="col-md-6">
        <div class="card p-4">
          <h4 class="mb-3"><i class="fa fa-key me-2 text-warning"></i> Change Password</h4>
          <form method="POST">
            <div class="mb-3"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required></div>
            <div class="mb-3"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" name="change_password" class="btn btn-primary w-100"><i class="fa fa-lock me-2"></i> Change Password</button>
          </form>
        </div>
      </div>
      <!-- Preferences -->
      <div class="col-md-12">
        <div class="card p-4">
          <h4 class="mb-3"><i class="fa fa-cog me-2 text-success"></i> Preferences</h4>
          <form method="POST">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" <?= $user['email_notifications'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="email_notifications">Enable Email Notifications</label>
            </div>
            <div class="mb-3">
              <label>Theme Mode</label>
              <select id="theme_mode" name="theme_mode" class="form-select">
                <option value="light" <?= $user['theme_mode']=='light'?'selected':'' ?>>Light</option>
                <option value="dark" <?= $user['theme_mode']=='dark'?'selected':'' ?>>Dark</option>
              </select>
            </div>
            <button type="submit" name="update_preferences" class="btn btn-primary w-100"><i class="fa fa-check me-2"></i> Save Preferences</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

// Initialize Bootstrap tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

// Load sidebar state
if(localStorage.getItem('sidebarCollapsed') === 'true') {
    sidebar.classList.add('collapsed');
}

// Toggle sidebar
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
});

// Auto collapse on small screens
function checkScreen() {
    if(window.innerWidth <= 768) sidebar.classList.add('collapsed');
}
window.addEventListener('resize', checkScreen);
checkScreen();
</script>
</body>
</html>
