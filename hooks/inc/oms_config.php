<?php

// url to access OMS
$oms_hostname = "http://localhost:10000";
// credentials of the user belonging to 'admins' group
$oms_user = "opennode";
$oms_password = "changeme";

$oms_img = "images/onc_logo_login_transparent.png";

$oms_usage_db = "OMS_USAGE";
// Used to connect to externalApi in cron
$whmcs_admin_user = "admin";
$whmcs_admin_password = "password";
$whmcs_api_url = "http://localhost/includes/api.php";
$whmcs_code_folder = "/var/www/html/whmcs/";
$whmcs_upload_folder = "tmp_secret_folder/inside/whmcs_code_folder";

$product_core_name="Core";
$product_disk_name="10GB Storage";
$product_memory_name="GB RAM";

$oms_bundles_group_id=4;
$oms_generated_group_id=5;

//name of config group where oms templates config options are created (config group is created automattically)
$oms_templates_conf_group_name = "Bundles fields";

$vm_default_nameservers = '8.8.8.8';

//if this is defined or not empty then templates are not queried from OMS
//For display:
$oms_templates = array('CentOS 6 64-bit','Ubuntu 12.04 LTS','OpenSuse');
//For finding oms template by name. Add elements, dont remove.
$oms_templates_mapping = array('CentOS 6 64-bit'=>'centos-4.9-x86_64-asys','Ubuntu 12.04 LTS'=>'ubuntu-12.04-x86_64-asys','OpenSuse'=>'suse-12.2-x86_64-asys');

?>
