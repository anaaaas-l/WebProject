<?php
require_once __DIR__ . '/../config.php';

session_destroy();
header('Location: /share/admin/login.php');
exit;
