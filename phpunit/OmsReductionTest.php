<?php

class OmsReductionTest extends PHPUnit_Framework_TestCase implements \Opennode\Whmcs\Service\WhmcsExternalServiceInterface {

    /**
     * @dataProvider provider
     */
    public function testParseLegacyArrayForData($inputArray, $usersAmountsToRemove, $recordIdsToUpdate) {
        $whmcsDbService = $this -> getMock('WhmcsDbService', /* name of class to mock     */
        array('getProductPriceByName') /* list of methods to mock   */
        );

        $whmcsDbService -> expects($this -> any()) -> method('getProductPriceByName') -> with($this -> anything()) -> will($this -> returnCallback(array(
            $this,
            'getProductPriceByNameCallback'
        )));
        $omsReduction = new \Opennode\Whmcs\Service\OmsReductionService("Core", "10GB Storage", "GB RAM", "", $this, $whmcsDbService);
        $this -> assertNotNull($omsReduction);

        $parsedResult = $omsReduction -> parseLegacyArrayForData($inputArray);
        $this -> assertEquals($parsedResult['usersAmountsToRemove'], $usersAmountsToRemove);
        $this -> assertEquals($parsedResult['recordIdsToUpdate'], $recordIdsToUpdate);
    }

    public function provider() {

        $arr = array();
        foreach (glob("omsReductionData/*.json") as $filename) {
            $str_data = file_get_contents($filename);
            $data = json_decode($str_data, true);
            $arr[] = array(
                $data['input'],
                $data['usersAmountsToRemove'],
                $data['recordIdsToUpdate']
            );
        }
        return $arr;
    }

    /**
     * Callback methof for WhmcsDbService->getProductPriceByName()
     *
     * @return int
     */
    function getProductPriceByNameCallback($name) {
        switch ($name) {
            case "Core" :
                return 12.6;
            case "10GB Storage" :
                return 0.18;
            case "GB RAM" :
                return 16.42;
            default :
                return 0;
        }
    }

    /**
     * Override logActivity() with implementing for testing
     *
     * @return void
     */
    function logActivity($msg) {
        print_r($msg . "\n");
    }

    /**
     * Override callApi() with implementing for testing
     *
     * @return void
     */
    function callApi($postfields) {
        print_r($postfields . "\n");
    }

}
?>