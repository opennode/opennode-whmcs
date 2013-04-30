<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function create_new_vm($invoiceid) {
	global $oms_usage_db;
	if ($_SESSION[uid] != 13)//For testing if not developer
		return;

	logActivity("Starting to POST new vm data");

	//get invoice
	$command = "getinvoice";
	$adminuser = "admin";
	$values["invoiceid"] = $invoiceid;

	$invoice = localAPI($command, $values, $adminuser);

	//get order

	//get userid
	$userid = $invoice['whmcsapi']['userid'];
	$username = get_username($userid);

	$items = $invoice['whmcsapi']['userid'];
	foreach ($items as $item) {
		$itemId = $item['id'];
		//relid?
		//get item
		$command = "getproducts";
		$adminuser = "admin";
		$values["pid"] = $itemId;

		$product = localAPI($command, $values, $adminuser);
		//get product name
		//get bundle=product name
		//get bundles products (cores,memory,disk)
	}

	//send data to oms

	$command = '/bin/passwd?arg=-u&arg=' . $username . '&arg=' . $password;
	oms_command($command);

	logActivity("POST-ing new vm data ended.");
}

// add_hook("InvoicePaid", 1, "create_new_vm");
?>
