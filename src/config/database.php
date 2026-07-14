<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'umkm_marketplace';

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);

if (!$conn) {
    die(
        'Koneksi database gagal: '
        . mysqli_connect_error()
    );
}

mysqli_set_charset(
    $conn,
    'utf8mb4'
);

date_default_timezone_set(
    'Asia/Jakarta'
);

$db = $conn;
$mysqli = $conn;