<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_config.php');

function addParams($vars) {
	global $oms_img,$oms_hostname;
	return array("oms_img" => $oms_img, "oms_link"=>$oms_hostname);
}

add_hook("ClientAreaPage", 1, "addParams");
?>
