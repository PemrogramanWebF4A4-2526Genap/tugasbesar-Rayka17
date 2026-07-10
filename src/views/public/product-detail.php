<?php
session_start();
include "../../config/database.php";

if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$id = (int) $_GET['id'];

if ($id <= 0) {
    header("Location: home.php");
    exit;
}

$product = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        products.*,
        categories.name AS category_name,
        users.name AS seller_name
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    LEFT JOIN users ON products.seller_id = users.id
    WHERE products.id='$id'
"));

if (!$product) {
    die("Produk tidak ditemukan.");
}

$reviews = mysqli_query($conn, "
    SELECT 
        reviews.*,
        users.name AS buyer_name
    FROM reviews
    JOIN users ON reviews.user_id = users.id
    WHERE reviews.product_id='$id'
    ORDER BY reviews.id DESC
");

$ratingData = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        AVG(rating) AS avg_rating,
        COUNT(*) AS total_review
    FROM reviews
    WHERE product_id='$id'
"));

$avgRating = $ratingData['avg_rating'] ?? 0;
$totalReview = $ratingData['total_review'] ?? 0;

$imageSrc = "../../uploads/products/" . $product['image'];
$imageFile = __DIR__ . "/../../uploads/products/" . $product['image'];
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern min-h-screen">

<?php include "../layouts/buyer-navbar.php"; ?>

<section class="max-w-7xl mx-auto px-6 py-12">

    <div class="mb-8">

        <a href="home.php" class="modern-btn-outline">
            ← Kembali ke Marketplace
        </a>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-12">

        <div class="modern-card p-6">

            <?php if (!empty($product['image']) && file_exists($imageFile)) : ?>

                <img
                    src="<?= $imageSrc; ?>"
                    class="w-full h-[480px] object-contain bg-gray-50 rounded-3xl border p-5"
                    alt="<?= htmlspecialchars($product['name']); ?>"
                >

            <?php else : ?>

                <div class="w-full h-[480px] bg-gray-100 rounded-3xl border flex items-center justify-center text-gray-400">
                    No Image
                </div>

            <?php endif; ?>

        </div>

        <div class="modern-card p-8">

            <div class="mb-5">
                <span class="status-pill bg-gray-100 text-gray-700">
                    <?= htmlspecialchars($product['category_name'] ?? 'Tanpa Kategori'); ?>
                </span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-extrabold text-[#292c41] leading-tight mb-5">
                <?= htmlspecialchars($product['name']); ?>
            </h1>

            <div class="flex items-center gap-3 mb-6">

                <p class="text-yellow-500 font-extrabold">
                    ⭐ <?= number_format($avgRating, 1); ?>
                </p>

                <p class="text-gray-500">
                    (<?= $totalReview; ?> ulasan)
                </p>

            </div>

            <p class="text-4xl font-extrabold text-[#292c41] mb-8">
                Rp <?= number_format($product['price']); ?>
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">

                <div class="modern-card p-5">
                    <p class="text-gray-500 text-sm mb-1">
                        Stok
                    </p>

                    <h3 class="font-extrabold text-[#292c41]">
                        <?= $product['stock']; ?> tersedia
                    </h3>
                </div>

                <div class="modern-card p-5">
                    <p class="text-gray-500 text-sm mb-1">
                        Seller
                    </p>

                    <h3 class="font-extrabold text-[#292c41]">
                        <?= htmlspecialchars($product['seller_name'] ?? 'Seller'); ?>
                    </h3>
                </div>

            </div>

            <div class="mb-8">

                <h2 class="text-xl font-extrabold text-[#292c41] mb-3">
                    Deskripsi Produk
                </h2>

                <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                    <?= htmlspecialchars($product['description']); ?>
                </p>

            </div>

            <?php if ($product['stock'] > 0) : ?>

                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'buyer') : ?>

                    <a
                        href="../buyer/add-to-cart.php?id=<?= $product['id']; ?>"
                        class="modern-btn w-full py-4"
                    >
                        + Tambah ke Keranjang
                    </a>

                <?php elseif (!isset($_SESSION['user'])) : ?>

                    <a
                        href="login.php"
                        class="modern-btn w-full py-4"
                    >
                        Login untuk Membeli
                    </a>

                <?php else : ?>

                    <div class="bg-gray-100 text-gray-500 text-center py-4 rounded-2xl font-bold">
                        Login sebagai buyer untuk membeli produk
                    </div>

                <?php endif; ?>

            <?php else : ?>

                <button
                    disabled
                    class="w-full bg-gray-300 text-gray-500 py-4 rounded-2xl font-bold cursor-not-allowed"
                >
                    Stok Habis
                </button>

            <?php endif; ?>

        </div>

    </div>

    <div class="modern-card p-8">

        <h2 class="text-3xl font-extrabold text-[#292c41] mb-6">
            Ulasan Pembeli
        </h2>

        <?php if (mysqli_num_rows($reviews) > 0) : ?>

            <div class="space-y-5">

                <?php while($review = mysqli_fetch_assoc($reviews)) : ?>

                    <div class="border-b pb-5">

                        <div class="flex justify-between items-start gap-4 mb-3">

                            <div>
                                <h3 class="font-extrabold text-[#292c41]">
                                    <?= htmlspecialchars($review['buyer_name']); ?>
                                </h3>

                                <p class="text-sm text-gray-400">
                                    <?= $review['created_at']; ?>
                                </p>
                            </div>

                            <p class="text-yellow-500">
                                <?= str_repeat("⭐", $review['rating']); ?>
                            </p>

                        </div>

                        <p class="text-gray-600 leading-relaxed">
                            <?= htmlspecialchars($review['comment']); ?>
                        </p>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else : ?>

            <div class="bg-gray-50 rounded-3xl p-8 text-center">

                <h3 class="text-xl font-extrabold text-[#292c41] mb-2">
                    Belum Ada Ulasan
                </h3>

                <p class="text-gray-500">
                    Jadilah pembeli pertama yang memberikan review.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>

<script src="../../assets/js/modern.js"></script>

</body>
</html>