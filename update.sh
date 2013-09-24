#!/bin/bash
# Update OpenNode-WHMCS integration


WHMCS=/var/www/html/whmcs
BACKUPDIR=/opt/backups/whmcs
# perform backup of the existing system
foldername=whmcs-$(date +"%m-%d-%Y")

echo Backing up existing installation: $WHMCS -> $BACKUPDIR/$foldername
cp -vfr $WHMCS $BACKUPDIR/$foldername

# update hooks
rsync -av --exclude 'oms_config.php' hooks $WHMCS/includes/
# update business layer
rsync -av Classes $WHMCS/
# update support scripts
rsync -av *.php $WHMCS/
# update language
rsync -av lang $WHMCS/
# update smarty functions
rsync -av smarty $WHMCS/includes/
# update templates
rsync -av templates $WHMCS/
