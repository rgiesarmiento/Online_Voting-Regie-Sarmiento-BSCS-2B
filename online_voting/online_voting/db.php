<?php
// db.php - central DB connection used by api.php
// Update these values if your MySQL credentials differ (XAMPP default: root / empty password)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'online_voting';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    // For public pages we do not echo DB errors. api.php will include this file and handle errors.
    error_log("DB connection failed: " . $mysqli->connect_error);
    exit;
}
$mysqli->set_charset('utf8mb4');
?>