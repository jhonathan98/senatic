<?php
// Configuration settings for BookData application
define('DB_HOST', 'localhost');
define('DB_NAME', 'bookdata');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'BookData');
define('SITE_URL', 'http://localhost/senatic/senatic/gonzalomejia/BookData');

// Session configuration
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone setting
date_default_timezone_set('America/Bogota');
?>