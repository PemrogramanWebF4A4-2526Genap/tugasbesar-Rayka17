<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $order_id = !empty($_POST['order_id']) ? (int) $_POST['order_id'] : "NULL";
        $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
        $message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');

        if ($title === '' || $message === '') {
            header("Location: complaints.php?error=1");
            exit;
        }

        if ($order_id !== "NULL") {
            $checkOrder = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT id
                FROM laundry_orders
                WHERE id='$order_id'
                AND user_id='$user_id'
                LIMIT 1
            "));

            if (!$checkOrder) {
                header("Location: complaints.php?error=1");
                exit;
            }
        }

        mysqli_query($conn, "
            INSERT INTO complaints(buyer_id,order_id,title,message,status)
            VALUES('$user_id',$order_id,'$title','$message','pending')
        ");

        $complaint_id = mysqli_insert_id($conn);

        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            SELECT id,
                   'Keluhan Baru',
                   'Pelanggan membuat keluhan #$complaint_id.',
                   0
            FROM users
            WHERE role='customer_service'
            AND status='active'
        ");

        header("Location: complaints.php?created=1");
        exit;
    }

    if ($action === 'reply') {
        $complaint_id = (int) ($_POST['complaint_id'] ?? 0);
        $reply = mysqli_real_escape_string($conn, $_POST['reply'] ?? '');

        $complaint = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT *
            FROM complaints
            WHERE id='$complaint_id'
            AND buyer_id='$user_id'
            LIMIT 1
        "));

        if (!$complaint || $reply === '') {
            header("Location: complaints.php?error=1");
            exit;
        }

        mysqli_query($conn, "
            INSERT INTO complaint_replies(complaint_id,replier_id,reply)
            VALUES('$complaint_id','$user_id','$reply')
        ");

        mysqli_query($conn, "
            UPDATE complaints
            SET status='process'
            WHERE id='$complaint_id'
            AND status!='done'
        ");

        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            SELECT id,
                   'Balasan Keluhan Pelanggan',
                   'Pelanggan membalas keluhan #$complaint_id.',
                   0
            FROM users
            WHERE role='customer_service'
            AND status='active'
        ");

        header("Location: complaints.php?replied=1");
        exit;
    }

    header("Location: complaints.php?error=1");
    exit;
}

$orders = mysqli_query($conn, "
    SELECT
        laundry_orders.id,
        laundry_orders.customer_name,
        laundry_orders.status,
        laundry_orders.total_price,
        laundry_services.service_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    WHERE laundry_orders.user_id='$user_id'
    ORDER BY laundry_orders.id DESC
");

$complaints = mysqli_query($conn, "
    SELECT
        complaints.*,
        laundry_orders.customer_name,
        laundry_services.service_name
    FROM complaints
    LEFT JOIN laundry_orders ON complaints.order_id = laundry_orders.id
    LEFT JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    WHERE complaints.buyer_id='$user_id'
    ORDER BY complaints.id DESC
");

function complaintBadge($status)
{
    $styles = [
        'pending' => 'background:#fef3c7;color:#92400e;',
        'process' => 'background:#dbeafe;color:#1d4ed8;',
        'done' => 'background:#dcfce7;color:#166534;',
    ];

    $labels = [
        'pending' => 'Menunggu',
        'process' => 'Diproses',
        'done' => 'Selesai',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}

function getReplies($conn, $complaint_id)
{
    return mysqli_query($conn, "
        SELECT
            complaint_replies.*,
            users.name AS replier_name,
            users.role AS replier_role
        FROM complaint_replies
        LEFT JOIN users ON complaint_replies.replier_id = users.id
        WHERE complaint_replies.complaint_id='$complaint_id'
        ORDER BY complaint_replies.id ASC
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Keluhan Saya</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/buyer-navbar.php"; ?>

<section style="padding:34px 7%;">

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
        <div>
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                Customer Service
            </p>

            <h1 class="page-title">
                Keluhan Saya
            </h1>

            <p class="page-subtitle">
                Buat keluhan terkait pesanan laundry dan lihat balasan customer service.
            </p>
        </div>

        <a href="orders.php" class="modern-btn-outline">
            Pesanan Saya
        </a>
    </div>

    <?php if (isset($_GET['created'])) : ?>
        <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
            Keluhan berhasil dibuat.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['replied'])) : ?>
        <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
            Balasan berhasil dikirim.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])) : ?>
        <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
            Gagal memproses keluhan.
        </div>
    <?php endif; ?>

    <details class="modern-card" style="padding:22px;margin-bottom:24px;" open>
        <summary style="cursor:pointer;font-weight:800;color:#0369a1;font-size:16px;">
            Buat Keluhan Baru
        </summary>

        <form method="POST" style="margin-top:20px;">
            <input type="hidden" name="action" value="create">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">
                        Pilih Pesanan
                    </label>

                    <select name="order_id" class="modern-input">
                        <option value="">Keluhan umum tanpa pesanan</option>

                        <?php if ($orders && mysqli_num_rows($orders) > 0) : ?>
                            <?php while ($order = mysqli_fetch_assoc($orders)) : ?>
                                <option value="<?= $order['id']; ?>">
                                    Order #<?= $order['id']; ?> - <?= htmlspecialchars($order['service_name']); ?> - <?= htmlspecialchars($order['status']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">
                        Judul Keluhan
                    </label>

                    <input type="text" name="title" class="modern-input" placeholder="Contoh: Pesanan belum selesai" required>
                </div>

                <div style="grid-column:1/-1;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">
                        Isi Keluhan
                    </label>

                    <textarea name="message" rows="5" class="modern-input" placeholder="Jelaskan keluhan kamu dengan jelas" required></textarea>
                </div>
            </div>

            <button type="submit" class="modern-btn" style="margin-top:16px;">
                Kirim Keluhan
            </button>
        </form>
    </details>

    <div style="display:flex;flex-direction:column;gap:18px;">

        <?php if ($complaints && mysqli_num_rows($complaints) > 0) : ?>

            <?php while ($row = mysqli_fetch_assoc($complaints)) : ?>

                <div class="modern-card" style="padding:22px;">

                    <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                        <div>
                            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                Keluhan #<?= $row['id']; ?> <?= $row['order_id'] ? '• Order #' . $row['order_id'] : ''; ?>
                            </p>

                            <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                <?= htmlspecialchars($row['title']); ?>
                            </h2>

                            <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                <?= $row['service_name'] ? htmlspecialchars($row['service_name']) : 'Keluhan umum'; ?>
                            </p>
                        </div>

                        <?= complaintBadge($row['status']); ?>
                    </div>

                    <div style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;margin-bottom:14px;">
                        <p style="font-weight:800;color:#0369a1;margin-bottom:7px;">
                            Keluhan
                        </p>

                        <p style="color:#64748b;line-height:1.7;font-size:13px;">
                            <?= nl2br(htmlspecialchars($row['message'])); ?>
                        </p>
                    </div>

                    <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                        <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                            Balasan Customer Service
                        </summary>

                        <div style="margin-top:14px;display:flex;flex-direction:column;gap:12px;">
                            <?php $replies = getReplies($conn, $row['id']); ?>

                            <?php if ($replies && mysqli_num_rows($replies) > 0) : ?>

                                <?php while ($reply = mysqli_fetch_assoc($replies)) : ?>
                                    <div style="background:white;border:1px solid #e0f2fe;border-radius:16px;padding:13px;">
                                        <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">
                                            <?= htmlspecialchars($reply['replier_name'] ?: 'User'); ?>
                                            <span style="font-weight:700;color:#64748b;">
                                                (<?= htmlspecialchars($reply['replier_role'] ?: '-'); ?>)
                                            </span>
                                        </p>

                                        <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                            <?= nl2br(htmlspecialchars($reply['reply'])); ?>
                                        </p>

                                        <p style="color:#94a3b8;font-size:12px;margin-top:8px;">
                                            <?= htmlspecialchars($reply['created_at']); ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>

                            <?php else : ?>

                                <p style="color:#64748b;">
                                    Customer service belum memberi balasan.
                                </p>

                            <?php endif; ?>

                            <?php if ($row['status'] !== 'done') : ?>
                                <form method="POST" style="margin-top:10px;">
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="complaint_id" value="<?= $row['id']; ?>">

                                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">
                                        Tambah Balasan
                                    </label>

                                    <textarea name="reply" rows="3" class="modern-input" placeholder="Tulis balasan lanjutan" required></textarea>

                                    <button type="submit" class="modern-btn" style="margin-top:12px;">
                                        Kirim Balasan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </details>

                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <div class="modern-card" style="padding:36px;text-align:center;">
                <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Belum Ada Keluhan
                </h2>

                <p style="color:#64748b;">
                    Buat keluhan baru jika ada kendala pada pesanan laundry.
                </p>
            </div>

        <?php endif; ?>

    </div>

</section>

<style>
@media (max-width: 900px) {
    form div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>