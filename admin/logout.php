<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['admin_logged_in'], $_SESSION['admin_email']);
header('Location: ' . PLATFORM_URL . '/admin/login.php');
exit;
