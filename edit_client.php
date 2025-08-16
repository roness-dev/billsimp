<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_SESSION['user_id']);
$success = $error = '';

// Ensure we have a valid client ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: clients.php");
    exit();
}
$client_id = intval($_GET['id']);

// Fetch client data
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ? AND user_id = ?");
$stmt->bind_param("ii", $client_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    header("Location: clients.php?error=Client not found");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name && $email && $phone) {
        $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, address = ? WHERE client_id = ? AND user_id = ?");
        $stmt->bind_param("ssssii", $name, $email, $phone, $address, $client_id, $user_id);

        if ($stmt->execute()) {
            header("Location: clients.php?success=Client updated successfully");
            exit();
        } else {
            $error = "Failed to update client. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Client</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-5">
    <h3 class="mb-4"><i class="fas fa-edit me-2"></i> Edit Client</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow border-0">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"><?= htmlspecialchars($client['address']) ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="clients.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Client</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
