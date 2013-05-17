<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {oms_bundle_credit_time} function plugin
 *
 * Type:     function<br>
 * Name:     oms_bundle_credit_time<br>
 * Date:     April 12, 2013<br>
 * Purpose:  Provides OMS bundles with product items sum and with product items names if not defined manually.
 * @version  1.0
 * @param array
 * @param Smarty
 * @return Integer logged in user credit amount
 */
function smarty_function_oms_bundle_credit_time($params, &$smarty) {
	$groupId = (empty($params['groupId'])) ? null : $params['groupId'];
	$bundleNameAndSum = array();
	$smarty -> assign('bundleNameAndSum', $bundleNameAndSum);
	if ($groupId) {

		//Query for bundles
		$table = "tblbundles";
		$fields = "*";
		$where = array("gid" => $groupId);
		$sort = "id";
		$sortorder = "ASC";
		$result = select_query($table, $fields, $where, $sort, $sortorder);

		if ($result) {

			while ($data = mysql_fetch_array($result)) {
				$productIds = array();
				$itemdata = $data['itemdata'];
				//find product ids from string
				$ptn = "*\"pid\";[a-z]:[0-9]+:\"[0-9]+\"*";

				preg_match_all($ptn, $itemdata, $matches);

				foreach ($matches[0] as $match) {
					$ptnNr = "/[0-9]+$/";
					$str = str_replace("\"", "", $match);
					preg_match($ptnNr, $str, $matchNr);
					if ($matchNr) {
						$productIds[$matchNr[0]]++;
					} else
						logActivity("Error parsing itemdata to get product id.");
				}
				$sum = 0;
				foreach ($productIds as $id => $count) {
					//print_r("Product with id:".$id.", count:".$count);
					//Query for products
					$sql = "SELECT DISTINCT * FROM tblproducts product JOIN tblpricing price ON product.id = price.relid WHERE price.type='product' AND product.id = '" . $id . "'";
					$query = mysql_query($sql);
					$product = mysql_fetch_array($query);
					if ($product) {
						$sum += $product['monthly'] * $count;
					} else {
						logActivity("Error getting product");
					}
				}
				$bundleNameAndSum[] = array('name' => $data['name'], 'price' => $sum);

			}

			$smarty -> assign('bundleNameAndSum', $bundleNameAndSum);
			//print_r("<PRE>");
			//print_r($bundleNameAndSum);
			//print_r("</PRE>");
		} else {
			logActivity("Error getting bundles products");
		}

	}

}
?>
