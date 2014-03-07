<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once (dirname(__FILE__) . '/inc/oms_config.php');

function add_oms_user_promotion($vars) {
    global $whmcs_admin_user;

    // Get promo code (this is just very wrong on all levels)
    $sql = "SELECT id FROM tblcustomfields WHERE fieldname='promotion'";
    $result = mysql_query($sql);
    if (!$result) {
        logActivity("Error: failed to retrieve promotion field index from database (user ID: {$vars['userid']}), MySQL error: " . mysql_error());
        return;
    }
    $resultArr = mysql_fetch_assoc($result);
    $promotionFieldIndex = (int) $resultArr['id'];
    $promoCode = $_POST['customfield'][$promotionFieldIndex];
    if (!$promoCode) {
        logActivity("No promo code provided (user ID: {$vars['userid']}, promotion field ID: {$promotionFieldIndex})...");
        return;
    }

    // Get promotion
    $values = array(
        'code' => $promoCode,
    );
    $result = localAPI("getpromotions", $values, $whmcs_admin_user);
    if ($result['result'] != "success") {
        logActivity("Failed to retrieve promotions (user ID: {$vars['userid']}), API call result: " . print_r($result, true));
        return;
    }
    if ($result['totalresults'] < 1) {
        logActivity("No promotions found (user ID: {$vars['userid']})");
        return;
    }

    $promotion = $result['promotions']['promotion'][0]; // TODO: What if multiple promotions are found for one code?
    if (!$promotion) {
        logActivity("API error: promotion count > 1 reported but no promotions returned (user ID: {$vars['userid']})");
        return;
    }
    logActivity("Promotion found (user ID: {$vars['userid']}, promo code: $promoCode)");

    // TODO: check $promotion['uses'] < $promotion['maxuses']
    // TODO: check time() < strtotime($promotion['expirationdate'])

    // Add credit to client
    $values = array(
        'amount' => $promotion['value'],
        'clientid' => $vars['userid'],
        'description' => "Promotion code: $promoCode",
    );
    $result = localAPI("addcredit", $values, $whmcs_admin_user);
    if ($result['result'] != "success") {
        logActivity("Failed to add credit from promotion (user ID: {$vars['userid']}, promo code: {$promoCode}), API call result: " . print_r($result, true));
        return;
    }
}

add_hook("ClientAdd", 1, "add_oms_user_promotion");

