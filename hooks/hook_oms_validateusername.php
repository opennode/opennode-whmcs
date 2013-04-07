<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once(dirname(__FILE__).'/inc/oms_utils.php');

function validate_oms_username($vars) {
	global $errormessage;
	$requested_username = $_POST['customfield'][2];
	print_r($_POST);
	print('Username: '.$requested_username.'<br />');
	if (strlen($requested_username) == 0) {
	    return; 
	}
	if (exists_username($requested_username)) {
	    $errormessage .= "The selected username already exists, please choose a different one";
	}
	return $errormessage;
}

add_hook("ClientDetailsValidation", 0, "validate_oms_username");

?>
