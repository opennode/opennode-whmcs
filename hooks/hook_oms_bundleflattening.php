<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');


function flatten_bundle($serviceid) {
	global $oms_generated_group_id;
	if (!$oms_generated_group_id) {
		logActivity("Error: No $oms_generated_group_id defined.");
		return;
	}


	$bundles = getBundlesWithUpdatedData();
	if (!$bundles)
		return;
	foreach ($bundles as $bundle) {

		$values["type"] = "server";
		$values["gid"] = $oms_generated_group_id;
		$values["name"] = $bundle[name];
		$values["description"] = $bundle[description];
		$values["welcomeemail"] = "5";
		$values["paytype"] = "onetime";
		$values["pricing"][1] = array("monthly" => $bundle[displayprice]);
		createOrUpdateProduct($values);

	}
}

function createOrUpdateProduct($values) {
	global $oms_generated_group_id;
	$sql = "SELECT * FROM tblproducts WHERE name = '" . $values[name] . "' and gid=" . $oms_generated_group_id;
	$query = mysql_query($sql);
	$product = mysql_fetch_array($query);
	if ($product) {
		logActivity("Updating product with id:" . $product[id]);

		$pricing = $values["pricing"];
		unset($values["pricing"]);

		//update product
		$table = "tblproducts";
		$where = array("id" => $product[id]);

		//update product prices
		$table = "tblpricing";
		$where = array("relid" => $product[id]);
		foreach ($pricing as $price) {
			update_query($table, $price, $where);
		}
	} else {
		logActivity("Creating product with name:" . $values[name]);
		$command = "addproduct";
		$adminuser = "admin";
		$result = localAPI($command, $values, $adminuser);
		if ($result['result'] == 'success') {
			logActivity("Created product with name: " . $values[name] . ". PID:" . $result['pid']);
			return $result['pid'];
		} else if ($result['result'] == 'error') {
			logActivity("Error creating product with name: " . $values[name] . ". Message:" . $result['message']);
		}
	}
	return -1;
}

function getBundlesWithUpdatedData() {
	global $oms_bundles_group_id;
	if (!$oms_bundles_group_id) {
		logActivity("Error: No oms_bundles_group_id defined.");
		return;
	}
	$groupId = $oms_bundles_group_id;

	//Query for bundles
	$table = "tblbundles";
	$fields = "*";
	$where = array("gid" => $groupId);
	$sort = "id";
	$sortorder = "ASC";
	$result = select_query($table, $fields, $where, $sort, $sortorder);
	$bundles = array();
	if ($result) {

		//get Bundles product id-s and count them on a bundle
		$productIds = array();
		$productCount = array();
		while ($data = mysql_fetch_array($result)) {
			$id = $data['id'];
			$bundle[$id] = $data;
			$itemdata = $data['itemdata'];
			//find product ids from string
			$ptn = "*\"pid\";[a-z]:[0-9]+:\"[0-9]+\"*";

			preg_match_all($ptn, $itemdata, $matches);

			foreach ($matches[0] as $match) {
				$ptnNr = "/[0-9]+$/";
				$str = str_replace("\"", "", $match);
				preg_match($ptnNr, $str, $matchNr);
				if ($matchNr) {
					$productId = $matchNr[0];
					$productIds[$productId]++;
					$productCount[$id][$productId] = $productIds[$productId];
				} else
					logActivity("Error parsing itemdata to get product id.");
			}
		}

		//Calculate bundles sum and desc based on products data
		$bundlesCalculated = array();
		foreach ($productIds as $id) {
			//Query for products
			$sql = "SELECT DISTINCT * FROM tblproducts product JOIN tblpricing price ON product.id = price.relid WHERE price.type='product' AND product.id = '" . $id . "'";
			$query = mysql_query($sql);
			$product = mysql_fetch_array($query);
			if ($product) {
				foreach ($productCount as $bundleId => $bundleValue) {
					if ($bundleValue[$id]) {
						$count = $bundleValue[$id];
						$bundlesCalculated[$bundleId][sum] += $product['monthly'] * $count;
						$itemDesc = (($count > 1) ? ($count . " x ") : '') . $product['name'];
						$bundlesCalculated[$bundleId][desc] .= $itemDesc . "\n";
					}
				}
			} else {
				logActivity("Error getting product");
			}
		}

		//add calculated desc and price to bundle
		foreach ($bundlesCalculated as $bundleCalcId => $bundleCalcVal) {
			$bun = $bundle[$bundleCalcId];
			if ($bun[displayprice] == 0.00) {
				$bun[displayprice] = $bundleCalcVal[sum];
			}
			if (!$bun[description]) {
				$bun[description] = $bundleCalcVal[desc];
			}
			$bundles[] = $bun;
		}
		return $bundles;
	} else {
		logActivity("Error getting bundles products");
	}
	return null;
}

add_hook("AdminServiceEdit", 0, "flatten_bundle");
?>
