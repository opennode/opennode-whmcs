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
	global $oms_usage_db, $product_core_name, $product_disk_name, $product_memory_name;

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
					$mbsInGb = 1024;
					$data['disk'] = $data['disk'] / $mbsInGb;
					$amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
					$hoursInMonth = 720;
					error_log("Last timestamp for " . $username . ":" . $lastTimestamp . ".");	
					if ($hours > 0) {
						error_log("Time since the last reduction is >1h, proceeding");
						logActivity("Going to remove credit for user:" . $username . ". Amount: " . $amount / $hoursInMonth . " EUR * " . $hours . " hours");
						$isSuccess = removeCreditForUserId($userid, $username, -$amount * $hours / $hoursInMonth, "OMS_USAGE:(".date('H:i:s',strtotime($lastTimestamp)).")[".round($amount, 5)." per month/".(round($amount, 5) * $hours / $hoursInMonth)." for ".$hours." hours] " . $data['cores'] . " cores. " . round($data['disk'], 2) . " GB storage." . $data['memory'] . " GB RAM." . $data['number_of_vms'] . " vms.");
						if ($isSuccess) {
							updateUserCreditReductionRuntime($userid);
							updateClientCreditBalance($userid);
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

	logActivity("Client credit reduction CRON job ended.");
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
