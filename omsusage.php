<?php
define("ADMINAREA", true);

require ("init.php");
include_once ('includes/hooks/inc/oms_utils.php');

$ca = new WHMCS_ClientArea();
$ca -> setPageTitle("Resource consumption summary");

$ca -> addToBreadCrumb('index.php', $whmcs -> get_lang('globalsystemname'));
$ca -> addToBreadCrumb('oms_usage.php', 'OMS usage');
$ca -> initPage();

$DATE_FORMAT_INPUT = 'd-m-Y';
$DATE_FORMAT_OUTPUT = 'Y-m-d';
//get parameters
$start_date = ($_GET['start_date']) ? DateTime::createFromFormat($DATE_FORMAT_INPUT, $_GET['start_date']) : date_sub(date_create(), date_interval_create_from_date_string("1 days"));
$end_date = ($_GET['end_date']) ? DateTime::createFromFormat($DATE_FORMAT_INPUT, $_GET['end_date']) : date_create();
$user_id = mysql_real_escape_string($_GET['user_id']);

$datebetweenStr = "start_date: " . $start_date -> format($DATE_FORMAT_INPUT) . " ... end_date: " . $end_date -> format($DATE_FORMAT_INPUT);
$ca -> assign('datebetween', $datebetweenStr);

$isAdmin = $_SESSION['adminid'];
if ($start_date && $end_date && $isAdmin > 0) {
	$sql = "SELECT clientid, SUM(amount) as credit FROM tblcredit WHERE  description LIKE 'OMS_USAGE%'  AND date BETWEEN '" . $start_date -> format($DATE_FORMAT_OUTPUT) . "' AND '" . $end_date -> format($DATE_FORMAT_OUTPUT) . "' ";
	if ($user_id)
		$sql .= " AND clientid=" . $user_id;
	$sql .= "  GROUP BY clientid ";
	$query = mysql_query($sql);
	$clients = Array();
	while ($client = mysql_fetch_array($query)) {
		$clients[] = $client;
	}
	$ca -> assign('clients', $clients);
}

$ca -> setTemplate('omsusage');

$ca -> output();
?>