<?php
namespace Opennode\Whmcs\Service;

/**
 * Class for functions to run in hooks.
 */
class HookService {

    public static function testClientAreaPage($vars) {
        //not testing currently
        //return;

        global $oms_hostname, $oms_user, $oms_password, $whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db;
        if ($_SESSION[uid] != 36)
            return;

        // $omsHelper = new \Opennode\Whmcs\Service\OmsService($oms_hostname, $oms_user, $oms_password);
        $whmcsExternalService = new \Opennode\Whmcs\Service\WhmcsExternalService($whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db);
        //$whmcsExternalService->removeCreditForUserId($_SESSION[uid],"",0,"test");
        $whmcsDbService = new \Opennode\Whmcs\Service\WhmcsDbService();
        // $whmcsDbService -> removeCreditFromClient($_SESSION[uid], "", -0.00001, "testDB00001");

    }

    public static function omsClientAdd($vars) {
        global $oms_hostname, $oms_user, $oms_password;
        $userid = $vars['userid'];
        $password = $vars['password'];
        $username = get_username($userid);
        if ($username) {
            $omsHelper = new \Opennode\Whmcs\Service\OmsService($oms_hostname, $oms_user, $oms_password);

            $isSuccess = $omsHelper -> createOmsAccount($username, $password);
            if ($isSuccess) {
                error_log("Created OMS user: " . $username);
            } else {
                error_log("omsClientAdd: Error when creating OMS user: " . $username);
            }
        } else {
            error_log("omsClientAdd: No username fount with id:" . $userid);
        }
    }

    /*
     * Add password to cookie after WHMCS user creation for OMS iframe login.
     */
    public static function setPasswordCookieClientAdd($vars) {
        // TODO: encrypt password
        $password = $vars['password'];
        setcookie("p", $password);
    }

    /*
     * Add clients OMS conf usage to display in clienthome.tpl
     */
    public static function addOmsConfUsageClientAreaPage($vars) {
        global $product_core_name, $product_disk_name, $product_memory_name, $oms_usage_db, $whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db;

        $clientId = $_SESSION['uid'];
        if (is_numeric($clientId)) {
            $whmcsDbService = new \Opennode\Whmcs\Service\WhmcsDbService();
            $whmcsExternalService = new \Opennode\Whmcs\Service\WhmcsExternalService($whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db);
            $omsReduction = new \Opennode\Whmcs\Service\OmsReductionService($product_core_name, $product_disk_name, $product_memory_name, $oms_usage_db, $whmcsExternalService, $whmcsDbService);
			
			$startDate = date_sub(date_create(), date_interval_create_from_date_string("1 months"));
			$endDate= date_create();
            $confChanges = $omsReduction -> findClientConfChanges($clientId, $startDate, $endDate);
            $parsedChanges = $omsReduction -> parseClientConfChanges($confChanges);
            return array("omsconfs" => $parsedChanges);
        }

    }

}
?>
