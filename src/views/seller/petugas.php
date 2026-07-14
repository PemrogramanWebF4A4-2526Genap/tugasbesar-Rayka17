<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'petugas'
]);

appRedirect(
    appFirstUrl([
        'src/views/seller/petugas-dashboard.php',
        'src/views/seller/petugas_dashboard.php'
    ])
);