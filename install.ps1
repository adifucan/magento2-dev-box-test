Write-Host "Creating magento2 folder"
mkdir ../magento2

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
    - ..\magento2:/var/www/magento2
  ports:
    - "1748:80"
  links:
    - db:db
  command: "apache2-foreground"
"@

Set-Content docker-compose.yml $yml

$composer_public_key = Read-Host -Prompt 'Enter your magento public key'
$composer_private_key = Read-Host -Prompt 'Enter your magento private key'
$auth_json = "{""http-basic"": {""repo.magento.com"": {""username"": ""$composer_public_key"", ""password"": ""$composer_private_key""}}}"
Set-Content web/auth.json $auth_json

Write-Host "Build docker images"

docker-compose up --build

