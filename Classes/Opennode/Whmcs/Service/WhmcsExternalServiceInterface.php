<?php
namespace Opennode\Whmcs\Service;

interface WhmcsExternalServiceInterface {

  
    public function logActivity($msg);

    /**
     * Function to call external API
     */
    public function callApi($postfields);

}
?>