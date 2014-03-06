<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function add_oms_user_promotion($vars) {
    $localApiAdminUser = "admin";

    // Achivement unlocked: ugly hack with ugliness over 9000
    // 'customfield' index must match field ordering in Setup > Custom Client Fields
    // TODO: better fix fix needed, for example: read all custom fields and find the one with name 'promotion'
    $promoCode = $_POST['customfield'][1];
    if (!$promoCode) {
        logActivity("No promo code provided (user ID: {$vars['userid']})...");
        return;
    }

    $values = array(
        'code' => $promoCode,
    );
    $result = localAPI("getpromotions", $values, $localApiAdminUser);
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

    $values = array(
        'amount' => $promotion['value'],
        'clientid' => $vars['userid'],
        'description' => "Promotion code: $promoCode",
    );
    $result = localAPI("addcredit", $values, $localApiAdminUser);
    if ($result['result'] != "success") {
        logActivity("Failed to add credit from promotion (user ID: {$vars['userid']}, promo code: {$promoCode}), API call result: " . print_r($result, true));
        return;
    }

    logActivity("Credit added: {$promotion['value']} (user ID: {$vars['userid']}, promo code: {$promoCode})");
}

add_hook("ClientAdd", 1, "add_oms_user_promotion");

