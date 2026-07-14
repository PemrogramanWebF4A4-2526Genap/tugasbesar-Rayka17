<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'customer_service',
    'admin'
]);

$dashboardFile =
    __DIR__
    . '/dashboard.php';

if (!is_file($dashboardFile)) {
    http_response_code(404);

    exit(
        'Dashboard Customer Service tidak ditemukan.'
    );
}

require $dashboardFile;