#!/usr/bin/env php
<?php

// DB migration script

// NB make sure that the path is correct!
require_once ("/var/www/html/whmcs/configuration.php");
require_once ("/var/www/html/whmcs/includes/hooks/inc/oms_config.php");
require_once ("/var/www/html/whmcs/Classes/Autoloader.php");

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
    exit(1);
}

// Get OMS username field ID
$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
$usernameField = mysql_fetch_assoc($result);

// Get usernames
$result = mysql_query("SELECT DISTINCT relid AS user_id, value AS username FROM tblcustomfieldsvalues WHERE fieldid = " . (int) $usernameField['id']);
while ($user = mysql_fetch_assoc($result)) {
    error_log("User id={$user['user_id']} username={$user['username']}");
    $sql = "UPDATE CONF_CHANGES SET username = '" . mysql_real_escape_string($user['user_id']) . "' WHERE username = '" . mysql_real_escape_string($user['username']) . "'";
    // No migration yet
    print $sql . "\n";
    mysql_query($sql) or die("MySQL error: " . mysql_error());
}

