<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
session_destroy();
echo "<script>window.location.href='index.php';</script>";
?>
