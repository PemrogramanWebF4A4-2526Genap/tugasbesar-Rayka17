<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = (int) $_GET['id'];

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM users
    WHERE id='$id'
"));

if (!$user) {
    die("User tidak ditemukan.");
}

if ($user['role'] == 'admin') {
    die("Admin tidak boleh dihapus.");
}

mysqli_query($conn, "
    DELETE FROM users
    WHERE id='$id'
");

header("Location: users.php");
exit;