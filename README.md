# Magento 2 Application with Docker Compose for developers

## Prerequisites
Docker (https://www.docker.com/products/docker#/windows) for windows should be installed.
Run PowerShell as administrator and give permission to run scripts with command: `Set-ExecutionPolicy Unrestricted`

## Installation (Windows 10)
1. Clone this repository && cd magento2-dev-box
2. `& ".\install.ps1"` - during command execution you will be asked for your magento repo credentials
3. Open in browser http://localhost:1748/

## Debug your PHP in Docker with Intellij/PHPStorm and Xdebug
1. Create new server to PhpStorm Servers:
   - Name: {{your server name}}
   - Host: localhost
   - Port: 1748
   - Use path mappings: Yes
   - File/Directory: magento2-dev-box/webroot-> /var/www/magento2
2. Add PHP Remote Debug to new configurations in PhpStorm:
   - Name: {{your name}}
   - Servers: {{your server name from step 1}}
   - Ide key: PHPSTORM
3. Make sure that you have 9000 Xdebug port in Languages & Frameworks > PHP > Debug

## Static content deploy
To deploy static view files you need:
- Enter docker container by running: `docker exec -it --privileged magento2-devbox-web /bin/bash`
- Enter magento root: `cd \var\www\magento2`
- Deploy static files: `php bin/magento setup:static-content:deploy`

## Compile CSS styles with Grunt
To compile CSS out of LESS via Grunt you need:
- Enter docker container by running: `docker exec -it --privileged magento2-devbox-web /bin/bash`
- Enter magento root: `cd \var\www\magento2`
- Run: `npm install && grunt refresh`

## Configure PhpStorm to run tests:
1. Go to Languages & Frameworks > PHP and add new Interpreter:
 - Select Interpreter path: Remote
 - Name: {{your_name}}
 - Remote: SSH Credentials
 - Host: 127.0.0.1 Port: 2222
 - Username: root
 - Password: 
 - PHP executable: /usr/local/bin/php
 
2. Go to Languages & Frameworks > PHP:
 - Interpreter: Remote PHP 7
 - Path mapping: ../magento2-dev-box/shared/webroot -> /var/www/magento2
 
3. Go to Languages & Frameworks > PHP > PHPUnit and click +:
 - Interpreter: Remote PHP 7
 - Path mappings: ../magento2-dev-box/shared/webroot -> /var/www/magento2
 - PHPUnit Library: Use Composer autoloader
 - Path to script: /var/www/magento2/vendor/autoload.php
 