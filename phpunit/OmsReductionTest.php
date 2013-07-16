<?php

include_once ("../OmsReduction.php");

/**
 * Override getProductPriceByName() in current namespace for testing
 *
 * @return int
 */
function getProductPriceByName($name) {
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
 * Override time() in current namespace for testing
 *
 * @return void
 */
function logActivity($msg) {
    print_r($msg . "\n");
}

class OmsReductionTest extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider provider
     */
    public function testParseLegacyArrayForData($inputArray, $usersAmountsToRemove, $recordIdsToUpdate) {
        $omsReduction = new \Opennode\Whmcs\Oms\OmsReduction("Core", "10GB Storage", "GB RAM", "");
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

}
?>