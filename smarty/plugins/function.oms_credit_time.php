<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {oms_credit_time} function plugin
 *
 * Type:     function<br>
 * Name:     oms_credit_time<br>
 * Date:     April 12, 2013<br>
 * Purpose:  Providing buyer with the info about how long will a certain credit have him running for the defined bundles.
 * @version  1.0
 * @param array
 * @param Smarty
 * @return Integer logged in user credit amount
 */
function smarty_function_oms_credit_time($params, &$smarty) {

	$eurPerHour = (empty($params['eurPerHour'])) ? 0 : $params['eurPerHour'];
	$credit = (empty($params['credit'])) ? null : $params['credit'];
	$digits = (empty($params['digits'])) ? 1 : $params['digits'];
	if ($credit) {
		return round($credit / $eurPerHour, $digits);
	} else {
		if ($_SESSION['uid']) {
			$clientCredit = 0;

			$command = "getcredits";
			$adminuser = "admin";
			$values["clientid"] = $_SESSION['uid'];

			$clientData = localAPI($command, $values, $adminuser);

			if ($clientData['result'] == "success") {
				foreach ($clientData['credits'] as $creditArr) {
					foreach ($creditArr as $credit) {
						$clientCredit += $credit['amount'];
					}
				}
			}

			return round($clientCredit / $eurPerHour, $digits);
		}
	}
	return 0;
}
?>
