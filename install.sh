#!/bin/bash

echo 'Creating docker-compose config'

read -p 'Do you wish to install RabbitMQ (y/N): ' install_rabbitmq

webroot_path="./shared/webroot"
read -p 'Do you have existing copy of Magento 2 (y/N): ' yes_no
if [[ $yes_no = 'y' ]]
    then
        read -p 'Please provide full path to the magento2 folder: ' webroot_path
fi
composer_path="./shared/.composer"
read -p 'Do you have existing copy of .composer folder (y/N): ' yes_no
if [[ $yes_no = 'y' ]]
    then
        read -p 'Please provide full path to the .composer folder: ' composer_path
fi
ssh_path="./shared/.ssh"
read -p 'Do you have existing copy of .ssh folder (y/N): ' yes_no
if [[ $yes_no = 'y' ]]
    then
        read -p 'Please provide full path to the .ssh folder: ' ssh_path
fi
db_path="./shared/webroot"
read -p 'Do you have existing copy of the database files folder (y/N): ' yes_no
if [[ $yes_no = 'y' ]]
    then
        read -p 'Please provide full path to the database files folder: ' db_path
fi

cat > docker-compose.yml <<- EOM
##
# Services needed to run Magento2 application on Docker
#
# Docker Compose defines required services and attach them together through aliases
##
version: '2'
services:
  db:
    container_name: magento2-devbox-db
    restart: always
    image: mysql:5.6
    ports:
      - "1345:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=magento2
    volumes:
      - $db_path:/var/lib/mysql
EOM

rabbit_host='rabbit'
rabbit_port=5672
if [[ $install_rabbitmq = 'y' ]]
    then
        cat << EOM >> docker-compose.yml
  $rabbit_host:
    container_name: magento2-devbox-rabbit
    image: rabbitmq:3-management
    ports:
      - "8282:15672"
      - "$rabbit_port:$rabbit_port"
EOM
fi

read -p 'Do you wish to setup Redis as session storage (y/N): ' redis_session
read -p 'Do you wish to setup Redis (r) or Varnish (v) as page cache mechanism (any key for default file system storage) (r/v): ' cache_adapter

redis_cache=0
if [[ $cache_adapter = 'v' ]]
    then
        install_varnish='y'
        redis_cache=1
fi

if [[ $cache_adapter = 'r' ]] || [[ $redis_session = 'y' ]]
    then install_redis='y'
fi

redis_host='redis'
if [[ $install_redis = 'y' ]]
    then
        cat << EOM >> docker-compose.yml
  $redis_host:
    container_name: magento2-devbox-redis
    image: redis:3.0.7
EOM
fi

web_port=1748
if [[ $install_varnish = 'y' ]]
    then
        cat << EOM >> docker-compose.yml
  varnish:
    build: varnish
    container_name: magento2-devbox-varnish
    links:
      - web:web
    ports:
      - "1748:6081"
EOM
web_port=1749
fi

magento_path='/var/www/magento2'
cat << EOM >> docker-compose.yml
  web:
    build: web
    container_name: magento2-devbox-web
    volumes:
      - $webroot_path:$magento_path
      - $composer_path:/root/.composer
      - $ssh_path:/root/.ssh
      #    - ./shared/.magento-cloud:/root/.magento-cloud
    ports:
      - "$web_port:80"
EOM

echo "Creating shared folders"

mkdir -p shared/.composer
mkdir -p shared/.ssh
mkdir -p shared/webroot
mkdir -p shared/db

echo 'Build docker images'

docker-compose up --build -d

docker exec -it --privileged magento2-devbox-web php /root/scripts/composerInstall.php
docker exec -it --privileged magento2-devbox-web php /root/scripts/magentoSetup.php \
    --install-rabbitmq=$install_rabbitmq --rabbit-host=$rabit_host --rabbit-port=$rabbit_port

if [[ $install_redis ]]
    then docker exec -it --privileged magento2-devbox-web php /root/scripts/setupRedis.php \
        --redis-host=$redis_host --magento-path=$magento_path --as-cache=$redis_cache \
        --as-session=$redis_session
fi

docker exec -it --privileged magento2-devbox-web php /root/scripts/postInstall.php
