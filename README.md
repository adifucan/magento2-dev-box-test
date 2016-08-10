# Magento 2 Application with Docker Compose for developers

## Prerequisites
Docker (https://www.docker.com/products/docker#/windows) and git (https://git-scm.com/download/win) for windows should be installed.
Run PowerShell as administrator and give permission to run scripts with command: `Set-ExecutionPolicy Unrestricted`

## Installation (Windows 10)
1. Clone this repository && cd magento2-dev-box
2. `& ".\install.ps1"` - during command execution you will be asked for your magento repo credentials
3. Open in browser http://localhost:1748/

## Notes
Probably good idea run after installation this commands in the PowerShell console window:
```
docker exec -it --privileged magento2-devbox-web php /var/www/magento2/bin/magento deploy:mode:set developer
docker exec -it --privileged magento2-devbox-web php /var/www/magento2/bin/magento setup:static-content:deploy
```