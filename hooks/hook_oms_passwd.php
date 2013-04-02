<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

require(dirname(__FILE__).'/inc/oms_utils.php');

function modify_oms_passwd($vars) {
    $userid = $vars['userid'];
    $password = $vars['password'];

    $username = get_username($userid);
    
    $command = '/bin/passwd?arg=-u&arg='.$username.'&arg='.$password;
    oms_command($command);
}

add_hook("ClientChangePassword", 1, "modify_oms_passwd");

?>
