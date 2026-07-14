<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'admin'
]);

$target = __DIR__
    . '/mitras.php';

if (!is_file($target)) {
    http_response_code(404);

    exit(
        'File admin/mitras.php tidak ditemukan.'
    );
}

require $target;