<?php
 
 define("CLIENTAREA",true);
 //define("FORCESSL",true); // Uncomment to force the page to use https://
 
 require("init.php");
 
 $ca = new WHMCS_ClientArea();
 
 $ca->setPageTitle("Console");
 
 $ca->addToBreadCrumb('index.php',$whmcs->get_lang('globalsystemname'));
 $ca->addToBreadCrumb('console.php','Console');
 
 $ca->initPage();
 
 $ca->requireLogin(); 
 
 $ca->setTemplate('console');
 
 $ca->output();
 
?>