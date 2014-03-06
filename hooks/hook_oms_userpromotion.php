<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function add_oms_user_promotion($vars) {
    $localApiAdminUser = "admin";

    logActivity("Searching for promotions for newly created user...");
    logActivity("[DEBUG] Client data: " . print_r($vars, true)); // FIXME
    $values = array(
        'code' => "FIXME", // FIXME
    );

    $result = localAPI("getpromotions", $values, $localApiAdminUser);
    logActivity("[DEBUG] 'getpromotions' call result: " . print_r($result, true)); // FIXME
    if (false) { // FIXME
        logActivity("Promotion code found, adding credit to user...");
        $values = array(
            'amount' => "FIXME", // FIXME
            'clientid' => "FIXME", // FIXME
            'description' => "FIXME", // FIXME
        );
        $result2 = localAPI("addcredit", $values, $localApiAdminUser);
        logActivity("[DEBUG] 'addcredit' call result: " . print_r($result2, true)); // FIXME
    }
}

add_hook("ClientAdd", 1, "hook_add_oms_user_promotion");

