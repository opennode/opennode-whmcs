<?php
namespace Opennode\Whmcs\Service;

class OmsReductionService {
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
        $this -> whmcsExternalService -> logActivity("Starting clients credit reduction CRON job.");
        $result = $this -> queryForConfChanges();
        $parsedResult = $this -> parseLegacyArrayForData($result);

        $this -> applyCreditRemovingFromUsersAmounts($parsedResult['usersAmountsToRemove']);
        $this -> updateRecordIds($parsedResult['recordIdsToUpdate']);
        $this -> whmcsExternalService -> logActivity("Client credit reduction CRON job ended.");
    }

    public function parseLegacyArrayForData($result) {

        //Get products prices
        $p_core = $this -> whmcsDbService -> getProductPriceByName($this -> product_core_name);
        $p_disk = $this -> whmcsDbService -> getProductPriceByName($this -> product_disk_name);
        $p_memory = $this -> whmcsDbService -> getProductPriceByName($this -> product_memory_name);

        if (!$p_core || !$p_disk || !$p_memory) {
            $this -> whmcsExternalService -> logActivity("Error: Product prices not set.");
            return;
        } else {

            $this -> whmcsExternalService -> logActivity("Using product prices for calculations: Cores:" . $p_core . ". Disk:" . $p_disk . ".Memory:" . $p_memory);
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
                        $this -> whmcsExternalService -> logActivity("Switching users:" . $prevRecord['username'] . "->" . $currRecord['username']);
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
			AND timestamp <= DATE_SUB(now(), INTERVAL 1 HOUR) 
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

    function updateRecordIds($recordIdsToUpdate) {
        $table = $this -> oms_usage_db . ".CONF_CHANGES";
        if (count($recordIdsToUpdate) > 0) {
            $sql = "UPDATE " . $table . " SET processed=true WHERE id IN(" . implode(',', $recordIdsToUpdate) . ')';
            $result = mysql_query($sql);
            if ($result) {
                $this -> whmcsExternalService -> logActivity("Successfully updated " . $table . " with ids:" . implode(',', $recordIdsToUpdate));
            } else {
                $this -> whmcsExternalService -> logActivity("Error updating " . $table);
            }
        }
    }

    function applyCreditRemovingFromUsersAmounts($usersAmountsToRemove) {
        foreach ($usersAmountsToRemove as $username => $amountToRemove) {
            $userid = $this -> whmcsDbService -> getUserid($username);
            if ($userid) {
                $this -> whmcsExternalService -> logActivity("Going to remove credit for user:" . $username . ". Amount: " . $amountToRemove . " EUR ");
                $isSuccess = $this -> whmcsExternalService -> removeCreditForUserId($userid, $username, -$amountToRemove, "OMS_USAGE:(" . date('H:i:s', time()) . ")[removed:" . round($amountToRemove, 5) . " EUR] ");
                if ($isSuccess) {
                    //$this -> updateUserCreditReductionRuntime($userid);
                    $this -> whmcsExternalService -> updateClientCreditBalance($userid);
                } else {
                    $this -> whmcsExternalService -> logActivity("Error: Credit reduction error for user:" . $username . ".");
                }
            } else {
                $this -> whmcsExternalService -> logActivity("Userid not found for username " . $username);
            }
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

}
?>
