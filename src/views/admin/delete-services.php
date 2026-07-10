<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: services.php");
    exit;
}

$id = (int) $_GET['id'];
$status = $_GET['status'];

if (!in_array($status, ['active', 'inactive'])) {
    die("Status layanan tidak valid.");
}

mysqli_query($conn, "
    UPDATE laundry_services
    SET status='$status'
    WHERE id='$id'
");

header("Location: services.php");
exit;