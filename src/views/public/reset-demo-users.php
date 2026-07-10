<?php
include "../../config/database.php";

$password = password_hash("123456", PASSWORD_DEFAULT);

$accounts = [
    [
        "name" => "Admin Laundry",
        "email" => "admin@gmail.com",
        "role" => "admin",
        "phone" => "081111111111",
        "address" => "Kantor Laundry UMKM"
    ],
    [
        "name" => "Pelanggan Laundry",
        "email" => "pelanggan@gmail.com",
        "role" => "buyer",
        "phone" => "083333333333",
        "address" => "Bekasi"
    ],
    [
        "name" => "Mitra Laundry Cemerlang",
        "email" => "mitra@gmail.com",
        "role" => "seller",
        "phone" => "084444444444",
        "address" => "Bekasi"
    ],
    [
        "name" => "Kurir Laundry",
        "email" => "kurir@gmail.com",
        "role" => "courier",
        "phone" => "085555555555",
        "address" => "Bekasi"
    ]
];

foreach ($accounts as $acc) {
    $email = mysqli_real_escape_string($conn, $acc['email']);
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1"));

    if ($check) {
        mysqli_query($conn, "
            UPDATE users
            SET
                name='{$acc['name']}',
                password='$password',
                role='{$acc['role']}',
                status='active',
                phone='{$acc['phone']}',
                address='{$acc['address']}'
            WHERE email='$email'
        ");
    } else {
        mysqli_query($conn, "
            INSERT INTO users(name, email, password, role, status, phone, address)
            VALUES(
                '{$acc['name']}',
                '{$acc['email']}',
                '$password',
                '{$acc['role']}',
                'active',
                '{$acc['phone']}',
                '{$acc['address']}'
            )
        ");
    }
}

$mitraUser = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id
    FROM users
    WHERE email='mitra@gmail.com'
    LIMIT 1
"));

if ($mitraUser) {
    $mitra_user_id = $mitraUser['id'];

    $partnerCheck = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT id
        FROM laundry_partners
        WHERE user_id='$mitra_user_id'
        LIMIT 1
    "));

    if ($partnerCheck) {
        mysqli_query($conn, "
            UPDATE laundry_partners
            SET
                mitra_name='Mitra Laundry Cemerlang',
                owner_name='Owner Mitra',
                phone='084444444444',
                city='Bekasi',
                address='Bekasi',
                description='Mitra laundry aktif dengan layanan kiloan, express, sepatu, tas, dan antar jemput.',
                pickup_fee=8000,
                delivery_fee=8000,
                status='active'
            WHERE user_id='$mitra_user_id'
        ");
    } else {
        mysqli_query($conn, "
            INSERT INTO laundry_partners(
                user_id,
                mitra_name,
                owner_name,
                phone,
                city,
                address,
                description,
                pickup_fee,
                delivery_fee,
                status
            ) VALUES (
                '$mitra_user_id',
                'Mitra Laundry Cemerlang',
                'Owner Mitra',
                '084444444444',
                'Bekasi',
                'Bekasi',
                'Mitra laundry aktif dengan layanan kiloan, express, sepatu, tas, dan antar jemput.',
                8000,
                8000,
                'active'
            )
        ");
    }
}

echo "<h2>Reset akun demo berhasil.</h2>";
echo "<p>Semua password sekarang: <b>123456</b></p>";
echo "<ul>";
echo "<li>admin@gmail.com / 123456</li>";
echo "<li>pelanggan@gmail.com / 123456</li>";
echo "<li>mitra@gmail.com / 123456</li>";
echo "<li>kurir@gmail.com / 123456</li>";
echo "</ul>";
echo "<a href='login.php'>Kembali ke Login</a>";