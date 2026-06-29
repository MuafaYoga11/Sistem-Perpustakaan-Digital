<?php
// logout.php
session_start();
require_once '../../config/database.php';
session_destroy();
header('Location: ' . BASE_URL . '/pages/auth/login.php');
exit;
