<?php
session_start();
$conn = new mysqli("localhost","root","","billsimp_db");
$id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id'] ?? 0);
$conn->query("DELETE FROM quotes WHERE quote_id=$id AND user_id=$user_id");
header("Location: quotes.php");
exit();
