<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

require(dirname(__FILE__).'/inc/oms_utils.php');

function create_oms_account($vars) {
    $userid = $vars['userid'];
    $password = $vars['password'];

    $username = get_username($userid);

    $command = '/bin/adduser?arg='.$username.'&arg='.$password;
    oms_command($command);
}

add_hook("ClientAdd", 1, "create_oms_account");

?>
