<?php
namespace Opennode\Whmcs\Service;

class OmsReductionService {
    public static $DATE_FORMAT_INPUT = 'd-m-Y';
    public static $DATE_FORMAT_OUTPUT = 'Y-m-d';
    public static $DATETIME_FORMAT = 'Y-m-d H:i:s';

    private $product_core_name;
    private $product_disk_name;
    private $product_memory_name;
    private $oms_usage_db;
    private $whmcsExternalService;
    private $whmcsDbService;

    public function __construct($product_core_name, $product_disk_name, $product_memory_name, $oms_usage_db, $whmcsExternalService, $whmcsDbService) {
        $this -> product_core_name = $product_core_name;
        $this -> product_disk_name = $product_disk_name;
        $this -> product_memory_name = $product_memory_name;
        $this -> oms_usage_db = $oms_usage_db;
        $this -> whmcsExternalService = $whmcsExternalService;
        $this -> whmcsDbService = $whmcsDbService;
        date_default_timezone_set('Europe/Helsinki');
    }

    public function reduce_users_credit() {
        $this -> whmcsExternalService -> logActivity("Starting credit reduction job.");
        $result = $this -> queryForConfChanges();
        $parsedResult = $this -> parseLegacyArrayForData($result);

        $this -> applyCreditRemovingFromUsersAmounts($parsedResult['usersAmountsToRemove']);
        $this -> updateRecordIds($parsedResult['recordIdsToUpdate']);
        $this -> whmcsExternalService -> logActivity("Credit reduction job has ended.");
    }

    public function parseLegacyArrayForData($result) {
		$this -> whmcsExternalService -> logActivity("Starting configuration log parsing");
        //Get products prices
        $p_core = $this -> whmcsDbService -> getProductPriceByName($this -> product_core_name);
        $p_disk = $this -> whmcsDbService -> getProductPriceByName($this -> product_disk_name);
        $p_memory = $this -> whmcsDbService -> getProductPriceByName($this -> product_memory_name);

        if (!$p_core || !$p_disk || !$p_memory) {
            $this -> whmcsExternalService -> logActivity("Error: Product prices are not set.");
            return;
        } else {

            $this -> whmcsExternalService -> logActivity("Product prices: Core (1 core)= " . $p_core . 
            	", disk (1GB) = " . $p_disk . ", Memory (1GB) = " . $p_memory);
        }

        $usersAmountsToRemove = array();
        $recordIdsToUpdate = array();
        if ($result) {

            $mbsInGb = 1024;
            $hoursInMonth = 720;
            $prevRecord = null;
            foreach ($result as $currRecord) {
                if ($prevRecord) {
                    if ($prevRecord['username'] == $currRecord['username']) {
                        $username = $prevRecord['username'];
                        $hoursInBetween = (strtotime($currRecord['timestamp']) - strtotime($prevRecord['timestamp'])) / 3600;

                        $prevRecord['disk'] = $prevRecord['disk'] / $mbsInGb;
                        $amount = $prevRecord['cores'] * $p_core + $prevRecord['disk'] * $p_disk + $prevRecord['memory'] * $p_memory;

                        $addAmountToUser = $amount * $hoursInBetween / $hoursInMonth;
                        if (!isset($usersAmountsToRemove[$username]))
                            $usersAmountsToRemove[$username] = 0;

                        $usersAmountsToRemove[$username] += $addAmountToUser;
                        $recordIdsToUpdate[] = $prevRecord['id'];
                        //$this -> whmcsExternalService -> logActivity("Adding " . $addAmountToUser . " EUR to user :" . $username . " for " . $hoursInBetween . " hours. Adding Id:" . $prevRecord['id'] . " in array recordIdsToUpdate. Amount for month is:" . $amount);
                    } else {
                        $this -> whmcsExternalService -> logActivity("Switching users when parsing logs:" . $prevRecord['username'] 
                        		. "->" . $currRecord['username']);
                    }
                }
                $prevRecord = $currRecord;
            }
        }
        return array(
            'usersAmountsToRemove' => $usersAmountsToRemove,
            'recordIdsToUpdate' => $recordIdsToUpdate
        );
    }

