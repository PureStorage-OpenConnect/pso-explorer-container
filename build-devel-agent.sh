#!/bin/bash

if [ -z "$1" ] ; then
  echo "No argument supplied, add release version"
  exit 1
fi
 
# Login to repo
docker login quay.io
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mLogin to image repo failed!\033[0m"
  exit 1 
else
  echo -e "\033[0;32mLogin to image repo successful.\033[0m"
fi

# Build docker image
cd psox-agent
docker build -t psox-agent:$1 .
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mDocker build has failed!\033[0m"
  exit 1
else
  echo -e "\033[0;32mDocker build successful.\033[0m"
fi

docker tag psox-agent:$1 quay.io/purestorage/psox-agent:$1
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mDocker tag has failed!\033[0m"
  exit 1
else
  echo -e "\033[0;32mSuccessfully taged the image.\033[0m"
fi

docker push quay.io/purestorage/psox-agent:$1
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mFailed to push image to repo!\033[0m"
  exit 1
else
  echo -e "\033[0;32mImage successfully pushed to the repo.\033[0m"
fi
