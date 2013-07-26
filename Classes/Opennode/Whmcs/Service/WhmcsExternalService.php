<?php
namespace Opennode\Whmcs\Service;

class WhmcsExternalService implements WhmcsExternalServiceInterface {

    private $adminUser;
    private $adminPassword;
    private $apiUrl;

    public function __construct($whmcs_admin_user, $whmcs_admin_password, $whmcs_api_url, $oms_usage_db) {
        $this -> adminUser = $whmcs_admin_user;
        $this -> adminPassword = $whmcs_admin_password;
        $this -> apiUrl = $whmcs_api_url;
    }

    public function logActivity($msg) {
        $postfields["action"] = "logactivity";
        $postfields["description"] = $msg;
        error_log($msg);
        $this -> callApi($postfields);
    }

    /**
     * Updates client credit with external api, internal did not work.
     */
    function updateClientCreditBalance($userId) {
    	
        $this -> logActivity("Updating client $userId");
        $clientCredit = 0;
        $postfields["action"] = "getcredits";
        $postfields["clientid"] = $userId;
        $clientData = $this -> callAPI($postfields);

        if ($clientData['result'] == "success") {
            foreach ($clientData['credits'] as $creditArr) {
                //Make one dimentsional two dimentsional
                if (!is_array($creditArr[0]))
                    $creditArr = Array($creditArr);
                foreach ($creditArr as $credit) {
                    $clientCredit += $credit['amount'];
                }
            }
            $this -> logActivity("Updating client $userId with credit: $clientCredit");
            $postfields["action"] = "updateclient";
            $postfields["clientid"] = $userId;
            $postfields["credit"] = $clientCredit;

            $results = $this -> callAPI($postfields);
            if ($clientData['result'] == "success") {
                $this -> logActivity("Successfully updated client credit.");
            } else {
                $this -> logActivity("Error updating client credit.");
            }

        }
    }

    /**
     * Function to call external API
     */
    function callApi($postfields) {
        $postfields["username"] = $this -> adminUser;
        $postfields["password"] = md5($this -> adminPassword);
        $ch = curl_init();
        $url = $this -> apiUrl;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $data = curl_exec($ch);
        if (!$data) {
            error_log('Error during callApi: ' . curl_error($ch));
            curl_close($ch);
            return -1;
        }

        curl_close($ch);
        $data = explode(";", $data);
        // $results = array();
        if ($data) {
            foreach ($data AS $temp) {
                if (strpos($temp, "<?xml") === 0)
                    return json_decode(json_encode((array) simplexml_load_string($temp)), 1);

                $temp = explode("=", $temp);
                if (isset($temp[1])) {
                    $results[$temp[0]] = $temp[1];
                }
            }
            /*
             if ($results["result"] == "success") {
             # Result was OK!
             } else {
             # An error occured
             echo "The following error occured: " . $results["message"];
             }*/
            return $results;
        }
        return null;
    }

    /**
     * Function to remove credit from user
     */
    function removeCreditForUserId($userId, $username, $amount, $desc) {
    	error_log("Depreached: WHMCS rounds users credit.");
        if ($amount > 0) {
            $this -> logActivity("Error. Tried to ADD credit to userId:" . $userId);
            return;
        }

        $postfields["action"] = "addcredit";
        $postfields["clientid"] = $userId;
        $postfields["description"] = $desc;
        $postfields["amount"] = $amount;

        $clientData = $this -> callAPI($postfields);

        if ($clientData['result'] == "success") {
            $this -> logActivity("Successfully removed amount of " . $amount . " credit from userId:" . $userId . "(" . $username . ")");
            return true;
        } else if ($clientData['result'] == "error") {
            $this -> logActivity("Error removing credit from userId:" . $userId . ". Error:" . $clientData['message']);
            return false;
        }

        return false;
    }
    
        /**
     * Function to remove credit from user
     */
    function createInvoice($clientId, $amount, $is_taxed, $desc) {
        if ($amount < 0) {
            $this -> logActivity("Error: The client's saldo over the last month is positive. Not generating an invoice");
            return;
        } elseif ($amount == 0) {
            $this -> logActivity("Warning: The client's saldo over the last month is 0. Not generating an invoice");
            return;
        }
        $postfields["action"] = "createinvoice";
        $postfields["userid"] = $clientId;

        $postfields["date"] = date('Ymd');
        $postfields["duedate"] = date('Ymd', strtotime('+2 week'));

        $postfields["sendinvoice"] = false;

        $postfields["itemamount1"] = $amount;
        $postfields["itemdescription1"] = $desc;
        $postfields["itemtaxed1"] = $is_taxed;

        $clientData = $this -> callAPI($postfields);

        if ($clientData['result'] == "success") {
            $this -> logActivity("Successfully created invoice for " . $clientId . " (" .$amount . ")");
            return true;
        } else if ($clientData['result'] == "error") {
            $this -> logActivity("Error creating invoice for " . $clientId . " (" .$amount .
                                            "). Error:" . $clientData['message']);
            return false;
        }

        return false;
    }
    

}
?>