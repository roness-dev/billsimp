<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");
require_once "includes/db.php";

$user_id = intval($_SESSION['user_id']);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $conn->real_escape_string($_POST['client_name']);
    $status = $conn->real_escape_string($_POST['status']);
    $items = $_POST['items'] ?? [];

    if (empty($client_name) || empty($items)) {
        $error = "Client and at least one item are required.";
    } else {
        $total = 0;
        foreach ($items as $item) $total += floatval($item['quantity']) * floatval($item['unit_price']);
        $quote_id = "Q" . time();

        $stmt = $conn->prepare("INSERT INTO quotes (quote_id, user_id, client_name, amount, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssd", $quote_id, $user_id, $client_name, $total, $status);
        $success = $stmt->execute() ? "Quote created successfully!" : "Error: " . $conn->error;
    }
}

$clientsResult = $conn->query("SELECT client_name FROM clients WHERE user_id = $user_id ORDER BY client_name ASC");
$clients = [];
while ($row = $clientsResult->fetch_assoc()) $clients[] = $row['client_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Quote - BillSimp</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
h4 { font-weight: 600; color: #1f2937; }
.btn-primary { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border:none; }
.btn-primary:hover { background: linear-gradient(135deg,#2563eb,#1e40af); }
.remove-item-btn { margin-top:0; }
.form-control, .form-select { border-radius: 8px; }
.alert { border-radius: 8px; }
.item-row { align-items: center; }
</style>

<script>
$(document).ready(function() {
    let itemIndex = 0;
    $("#addItemBtn").click(function(e) {
        e.preventDefault(); itemIndex++;
        const newRow = `<div class="row g-2 mb-2 item-row">
            <div class="col-md-5"><input type="text" name="items[${itemIndex}][description]" class="form-control" placeholder="Item Description" required></div>
            <div class="col-md-2"><input type="number" name="items[${itemIndex}][quantity]" class="form-control" placeholder="Qty" min="1" step="1" required></div>
            <div class="col-md-3"><input type="number" name="items[${itemIndex}][unit_price]" class="form-control" placeholder="Unit Price" min="0" step="0.01" required></div>
            <div class="col-md-2"><button class="btn btn-danger remove-item-btn w-100"><i class="fa fa-trash"></i> Remove</button></div>
        </div>`;
        $("#itemsContainer").append(newRow);
    });
    $(document).on("click",".remove-item-btn",function(e){e.preventDefault();$(this).closest(".item-row").remove();});
});
</script>
</head>
<body>
<div class="container my-5">
    <div class="card p-4">
        <h4>Create New Quote</h4>
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>Client</label>
                    <select name="client_name" class="form-select" required>
                        <option value="">Select Client</option>
                        <?php foreach($clients as $client): ?>
                        <option value="<?= htmlspecialchars($client) ?>"><?= htmlspecialchars($client) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Pending">Pending</option>
                        <option value="Sent">Sent</option>
                    </select>
                </div>
            </div>

            <h5>Items</h5>
            <div id="itemsContainer">
                <div class="row g-2 mb-2 item-row">
                    <div class="col-md-5"><input type="text" name="items[0][description]" class="form-control" placeholder="Item Description" required></div>
                    <div class="col-md-2"><input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" min="1" step="1" required></div>
                    <div class="col-md-3"><input type="number" name="items[0][unit_price]" class="form-control" placeholder="Unit Price" min="0" step="0.01" required></div>
                    <div class="col-md-2"><button class="btn btn-danger remove-item-btn w-100"><i class="fa fa-trash"></i> Remove</button></div>
                </div>
            </div>
            <button id="addItemBtn" class="btn btn-secondary mb-3"><i class="fa fa-plus"></i> Add Item</button>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fa fa-paper-plane"></i> Create Quote</button>
                <a href="quotes.php" class="btn btn-outline-secondary flex-grow-1"><i class="fa fa-arrow-left"></i> Back to Quotes</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

