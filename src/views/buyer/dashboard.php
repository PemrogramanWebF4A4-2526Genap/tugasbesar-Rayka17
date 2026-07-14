<?php

require_once __DIR__
    . '/../../config/route-helper.php';

appRequireRole([
    'buyer'
]);

appRedirect(
    appUrl(
        'src/views/public/home.php'
    )
);