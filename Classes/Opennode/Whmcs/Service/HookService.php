<?php
namespace Opennode\Whmcs\Service;

/**
 * Class for functions to run in hooks.
 */
class HookService {

    public static function testClientAreaPage($vars) {
        //not testing currently
        return;

        global $oms_hostname, $oms_user, $oms_password, $whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db;
        if ($_SESSION[uid] != 54)
            return;

        // $omsHelper = new \Opennode\Whmcs\Service\OmsService($oms_hostname, $oms_user, $oms_password);
        // $whmcsExternalService = new \Opennode\Whmcs\Service\WhmcsExternalService($whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db);
        //$whmcsExternalService->removeCreditForUserId($_SESSION[uid],"",0,"test");
        //$whmcsDbService = new \Opennode\Whmcs\Service\WhmcsDbService();
        // $whmcsDbService -> removeCreditFromClient($_SESSION[uid], "", -0.00001, "testDB00001");
        //print_r(\Opennode\Whmcs\Service\WhmcsDbService::getClientsTaxrate($_SESSION[uid]));
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
            $endDate = date_create();
            $confChanges = $omsReduction -> findClientConfChanges($clientId, $startDate, $endDate);
            $parsedChanges = $omsReduction -> parseClientConfChanges($confChanges, $clientId);
            return array("omsconfs" => $parsedChanges);
        }

    }

    /**
     * Function that gets OMC VM data to display on template
     */
    public static function addOmsUsageClientAreaPage($vars) {
        global $oms_usage_db, $product_core_name, $product_disk_name, $product_memory_name;

 		$userId = $_SESSION['uid'];
        //Get products prices
        $hours_per_month = 720;
        $p_core = getProductPriceByName($product_core_name) / $hours_per_month;
        $p_disk = getProductPriceByName($product_disk_name) / $hours_per_month;
        $p_memory = getProductPriceByName($product_memory_name) / $hours_per_month;
        //logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);

        if (!$p_core || !$p_disk || !$p_memory) {
            logActivity("Error: Product prices not set.");
            return;
        }

        $username = get_username($userId);

        $table = $oms_usage_db . ".CONF_CHANGES";

        $sql = "select * from " . $table . " WHERE username='" . $username . "' ORDER BY timestamp DESC LIMIT 1";

        $result = mysql_query($sql);

        if ($result) {
            $data = mysql_fetch_array($result);
            if ($data) {
                $id = $data['id'];
                $mbsInGb = 1024;
                $data['disk'] = $data['disk'] / $mbsInGb;
                $amount = $data['cores'] * $p_core + $data['disk'] * $p_disk + $data['memory'] * $p_memory;
                $data['vm_cost'] = \Opennode\Whmcs\Service\OmsReductionService::applyTax($userId, $amount);
            }
        }
        $data['currentcredit'] = getCreditForUserId($userId);
        return array('omsdata'=>$data);
    }

}
?>
