Write-Host "Creating docker-compose config"

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
    - ./shared/db:/var/lib/mysql
web:
  build: web
  container_name: magento2-devbox-web
  volumes:
    - ./shared/webroot:/var/www/magento2
    - ./shared/.composer:/root/.composer
    - ./shared/.ssh:/root/.ssh
    #    - ./shared/.magento-cloud:/root/.magento-cloud
    - ./scripts:/root/scripts
  ports:
    - "1748:80"
  links:
    - db:db
    - rabbit:rabbit
  command: "apache2-foreground"
"@

$install_rabbitmq = Read-Host 'Do you wish to install RabbitMQ (y/N)'

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

#docker-compose up --build -d

#docker exec -it --privileged magento2-devbox-web php /root/scripts/composerInstall.php
#docker exec -it --privileged magento2-devbox-web php /root/scripts/magentoSetup.php
#docker exec -it --privileged magento2-devbox-web php /root/scripts/postInstall.php
