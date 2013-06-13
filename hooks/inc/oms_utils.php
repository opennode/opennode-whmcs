<?php

require (dirname(__FILE__) . '/oms_config.php');

/*
 Extract username from the custom field with a specified name ('Username').
 */
function get_username($userid) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// get username value
	$result = mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and relid = " . $userid);
	$usernamefield = mysql_fetch_row($result);
	$username = $usernamefield[0];
	return $username;
}

function get_userid($username) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// get username value
	$result = mysql_query("SELECT relid FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and value = '" . $username . "'");
	$useridfield = mysql_fetch_row($result);
	$userid = $useridfield[0];
	return $userid;
}

/*
 Extract users Balance limit from the custom field with a specified name ('Balance limit').
 */
function get_balance_limit($userid) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Balance limit'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// get Balance limit  value
	$result = mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and relid = " . $userid);
	$bfield = mysql_fetch_row($result);
	$blimit = $bfield[0];
	return $blimit;
}

/*
 Check if a specified username already exists.
 */
function exists_username($username) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// check if we have at least one username
	// XXX potential sql injection place
	$query = "SELECT count(*) FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and value = \"" . mysql_real_escape_string($username) . "\"";
	$result = mysql_query($query);
	$r = mysql_fetch_row($result);
	return $r[0] > 0;
}

/*
 Execute command against OMS server.
 */
function oms_command($command_path, $data, $req_type) {
	global $oms_hostname, $oms_user, $oms_password;
	// construct full url
	$curl = curl_init($oms_hostname . $command_path);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	// user PUT for triggering command in OMS
	if ($req_type)
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $req_type);
	else
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');

	// add basic auth
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $oms_user . ":" . $oms_password);

	curl_setopt($curl, CURLOPT_POST, 1);
	if ($data) {
		//redefine, if data then POST
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
	}

	$res = curl_exec($curl);

	if (!$res) {
		error_log('Error during OMS call: ' . curl_error($curl));
		curl_close($curl);
		return -1;
	}
	curl_close($curl);
	return $res;
}

/**
 * Function to call external API
 */
function callApi($postfields) {
	global $whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url;
	$postfields["username"] = $whmcs_admin_user;
	$postfields["password"] = md5($whmcs_admin_password);
	$ch = curl_init();
	$url = $whmcs_api_url;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$data = curl_exec($ch);
	curl_close($ch);
	$data = explode(";", $data);
	if ($data) {
		foreach ($data AS $temp) {
			if (strpos($temp, "<?xml") === 0)
				return json_decode(json_encode((array) simplexml_load_string($temp)), 1);
			$temp = explode("=", $temp);
			if (isset($temp[1])) {
				$results[$temp[0]] = $temp[1];
			}
		}

		if ($results["result"] == "success") {
			# Result was OK!
		} else {
			# An error occured
			echo "The following error occured: " . $results["message"];
		}
		return $results;
	}
	return null;
}

/**
 * Updates client credit with external api, internal did not work.
 */
function updateClientCreditBalance($userId) {
	logActivity("Updating client $userId");
	$clientCredit = 0;
	$postfields["action"] = "getcredits";
	$postfields["clientid"] = $userId;
	$clientData = callAPI($postfields);

	if ($clientData['result'] == "success") {
		foreach ($clientData['credits'] as $creditArr) {
			//Make one dimentsional two dimentsional
			if (!is_array($creditArr[0]))
				$creditArr = Array($creditArr);
			foreach ($creditArr as $credit) {
				$clientCredit += $credit['amount'];
			}
		}
		logActivity("Updating client $userId with credit: $clientCredit");
		$postfields["action"] = "updateclient";
		$postfields["clientid"] = $userId;
		$postfields["credit"] = $clientCredit;
		$results = callAPI($postfields);
		if ($clientData['result'] == "success") {
			logActivity("Successfully updated client credit.");
		} else {
			logActivity("Error updating client credit.");
		}

	}
}

/**
 * Get product item price, if item is 10GB then return 1GB price
 */
function getProductPriceByName($name) {
	$sql = "SELECT DISTINCT * FROM tblproducts product JOIN tblpricing price ON product.id = price.relid WHERE price.type='product' AND product.name = '" . $name . "'";
	$query = mysql_query($sql);
	$product = mysql_fetch_array($query);
	if ($product) {
		preg_match('/^\d*/', $product['name'], $matches);
		$amount = ($matches[0]) ? $matches[0] : 1;
		$sum = $product['monthly'] / $amount;
		// Calculate one unit price. Can be 10GB disk, need price of 1GB.
		return $sum;
	} else {
		logActivity("Error getting product");
	}
}

?>
