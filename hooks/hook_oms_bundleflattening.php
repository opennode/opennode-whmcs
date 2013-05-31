<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

include_once (dirname(__FILE__) . '/inc/oms_utils.php');

/**
 * Main function that updates/created congig group, config options and suboptions based on oms templates query.
 * And creates/updates products based on bundles group.
 */
function hook_bundleflattening($vars) {
	global $oms_generated_group_id;

	if (!$oms_generated_group_id) {
		logActivity("Error: No $oms_generated_group_id defined.");
		return;
	} else {
		logActivity("Flattening bundles to: $oms_generated_group_id ");
	}

	$productConfGroupId = createOrUpdateCongfigOptions();

	$bundles = getBundlesWithUpdatedData();
	if (!$bundles)
		return;
	foreach ($bundles as $bundle) {

		$values["type"] = "server";
		$values["gid"] = $oms_generated_group_id;
		$values["name"] = $bundle[name];
		$values["description"] = $bundle[description];
		$values["paytype"] = "onetime";
		$values["pricing"][1] = array("monthly" => $bundle[displayprice]);
		$productId = createOrUpdateProduct($values);
		createOrUpdateProductCongfigLinks($productConfGroupId, $productId);
	}
}

function createOrUpdateCongfigOptions() {
	$productConfGroupId = createOrUpdateProductCongfigGroup();
	$productConfOptionId = createOrUpdateProductCongfigOptions($productConfGroupId);

	//lets remove all templates before updating
	deleteProductCongfigOptionsSub($productConfOptionId);
	updateTemplates($productConfOptionId);
	return $productConfGroupId;
}

function createOrUpdateProductCongfigGroup() {
	global $oms_templates_conf_group_name;
	$values[name] = $oms_templates_conf_group_name;

	$table = "tblproductconfiggroups";
	$sql = "SELECT * FROM " . $table . "  WHERE name = '" . $values[name] . "'";
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);

	if ($result) {
		logActivity("Updating product config group with name:" . $values[name]);
		$productConfGroupId = $result[id];
		$where = array("id" => $productConfGroupId);
		update_query($table, $values, $where);
	} else {
		logActivity("Creating product config group with name:" . $values[name]);
		$productConfGroupId = insert_query($table, $values);
	}
	return $productConfGroupId;
}

function createOrUpdateProductCongfigOptions($productConfGroupId) {
	$values[gid] = $productConfGroupId;
	$values[optionname] = "Template";
	$values[optiontype] = 1;
	$values[qtyminimum] = 0;
	$values[qtymaximum] = 0;
	$values[order] = 0;
	$values[hidden] = 0;

	$table = "tblproductconfigoptions";
	$sql = "SELECT * FROM " . $table . " WHERE optionname = '" . $values[optionname] . "'";
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);

	if ($result) {
		logActivity("Updating product config option with optionname:" . $values[optionname]);
		$productConfOptionId = $result[id];
		$where = array("id" => $productConfOptionId);
		update_query($table, $values, $where);
	} else {
		logActivity("Creating product config option with optionname:" . $values[optionname]);
		$productConfOptionId = insert_query($table, $values);
	}
	return $productConfOptionId;
}

/**
 * Update templates from OMS or from oms_templates array
 */
function updateTemplates($productConfOptionId) {
	global $oms_templates;

	if (is_array($oms_templates) && count($oms_templates) > 0) {
		logActivity("Building templates from array oms_templates.");
		$i = 0;
		foreach ($oms_templates as $template) {
			$values[configid] = $productConfOptionId;
			$values[optionname] = $template;
			$values[sortorder] = $i;
			$values[hidden] = 0;
			createOrUpdateProductCongfigOptionsSub($values);
			$i++;
		}
	} else {
		logActivity("Quering templates from oms.");
		$command = '/templates?depth=1&attrs=name&exclude=actions';
		$result = oms_command($command, null, "GET");
		$i = 0;
		$data = json_decode($result);
		foreach ($data->children as $template) {
			$values[configid] = $productConfOptionId;
			$values[optionname] = $template -> name;
			$values[sortorder] = $i;
			$values[hidden] = 0;
			createOrUpdateProductCongfigOptionsSub($values);
			$i++;
		}
	}
}

/*
 * Delete all config options for productConfGroupId
 */
function deleteProductCongfigOptionsSub($productConfGroupId) {
	logActivity("Delete configOptionSub for configid:" . $productConfGroupId);
	$table = "tblproductconfigoptionssub";
	$sql = "DELETE FROM " . $table . " WHERE configid = " . $productConfGroupId;
	$query = mysql_query($sql);
}

/*
 * Create or update product config option subs
 */
function createOrUpdateProductCongfigOptionsSub($values) {
	$table = "tblproductconfigoptionssub";
	$sql = "SELECT * FROM " . $table . " WHERE optionname = '" . $values[optionname] . "'";
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);

	if ($result) {
		logActivity("Updating product config suboption  with optionname:" . $values[optionname]);
		$productConfSubOptionId = $result[id];
		$where = array("id" => $productConfSubOptionId);
		update_query($table, $values, $where);
	} else {
		logActivity("Creating product config suboption with optionname:" . $values[optionname]);
		$productConfSubOptionId = insert_query($table, $values);
	}
	return $productConfSubOptionId;
}

function createOrUpdateProductCongfigLinks($productConfGroupId, $productId) {
	$table = "tblproductconfiglinks";
	$sql = "SELECT * FROM " . $table . " WHERE gid = " . $productConfGroupId . " AND pid=" . $productId;
	$query = mysql_query($sql);
	$result = mysql_fetch_array($query);
	$values[gid] = $productConfGroupId;
	$values[pid] = $productId;

	if ($result) {
		logActivity("Product with id:" . $productId . " is already linked with config group id:" . $productConfGroupId);
	} else {
		logActivity("Product with id:" . $productId . " is linked with config group id:" . $productConfGroupId);
		insert_query($table, $values);
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
		update_query($table, $values, $where);

		//update product prices
		$table = "tblpricing";
		$where = array("relid" => $product[id]);
		foreach ($pricing as $price) {
			update_query($table, $price, $where);
		}
		return $product[id];
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
					$productIds[$productId] = $productId;
					$productCount[$id][$productId]++;
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
						if(preg_match('/^\d/', $product['name']) === 1){
							if($count>1)
								$itemDesc = $count . "x" . $product['name'];
							else
								$itemDesc = $product['name'];
						}else{
							$itemDesc = $count . " " . $product['name'];
						}
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
			//if (!$bun[description]) {
			$bun[description] = $bundleCalcVal[desc];
			//	}
			$bundles[] = $bun;
		}
		return $bundles;
	} else {
		logActivity("Error getting bundles products");
	}
	return null;
}

add_hook("ProductEdit", 1, "hook_bundleflattening");
?>
