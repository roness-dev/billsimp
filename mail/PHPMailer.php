<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// DB connection
$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Fetch clients
$clients = $conn->query("SELECT client_id, client_name, email FROM clients");

// Handle form submission
$success = $error = '';
if (isset($_POST['send_email'])) {
    $client_id = intval($_POST['client_id']);
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Get client email
    $result = $conn->query("SELECT email FROM clients WHERE client_id=$client_id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $to_email = $row['email'];

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.example.com'; // Replace with your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@example.com'; // Your SMTP email
            $mail->Password   = 'your_email_password';    // Your SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('your_email@example.com', 'BillSimp');
            $mail->addAddress($to_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
            $success = "Email sent successfully to $to_email!";
        } catch (Exception $e) {
            $error = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Client email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Email | BillSimp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; // include your dashboard navbar ?>
<div class="container mt-4">
    <h2>Send Email</h2>
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label for="client_id" class="form-label">Select Client</label>
            <select name="client_id" id="client_id" class="form-select" required>
                <option value="">-- Select Client --</option>
                <?php while($client = $clients->fetch_assoc()): ?>
                    <option value="<?= $client['client_id'] ?>"><?= $client['client_name'] ?> (<?= $client['email'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" class="form-control" rows="6" required></textarea>
        </div>
        <button type="submit" name="send_email" class="btn btn-primary">Send Email</button>
    </form>
</div>
</body>
</html>

