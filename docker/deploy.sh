#!/bin/bash
#Deploy to prod/stg
ENV=$1

SERVICE_NAME=apprien-magento-$ENV


#No need to change anything below this line
export IMAGE_VERSION=$2
COMPOSE_FILE="docker-compose.${ENV}.yml"
export CONFIG_VERSION=`date +%Y%m%d%H%m`

if [ -z "$SERVICE_NAME" ] || [ -z "$IMAGE_VERSION" ]; then
    echo "Usage: ./deploy.sh [env] <version>, e.g. ./deploy.sh prod prod-build-124"
    exit 1
fi

#Login to docker hub
docker login

echo "Deploying $IMAGE_VERSION to $ENV"
export DOCKER_HOST=docker-swarm-master.in.phz.fi
#docker stack rm $SERVICE_NAME
docker stack deploy --with-registry-auth --compose-file docker-compose.$ENV.yml $SERVICE_NAME
export DOCKER_HOST=


