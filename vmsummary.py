#!/usr/bin/env python

import sys
import json
import csv

try:
    import requests
except ImportError:
    print "You are missing a requests library. Please install (e.g. pip install requests) and run again."
    sys.exit(1)

try:
    import argparse
except:
    print "You are missing argparse module. With py26 it can be installed on 'yum install python-argparse'"
    sys.exit(1)

parser = argparse.ArgumentParser(description='Collect VM stats')
parser.add_argument("oms_url")
parser.add_argument("oms_username")
parser.add_argument("oms_password")
parser.add_argument("dump_filename")
args = parser.parse_args()

# MODIFY TO MATCH YOUR INSTALLATION
OMS_URL = args.oms_url
COMPUTES_INFO = "/computes/?depth=1&attrs=hostname,license_activated,owner,memory,num_cores,diskspace,state,ipv4_address"
OMS_USERNAME = args.oms_username
OMS_PASSWORD = args.oms_password
FILENAME = args.dump_filename
DEBUG = False

# requests debug
if DEBUG:
    import httplib
    httplib.HTTPConnection.debuglevel = 1


def get_client_info(name):
    """Retrieve client specific info as seen by OMS"""
    r = requests.get(OMS_URL + '/home/%s?depth=1&attrs=uid,name' % name, auth=(OMS_USERNAME, OMS_PASSWORD), verify=False)
    return json.loads(r.text)


# get all the known VMs
# print "About to connect to %s to get the information. If the response takes longer, please check the set credentials" % OMS_URL
r = requests.get(OMS_URL + COMPUTES_INFO, auth=(OMS_USERNAME, OMS_PASSWORD), verify=False)
if r.status_code == 200:
    vms = json.loads(r.text)
else:
    print "Failed to get a reasonable response from OMS. Switch on DEBUG to get more info"
    sys.exit(2)


# write down statistics into a csv file and output as a csv into stdout
with open(FILENAME, 'wb') as csvfile:
    csvwriter = csv.writer(csvfile, delimiter=';',
                                    quotechar='"', quoting=csv.QUOTE_MINIMAL)
    csvwriter.writerow(['Hostname', 'Owner name', 'Owner id', 'Memory', 'Disk', 'Cores', 'IP'])

    for v in vms['children']:
        if v['id'] == 'openvz':
            continue
        owner = v['owner']
        oinfo = {}
        if owner != None:
            oinfo = get_client_info(owner)
        vmdata = [v['hostname'], v['owner'],
                  oinfo.get('uid'),
                  v['memory'] / 1024.0, v['diskspace']['total'] / 1024.0,
                  v['num_cores'],
                  v['ipv4_address']]
        csvwriter.writerow(vmdata)
        items = map(lambda x: str(x), vmdata)
        print ';'.join(items)
