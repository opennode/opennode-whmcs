<?php
// To be seen by admin only
define("ADMINAREA", true);



require ("../init.php");
include_once ('../includes/hooks/inc/oms_utils.php');

$ca = new WHMCS_ClientArea();
$ca -> setPageTitle("VM statistics");

$ca -> addToBreadCrumb('index.php', $whmcs -> get_lang('globalsystemname'));
$ca -> initPage();

global $oms_user, $oms_password, $oms_hostname, $whmcs_upload_folder;

// execute an external program to get all the info from oms
$id  = exec("${whmcs_code_folder}/vmsummary.py ${oms_hostname} ${oms_user} ${oms_password} " .
                  "${whmcs_code_folder}/${whmcs_upload_folder}/omssstats.csv", $arrEntries);
$vms = Array();

foreach ($arrEntries as $vmdata) {
    $vms[] = explode(";", $vmdata);
}
$ca -> assign('vms', $vms);
$ca -> assign('upload_folder', $whmcs_upload_folder);


$ca -> setTemplate('omsvmstats');

$ca -> output();
?>
