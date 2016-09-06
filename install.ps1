Write-Host "Creating docker-compose config"

$install_rabbitmq = Read-Host 'Do you wish to install RabbitMQ (y/N)'

if ((Read-Host 'Do you have existing copy of Magento 2 (y/N)') -eq 'y') {
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
    - %%%COMPOSER_PATH%%%:/root/.composer
    - %%%SSH_PATH%%%:/root/.ssh
    #    - ./shared/.magento-cloud:/root/.magento-cloud
  ports:
    - "1748:80"
  links:
    - db:db
"@

if ($install_rabbitmq -eq 'y') {
$yml += @"

    - rabbit:rabbit
"@
}

$yml += @"

  command: "apache2-foreground"
"@

if ($install_rabbitmq -eq 'y') {
$yml += @"

rabbit:
  container_name: magento2-devbox-rabbit
  image: rabbitmq:3-management
  ports:
    - "8282:15672"
    - "5672:5672"
"@
}

$yml = $yml -Replace "%%%WEBROOT_PATH%%%", $webroot_path
$yml = $yml -Replace "%%%COMPOSER_PATH%%%", $composer_path
$yml = $yml -Replace "%%%SSH_PATH%%%", $ssh_path
$yml = $yml -Replace "%%%DB_PATH%%%", $db_path
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

docker exec -it --privileged magento2-devbox-web /bin/sh -c 'cd /root/scripts && composer install'
docker exec -it --privileged magento2-devbox-web /bin/sh -c 'cd /root/scripts && composer update'

docker exec -it --privileged magento2-devbox-web php -f /root/scripts/devbox magento:download
docker exec -it --privileged magento2-devbox-web php -f /root/scripts/devbox magento:setup --rabbitmq-install=$install_rabbitmq
docker exec -it --privileged magento2-devbox-web php -f /root/scripts/devbox magento:prepare
