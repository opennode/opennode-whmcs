<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function create_new_vm_with_invoice($vars) {
	global $oms_usage_db, $oms_templates_mapping, $vm_default_nameservers;
	$invoiceId = $vars['invoiceid'];

	logActivity("Starting to POST new vm data and adding credit for invoice:" . $invoiceId);

	$invoice = getInvoiceById($invoiceId);

	$userId = $invoice['userid'];
	$username = get_username($userId);
	if (!$username) {
		logActivity("No username found for id:$userId");
		return;
	}
	//On invoice payment it is needed that credit stays to account. It is automattically removed, manually added:
	$amountPaid = $invoice['total'];
	
	$boughtVmProduct = false;// To add credit only when client buys vm product, not adds funds.
	$items = $invoice['items'];
	foreach ($items as $item) {
		$vmData = array();
		$itemId = $item[0]['relid'];
		$clientproduct = getClientsProduct($userId, $itemId);

		//get template
		if ($clientproduct['configoptions']) {
			foreach ($clientproduct['configoptions'] as $configoption) {
				$confopt = $configoption[0];
				if ($confopt['option'] == "Template") {
					$boughtVmProduct = true;
					//Use value(OMS template name) or find it from mapping variableF
					if (is_array($oms_templates_mapping) && count($oms_templates_mapping) > 0) {

						$template = $oms_templates_mapping[$confopt['value']];
						if (!$template) {
							logActivity("Error: No OMS template found in oms_templates_mapping for value(using it instead):" . $confopt['value']);
							$vmData['template'] = $confopt['value'];
						} else {
							$vmData['template'] = $template;
							logActivity("Using template name from mapping variable:" . $template);
						}
					} else {
						$vmData['template'] = $confopt['value'];
					}
				}
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
		$vmData['nameservers'] = $vm_default_nameservers;

		//$vmData[swap_size]=0.5;
		if ($clientproduct) {
			//get item
			$products = getBundlesProductsByOtherProductId($clientproduct['pid']);
			if ($products) {
				foreach ($products as $product) {
					// If name contains number. eg 10GB
					preg_match('/^\d*/', $product['name'], $matches);
					$amount = ($matches[0]) ? $matches[0] : 1;

					if (stristr($product['name'], "core"))
						$vmData['num_cores'] = $product['count'] * $amount;
					if (stristr($product['name'], "ram"))
						$vmData['memory'] = $product['count'] * $amount;
					if (stristr($product['name'], "storage"))
						$vmData['diskspace'] = $product['count'] * $amount;

				}

				logActivity("VM settings. cores: " . $vmData['num_cores'] . ". memory:" . $vmData['memory'] . ". disk: " . $vmData['diskspace']);

				if ($vmData['num_cores'] > 0 && $vmData['memory'] > 0 && $vmData['diskspace'] > 0) {
					logActivity("Running oms command to create VM.");

					$command = '/machines/hangar/vms-openvz';
					$result = oms_command($command, json_encode($vmData));
					logActivity($result);
					$data = json_decode($result);
					$id = $data -> result -> id;

					if ($id) {
						$urlHangar = $command . "/" . $id;
						$urlChowning = $urlHangar;

						logActivity("Running command Chown for username:" . $username . " and url:" . $urlChowning);
						oms_command('/bin/chown?arg=' . $username . '&arg=' . $urlChowning);

						$urlHangarAllocate = $urlHangar . "/actions/allocate";
						logActivity("Attempting to allocate " . $urlHangar);
						$result = oms_command($urlHangarAllocate);
						logActivity("Allocation of " . $urlHangar . " result: " . $result);

					} else {
						logActivity("Error running command Chown for username:" . $username . ". No computeId");
					}
				} else {
					logActivity("Error: VM settings not set.");
				}
			}
		}
	}

	if($boughtVmProduct){
		$desc = "Adding credit for invoice:" . $invoiceId;
		addCreditForUserId($userId, $username, $amountPaid, $desc);
		updateClientCreditBalance($userId);
	}
	/*logActivity("Removing orders for userId:" . $userId);
	$orders = getUsersOrders($userId);
	if ($orders) {
		foreach ($orders as $order) {
			removeUsersOrder($order['id']);
		}
	}*/
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

/**
 * Functions for removing orders, so flattened products can be removed.
 */
function removeUsersOrder($orderId) {
	$command = "deleteorder";
	$adminuser = "admin";
	$values["orderid"] = $orderId;

	$results = localAPI($command, $values, $adminuser);
	if ($results['result'] == "success") {
		return true;
	} else if ($results['result'] == "error") {
		logActivity("Error removing order. Error:" . $clientData['message']);
		return false;
	} else {
		logActivity("removeUsersOrder: no success or error.");
	}
	return false;
}

function getUsersOrders($userId) {
	$command = "getorders";
	$adminuser = "admin";
	$values["userid"] = $userId;
	// without this all record are returned

	$results = localAPI($command, $values, $adminuser);
	if ($results['result'] == "success") {
		if ($results['orders']['order']) {
			return $results['orders']['order'];
		}
	} else if ($results['result'] == "error") {
		logActivity("Error getting orders for userId:" . $userId . ". Error:" . $clientData['message']);
	} else {
		logActivity("getUsersOrders: no success or error.");
	}
	return null;
}

add_hook("InvoicePaid", 1, "create_new_vm_with_invoice");
?>
