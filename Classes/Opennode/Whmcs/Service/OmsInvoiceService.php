<?php
namespace Opennode\Whmcs\Service;

class OmsInvoiceService {
    public static $DATE_FORMAT_INPUT = 'd-m-Y';
    public static $DATE_FORMAT_OUTPUT = 'Y-m-d';
    public static $DATETIME_FORMAT = 'Y-m-d H:i:s';

    private $whmcsExternalService;
    private $whmcsDbService;
    private $oms_monthly_client_group;

    public function __construct($oms_monthly_client_group, $whmcsExternalService, $whmcsDbService) {
        $this -> whmcsExternalService = $whmcsExternalService;
        $this -> whmcsDbService = $whmcsDbService;
        $this -> oms_monthly_client_group = $oms_monthly_client_group;
        date_default_timezone_set('Europe/Helsinki');
    }

    public function generate_users_monthly_invoice() {
        $this -> whmcsExternalService -> logActivity("Starting monthly invoice generation CRON job.");
        $client_list = $this -> queryForClientList();
        $last_month_data = $this -> getLastMonthData($client_list);

        foreach($last_month_data as $lm) {
            $this -> whmcsExternalService -> createInvoice($lm[0], $lm[1], $lm[2],
                              'IT resource consumption (' . $lm[3] . ' to ' . $lm[4] . ')');
        }

        $this -> whmcsExternalService -> logActivity("Finished monthly invoice generation CRON job.");
    }

    public function queryForClientList() {
        $client_group_id = $this -> whmcsDbService -> getClientGroupId($this -> oms_monthly_client_group);
        $client_ids = $this -> whmcsDbService -> getClientsByGroup($client_group_id);
        return $client_ids;
    }

    public function getLastMonthData($client_list) {
        $total_result = array();

        $start_date = date("Y-m-1", strtotime("first day of previous month"));
        $end_date = date("Y-m-t", strtotime("first day of previous month"));
        $arrSize = count($client_list);
        for ($i = 0; $i < $arrSize; $i++) {
            $client_id = $client_list[$i]['id'];
            $sql = "select sum(amount) from tblcredit where clientid = " . $client_id . " and " .
                             "date >= DATE('". $start_date ."') and date <= DATE('" . $end_date . "')";
            $result = mysql_query($sql);
            $sum_query = mysql_fetch_row($result);
            $sum = $sum_query[0];
            $taxed_sum = OmsReductionService::applyTax($client_id, $sum);
            $total_result[$i] = array($client_id, -$sum, $sum == $taxed_sum, $start_date, $end_date);
        }

        return $total_result;
    }

}
?>
