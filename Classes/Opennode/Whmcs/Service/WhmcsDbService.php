<?php
namespace Opennode\Whmcs\Service;

class WhmcsDbService {

    public function __construct() {

    }

    public function logActivity($msg) {
        error_log($msg);
    }

    /**
     * Get product item price, if item is 10GB then return 1GB price
     */
    public function getProductPriceByName($name) {
        $sql = "SELECT DISTINCT * FROM tblproducts product JOIN tblpricing price ON product.id = price.relid WHERE price.type='product' AND product.name = '" . $name . "'";
        $query = mysql_query($sql);
        $product = mysql_fetch_array($query);
        if ($product) {
            preg_match('/^\d*/', $product['name'], $matches);
            $amount = ($matches[0]) ? $matches[0] : 1;
            $sum = $product['monthly'] / $amount;
            // Calculate one unit price. Can be 10GB disk, need price of 1GB.
            return $sum;
        } else {
            error_log("Error getting product");
        }
    }

    /**
     * Get clients taxrate
     */
    public static function getClientsTaxrate($clientId) {
        if (is_numeric($clientId)) {
            $sql = "select taxrate from tbltax where country=(select country from tblclients where id=" . $clientId . ")";
            $query = mysql_query($sql);
            if ($query) {
                $row = mysql_fetch_row($query);
                if ($row)
                    return $row[0];
            }
        }
    }

    function getUserid($username) {
        $result = mysql_query("SELECT id FROM tblcustomfields WHERE fieldname='Username'");
        $grab_customfieldid = mysql_fetch_row($result);
        $username_customfieldid = $grab_customfieldid[0];

        // get username value
        $result = mysql_query("SELECT relid FROM tblcustomfieldsvalues WHERE fieldid = " . $username_customfieldid . " and value = '" . $username . "'");
        $useridfield = mysql_fetch_row($result);
        $userid = $useridfield[0];
        return $userid;
    }

    /**
     * Removes credit
     */
    public function removeCreditFromClient($clientId, $username, $amount, $desc) {
        if ($amount > 0) {
            $this -> logActivity("Error. Tried to ADD credit to userId:" . $clientId);
            return;
        }
        if (is_numeric($amount) && $amount < 0) {
            $this -> addCredit($clientId, $username, $amount, $desc);
            $this -> updateCreditSum($clientId);
        }
    }

    /**
     * Updates clients credit sum based on credit rows
     */
    public function updateCreditSum($clientId) {
        if (is_numeric($clientId)) {
            $result = mysql_query("UPDATE tblclients as cl SET cl.credit=(SELECT sum(amount) FROM  tblcredit as cr WHERE cr.clientid=cl.id) WHERE cl.id=" . $clientId);
        } else {
            error_log("Wrong clientId:" . $clientId);
        }
    }

    /**
     * Adds credit record directly to db.
     */
    private function addCredit($clientId, $username, $amount, $desc) {

        $this -> logActivity("Adding credit row for client: " . $clientId);
        $table = "tblcredit";

        $sql = "INSERT INTO " . $table . " (clientid, date, amount, description) VALUES (" . $clientId . ", CURDATE() ,'" . $amount . "','" . $desc . "' )";
        $retval = mysql_query($sql);
        if (!$retval) {
            $this -> logActivity("addCredit error for userid: " . $clientId);
        }
        return $retval;
    }
	
	/**
	 * Get id of the specified client group. 
	 */
	function getClientGroupId($client_group_name) {
        $result = mysql_query("SELECT id FROM tblclientgroups WHERE groupname='". $client_group_name ."'");
        $grab_customfieldid = mysql_fetch_row($result);
        return $grab_customfieldid[0];
    }
	
	/**
	 * Return list of client id's belonging to a defined customer group.
	 */
	function getClientsByGroup($client_group_id) {
		$result = mysql_query("SELECT id FROM tblclients WHERE groupid='". $client_group_id ."'");
		$resultsAsArray = array();
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $resultsAsArray[] = $row;
            }
        }
        return $resultsAsArray;
	}
}
?>