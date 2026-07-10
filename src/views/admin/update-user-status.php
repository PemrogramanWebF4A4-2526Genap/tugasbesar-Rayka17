<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

$user_id = (int) ($_POST['user_id'] ?? 0);
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

if (!in_array($status, ['active', 'blocked'])) {
    header("Location: users.php?error=1");
    exit;
}

if ($user_id == $_SESSION['user']['id']) {
    header("Location: users.php?error=1");
    exit;
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM users
    WHERE id='$user_id'
    LIMIT 1
"));

if (!$user) {
    header("Location: users.php?error=1");
    exit;
}

mysqli_query($conn, "
    UPDATE users
    SET status='$status'
    WHERE id='$user_id'
");

if ($user['role'] == 'seller') {
    mysqli_query($conn, "
        UPDATE laundry_partners
        SET status='$status'
        WHERE user_id='$user_id'
    ");
}

header("Location: users.php?updated=1");
exit;