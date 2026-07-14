<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'admin'
]);

appRedirect(
    appUrl(
        'src/views/admin/dashboard.php'
    )
);