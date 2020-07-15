[![Apache License](https://img.shields.io/badge/license-Apache%202-blue.svg)](https://raw.githubusercontent.com/PureStorage-OpenConnect/pso-explorer/master/LICENSE)
[![GitHub Release](https://img.shields.io/github/v/release/PureStorage-OpenConnect/pso-explorer.svg)]()
[![PR's Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com)

# Pure Service Orchestrator™ Explorer

A unified view into storage, empowering Kubernetes admins and storage admins with 360-degree container storage visibility.

## What is PSO Explorer?

Pure Service Orchestrator™ Explorer (or PSO Explorer) provides a web based user interface for [Pure Service Orchestrator™](https://github.com/purestorage/helm-charts). It shows details of the persistent volumes and snapshots that have been provisioned using PSO, showing provisioned space, actual used space, performance and growth characteristics. The PSO Explorer dashboard provides a quick overview of the number of volumes, snapshots, storageclasses and arrays in the cluster, in addition to the volume usage, the volume growth over the last 24 hours and cluster-level performance statistics.

## How to use?

For instructions on how to deploy and use Pure Service Orchestrator™ Explorer, please visit [https://github.com/PureStorage-OpenConnect/pso-explorer](https://github.com/PureStorage-OpenConnect/pso-explorer).

## About this repo

This repository contains the PHP web application for PSO Explorer. 
The application is: 
- developed using the [Laravel](https://laravel.com/) Framework. 
- distributed as a Docker container hosted on the [Pure Storage® Quay.io repo](https://quay.io/repository/purestorage/pso-explorer).
- installed using a Helm chart as described [here](https://github.com/PureStorage-OpenConnect/pso-explorer/blob/master/README.md).

# How to get started?

We are welcoming pull request (PR's) for further development of PSO Explorer. 

## Download source code

To get started on you development, run the following steps on your Mac or Linux workstation:

```
git clone https://github.com/PureStorage-OpenConnect/pso-explorer-container

cd pso-explorer-comntainer
composer update
```

The `git` command will clone the repo to your workstation. However we've removed the `vendor` subdirectory from the project to keep the project small. This means that before you can use the project, you'll first have to download the third party plugins using the `composer update` command.

## Setup Redis and a Web server

Now that you have the complete project available, the next step is to install redis on your development workstation, which is required by the application to run. For Mac sure you have the `brew` [package manager](https://brew.sh/) installed on your system and run the following commands:

```
brew install redis
```
The next step is to run install a web browser that includes PHP. We suggest that you use the [Laravel Valet project](https://laravel.com/docs/7.x/valet) for this. Once you have valet installed, you can link the application by running the following command from the pso-explorer-container directory:

```
valet link
```
Now you can access the application by browsing to [http://pso-explorer-comntainer.test](http://pso-explorer-comntainer.test).

## Configure the Kubernetes secrets

You are now almost ready to get started, however the application still needs access to your Kubernetes cluster, since it cannot use the in-cluster credentials. If you have PSO Explorer running on your Kubernetes environment, you can get the Kubernetes credentials by login into the container.

```
kubectl get pod -n pso-explorer
```

Use the pod name returned to execute the following (replace the pod name):

```
kubectl exec -it pure-explorer-pso-explorer-78fcf44746-r7fln -n pso-explorer -- /bin/bash
```

You can now get the token file and ca.crt file by using:

```
cat /run/secrets/kubernetes.io/serviceaccount/token
cat /run/secrets/kubernetes.io/serviceaccount/ca.crt
```
Save the contents of these files to your workstation in:

`/etc/pso-explorer/token`
`/etc/pso-explorer/ca.crt`

Once you've saved the credentials, you are ready to access the application via your web browser, as shown earlier.

## Build your own container

Once yuo are ready with your development activities, you can build you own docker container. For this you will need to have Docker Desktop installed on your development workstation. And then you can use the following to build your own docker container and upload it to your own container repo.

```
docker login quay.io
docker build -t pso-explorer:devel .
docker tag pso-explorer:devel quay.io/[your repo]/pso-explorer:devel
docker push quay.io/[your repo]/pso-explorer:devel
```
# Feel free to contribute

We encourage you to get started on this project and make your suggestions for Pull Requests via this GitHub repo. If you run into issues, feel free to open an issue on this repo.
