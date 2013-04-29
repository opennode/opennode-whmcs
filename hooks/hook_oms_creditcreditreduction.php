<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function reduce_users_credit() {

	logActivity("Starting clients credit reduction job.");

	//read timestamp, write new timestamp
	// Or write at end row as processed=true ?

	//Get products prices
	$p_core = getProductPriceByName("1 Core");
	$p_disk = getProductPriceByName("GB Storage");
	$p_memory = getProductPriceByName("1GB RAM");

	$table = "CONF_CHANGES";
	$fields = "*";
	$where = array("processed" => false);
	$sort = "username";
	$sortorder = "ASC";
	$result = select_query($table, $fields, $where, $sort, $sortorder);

	if ($result) {
		$productIds = array();
		while ($data = mysql_fetch_array($result)) {
			$username = $data['username'];
			$userid = get_userid($username);
			$amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
			removeCreditForUserId($userid, $amount, $data['cores'] . " cores. " . $data['disk'] . " GB storage." . $data['memory'] . " GB RAM." . $data['number_of_vms'] . " vms.");
		}
	}

	logActivity("Client credit reduction job ended.");
}

function removeCreditForUserId($userId, $amount, $desc) {
	if ($amount > 0) {
		logActivity("Error. Tried to add credit to userId:" . $userId);
		return;
	}

	$command = "addcredit";
	$adminuser = "admin";
	$values["clientid"] = $userId;
	$values["description"] = $desc;
	$values["amount"] = $amount;

	$clientData = localAPI($command, $values, $adminuser);

	if ($clientData['result'] == "success") {
		logActivity("Successfully removed amount of " . $amount . " credit from userId:" . $userId);
		//print_r($userId . " newbalance:" . $clientData['newbalance']);
	} else if ($clientData['result'] == "error") {
		logActivity("Error removing credit from userId:" . $userId . ". Error:" . $clientData['message']);
	}

	return $clientCredit;
}

function getProductPriceByName($name) {
	$sql = "SELECT DISTINCT * FROM tblproducts product JOIN tblpricing price ON product.id = price.relid WHERE price.type='product' AND product.name = '" . $name . "'";
	$query = mysql_query($sql);
	$product = mysql_fetch_array($query);
	if ($product) {
		$sum = $product['monthly'];
		return $sum;
	} else {
		logActivity("Error getting product");
	}
}

add_hook("DailyCronJob", 1, "reduce_users_credit");
?>
