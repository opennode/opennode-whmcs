#!/bin/bash

WHMCS=/var/www/html/whmcs

rsync -av --exclude 'oms_config.php' hooks $WHMCS/includes/

