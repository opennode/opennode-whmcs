<?php

include_once ("configuration.php");
include_once ('includes/hooks/inc/oms_utils.php');

try {
	// Check MySQL Configuration
	$db_conn = mysql_connect($db_host, $db_username, $db_password);
	if (empty($db_conn))
		throw new Exception('Unable to connect to DB');

	$db_select = @mysql_select_db($db_name, $db_conn);
	if (empty($db_select))
		throw new Exception('Unable to select WHMCS database');

} catch (Exception $e) {
	echo 'Error: ', $e -> getMessage(), "\n";
}

function logActivity($msg) {
	$postfields["action"] = "logactivity";
	$postfields["description"] = $msg;
	callApi($postfields);
}

function reduce_users_credit() {
	global $product_core_name, $product_disk_name, $product_memory_name;

	logActivity("Starting clients credit reduction CRON job.");

	//Get products prices
	$p_core = getProductPriceByName($product_core_name);
	$p_disk = getProductPriceByName($product_disk_name);
	$p_memory = getProductPriceByName($product_memory_name);

	if (!$p_core || !$p_disk || !$p_memory) {
		logActivity("Error: Product prices not set.");
		return;
	} else {
		logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);
	}

	$result = queryForConfChanges();
	if ($result) {
		$usersAmountsToRemove = array();
		$recordIdsToUpdate = array();

		$mbsInGb = 1024;
		$hoursInMonth = 720;
		$prevRecord = null;
		foreach ($result as $currRecord) {
			if ($prevRecord) {
				if ($prevRecord['username'] == $currRecord['username']) {
					$username = $prevRecord['username'];
					$hoursInBetween = (strtotime($currRecord['timestamp']) - strtotime($prevRecord['timestamp'])) / 3600;

					$prevRecord['disk'] = $prevRecord['disk'] / $mbsInGb;
					$amount = $prevRecord['cores'] * $p_core + $prevRecord['disk'] * $p_disk + $prevRecord['memory'] * $p_memory;

					$addAmountToUser = $amount * $hoursInBetween / $hoursInMonth;
					if (!isset($usersAmountsToRemove[$username]))
						$usersAmountsToRemove[$username] = 0;

					$usersAmountsToRemove[$username] += $addAmountToUser;
					$recordIdsToUpdate[] = $prevRecord['id'];
					//error_log("Adding " . $addAmountToUser . " EUR to user :" . $username . " for " . $hoursInBetween . " hours.");
				} else {
					error_log("Switching users:" . $prevRecord['username'] . "->" . $currRecord['username']);
				}
			}

			$prevRecord = $currRecord;
		}
		applyCreditRemovingFromUsersAmounts($usersAmountsToRemove);
		updateRecordIds($recordIdsToUpdate);
	}

	logActivity("Client credit reduction CRON job ended.");
}
/**
 * Query for conf changes
 * Return array
 */
function queryForConfChanges() {
	global $oms_usage_db;

	$table = $oms_usage_db . ".CONF_CHANGES";

	$sql = "select conf.id, conf.username, conf.timestamp, conf.cores, conf.disk, conf.memory, conf.number_of_vms from 
			" . $table . " as conf 
			where conf.processed = false 
			AND conf.cores > 0 
			AND conf.disk > 0 
			AND conf.memory > 0 
			AND conf.number_of_vms > 0 
			AND timestamp <= DATE_SUB(now(), INTERVAL 1 HOUR) 
			ORDER BY conf.username, conf.timestamp";

	$result = mysql_query($sql);

	$resultsAsArray = array();
	while ($row = mysql_fetch_assoc($result)) {

		$resultsAsArray[] = $row;
	}
	return $resultsAsArray;
}

function updateRecordIds($recordIdsToUpdate) {
	global $oms_usage_db;
	$table = $oms_usage_db . ".CONF_CHANGES";
	if (count($recordIdsToUpdate) > 0) {
		$sql = "UPDATE " . $table . " SET processed=true WHERE id IN(" . implode(',', $recordIdsToUpdate) . ')';
		$result = mysql_query($sql);
		if ($result) {
			error_log("Successfully updated " . $table . " with ids:" . implode(',', $recordIdsToUpdate));
		} else {
			error_log("Error updating " . $table);
		}
	}
}

function applyCreditRemovingFromUsersAmounts($usersAmountsToRemove) {
	foreach ($usersAmountsToRemove as $username => $amountToRemove) {
		$userid = get_userid($username);
		if ($userid) {
			error_log("Username:" . $username . " To remove:" . $amountToRemove);
			logActivity("Going to remove credit for user:" . $username . ". Amount: " . $amountToRemove . " EUR ");
			$isSuccess = removeCreditForUserId($userid, $username, -$amountToRemove, "OMS_USAGE:(" . date('H:i:s', time()) . ")[removed:" . round($amountToRemove, 5) . " EUR] ");
			if ($isSuccess) {
				updateUserCreditReductionRuntime($userid);
				updateClientCreditBalance($userid);
			} else {
				logActivity("Error: Credit reduction error for user:" . $username . ".");
			}
		} else {
			error_log("Userid not found for username " . $username);
		}
	}
}

function removeCreditForUserId($userId, $username, $amount, $desc) {
	if ($amount > 0) {
		logActivity("Error. Tried to ADD credit to userId:" . $userId);
		return;
	}

	$postfields["action"] = "addcredit";
	$postfields["clientid"] = $userId;
	$postfields["description"] = $desc;
	$postfields["amount"] = $amount;

	$clientData = callAPI($postfields);

	if ($clientData['result'] == "success") {
		logActivity("Successfully removed amount of " . $amount . " credit from userId:" . $userId . "(" . $username . ")");
		return true;
	} else if ($clientData['result'] == "error") {
		logActivity("Error removing credit from userId:" . $userId . ". Error:" . $clientData['message']);
		return false;
	}

	return false;
}

function getUserCreditLastReductionRuntime($userId, $username) {

	global $oms_usage_db;
	$table = $oms_usage_db . ".CREDIT_REDUCTION";
	$sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE userid=" . $userId;
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);
	if ($result['timestamp']) {
		error_log("== Last timestamp for " . $username . ": " . $result['timestamp']);
		return $result['timestamp'];
	} else {
		// If script is run for first time for user, then timestamp must come from conf_changes table
		$table = $oms_usage_db . ".CONF_CHANGES";
		$sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE username='" . $username . "'";
		$query = mysql_query($sql);
		$result = mysql_fetch_array($query);
		if ($result) {
			return $result['timestamp'];
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

reduce_users_credit();
?>