    /**
     * Query for conf changes
     * Return array
     */
    function queryForConfChanges() {

        $table = $this -> oms_usage_db . ".CONF_CHANGES";

        $sql = "select conf.id, conf.username, conf.timestamp, conf.cores, conf.disk, conf.memory, conf.number_of_vms from
" . $table . " as conf
where conf.processed = false
AND timestamp <= now()
ORDER BY conf.username, conf.timestamp";

        $result = mysql_query($sql);
        $resultsAsArray = array();
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {

                $resultsAsArray[] = $row;
            }
        }
        return $resultsAsArray;
    }

    /**
     * Query for clients OMS conf changes between dates
     * Return array
     */
    function findClientConfChanges($clientId, $startDate, $endDate) {
        $table = $this -> oms_usage_db . ".CONF_CHANGES";
        $username = \Opennode\Whmcs\Service\OmsService::getOmsUsername($clientId);
        if ($username) {
            $sql = "SELECT  timestamp as begintimestamp, cores, disk, memory, number_of_vms FROM " . $table;
            $sql .= " WHERE ";
            $sql .= " username='" . $username . "'";
            if ($startDate && $endDate)
                // FIXME: use self::$DATE_FORMAT instead
                $sql .= " AND timestamp BETWEEN '" . $startDate -> format(OmsReductionService::$DATETIME_FORMAT) . "' AND '" . $endDate -> format(OmsReductionService::$DATETIME_FORMAT) . "' ";

            $sql .= " ORDER BY timestamp ASC";
            $result = mysql_query($sql); // FIXME: don't use mysql_*, use mysqli_* instead
            $changes = array();
            if ($result) {
                $prevRecord = null;
                $prevPrevRecord = null;
                $lastChange = null;
                while ($curRecord = mysql_fetch_assoc($result)) {
                    if (!$lastChange || self::isConfigChange($curRecord, $prevRecord, $prevPrevRecord) && self::compareUsageRecords($curRecord, $lastChange) !== 0) {
                        $resultsAsArray[] = $curRecord;
                        $lastChange = $curRecord;
                    }
                    $prevPrevRecord = $prevRecord;
                    $prevRecord = $curRecord;
                }
            }
            return $resultsAsArray;
        }
    }

    /**
     * Parse clients conf changes
     * Confs begin and end date is added. And running cost.
     */
    public function parseClientConfChanges($result, $clientId) {

        //Get products prices
        $p_core = $this -> whmcsDbService -> getProductPriceByName($this -> product_core_name);
        $p_disk = $this -> whmcsDbService -> getProductPriceByName($this -> product_disk_name);
        $p_memory = $this -> whmcsDbService -> getProductPriceByName($this -> product_memory_name);

        if (!$p_core || !$p_disk || !$p_memory) {
            $this -> whmcsExternalService -> logActivity("Error: Product prices are not set.");
            return;
        }

        if ($result) {
            $mbsInGb = 1024;
            $hoursInMonth = 720;
            $prevRecord = null;
            $resultsAsArray = array();
            $arrSize = count($result);
            for ($i = 0; $i <= $arrSize; $i++) {
                $currRecord = ($result[$i]) ? $result[$i] : $currRecord;
                if ($prevRecord) {
                    //if we are on last item, then calcutulate current time
                    if ($arrSize == $i) {
                        $objDateTime = new \DateTime('NOW');
                        $currRecord['begintimestamp'] = $objDateTime -> format(OmsReductionService::$DATETIME_FORMAT);
                    }
                    $endTime = strtotime($currRecord['begintimestamp']);
                    $beginTime = strtotime($prevRecord['begintimestamp']);

                    $hoursInBetween = ($endTime - $beginTime) / 3600;

                    $prevRecord['disk'] = $prevRecord['disk'] / $mbsInGb;
                    $amount = $prevRecord['cores'] * $p_core + $prevRecord['disk'] * $p_disk + $prevRecord['memory'] * $p_memory;

                    $cost = $amount * $hoursInBetween / $hoursInMonth;

                    $prevRecord['hoursInBetween'] = $hoursInBetween;
                    $prevRecord['end'] = $currRecord['begintimestamp'];
                    $prevRecord['begin'] = $prevRecord['begintimestamp'];
                    $prevRecord['cost'] = self::applyTax($clientId, $cost);
                    $prevRecord['price'] = self::applyTax($clientId, $amount);

                    $resultsAsArray[] = $prevRecord;
                }
                $prevRecord = $currRecord;
            }

            return $resultsAsArray;
        }
        return null;
    }

    function updateRecordIds($recordIdsToUpdate) {
        $table = $this -> oms_usage_db . ".CONF_CHANGES";
        if (count($recordIdsToUpdate) > 0) {
            $sql = "UPDATE " . $table . " SET processed=true WHERE id IN(" . implode(',', $recordIdsToUpdate) . ')';
            $result = mysql_query($sql);
            if ($result) {
                $this -> whmcsExternalService -> logActivity("Successfully updated " . $table . ". Updated " . sizeof($recordIdsToUpdate) . " entries.");
            } else {
                $this -> whmcsExternalService -> logActivity("Error updating " . $table);
            }
        }
    }

    function applyCreditRemovingFromUsersAmounts($usersAmountsToRemove) {
        foreach ($usersAmountsToRemove as $username => $amountToRemove) {
            $userid = $this -> whmcsDbService -> getUserid($username);
            if ($userid) {
                $amountToRemoveTaxAware = self::applyTax($userid, $amountToRemove);
                $this -> whmcsExternalService -> logActivity("Going to remove credit for username: " . $username . 
                	". Amount: " . $amountToRemoveTaxAware . " EUR. With VAT: " . $amountToRemove);
                $this -> whmcsDbService -> removeCreditFromClient($userid, $username, -$amountToRemoveTaxAware,
                		"OMS_USAGE:(" . date('H:i:s', time()) . ")[removed:" . round($amountToRemoveTaxAware, 5) . " EUR] ");
            } else {
                $this -> whmcsExternalService -> logActivity("Username " . $username .
                	" is missing from WHMCS DB, yet present in the usage table. Consider a cleanup.");
            }
        }
    }

    /**
     * Applies tax, if any, to given user.
     */
    public static function applyTax($clientId, $amount) {
        $taxrate = \Opennode\Whmcs\Service\WhmcsDbService::getClientsTaxrate($clientId);
		$vat = 20;
        if ($taxrate < $vat) {
        	$amountWithoutTax = $amount / (100 + $vat - $taxrate) * 100;
        	return $amountWithoutTax;
		} else {
			return $amount;
		}
    }

    /*
     *
     * @Depreached
     */
    function getUserCreditLastReductionRuntime($userId, $username) {

        $table = $this -> oms_usage_db . ".CREDIT_REDUCTION";
        $sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE userid=" . $userId;
        $query = mysql_query($sql);
        $result = mysql_fetch_array($query);
        if ($result['timestamp']) {
            $this -> whmcsExternalService -> logActivity("== Last timestamp for " . $username . ": " . $result['timestamp']);
            return $result['timestamp'];
        } else {
            // If script is run for first time for user, then timestamp must come from conf_changes table
            $table = $this -> oms_usage_db . ".CONF_CHANGES";
            $sql = "SELECT MAX(TIMESTAMP) as timestamp FROM " . $table . " WHERE username='" . $username . "'";
            $query = mysql_query($sql);
            $result = mysql_fetch_array($query);
            if ($result) {
                return $result['timestamp'];
            } else {
                $this -> whmcsExternalService -> logActivity("No result from CREDIT_REDUCTION or CONF_CHANGES for userid: " . $userId);
            }
        }
        return null;
    }

    /*
     *
     * @Depreached
     */
    function updateUserCreditReductionRuntime($userId) {
        $table = $this -> oms_usage_db . ".CREDIT_REDUCTION";
        $sql = "INSERT INTO " . $table . " (userid, timestamp) VALUES (" . $userId . ", CURRENT_TIMESTAMP)";
        $retval = mysql_query($sql);
        if (!$retval) {
            $this -> whmcsExternalService -> logActivity("Credit reduction run time update error for userid: " . $userId);
        }
        return $retval;
    }

    private static function isConfigChange($rec, $prevRec, $prevPrevRec) {
        if (!$prevRec || !$prevPrevRec) {
            // No previous two records -- this record is not a change (just ignoring diff between first two records)
            return false;
        } elseif (self::compareUsageRecords($rec, $prevPrevRec) === 0) {
            // N-2 record is the same as this one -- this record is not a change
            // Even if N-1 is different, it is probably a short fluctuation; not reporting change
            return false;
        } elseif (self::compareUsageRecords($rec, $prevRec) === 0) {
            // N-1 record is the same as this one -- stable state for at least two polling cycles
            // N-2 is different (see above) -- reporting a change
            return true;
        }

        // N-2, N-1 and N exist and are all different -- ignoring possible change
        // Will wait for the same result on at least two polling cycles
        return false;
    }

    /*
     * Compares usage records by these field values: cores, disk, memory, number_of_vms.
     * Returns
     *  0 if all values are equal,
     *  -1 if *any* of listed values of a is less than corresponding b value
     *  1 otherwise (none of listed a values is less than corresponding b value)
     */
    private static function compareUsageRecords($a, $b) {
        $result = 0;

        foreach (array('cores', 'disk', 'memory', 'number_of_vms') as $key) {
            if ($a[$key] < $b[$key]) {
                return -1;
            } elseif ($a[$key] > $b[$key]) {
                $result = 1;
            }
        }

        return $result;
    }
}
?>
