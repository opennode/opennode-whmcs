#!/usr/bin/env php
<?php

//require_once '/path/to/db_connect.php'

// DB migration script
// See also: LEV-169

// Get OMS username field ID
$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
$usernameField = mysql_fetch_assoc($result);

// Get usernames
$result = mysql_query("SELECT DISTINCT relid AS user_id, value AS username FROM tblcustomfieldsvalues WHERE fieldid = " . (int) $usernameField['id']);
while ($user = mysql_fetch_assoc($result)) {
	$sql = "UPDATE CONF_CHANGES SET username = '" . mysql_real_escape_string($user['user_id']) . "' WHERE username = '" . mysql_real_escape_string($user['username']) . "'";
	error_log("Will run SQL query:\n$sql");
	// No migration yet
	//mysql_query() or die("Failed to run SQL query:\n$sql\nMySQL error:\n" . mysql_error());
}

