<?php
require ("init.php");
require_once (dirname(__FILE__) . '/includes/hooks/inc/oms_config.php');
require_once (__DIR__ . '/Classes/Autoloader.php');

$ca = new WHMCS_ClientArea();
$ca -> setPageTitle("Resource consumption summary");

$ca -> addToBreadCrumb('index.php', $whmcs -> get_lang('globalsystemname'));
$ca -> addToBreadCrumb('oms_usage.php', 'OMS usage');
$ca -> initPage();

//get parameters
$user_id = mysql_real_escape_string($_GET['user_id']);
$clientId = $_SESSION['uid'];

global $product_core_name, $product_disk_name, $product_memory_name, $oms_usage_db, $whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db;

$whmcsDbService = new \Opennode\Whmcs\Service\WhmcsDbService();
$whmcsExternalService = new \Opennode\Whmcs\Service\WhmcsExternalService($whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db);
$omsReduction = new \Opennode\Whmcs\Service\OmsReductionService($product_core_name, $product_disk_name, $product_memory_name, $oms_usage_db, $whmcsExternalService, $whmcsDbService);


$confChanges = $omsReduction -> findClientConfChanges($clientId, null, null);
$parsedChanges = $omsReduction -> parseClientConfChanges($confChanges);


$ca -> assign('omsconfs', $parsedChanges);
$ca -> setTemplate('omsclientusage');
$ca -> output();
?>