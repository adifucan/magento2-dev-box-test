Write-Host "Creating docker-compose config"

$install_rabbitmq = Read-Host 'Do you wish to install RabbitMQ (y/N)'

$use_existing_sources = Read-Host 'Do you have existing copy of Magento 2 (y/N)'
if ($use_existing_sources -eq 'y') {
    $webroot_path = Read-Host 'Please provide full path to the magento2 folder'
} else {
    $use_existing_sources = 'n'
    $webroot_path = "./shared/webroot"
}
if ((Read-Host 'Do you have existing copy of .composer folder (y/N)') -eq 'y') {
    $composer_path = Read-Host 'Please provide full path to the .composer folder'
} else {
    $composer_path = "./shared/.composer"
}
if ((Read-Host 'Do you have existing copy of .ssh folder (y/N)') -eq 'y') {
    $ssh_path = Read-Host 'Please provide full path to the .ssh folder'
} else {
    $ssh_path = "./shared/.ssh"
}
if ((Read-Host 'Do you have existing copy of the database files folder (y/N)') -eq 'y') {
    $db_path = Read-Host 'Please provide full path to the database files folder'
} else {
    $db_path = "./shared/db"
}

$db_host = "db"
$db_port = 3306
$db_password = "root"
$db_user = "root"
$db_name = "magento2"
$magento_path = "/var/www/magento2"
$main_host = "web"
$main_host_port = 80
$main_host_container = "magento2-devbox-web"

$yml = @"
##
# Services needed to run Magento2 application on Docker
#
# Docker Compose defines required services and attach them together through aliases
##
version: '2'
services:
  %%%DB_HOST%%%:
    container_name: magento2-devbox-db
    restart: always
    image: mysql:5.6
    ports:
        - "1345:%%%DB_PORT%%%"
    environment:
        - MYSQL_ROOT_PASSWORD=%%%DB_USER%%%
        - MYSQL_DATABASE=%%%DB_NAME%%%
    volumes:
        - %%%DB_PATH%%%:/var/lib/mysql
  %%%MAIN_HOST%%%:
    container_name: %%%MAIN_HOST_CONTAINER%%%
    image: magento/magento2devbox_web:latest
    volumes:
        - %%%WEBROOT_PATH%%%:%%%MAGENTO_PATH%%%
        - %%%COMPOSER_PATH%%%:/home/magento2/.composer
        - %%%SSH_PATH%%%:/home/magento2/.ssh
        #    - ./shared/.magento-cloud:/home/magento2/.magento-cloud
    ports:
        - "%%%WEB_PORT%%%:%%%MAIN_HOST_PORT%%%"
        - "2222:22"
"@

$rabbit_host = 'rabbit'
$rabbit_port = 5672
if ($install_rabbitmq -eq 'y') {
$yml += @"

  %%%RABBIT_HOST%%%:
    container_name: magento2-devbox-rabbit
    image: rabbitmq:3-management
    ports:
        - "8282:15672"
        - "%%%RABBIT_PORT%%%:%%%RABBIT_PORT%%%"
"@
}

$redis_session = Read-Host 'Do you wish to setup Redis as session storage (y/N)'
$redis_all_cache = Read-Host 'Do you wish to setup Redis as default cache for all types (except Full Page Cache) (y/N)'
$cache_adapter = Read-Host 'For Full Page cache do you wish to setup Redis (r) or Varnish (v) or use default file system storage (any key) (r/v/anykey)'

$install_varnish = if ($cache_adapter -eq 'v') {"y"} else {"n"}
$redis_cache = if ($cache_adapter -eq 'r') {1} else {0}
$install_redis = if (($cache_adapter -eq 'r') -or ($redis_session -eq 'y') -or ($redis_all_cache -eq 'y')) {"y"} else {"n"}

$redis_host = 'redis'
if ($install_redis -eq 'y') {
$yml += @"

  %%%REDIS_HOST%%%:
    container_name: magento2-devbox-redis
    image: redis:3.0.7
"@
}

