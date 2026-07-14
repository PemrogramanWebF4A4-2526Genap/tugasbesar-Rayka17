<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'seller'
]);

appRedirect(
    appUrl(
        'src/views/seller/dashboard.php'
    )
);