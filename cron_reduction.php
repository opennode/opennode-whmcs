<?php
include_once ("configuration.php");
include_once ("OmsReduction.php");

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

reduce_users_credit();
?>