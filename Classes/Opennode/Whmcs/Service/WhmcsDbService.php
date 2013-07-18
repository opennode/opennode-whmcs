<?php
namespace Opennode\Whmcs\Service;

class WhmcsDbService {

    public function __construct() {

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

}
?>