<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

function p($data) {
	print_r("<PRE>");
	print_r($data);
	print_r("</PRE>");
}

function flatten_bundle() {
	global $oms_generated_group_id;
	if (!$oms_generated_group_id) {
		logActivity("Error: No $oms_generated_group_id defined.");
		return;
	}
	if ($_SESSION[uid] != 13)//For testing if not developer
		return;

	$bundles = getBundlesWithUpdatedData();
	if (!$bundles)
		return;
	foreach ($bundles as $bundle) {

		$sql = "SELECT * FROM tblproducts WHERE name = '" . $bundle[name] . "' and gid=".$oms_generated_group_id;
		$query = mysql_query($sql);
		$product = mysql_fetch_array($query);

		$table = "tblproducts";
		$values = array("name" => $bundle[name], "type" => "server", "paytype" => "onetime", "description" => $bundle[description], "displayprice" => $bundle[displayprice], "gid" => $oms_generated_group_id);
		if ($product) {
			logActivity("Updating product with name:" . $bundle[name]);
			p("Updating product with id:" . $product[id]);

			$where = array("id" => $product[id]);
			update_query($table, $values, $where);

		} else {
			logActivity("Creating product with name:" . $bundle[name]);
			p("Creating product with name:" . $bundle[name]);
			$newid = insert_query($table, $values);
			p($newid);
		}
	}
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

add_hook("ClientAreaPage", 1, "flatten_bundle");
?>
