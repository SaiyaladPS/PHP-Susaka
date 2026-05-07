<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /src/account/index.php');
    exit;
}

$page_title = 'Order';
$active_nav = 'order';
require_once __DIR__ . '/../../includes/layout/header.php';
?>
<h1>order</h1>
