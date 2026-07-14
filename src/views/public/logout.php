<?php

require_once __DIR__
    . '/../../config/auth.php';

laundry_destroy_session();

laundry_redirect(
    laundry_url(
        'src/views/public/login.php?logout=1'
    )
);