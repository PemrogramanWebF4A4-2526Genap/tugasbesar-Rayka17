<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer_service') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $complaint_id = (int) ($_POST['complaint_id'] ?? 0);

    $complaint = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM complaints
        WHERE id='$complaint_id'
        LIMIT 1
    "));

    if (!$complaint) {
        header("Location: complaints.php?error=1");
        exit;
    }

    if ($action === 'reply') {
        $reply = mysqli_real_escape_string($conn, $_POST['reply'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'process');

        $allowedStatus = ['pending', 'process', 'done'];

        if ($reply === '' || !in_array($status, $allowedStatus)) {
            header("Location: complaints.php?error=1");
            exit;
        }

        mysqli_query($conn, "
            INSERT INTO complaint_replies(complaint_id,replier_id,reply)
            VALUES('$complaint_id','$user_id','$reply')
        ");

        if ($status === 'done') {
            mysqli_query($conn, "
                UPDATE complaints
                SET status='$status',
                    reply_by='$user_id',
                    closed_at=IF(closed_at IS NULL, NOW(), closed_at)
                WHERE id='$complaint_id'
            ");
        } else {
            mysqli_query($conn, "
                UPDATE complaints
                SET status='$status',
                    reply_by='$user_id'
                WHERE id='$complaint_id'
            ");
        }

        if (!empty($complaint['buyer_id'])) {
            mysqli_query($conn, "
                INSERT INTO notifications(user_id,title,message,is_read)
                VALUES(
                    '{$complaint['buyer_id']}',
                    'Keluhan Ditanggapi',
                    'Customer service sudah menanggapi keluhan #$complaint_id.',
                    0
                )
            ");
        }

        header("Location: complaints.php?updated=1");
        exit;
    }

    if ($action === 'done') {
        mysqli_query($conn, "
            UPDATE complaints
            SET status='done',
                reply_by='$user_id',
                closed_at=IF(closed_at IS NULL, NOW(), closed_at)
            WHERE id='$complaint_id'
        ");

        header("Location: complaints.php?updated=1");
        exit;
    }

    header("Location: complaints.php?error=1");
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';

$where = "WHERE 1=1";

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND complaints.status='$safeStatus'";
}

$complaints = mysqli_query($conn, "
    SELECT
        complaints.*,
        users.name AS buyer_name,
        users.email AS buyer_email,
        laundry_orders.customer_name,
        laundry_orders.phone,
        laundry_orders.total_price,
        laundry_orders.status AS order_status
    FROM complaints
    LEFT JOIN users ON complaints.buyer_id = users.id
    LEFT JOIN laundry_orders ON complaints.order_id = laundry_orders.id
    $where
    ORDER BY complaints.id DESC
");

function csBadgeComplaint($status)
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

function getComplaintRepliesCS($conn, $complaint_id)
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
    <title>Keluhan Pelanggan</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/customer-service-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/customer-service-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="margin-bottom:22px;">
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Customer Service</p>
            <h1 class="page-title">Keluhan Pelanggan</h1>
            <p class="page-subtitle">Balas keluhan pelanggan dan ubah status penyelesaian.</p>
        </div>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Keluhan berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Gagal memperbarui keluhan.
            </div>
        <?php endif; ?>

        <div class="modern-card" style="padding:16px;margin-bottom:22px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php
                $filters = [
                    'all' => 'Semua',
                    'pending' => 'Menunggu',
                    'process' => 'Diproses',
                    'done' => 'Selesai'
                ];
                ?>

                <?php foreach ($filters as $key => $label) : ?>
                    <a href="complaints.php?status=<?= $key; ?>" class="<?= $statusFilter === $key ? 'modern-btn' : 'modern-btn-outline'; ?>" style="padding:10px 16px;">
                        <?= $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:18px;">
            <?php if ($complaints && mysqli_num_rows($complaints) > 0) : ?>

                <?php while ($row = mysqli_fetch_assoc($complaints)) : ?>

                    <div class="modern-card complaint-card" style="padding:22px;">
                        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

                            <div>
                                <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                                    <div>
                                        <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                            Keluhan #<?= $row['id']; ?> <?= $row['order_id'] ? '• Order #' . $row['order_id'] : ''; ?>
                                        </p>

                                        <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                            <?= htmlspecialchars($row['title']); ?>
                                        </h2>

                                        <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                            <?= htmlspecialchars($row['buyer_name'] ?: $row['customer_name'] ?: 'Pelanggan'); ?>
                                            <?= $row['buyer_email'] ? ' • ' . htmlspecialchars($row['buyer_email']) : ''; ?>
                                        </p>
                                    </div>

                                    <?= csBadgeComplaint($row['status']); ?>
                                </div>

                                <div style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;margin-bottom:14px;">
                                    <p style="font-weight:800;color:#0369a1;margin-bottom:7px;">Isi Keluhan</p>
                                    <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                        <?= nl2br(htmlspecialchars($row['message'])); ?>
                                    </p>
                                </div>

                                <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                                    <summary style="cursor:pointer;font-weight:800;color:#0369a1;">Riwayat Balasan</summary>

                                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:12px;">
                                        <?php $replies = getComplaintRepliesCS($conn, $row['id']); ?>

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
                                            <p style="color:#64748b;">Belum ada balasan.</p>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </div>

                            <div class="modern-card" style="padding:19px;background:#f8fdff;">
                                <h3 style="font-size:20px;font-weight:800;color:#0369a1;margin-bottom:14px;">
                                    Tanggapi Keluhan
                                </h3>

                                <form method="POST">
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="complaint_id" value="<?= $row['id']; ?>">

                                    <div style="margin-bottom:13px;">
                                        <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Status</label>
                                        <select name="status" class="modern-input" required>
                                            <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="process" <?= $row['status'] === 'process' ? 'selected' : ''; ?>>Diproses</option>
                                            <option value="done" <?= $row['status'] === 'done' ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>

                                    <div style="margin-bottom:15px;">
                                        <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Balasan</label>
                                        <textarea name="reply" class="modern-input" rows="5" placeholder="Tulis balasan customer service" required></textarea>
                                    </div>

                                    <button type="submit" class="modern-btn" style="width:100%;">
                                        Kirim Balasan
                                    </button>
                                </form>

                                <?php if ($row['status'] !== 'done') : ?>
                                    <form method="POST" style="margin-top:12px;">
                                        <input type="hidden" name="action" value="done">
                                        <input type="hidden" name="complaint_id" value="<?= $row['id']; ?>">

                                        <button type="submit" class="modern-btn-outline" style="width:100%;">
                                            Tandai Selesai
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:36px;text-align:center;">
                    <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                        Belum Ada Keluhan
                    </h2>
                    <p style="color:#64748b;">Keluhan pelanggan akan muncul di halaman ini.</p>
                </div>

            <?php endif; ?>
        </div>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    .complaint-card > div {
        grid-template-columns:1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>