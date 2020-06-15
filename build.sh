#!/bin/bash

docker login
docker tag pso-analytics dnix101/pso-analytics:0.2.2
docker push dnix101/pso-analytics:0.2.2
