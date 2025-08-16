<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "includes/db.php";

$error = '';

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, user_name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                header("Location: index.php"); // Redirect to index.php instead of dashboard.php
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Email not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - BillSimp</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color: #1e40af; font-family: 'Segoe UI', sans-serif; }
.form-container { max-width: 400px; margin: 80px auto; background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.2); }
.form-logo { font-size: 32px; font-weight: bold; color: #2563eb; text-align: center; margin-bottom: 20px; }
.form-control:focus { box-shadow: none; border-color: #2563eb; }
.btn-primary { background-color: #2563eb; border: none; }
.btn-primary:hover { background-color: #1e4fd5; }
.link-text { text-align: center; margin-top: 20px; }
.link-text a { color: #2563eb; text-decoration: none; }
.link-text a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="form-container">
    <div class="form-logo">BillSimp</div>
    <?php if($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" name="email" required placeholder="Email">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" name="password" required placeholder="Password">
            </div>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>
    <div class="link-text">
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
