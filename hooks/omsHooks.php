<?php
/**
 * Place for all hooks
 * define function to call HookService::staticFn
 */
require_once (dirname(__FILE__) . '/inc/oms_config.php');
require_once (__DIR__.'../../../Classes/Autoloader.php');
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function testClientAreaPage($vars){
	\Opennode\Whmcs\Service\HookService::testClientAreaPage($vars);
}

function omsClientAdd($vars){
	\Opennode\Whmcs\Service\HookService::omsClientAdd($vars);
}
/*
 * Add password to cookie after WHMCS user creation for OMS iframe login.
 */
function setPasswordCookieClientAdd($vars) {
	\Opennode\Whmcs\Service\HookService::setPasswordCookieClientAdd($vars);
}
/*
 * Add clients OMS conf usage to display in clienthome.tpl
 */
function addOmsConfUsageClientAreaPage($vars){
	return \Opennode\Whmcs\Service\HookService::addOmsConfUsageClientAreaPage($vars);
}
add_hook("ClientAreaPage", 1, "testClientAreaPage");
add_hook("ClientAdd", 1, "omsClientAdd");
add_hook("ClientAdd", 2, "setPasswordCookieClientAdd");
add_hook("ClientAreaPage", 1, "addOmsConfUsageClientAreaPage");
?>
