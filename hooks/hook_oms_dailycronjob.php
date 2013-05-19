<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function stop_users_vms() {
	logActivity("Starting to stop vms for users.");
	//Find all users whos credit is low
	$table = "tblclients";
	$fields = "*";
	$result = select_query($table, $fields);

	if ($result) {
		while ($data = mysql_fetch_array($result)) {
			$userid = $data['id'];
			$username = get_username($userid);
			if (getCreditForUserId($userid) < 0) {
				logActivity("Stopping vms for user: " . $username);

				$command = '/bin/stopvms?arg=-u&arg=' . $username;
				$res = oms_command($command);

				if ($res != -1)
					logActivity("Stopped vms for user: " . $username . ". Result:" . $res);
				else
					logActivity("Stoping vms Failed for user: " . $username);
			}
		}
	}
	logActivity("Stopping vms for users ended.");
}

function getCreditForUserId($userId) {
	$clientCredit = 0;

	$command = "getcredits";
	$adminuser = "admin";
	$values["clientid"] = $userId;

	$clientData = localAPI($command, $values, $adminuser);

	if ($clientData['result'] == "success") {
		foreach ($clientData['credits'] as $creditArr) {
			foreach ($creditArr as $credit) {
				$clientCredit += $credit['amount'];
			}
		}
	}
	
	return $clientCredit;
}

add_hook("DailyCronJob", 1, "stop_users_vms");
?>
