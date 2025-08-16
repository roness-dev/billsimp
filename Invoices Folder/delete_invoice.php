<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$conn = new mysqli("localhost", "root", "", "billsimp_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_SESSION['user_id']);

if (isset($_GET['id'])) {
    $invoice_id = $conn->real_escape_string($_GET['id']);
    // Delete invoice
    $conn->query("DELETE FROM invoices WHERE invoice_id='$invoice_id' AND user_id=$user_id");
}

header("Location: invoices.php");
exit;
?>
