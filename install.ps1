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
    - "3306:3306"
  environment:
    - MYSQL_ROOT_PASSWORD=root
    - MYSQL_DATABASE=magento2
web:
  build: web
  container_name: magento2-devbox-web
  volumes:
    - ../magento2-dev-box/shared/webroot:/var/www/magento2
    - ../magento2-dev-box/shared/.composer:/root/.composer
    - ../magento2-dev-box/shared/.ssh:/root/.ssh
    - ../magento2-dev-box/scripts:/root/scripts
  ports:
    - "1748:80"
  links:
    - db:db
  command: "apache2-foreground"
"@

Set-Content docker-compose.yml $yml

# TODO: check for existence
Write-Host "Creating shared folders"
mkdir shared
mkdir shared/.composer
mkdir shared/.ssh
mkdir shared/webroot

# TODO: move into composerInstall.php
$composer_public_key = Read-Host -Prompt 'Enter your magento public key'
$composer_private_key = Read-Host -Prompt 'Enter your magento private key'
$auth_json = "{""http-basic"": {""repo.magento.com"": {""username"": ""$composer_public_key"", ""password"": ""$composer_private_key""}}}"
Set-Content shared/.composer/auth.json $auth_json

Write-Host "Build docker images"

docker-compose up --build -d

docker exec -it --privileged magento2-devbox-web php /root/scripts/composerInstall.php
docker exec -it --privileged magento2-devbox-web php /root/scripts/magentoSetup.php


