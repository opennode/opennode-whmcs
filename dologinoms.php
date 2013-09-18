<?php
include_once (dirname(__FILE__) . '/includes/hooks/inc/oms_utils.php');
global $oms_hostname, $oms_pass;

$token = $_POST['token'];
$username = $_POST['username'];
$password = $_POST['password'];
$_COOKIE['p'] = $password;

setcookie("p", $password);
header('Location: dologin.php?token=' . $token . '&username=' . $username . '&password=' . $password);
?>
