<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    // 'cookie_secure' => true, // enable when using HTTPS
    'cookie_samesite' => 'Lax',
]);

// DB credentials — edit to match environment:
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'lead_user');
define('DB_PASS', '1122');
define('DB_NAME', 'lead_system');

// Base URL (folder name of app)
define('BASE_URL', '/lead_system'); 

function db_connect(): mysqli {
    $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($m->connect_errno) {
        die('Database connection failed: ' . $m->connect_error);
    }
    $m->set_charset('utf8mb4');
    return $m;
}
