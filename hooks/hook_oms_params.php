<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function addParams($vars) {
	global $oms_img, $oms_hostname, $oms_pass, $oms_bundles_group_id, $oms_generated_group_id;

	$oms_iframe_logout_src = $oms_hostname . 'logout';
	$oms_iframe_logout = '<iframe name="oms_iframe" src="' . $oms_iframe_logout_src . '" style="display:none"></iframe>';

	$arr = array("oms_img" => $oms_img, "oms_link" => $oms_hostname, "oms_iframe_logout" => $oms_iframe_logout, "OMS_BUNDLE_ID" => $oms_bundles_group_id, "OMS_GENERATED_ID" => $oms_generated_group_id);

	$userId = $_SESSION['uid'];
	if ($userId) {
		$omsArr = getOmsUsageForUserId($userId);
		$arr['omsdata'] = $omsArr;
	}
	return $arr;
}

/**
 * Function that gets OMC VM data to display on template
 */
function getOmsUsageForUserId($userId) {
	global $oms_usage_db, $product_core_name, $product_disk_name, $product_memory_name;;

	//Get products prices
	$p_core = getProductPriceByName($product_core_name);
	$p_disk = getProductPriceByName($product_disk_name);
	$p_memory = getProductPriceByName($product_memory_name);
	//logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);

	if (!$p_core || !$p_disk || !$p_memory) {
		logActivity("Error: Product prices not set.");
		return;
	}

	$username = get_username($userId);

	$table = $oms_usage_db . ".CONF_CHANGES";

	$sql = "select * from " . $table . " WHERE username='" . $username . "' ORDER BY timestamp DESC LIMIT 1";

	$result = mysql_query($sql);

	if ($result) {
		$data = mysql_fetch_array($result);
		if ($data) {
			$id = $data['id'];
			$amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
			$data['vm_cost'] = $amount;
		}
	}
	$data['currentcredit'] = getCreditForUserId($userId);
	return $data;
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
			$oms_iframe_src = $oms_hostname . 'basicauth?username=' . $usernameOms . '&password=' . $pass;
			return '<iframe name="oms_iframe" src="' . $oms_iframe_src . '" style="display:none"></iframe>';
		}

	}
=======
	global $oms_img,$oms_hostname, $oms_bundles_group_id, $oms_generated_group_id;
	return array("oms_img" => $oms_img, "oms_link"=>$oms_hostname, "OMS_BUNDLE_ID" => $oms_bundles_group_id, "OMS_GENERATED_ID" => $oms_generated_group_id);
>>>>>>> 162df1b... Changes to displaying data(OMS-377)
}

add_hook("ClientAreaPage", 1, "addParams");
add_hook("ClientAreaHeaderOutput", 1, "addIframe");
?>
