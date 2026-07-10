<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'petugas') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$staff = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        staff.*,
        laundry_mitras.mitra_name
    FROM staff
    JOIN laundry_mitras ON staff.mitra_id = laundry_mitras.id
    WHERE staff.user_id='$user_id'
    LIMIT 1
"));

$mitra_id = $staff['mitra_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff) {
    $action = $_POST['action'] ?? '';
    $task_id = (int) ($_POST['task_id'] ?? 0);

    $task = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            t.*,
            o.user_id AS buyer_id,
            o.mitra_id
        FROM laundry_staff_tasks t
        JOIN laundry_orders o ON t.order_id = o.id
        WHERE t.id='$task_id'
        AND o.mitra_id='$mitra_id'
        LIMIT 1
    "));

    if (!$task) {
        header("Location: petugas-tasks.php?error=1");
        exit;
    }

    $order_id = (int) $task['order_id'];
    $buyer_id = (int) $task['buyer_id'];

    if ($action === 'take') {
        if ($task['task_status'] !== 'waiting') {
            header("Location: petugas-tasks.php?error=1");
            exit;
        }

        mysqli_query($conn, "
            UPDATE laundry_staff_tasks
            SET staff_id='$user_id',
                task_status='assigned'
            WHERE id='$task_id'
            AND task_status='waiting'
        ");

        mysqli_query($conn, "
            UPDATE laundry_orders
            SET staff_id='$user_id'
            WHERE id='$order_id'
            AND mitra_id='$mitra_id'
        ");

        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            VALUES(
                '$buyer_id',
                'Tugas Diambil Petugas',
                'Petugas sudah mengambil tugas untuk pesanan #$order_id.',
                0
            )
        ");

        header("Location: petugas-tasks.php?updated=1");
        exit;
    }

    if ($action === 'update') {
        if ((int) $task['staff_id'] !== $user_id) {
            header("Location: petugas-tasks.php?error=1");
            exit;
        }

        $task_status = mysqli_real_escape_string($conn, $_POST['task_status'] ?? 'assigned');
        $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');

        $allowedStatus = ['assigned', 'on_process', 'completed', 'cancelled'];

        if (!in_array($task_status, $allowedStatus)) {
            header("Location: petugas-tasks.php?error=1");
            exit;
        }

        if ($task_status === 'completed') {
            mysqli_query($conn, "
                UPDATE laundry_staff_tasks
                SET task_status='$task_status',
                    note='$note',
                    completed_at=IF(completed_at IS NULL, NOW(), completed_at)
                WHERE id='$task_id'
                AND staff_id='$user_id'
            ");

            if ($task['task_type'] === 'pickup') {
                mysqli_query($conn, "
                    UPDATE laundry_orders
                    SET picked_up_at=IF(picked_up_at IS NULL, NOW(), picked_up_at)
                    WHERE id='$order_id'
                    AND mitra_id='$mitra_id'
                ");
            }

            if ($task['task_type'] === 'delivery') {
                mysqli_query($conn, "
                    UPDATE laundry_orders
                    SET delivered_at=IF(delivered_at IS NULL, NOW(), delivered_at),
                        status='diambil'
                    WHERE id='$order_id'
                    AND mitra_id='$mitra_id'
                ");
            }

            mysqli_query($conn, "
                INSERT INTO notifications(user_id,title,message,is_read)
                VALUES(
                    '$buyer_id',
                    'Tugas Selesai',
                    'Tugas pesanan #$order_id telah diselesaikan petugas.',
                    0
                )
            ");
        } else {
            mysqli_query($conn, "
                UPDATE laundry_staff_tasks
                SET task_status='$task_status',
                    note='$note'
                WHERE id='$task_id'
                AND staff_id='$user_id'
            ");

            mysqli_query($conn, "
                INSERT INTO notifications(user_id,title,message,is_read)
                VALUES(
                    '$buyer_id',
                    'Status Tugas Diperbarui',
                    'Status tugas pesanan #$order_id diperbarui petugas.',
                    0
                )
            ");
        }

        header("Location: petugas-tasks.php?updated=1");
        exit;
    }

    header("Location: petugas-tasks.php?error=1");
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';

$where = "
    WHERE o.mitra_id='$mitra_id'
    AND (
        t.task_status='waiting'
        OR t.staff_id='$user_id'
    )
";

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND t.task_status='$safeStatus'";
}

$tasks = mysqli_query($conn, "
    SELECT 
        t.*,
        o.customer_name,
        o.phone,
        o.address AS order_address,
        o.status AS order_status,
        o.payment_method,
        o.payment_status,
        o.total_price,
        s.service_name,
        s.unit,
        m.mitra_name
    FROM laundry_staff_tasks t
    JOIN laundry_orders o ON t.order_id = o.id
    JOIN laundry_services s ON o.service_id = s.id
    JOIN laundry_mitras m ON o.mitra_id = m.id
    $where
    ORDER BY 
        CASE
            WHEN t.task_status='waiting' THEN 1
            WHEN t.task_status='assigned' THEN 2
            WHEN t.task_status='on_process' THEN 3
            WHEN t.task_status='completed' THEN 4
            ELSE 5
        END ASC,
        t.id DESC
");

function taskBadgePetugas($status)
{
    $styles = [
        'waiting' => 'background:#fef3c7;color:#92400e;',
        'assigned' => 'background:#dbeafe;color:#1d4ed8;',
        'on_process' => 'background:#e0f2fe;color:#0369a1;',
        'completed' => 'background:#dcfce7;color:#166534;',
        'cancelled' => 'background:#fee2e2;color:#b91c1c;',
    ];

    $labels = [
        'waiting' => 'Menunggu',
        'assigned' => 'Ditugaskan',
        'on_process' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}

function taskTypePetugas($type)
{
    return $type === 'pickup' ? 'Pickup / Jemput Cucian' : 'Delivery / Antar Cucian';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tugas Petugas</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/seller-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/seller-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Panel Petugas</p>
                <h1 class="page-title">Tugas Pickup & Delivery</h1>
                <p class="page-subtitle">
                    Ambil tugas yang tersedia, lalu update status tugas.
                </p>
            </div>

            <a href="petugas-orders.php" class="modern-btn-outline">Lihat Pesanan</a>
        </div>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Tugas berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Gagal memproses tugas.
            </div>
        <?php endif; ?>

        <?php if (!$staff) : ?>

            <div class="modern-card" style="padding:34px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Akun Petugas Belum Terhubung
                </h2>
                <p style="color:#64748b;">Hubungi seller atau admin.</p>
            </div>

        <?php else : ?>

            <div class="modern-card" style="padding:16px;margin-bottom:22px;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <?php
                    $filters = [
                        'all' => 'Semua',
                        'waiting' => 'Menunggu',
                        'assigned' => 'Ditugaskan',
                        'on_process' => 'Diproses',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal'
                    ];
                    ?>

                    <?php foreach ($filters as $key => $label) : ?>
                        <a href="petugas-tasks.php?status=<?= $key; ?>" class="<?= $statusFilter === $key ? 'modern-btn' : 'modern-btn-outline'; ?>" style="padding:10px 16px;">
                            <?= $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:18px;">
                <?php if ($tasks && mysqli_num_rows($tasks) > 0) : ?>
                    <?php while ($task = mysqli_fetch_assoc($tasks)) : ?>
                        <div class="modern-card task-card" style="padding:22px;">
                            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

                                <div>
                                    <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                                        <div>
                                            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                                Tugas #<?= $task['id']; ?> • Order #<?= $task['order_id']; ?>
                                            </p>

                                            <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                                <?= htmlspecialchars(taskTypePetugas($task['task_type'])); ?>
                                            </h2>

                                            <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                                <?= htmlspecialchars($task['customer_name']); ?> • <?= htmlspecialchars($task['service_name']); ?>
                                            </p>
                                        </div>

                                        <?= taskBadgePetugas($task['task_status']); ?>
                                    </div>

                                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Kontak</p>
                                            <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                <?= htmlspecialchars($task['phone']); ?>
                                            </h3>
                                        </div>

                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Fee</p>
                                            <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                Rp <?= number_format($task['fee'], 0, ',', '.'); ?>
                                            </h3>
                                        </div>

                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Total Order</p>
                                            <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                Rp <?= number_format($task['total_price'], 0, ',', '.'); ?>
                                            </h3>
                                        </div>
                                    </div>

                                    <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                                        <summary style="cursor:pointer;font-weight:800;color:#0369a1;">Alamat & Catatan</summary>

                                        <p style="color:#64748b;line-height:1.7;font-size:13px;margin-top:14px;">
                                            <b>Alamat Tugas:</b><br>
                                            <?= nl2br(htmlspecialchars($task['address'] ?: $task['order_address'])); ?><br><br>

                                            <b>Catatan:</b><br>
                                            <?= nl2br(htmlspecialchars($task['note'] ?: '-')); ?>
                                        </p>
                                    </details>
                                </div>

                                <div class="modern-card" style="padding:19px;background:#f8fdff;">
                                    <h3 style="font-size:20px;font-weight:800;color:#0369a1;margin-bottom:14px;">
                                        Aksi Tugas
                                    </h3>

                                    <?php if ($task['task_status'] === 'waiting') : ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="take">
                                            <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                                            <button type="submit" class="modern-btn" style="width:100%;">
                                                Ambil Tugas
                                            </button>
                                        </form>

                                    <?php elseif ((int) $task['staff_id'] === $user_id) : ?>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="task_id" value="<?= $task['id']; ?>">

                                            <div style="margin-bottom:13px;">
                                                <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Status</label>
                                                <select name="task_status" class="modern-input" required>
                                                    <option value="assigned" <?= $task['task_status'] === 'assigned' ? 'selected' : ''; ?>>Ditugaskan</option>
                                                    <option value="on_process" <?= $task['task_status'] === 'on_process' ? 'selected' : ''; ?>>Diproses</option>
                                                    <option value="completed" <?= $task['task_status'] === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="cancelled" <?= $task['task_status'] === 'cancelled' ? 'selected' : ''; ?>>Batal</option>
                                                </select>
                                            </div>

                                            <div style="margin-bottom:15px;">
                                                <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Catatan</label>
                                                <textarea name="note" class="modern-input" rows="3"><?= htmlspecialchars($task['note'] ?? ''); ?></textarea>
                                            </div>

                                            <button type="submit" class="modern-btn" style="width:100%;">
                                                Simpan
                                            </button>
                                        </form>

                                    <?php else : ?>

                                        <div style="background:#fef3c7;color:#92400e;border-radius:16px;padding:13px;font-weight:800;font-size:13px;">
                                            Tugas ini sudah diambil petugas lain.
                                        </div>

                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="modern-card" style="padding:36px;text-align:center;">
                        <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">Belum Ada Tugas</h2>
                        <p style="color:#64748b;">Tugas pickup dan delivery akan muncul di halaman ini.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    .task-card > div,
    .task-card div[style*="grid-template-columns:repeat(3,1fr)"] {
        grid-template-columns:1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>