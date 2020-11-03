import datetime
import subprocess
import requests
import time
import os

# Define variables
node = os.environ.get('PSOX_NODENAME')
if (node is None): node = 'unknown host'
service = os.environ.get('PSOX_SERVICENAME')
if (service is None): service = 'pso-explorer'
num = int(os.environ.get('PSOX_PINGCOUNT'))
if (num is None): num = 3
wait = int(os.environ.get('PSOX_REFRESHTIMEOUT'))
if (wait is None): wait = 30

# URL to query for IP addresses to ping
url = 'http://' + service + '/api/nodeAgentSettings'


# Main program
while True:
  mytime=datetime.datetime.now().isoformat(timespec='minutes')
  print(mytime + ' INFO:    Contact PSO eXplorer to for IP addresses to ping')
  
  # Try to connect to PSO eXplorer for IP addresses
  try:
    r = requests.get(url, timeout=10)
  except:
    mytime=datetime.datetime.now().isoformat(timespec='minutes')
    print(mytime + ' ERROR:   Connection error while trying to connect to PSO eXplorer at "' + url + '"')
    time.sleep(wait)
    continue

  if (r.status_code == requests.codes.ok):
    try:
      json = r.json()
    except:
      mytime=datetime.datetime.now().isoformat(timespec='minutes')
      print(mytime + ' ERROR:   Invalid response received')
      time.sleep(wait)
      continue

    if ((type(json) == dict) and (type(json['ips']) == list)):
      for ip in json['ips']:
        mytime=datetime.datetime.now().isoformat(timespec='minutes')
        try:
          subprocess.check_output(['ping', '-c' + str(num), ip])
          print(mytime + ' INFO:    - Ping ' + ip + ' is successful')
          r = requests.post(url, data = {'ip':ip,'result':1,'node':node})
        except subprocess.CalledProcessError as err:
          print(mytime + ' ERROR:   - Ping ' + ip + ' has failed')
          r = requests.post(url, data = {'ip':ip,'result':0,'node':node})
    else:
      mytime=datetime.datetime.now().isoformat(timespec='minutes')
      print(mytime + ' ERROR:   No valid IP addresses found in response')
  else:
    mytime=datetime.datetime.now().isoformat(timespec='minutes')
    print(mytime + ' ERROR:   Unable to connect to PSO Explorer, error code "' + str(r.status_code) + '" received')
  
  time.sleep(wait) 
