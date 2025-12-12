<?php
require_once __DIR__ . '/config.php';

function ensure_db_ready() {
    global $conn;

    $res = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$res || $res->num_rows === 0) {
        header("Location: install.php");
        exit;
    }
}