$web_port = 1748
$varnish_host_container = "magento2-devbox-varnish"
if ($install_varnish -eq 'y') {
$yml += @"

  varnish:
    image: magento/magento2devbox_varnish:latest
    container_name: %%%VARNISH_HOST_CONTAINER%%%
    ports:
      - "1748:6081"
"@
$web_port = 1749
}

$yml = $yml -Replace "%%%WEBROOT_PATH%%%", $webroot_path
$yml = $yml -Replace "%%%COMPOSER_PATH%%%", $composer_path
$yml = $yml -Replace "%%%SSH_PATH%%%", $ssh_path
$yml = $yml -Replace "%%%DB_PATH%%%", $db_path
$yml = $yml -Replace "%%%RABBIT_HOST%%%", $rabbit_host
$yml = $yml -Replace "%%%RABBIT_PORT%%%", $rabbit_port
$yml = $yml -Replace "%%%REDIS_HOST%%%", $redis_host
$yml = $yml -Replace "%%%WEB_PORT%%%", $web_port
$yml = $yml -Replace "%%%DB_HOST%%%", $db_host
$yml = $yml -Replace "%%%DB_PORT%%%", $db_port
$yml = $yml -Replace "%%%DB_USER%%%", $db_user
$yml = $yml -Replace "%%%DB_PASSWORD%%%", $db_password
$yml = $yml -Replace "%%%DB_NAME%%%", $db_name
$yml = $yml -Replace "%%%VARNISH_HOST_CONTAINER%%%", $varnish_host_container
$yml = $yml -Replace "%%%MAGENTO_PATH%%%", $magento_path
$yml = $yml -Replace "%%%MAIN_HOST%%%", $main_host
$yml = $yml -Replace "%%%MAIN_HOST_PORT%%%", $main_host_port
$yml = $yml -Replace "%%%MAIN_HOST_CONTAINER%%%", $main_host_container
Set-Content docker-compose.yml $yml

Write-Host "Creating shared folders"
if ((Test-Path shared) -eq 0) {
    mkdir shared
}
if ((Test-Path shared/.composer) -eq 0) {
    mkdir shared/.composer
}
if ((Test-Path shared/.ssh) -eq 0) {
    mkdir shared/.ssh
}
if ((Test-Path shared/webroot) -eq 0) {
    mkdir shared/webroot
}
if ((Test-Path shared/db) -eq 0) {
    mkdir shared/db
}

Write-Host "Build docker images"

docker-compose up --build -d

docker exec -it --privileged magento2-devbox-web /bin/sh -c 'chown -R magento2:magento2 /home/magento2 && chown -R magento2:magento2 /var/www/magento2'

docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:download --use-existing-sources=$use_existing_sources
docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:setup --use-existing-sources=$use_existing_sources --rabbitmq-install=$install_rabbitmq --rabbitmq-host=$rabit_host --rabbitmq-port=$rabbit_port

if ($install_redis -eq 'y') {
    docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:setup:redis --as-all-cache=$redis_all_cache --as-cache=$redis_cache --as-session=$redis_session --host=$redis_host --magento-path=$magento_path
}

if ($install_varnish -eq 'y') {
    $varnish_file = "/home/magento2/scripts/default.vcl"
    docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:setup:varnish --db-host=$db_host --db-port=$db_port --db-user=$db_user --db-name=$db_name --db-password=$db_password --backend-host=$main_host --backend-port=$main_host_port --out-file-path=/home/magento2/scripts/default.vcl
    docker cp ${main_host_container}:/${varnish_file} ./web/scripts/command/default.vcl
    docker cp ./web/scripts/command/default.vcl ${varnish_host_container}:/etc/varnish/default.vcl
    docker-compose restart varnish
}

docker exec -it --privileged -u magento2 magento2-devbox-web mysql -h db -u root -proot -e 'CREATE DATABASE IF NOT EXISTS magento_integration_tests;'
docker cp ./web/integration/install-config-mysql.php magento2-devbox-web:/var/www/magento2/dev/tests/integration/etc/install-config-mysql.php

docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:prepare