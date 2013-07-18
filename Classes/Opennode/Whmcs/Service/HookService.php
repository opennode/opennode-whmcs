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

}
?>
