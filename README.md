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

## Build the application container

The repository contains the [Dockerfile](/Dockerfile) which can be used to build the application.
