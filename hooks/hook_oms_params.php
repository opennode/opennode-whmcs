<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function addParams($vars) {
	global $oms_img, $oms_hostname, $oms_pass, $oms_bundles_group_id, $oms_generated_group_id;

	$oms_iframe_logout_src = $oms_hostname . 'logout';
	$oms_iframe_logout = '<iframe name="oms_iframe" src="' . $oms_iframe_logout_src . '" style="display:none"></iframe>';

	$arr = array("oms_img" => $oms_img, "oms_link" => $oms_hostname, "oms_iframe_logout" => $oms_iframe_logout, "OMS_BUNDLE_ID" => $oms_bundles_group_id, "OMS_GENERATED_ID" => $oms_generated_group_id);

	return $arr;
}



function addIframe($vars) {
	global $oms_img, $oms_hostname, $oms_pass;
	if ($_COOKIE[p]) {
		$pass = $_COOKIE[p];
		setcookie('p', '');
		unset($_COOKIE[p]);

		$userid = $_SESSION[uid];
		$usernameOms = get_username($userid);
		if ($userid && $usernameOms) {
			// If ONC is in same domain, then we can login directly
			//oms_auth($usernameOms, $pass);
			//return '';
			$oms_iframe_src = $oms_hostname . 'basicauth?username=' . $usernameOms . '&password=' . $pass;
			return '<iframe name="oms_iframe" src="' . $oms_iframe_src . '" style="display:none"></iframe>';
		}

	}
}

add_hook("ClientAreaPage", 1, "addParams");
add_hook("ClientAreaHeaderOutput", 1, "addIframe");
?>
