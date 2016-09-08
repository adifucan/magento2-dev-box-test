Write-Host "Creating docker-compose config"

$install_rabbitmq = Read-Host 'Do you wish to install RabbitMQ (y/N)'

$use_existing_sources = Read-Host 'Do you have existing copy of Magento 2 (y/N)'
if ($use_existing_sources -eq 'y') {
    $webroot_path = Read-Host 'Please provide full path to the magento2 folder'
} else {
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

$yml = @"
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
        - %%%DB_PATH%%%:/var/lib/mysql
  web:
    build: web
    container_name: magento2-devbox-web
    volumes:
        - %%%WEBROOT_PATH%%%:/var/www/magento2
        - %%%COMPOSER_PATH%%%:/home/magento2/.composer
        - %%%SSH_PATH%%%:/home/magento2/.ssh
        #    - ./shared/.magento-cloud:/home/magento2/.magento-cloud
    ports:
        - "%%%WEB_PORT%%%:80"
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
$cache_adapter = Read-Host 'Do you wish to setup Redis (r) or Varnish (v) as page cache mechanism (any key for default file system storage) (r/v)'

if ($cache_adapter -eq 'v') {
    $install_varnish = 'y'
}

$redis_cache = 0
if ($cache_adapter -eq 'r') {
    $redis_cache = 1
}

if ($cache_adapter -eq 'r' || $redis_session -eq 'y') {
    $install_redis = 'y'
}

$redis_host = 'redis'
if ($install_redis -eq 'y') {
$yml += @"

  %%%REDIS_HOST%%%:
    container_name: magento2-devbox-redis
    image: redis:3.0.7
"@
}

$web_port=1748
if ($install_varnish -eq 'y') {
$yml += @"

  varnish:
    build: varnish
    container_name: magento2-devbox-varnish
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

docker exec -it --privileged -u magento2 magento2-devbox-web /bin/sh -c 'cd /home/magento2/scripts && composer install'
docker exec -it --privileged -u magento2 magento2-devbox-web /bin/sh -c 'cd /home/magento2/scripts && composer update'

docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:download --use-existing-sources=$use_existing_sources
docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:setup --rabbitmq-install=$install_rabbitmq --rabbitmq-host=$rabit_host --rabbitmq-port=$rabbit_port

if ($install_redis -eq 'y') {
    then docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:setup:redis --as-cache=$redis_cache --as-session=$redis_session --host=$redihost --magento-path=$magento_path
}

docker exec -it --privileged -u magento2 magento2-devbox-web php -f /home/magento2/scripts/devbox magento:prepare
