<?php
require_once ("configuration.php");
require_once ("includes/hooks/inc/oms_config.php");
require_once ("Classes/Autoloader.php");

try {
    // Check MySQL Configuration
    $db_conn = mysql_connect($db_host, $db_username, $db_password);
    if (empty($db_conn))
        throw new Exception('Unable to connect to DB');

    $db_select = @mysql_select_db($db_name, $db_conn);
    if (empty($db_select))
        throw new Exception('Unable to select WHMCS database');

} catch (Exception $e) {
    echo 'Error: ', $e -> getMessage(), "\n";
}
global $product_core_name, $product_disk_name,
       $product_memory_name, $oms_usage_db,
       $whmcs_admin_user, $whmcs_admin_password,
       $whmcs_api_url, $oms_usage_db;

$whmcsDbService = new \Opennode\Whmcs\Service\WhmcsDbService();
$whmcsExternalService = new \Opennode\Whmcs\Service\WhmcsExternalService($whmcs_admin_user, $whmcs_admin_password,
                                                                         $whmcs_api_url, $oms_usage_db);
$omsReduction = new \Opennode\Whmcs\Service\OmsReductionService($product_core_name, $product_disk_name,
                                    $product_memory_name, $oms_usage_db, $whmcsExternalService, $whmcsDbService);
$omsReduction -> reduce_users_credit();
?>