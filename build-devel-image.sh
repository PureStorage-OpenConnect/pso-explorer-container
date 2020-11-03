#!/bin/bash

if [ -z "$1" ] ; then
  echo "No argument supplied, add release version"
  exit 1
fi
 
# Change application version and debugging mode in .env
cd psox-app
mv .env .env-devel
cp .env-prod .env
sed -i '' 's/APP_VERSION=.*/APP_VERSION="'"$1"'"/' .env
sed -i '' 's/APP_DEBUG=.*/APP_DEBUG=false/' .env

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Update composer packages
composer update

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
docker build -t pso-explorer:$1 .
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mDocker build has failed!\033[0m"
  exit 1
else
  echo -e "\033[0;32mDocker build successful.\033[0m"
fi

docker tag pso-explorer:$1 quay.io/purestorage/pso-explorer:$1
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mDocker tag has failed!\033[0m"
  exit 1
else
  echo -e "\033[0;32mSuccessfully taged the image.\033[0m"
fi

docker push quay.io/purestorage/pso-explorer:$1
if [ $? != 0 ]
then
  echo "-----------------------------------------"
  echo -e "\033[0;31mFailed to push image to repo!\033[0m"
  exit 1
else
  echo -e "\033[0;32mImage successfully pushed to the repo.\033[0m"
fi

# Restore .env file to retain original version
mv .env-devel .env
