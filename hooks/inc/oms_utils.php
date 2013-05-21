<?php

require(dirname(__FILE__).'/oms_config.php');

/*
 Extract username from the custom field with a specified name ('Username').
*/
function get_username($userid) {
    $result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
    $grab_customfieldid = mysql_fetch_row($result);
    $username_customfieldid = $grab_customfieldid[0];

    // get username value
    $result = mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE fieldid = ".$username_customfieldid." and relid = ".$userid);
    $usernamefield = mysql_fetch_row($result);
    $username = $usernamefield[0];
    return $username;
}

/*
 Extract userid from the custom field with a specified name ('Username').
*/
function get_userid($username) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// get username value
	$result = mysql_query("SELECT relid FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and value = '" . $username . "'");
	$useridfield = mysql_fetch_row($result);
	$userid = $useridfield[0];
	return $userid;
}

/*
 Extract users Balance limit from the custom field with a specified name ('Balance limit').
 */
function get_balance_limit($userid) {
	$result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Balance limit'");
	$grab_customfieldid = mysql_fetch_row($result);
	$username_customfieldid = $grab_customfieldid[0];

	// get Balance limit  value
	$result = mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and relid = " . $userid);
	$bfield = mysql_fetch_row($result);
	$blimit = $bfield[0];
	return $blimit;
}

/* 
 Check if a specified username already exists.
*/
function exists_username($username) {
   $result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
   $grab_customfieldid = mysql_fetch_row($result);
   $username_customfieldid = $grab_customfieldid[0];

   // check if we have at least one username
   // XXX potential sql injection place
   $query = "SELECT count(*) FROM tblcustomfieldsvalues WHERE fieldid = ".$username_customfieldid.
		" and value = \"".mysql_real_escape_string($username)."\"";
   $result = mysql_query($query);
   $r = mysql_fetch_row($result);
   return $r[0] > 0;
}

/* 
 Execute command against OMS server.
*/
function oms_command($command_path, $data) {
    global $oms_hostname, $oms_user, $oms_password;
    // construct full url
    $curl = curl_init($oms_hostname.$command_path);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // user PUT for triggering command in OMS
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');

    // add basic auth
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
    curl_setopt($curl, CURLOPT_USERPWD, $oms_user . ":" . $oms_password);	
	curl_setopt($curl, CURLOPT_POST, 1);
	if ($data) {
		//redefine, if data then POST
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
	}
    $res = curl_exec($curl);
    if ( !$res ) {
	error_log('Error during OMS call: '.curl_error($curl));
        curl_close($curl); 
	return -1;
    }
    curl_close($curl); 
    return $res;
}

?>
