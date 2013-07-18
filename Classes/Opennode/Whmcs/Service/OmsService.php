<?php
namespace Opennode\Whmcs\Service;
/**
 * OMS utils
 */
class OmsService {
    private $omsHostname;
    private $omsUser;
    private $omsPassword;

    public function __construct($oms_hostname, $oms_user, $oms_password) {
        $this -> omsHostname = $oms_hostname;
        $this -> omsUser = $oms_user;
        $this -> omsPassword = $oms_password;
    }

    /**
     * Create oms user
     *
     * @return boolean if account creation was successful
     */
    function createOmsAccount($username, $password) {
        $command = '/bin/adduser?arg=' . $username . '&arg=' . $password;
        $resp = $this -> omsCommand($command);
        if ($resp != -1) {
            //Check if response contains message, that username already exists
            if (strpos($resp, 'already exists') !== false)
                return FALSE;
            else
                return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     *Extract username from the custom field with a specified name ('Username').
     */
    public static function getOmsUsername($userid) {
        $result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
        $grab_customfieldid = mysql_fetch_row($result);
        $username_customfieldid = $grab_customfieldid[0];

        // get username value
        $result = mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and relid = " . $userid);
        $usernamefield = mysql_fetch_row($result);
        $username = $usernamefield[0];
        return $username;
    }

    /**
     * Execute command against OMS server.
     */
    function omsCommand($command_path, $data, $req_type) {
        // construct full url
        $curl = curl_init($this -> omsHostname . $command_path);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // user PUT for triggering command in OMS
        if ($req_type)
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $req_type);
        else
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');

        // add basic auth
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this -> omsUser . ":" . $this -> omsPassword);

        curl_setopt($curl, CURLOPT_POST, 1);
        if ($data) {
            //redefine, if data then POST
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ));
        }

        $res = curl_exec($curl);

        if (!$res) {
            error_log('Error during OMS call: ' . curl_error($curl));
            curl_close($curl);
            return -1;
        }
        curl_close($curl);
        return $res;
    }

}
?>
