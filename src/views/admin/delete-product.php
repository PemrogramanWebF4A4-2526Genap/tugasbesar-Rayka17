<?php
session_start();
include "../../config/database.php";
include "../../config/security.php";

requireRole('admin');

$id = getId('id');

if ($id <= 0) {
    die("ID tidak valid.");
}

$product = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM products
    WHERE id='$id'
"));

if (!$product) {
    die("Produk tidak ditemukan.");
}

if (!empty($product['image'])) {
    $imagePath = "../../uploads/products/" . $product['image'];

    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

mysqli_query($conn, "
    DELETE FROM products
    WHERE id='$id'
");

header("Location: services.php");
exit;