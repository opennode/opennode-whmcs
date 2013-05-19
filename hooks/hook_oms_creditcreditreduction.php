<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function reduce_users_credit() {
	global $oms_usage_db, $product_core_name, $product_disk_name, $product_memory_name;

	logActivity("Starting clients credit reduction job.");

	//Get products prices
	$p_core = getProductPriceByName($product_core_name);
	$p_disk = getProductPriceByName($product_disk_name);
	$p_memory = getProductPriceByName($product_memory_name);
	logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);

	if (!$p_core || !$p_disk || !$p_memory) {
		logActivity("Error: Product prices not set.");
		return;
	}

	$table = $oms_usage_db . ".CONF_CHANGES";

	$sql = "select conf.id, conf.username, conf.timestamp, conf.cores, conf.disk, conf.memory, conf.number_of_vms
				from " . $table . " as conf
					inner join
				(select username, max(timestamp) as ts
					from " . $table . " where timestamp <= DATE_SUB(now(), INTERVAL 1 HOUR)
				group by username) maxconf
					on (conf.username = maxconf.username and conf.timestamp = maxconf.ts)
					GROUP BY conf.username";
	// GROUP BY is here in case there is two rows with same timestamp for username(e.g testdata)

	$result = mysql_query($sql);

	if ($result) {
		while ($data = mysql_fetch_array($result)) {
			$id = $data['id'];
			$username = $data['username'];
			$userid = get_userid($username);
			if ($userid) {
				$lastTimestamp = getUserCreditLastReductionRuntime($userid, $username);
				if ($lastTimestamp) {
					$hours = floor((time() - strtotime($lastTimestamp)) / 3600);
					$amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
					logActivity("Going to remove credit for user:" . $username . ". Amount: " . $amount . " EUR * " . $hours . " hours");
					if ($hours > 0) {
						$isSuccess = removeCreditForUserId($userid, $username, -$amount * $hours, $data['cores'] . " cores. " . $data['disk'] . " GB storage." . $data['memory'] . " GB RAM." . $data['number_of_vms'] . " vms.");
						if ($isSuccess) {
							updateUserCreditReductionRuntime($userid);
						} else {
							logActivity("Error: Credit reduction error for user:" . $username . ".");
						}
					}
				} else {
					error_log("No lastTimestamp found for user:" . $username . ".");
				}
			} else {
				error_log("Userid not found for username " . $username);
			}
		}
	}

	logActivity("Client credit reduction job ended.");
}

function removeCreditForUserId($userId, $username, $amount, $desc) {
	if ($amount > 0) {
		logActivity("Error. Tried to ADD credit to userId:" . $userId);
		return;
	}

	$command = "addcredit";
	$adminuser = "admin";
	$values["clientid"] = $userId;
	$values["description"] = $desc;
	$values["amount"] = $amount;

	$clientData = localAPI($command, $values, $adminuser);

	if ($clientData['result'] == "success") {
		logActivity("Successfully removed amount of " . $amount . " credit from userId:" . $userId . "(" . $username . ")");
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

function getUserCreditLastReductionRuntime($userId, $username) {

	global $oms_usage_db;
	$table = $oms_usage_db . ".CREDIT_REDUCTION";
	$sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE userid=" . $userId;
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);
	if ($result[timestamp]) {
		return $result[timestamp];
	} else {
		// If script is run for first time for user, then timestamp must come from conf_changes table
		$table = $oms_usage_db . ".CONF_CHANGES";
		$sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE username='" . $username . "'";
		$query = mysql_query($sql);
		$result = mysql_fetch_array($query);
		if ($result) {
			return $result[timestamp];
		} else {
			error_log("No result from CREDIT_REDUCTION or CONF_CHANGES for userid: " . $userId);
		}
	}
	return null;
}

function updateUserCreditReductionRuntime($userId) {
	global $oms_usage_db;
	$table = $oms_usage_db . ".CREDIT_REDUCTION";
	$sql = "INSERT INTO " . $table . " (userid, timestamp) VALUES (" . $userId . ", CURRENT_TIMESTAMP)";
	$retval = mysql_query($sql);
	if (!$retval) {
		error_log("Credit reduction run time update error for userid: " . $userId);
	}
	return $retval;
}

?>
