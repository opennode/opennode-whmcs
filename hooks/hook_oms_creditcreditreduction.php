<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function reduce_users_credit() {
	global $oms_usage_db;
	if ($_SESSION[uid] != 13)
		return;

	logActivity("Starting clients credit reduction job.");

	//Get products prices
	$p_core = getProductPriceByName("1 Core");
	$p_disk = getProductPriceByName("1GB Storage");
	$p_memory = getProductPriceByName("1GB RAM");
	logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);

	if (!$p_core || !$p_disk || !$p_memory) {
		logActivity("Error: Product prices not set.");
		return;
	}

	$table = $oms_usage_db . ".CONF_CHANGES";

	$fields = "*";
	$where = array("processed" => false);
	$sort = "id";
	$sortorder = "ASC";
	$result = select_query($table, $fields, $where, $sort, $sortorder);

	if ($result) {

		while ($data = mysql_fetch_array($result)) {
			$id = $data['id'];
			$username = $data['username'];
			$userid = get_userid($username);
			if ($userid) {
				$amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
				$isSuccess = removeCreditForUserId($userid, -$amount, $data['cores'] . " cores. " . $data['disk'] . " GB storage." . $data['memory'] . " GB RAM." . $data['number_of_vms'] . " vms.");
				if ($isSuccess) {
					logActivity("Marking row with id " . $id . " as processed.");
					$u_update = array("processed" => true);
					$u_where = array("id" => $id);
					update_query($table, $u_update, $u_where);
				}
			} else {
				logActivity("Userid not found for username " . $username);
			}
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
		return true;
	} else if ($clientData['result'] == "error") {
		logActivity("Error removing credit from userId:" . $userId . ". Error:" . $clientData['message']);
		return false;
	}

	return false;
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

add_hook("DailyCronJob", 0, "reduce_users_credit");
?>
