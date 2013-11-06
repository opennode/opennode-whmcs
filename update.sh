#!/bin/bash
# Update OpenNode-WHMCS integration


WHMCS=/var/www/html/whmcs
BACKUPDIR=/opt/backups/whmcs
# perform backup of the existing system
foldername=whmcs-$(date +"%m-%d-%Y")

echo "Backing up existing installation: $WHMCS -> $BACKUPDIR/$foldername"
mkdir -p $BACKUPDIR/$foldername
rsync -av $WHMCS $BACKUPDIR/$foldername/

# update hooks
rsync -av --exclude 'oms_config.php' hooks $WHMCS/includes/
# update business layer
rsync -av Classes $WHMCS/
# update support scripts
rsync -av *.php $WHMCS/
# update support scripts
rsync -av *.py $WHMCS/
# update language
rsync -av lang $WHMCS/
# update smarty functions
rsync -av smarty $WHMCS/includes/
# update templates
rsync -av templates $WHMCS/
# update logo
# replace logo with a correct one
#rsync -av logos/levira.png $WHMCS/templates/default/img/whmcslogo.png

# warning for vmusagestats
echo "Please, verify that your '\$whmcs_code_folder' and '\$whmcs_upload_folder' are set to correct loctions in configuration file."
echo Configuration file: $WHMCS/includes/hooks/inc/oms_config.php

