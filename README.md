# Magento 2 Application with Docker Compose for developers

## Prerequisites
This image is expected to have two-way synchronization of filessytem between host machine and containers.
The most efficient way to do it today is to use Docker Beta (for Mac and for Windows)
If you have already have Docker (or Docker Toolbox) installed, remove previous Docker installation, 
and install Docker for Mac (or for Windows). Docker Toolbox should continue working [https://docs.docker.com/docker-for-mac/docker-toolbox/](https://docs.docker.com/docker-for-mac/docker-toolbox/)

To install Docker Beta for Mac/Win, follow instructions on this link:
[https://blog.docker.com/2016/03/docker-for-mac-windows-beta/](https://blog.docker.com/2016/03/docker-for-mac-windows-beta/)


## Installation
1. Retrieve Magento source code: composer create-project --repository-url="https://repo.magento.com/" magento/project-community-edition magento2 
2. Clone this repository && cd magento2-dev-box
3. docker compose up
4. Navigate to [http://localhost:1748](http://localhost:1748) and install Magento