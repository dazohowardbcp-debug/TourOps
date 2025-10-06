<?php 
require_once '../inc/config.php';
session_start(); 
session_destroy(); 
redirect('../login.php'); 
?>
<?php session_start(); session_destroy(); header('Location: login.php'); ?>