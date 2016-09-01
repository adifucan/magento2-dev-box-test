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
