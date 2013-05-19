<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function create_new_vm_with_invoice($vars) {
	global $oms_usage_db;
	$invoiceId = $vars['invoiceid'];
	logActivity("Starting to POST new vm data and adding credit for invoice:".$invoiceId);

	$invoice = getInvoiceById($invoiceId);

	$userId = $invoice['userid'];
	$username = get_username($userId);
	if (!$username) {
		logActivity("No username found for id:$userId");
		return;
	}

	$items = $invoice['items'];
	foreach ($items as $item) {
		$vmData = array();
		$itemId = $item[0]['relid'];
		$clientproduct = getClientsProduct($userId, $itemId);

		//On invoice payment it is needed that credit stays to account. It is automattically removed, manually added:
		$amount = $clientproduct['firstpaymentamount'];
		//unused field 'recurringamount'
		$desc = "Adding credit for invoice:" . $invoiceId;
		addCreditForUserId($userId, $username, $amount, $desc);

		//get template
		if ($clientproduct['configoptions']) {
			foreach ($clientproduct['configoptions'] as $configoption) {
				$confopt = $configoption[0];
				if ($confopt['option'] == "Template")
					$vmData['template'] = $confopt['value'];
			}
		}
		if (!$vmData['template']) {
			logActivity("Error. No template found for product id:" . $itemId);
			return;
		}
		//get data from client product
		$vmData['hostname'] = $clientproduct['domain'];
		$vmData['root_password'] = $clientproduct['password'];
		$vmData['root_password_repeat'] = $clientproduct['password'];

		//$vmData[swap_size]=0.5;
		if ($clientproduct) {
			//get item
			$products = getBundlesProductsByOtherProductId($clientproduct['pid']);
			if ($products) {
				foreach ($products as $product) {
					if (stristr($product['name'], "core"))
						$vmData['num_cores'] = $product['count'];
					if (stristr($product['name'], "ram"))
						$vmData['memory'] = $product['count'];
					if (stristr($product['name'], "storage"))
						$vmData['diskspace'] = $product['count'];

				}
				logActivity("VM settings. cores: " . $vmData['num_cores'] . ". memory:" . $vmData['memory'] . ". disk: " . $vmData['diskspace']);
				if ($vmData['num_cores'] > 0 && $vmData['memory'] > 0 && $vmData['diskspace'] > 0) {
					logActivity("Running oms command to create VM.");

					$command = '/machines/hangar/vms-openvz';
					$result = oms_command($command, json_encode($vmData));
					$data = json_decode($result);
					if ($data -> result -> id) {
						logActivity("Running command Chown for username:" . $username . " and computeId:" . $data -> result -> id);
						$command = '/bin/chown?arg=' . $username . '&arg=/computes/by-name/' . $data -> result -> hostname . '&asynchronous';
						oms_command($command);
					} else {
						logActivity("Error running command Chown for username:" . $username . ". No computeId");
					}
				} else {
					logActivity("Error: VM settings not set.");
				}
			}
		}
	}

	logActivity("POST-ing new vm data ended.");
}

function getInvoiceById($invoiceId) {
	$command = "getinvoice";
	$adminuser = "admin";
	$values["invoiceid"] = $invoiceId;

	$invoice = localAPI($command, $values, $adminuser);
	return $invoice;
}

function getClientsProduct($clientId, $serviceId) {
	$command = "getclientsproducts";
	$adminuser = "admin";
	$values["clientid"] = $clientId;
	$values["serviceid"] = $serviceId;

	$results = localAPI($command, $values, $adminuser);
	if ($results['result'] == "success") {
		if ($results['products']['product']) {
			return $results['products']['product'][0];
		} else {
			logActivity("No product found. clientId:" . $clientId . ". serviceId:" . $serviceId);
		}

	} else if ($results['result'] == "error") {
		logActivity("Error getting clients products. clientId:" . $clientId . ". serviceId:" . $serviceId . ". Error:" . $clientData['message']);
	}
	return null;
}

function getBundlesProductsByOtherProductId($productId) {
	$sql = "SELECT bundle.id, bundle.name, bundle.itemdata FROM tblbundles bundle INNER JOIN  tblproducts product ON product.name = bundle.name AND product.id=" . $productId;
	$query = mysql_query($sql);
	$bundle = mysql_fetch_array($query);
	if ($bundle) {
		$itemdata = $bundle['itemdata'];
		//find product ids from string
		$ptn = "*\"pid\";[a-z]:[0-9]+:\"[0-9]+\"*";

		preg_match_all($ptn, $itemdata, $matches);

		foreach ($matches[0] as $match) {
			$ptnNr = "/[0-9]+$/";
			$str = str_replace("\"", "", $match);
			preg_match($ptnNr, $str, $matchNr);
			if ($matchNr)
				$productIds[$matchNr[0]]++;
			else
				logActivity("Error parsing itemdata to get product id.");
		}
		//find product names
		$products = array();
		$sum = 0;
		foreach ($productIds as $id => $count) {
			//print_r("Product with id:" . $id . ", count:" . $count);
			//Query for products
			$sql = "SELECT DISTINCT * FROM tblproducts product WHERE product.id = '" . $id . "'";
			$query = mysql_query($sql);
			$product = mysql_fetch_array($query);

			if ($product) {
				$product['count'] = $count;
				$products[] = $product;
			} else {
				logActivity("Error getting product with id:" . $id);
			}
		}
		return $products;
	} else {
		logActivity("Error getting bundle with id:" . $productId);
	}
	return null;
}

/**
 * Function to add credit to user.
 */
function addCreditForUserId($userId, $username, $amount, $desc) {
	if ($amount < 0) {
		logActivity("Error. Tried to REMOVE credit to userId:" . $userId);
		return;
	}

	$command = "addcredit";
	$adminuser = "admin";
	$values["clientid"] = $userId;
	$values["description"] = $desc;
	$values["amount"] = $amount;

	$clientData = localAPI($command, $values, $adminuser);

	if ($clientData['result'] == "success") {
		logActivity("Successfully added amount of " . $amount . " credit for userId:" . $userId . "(" . $username . ")");
		return true;
	} else if ($clientData['result'] == "error") {
		logActivity("Error adding credit for userId:" . $userId . ". Error:" . $clientData['message']);
		return false;
	}

	return false;
}

add_hook("InvoicePaid", 1, "create_new_vm_with_invoice");
?>
