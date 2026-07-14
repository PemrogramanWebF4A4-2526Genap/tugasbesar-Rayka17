<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$topbarUser = [];

if (
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {
    $topbarUser = $_SESSION['user'];
} elseif (
    isset($_SESSION['auth_user'])
    && is_array($_SESSION['auth_user'])
) {
    $topbarUser = $_SESSION['auth_user'];
}

$topbarRole = strtolower(
    trim(
        (string) (
            $topbarUser['role']
            ?? $_SESSION['role']
            ?? ''
        )
    )
);

$topbarRoleAliases = [
    'mitra' => 'seller',
    'penjual' => 'seller',
    'staff' => 'petugas',
    'kurir' => 'petugas'
];

$topbarRole =
    $topbarRoleAliases[$topbarRole]
    ?? $topbarRole;

$isPetugasTopbar =
    $topbarRole === 'petugas';

$topbarName = trim(
    (string) (
        $topbarUser['name']
        ?? $_SESSION['name']
        ?? $_SESSION['user_name']
        ?? (
            $isPetugasTopbar
                ? 'Petugas'
                : 'Seller'
        )
    )
);

if ($topbarName === '') {
    $topbarName =
        $isPetugasTopbar
            ? 'Petugas'
            : 'Seller';
}

if (
    function_exists(
        'mb_substr'
    )
) {
    $topbarInitial =
        mb_substr(
            $topbarName,
            0,
            1
        );
} else {
    $topbarInitial =
        substr(
            $topbarName,
            0,
            1
        );
}

$topbarInitial = strtoupper(
    $topbarInitial
);

$topbarTitle =
    $isPetugasTopbar
        ? 'Panel Petugas'
        : 'Panel Seller';

$topbarSubtitle =
    $isPetugasTopbar
        ? 'Kelola pesanan dan tugas laundry'
        : 'Kelola layanan, pesanan, dan petugas';

$topbarEscape = static function (
    $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<style>
    body.soft-bg-pattern
    .seller-layout-topbar {
        position: sticky !important;

        z-index: 1300 !important;

        top: 0 !important;

        display: flex !important;

        width: 100% !important;
        min-width: 0 !important;
        min-height:
            var(
                --seller-topbar-height,
                76px
            ) !important;

        align-items: center !important;
        justify-content: space-between !important;

        gap: 18px !important;

        margin: 0 !important;

        padding:
            0
            28px !important;

        border: 0 !important;

        border-bottom:
            1px solid
            #b6e4fa !important;

        background:
            rgba(
                255,
                255,
                255,
                0.98
            ) !important;

        box-shadow:
            0 5px 20px
            rgba(2, 132, 199, 0.06) !important;

        backdrop-filter: blur(14px);

        -webkit-backdrop-filter: blur(14px);
    }

    .seller-topbar-left {
        display: flex;

        min-width: 0;

        align-items: center;

        gap: 13px;
    }

    .seller-topbar-title {
        min-width: 0;
    }

    .seller-topbar-title strong,
    .seller-topbar-title span {
        display: block;
    }

    .seller-topbar-title strong {
        overflow: hidden;

        color: #075985;

        font-size: 20px;
        font-weight: 800;
        line-height: 1.25;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .seller-topbar-title span {
        margin-top: 6px;

        overflow: hidden;

        color: #64748b;

        font-size: 12px;
        line-height: 1.4;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .seller-topbar-right {
        display: flex;

        min-width: 0;

        flex: 0 0 auto;

        align-items: center;

        gap: 13px;
    }

    .seller-topbar-time {
        display: inline-flex;

        min-height: 40px;

        align-items: center;
        justify-content: center;

        padding:
            9px
            14px;

        border:
            1px solid
            #b6e4fa;

        border-radius: 999px;

        background: #e7f7ff;

        color: #075985;

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
    }

    .seller-topbar-avatar {
        display: inline-flex;

        width: 43px;
        height: 43px;

        flex: 0 0 43px;

        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background:
            linear-gradient(
                135deg,
                #0ea5e9,
                #0284c7
            );

        box-shadow:
            0 8px 18px
            rgba(2, 132, 199, 0.18);

        color: #ffffff;

        font-size: 14px;
        font-weight: 800;
    }

    .seller-sidebar-toggle {
        display: none;

        width: 42px;
        height: 42px;

        flex: 0 0 42px;

        cursor: pointer;

        align-items: center;
        justify-content: center;

        padding: 0;

        border:
            1px solid
            #b6e4fa;

        border-radius: 12px;

        background: #ffffff;

        color: #075985;
    }

    .seller-sidebar-toggle svg {
        width: 22px;
        height: 22px;

        fill: none;

        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
    }

    @media screen and (max-width: 1024px) {
        body.soft-bg-pattern
        .seller-layout-topbar {
            z-index: 1600 !important;
            min-height: 68px !important;

            padding:
                0
                13px !important;
        }

        .seller-sidebar-toggle {
            display: inline-flex;
        }

        .seller-topbar-left {
            flex: 1;
        }

        .seller-topbar-title {
            flex: 1;
        }

        .seller-topbar-title strong {
            font-size: 16px;
        }

        .seller-topbar-title span {
            display: none;
        }

        .seller-topbar-time {
            display: none;
        }

        .seller-topbar-avatar {
            width: 40px;
            height: 40px;

            flex-basis: 40px;

            font-size: 13px;
        }
    }
</style>

<header class="seller-layout-topbar">

    <div class="seller-topbar-left">

        <button
            type="button"
            class="seller-sidebar-toggle"
            id="sellerSidebarToggle"
            aria-label="Buka menu"
            aria-expanded="false"
            onclick="window.LaundrySellerSidebar && window.LaundrySellerSidebar.toggle()"
        >
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
            </svg>
        </button>

        <div class="seller-topbar-title">
            <strong>
                <?= $topbarEscape(
                    $topbarTitle
                ); ?>
            </strong>

            <span>
                <?= $topbarEscape(
                    $topbarSubtitle
                ); ?>
            </span>
        </div>

    </div>

    <div class="seller-topbar-right">

        <span
            class="seller-topbar-time"
            id="sellerCurrentTime"
        >
            Memuat waktu...
        </span>

        <span class="seller-topbar-avatar">
            <?= $topbarEscape(
                $topbarInitial
            ); ?>
        </span>

    </div>

</header>

<script>
(function () {
    const currentTimeElement =
        document.getElementById(
            'sellerCurrentTime'
        );

    function updateSellerTime() {
        if (!currentTimeElement) {
            return;
        }

        currentTimeElement.textContent =
            new Intl.DateTimeFormat(
                'id-ID',
                {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            ).format(new Date());
    }

    updateSellerTime();

    window.setInterval(
        updateSellerTime,
        30000
    );
})();
</script>