<?php
require 'register.php';
require 'login_handle.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

include 'login_handle.php';
// Separate form file for login and signup
?>
